<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
class monitor_core_module {
    /* TODO: this should be a config - var instead of a "constant" */
    var $interval = 5; // do the monitoring every 5 minutes

    var $module_name = 'monitor_core_module';
    var $class_name = 'monitor_core_module';
    /* No actions at this time. maybe later... */
    var $actions_available = array();

    /*
        This function is called when the module is loaded
    */
    function onLoad() {
        global $app;

        /*
        Annonce the actions that where provided by this module, so plugins
        can register on them.
        */
        /* none at them moment */
        //$app->plugins->announceEvents($this->module_name,$this->actions_available);

        /*
        As we want to get notified of any changes on several database tables,
        we register for them.

        The following function registers the function "functionname"
            to be executed when a record for the table "dbtable" is
            processed in the sys_datalog. "classname" is the name of the
            class that contains the function functionname.
        */
        /* none at them moment */
        //$app->modules->registerTableHook('mail_access','mail_module','process');

        /*
        Do the monitor every n minutes and write the result in the db
        */
        $min = date('i');
        if (($min % $this->interval) == 0)
        {
            $this->doMonitor();
        }
    }

    /*
     This function is called when a change in one of the registered tables is detected.
     The function then raises the events for the plugins.
    */
    function process($tablename, $action, $data) {
        //		global $app;
        //
        //		switch ($tablename) {
        //			case 'mail_access':
        //				if($action == 'i') $app->plugins->raiseEvent('mail_access_insert',$data);
        //				if($action == 'u') $app->plugins->raiseEvent('mail_access_update',$data);
        //				if($action == 'd') $app->plugins->raiseEvent('mail_access_delete',$data);
        //				break;
        //		} // end switch
    } // end function

    /*
    This method is called every n minutes, when the module ist loaded.
    The method then does a system-monitoring
    */
    // TODO: what monitoring is done should be a config-var
    function doMonitor()
    {
        /* Calls the single Monitoring steps */
        $this->monitorServer();
        $this->monitorDiskUsage();
        $this->monitorMemUsage();
        $this->monitorCpu();
        $this->monitorServices();
        $this->monitorMailLog();
        $this->monitorMailWarnLog();
        $this->monitorMailErrLog();
        $this->monitorMessagesLog();
        $this->monitorFreshClamLog();
        $this->monitorClamAvLog();
        $this->monitorIspConfigLog();
        $this->monitorSystemUpdate();
        $this->monitorMailQueue();
        $this->monitorRaid();
        $this->monitorRkHunter();
    }

    function monitorServer(){
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'server_load';

        /* Delete Data older than 1 day */
        $this->_delOldRecords($type, 0, 0, 1);

        /*
        Fetch the data into a array
        */
        $procUptime = shell_exec("cat /proc/uptime | cut -f1 -d' '");
        $data['up_days'] = floor($procUptime/86400);
        $data['up_hours'] = floor(($procUptime-$data['up_days']*86400)/3600);
        $data['up_minutes'] = floor(($procUptime-$data['up_days']*86400-$data['up_hours']*3600)/60);

        $data['uptime'] = shell_exec("uptime");

        $tmp = explode(",", $data['uptime'], 3);
        $tmpUser = explode(" ", trim($tmp[1]));
        $data['user_online'] = intval($tmpUser[0]);

        $loadTmp = explode(":" , trim($tmp[2]));
        $load = explode(",",  $loadTmp[1]);
        $data['load_1'] = floatval(trim($load[0]));
        $data['load_5'] = floatval(trim($load[1]));
        $data['load_15'] = floatval(trim($load[2]));

        /** The state of the server-load. */
        $state = 'ok';
        if ($data['load_1'] > 20 ) $state = 'info';
        if ($data['load_1'] > 50 ) $state = 'warning';
        if ($data['load_1'] > 100 ) $state = 'critical';
        if ($data['load_1'] > 150 ) $state = 'error';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorDiskUsage() {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'disk_usage';

        /* Delete Data older than 10 minutes */
        $this->_delOldRecords($type, 10);

        /** The state of the disk-usage */
        $state = 'ok';

        /** Fetch the data into a array */
        $dfData = shell_exec("df");

        // split into array
        $df = explode("\n", $dfData);

        /*
         * ignore the first line, process the rest
         */
        for($i=1; $i <= sizeof($df); $i++){
            if ($df[$i] != '')
            {
                /*
                 * Make a array of the data
                 */
                $s = preg_split ("/[\s]+/", $df[$i]);
                $data[$i]['fs'] = $s[0];
                $data[$i]['size'] = $s[1];
                $data[$i]['used'] = $s[2];
                $data[$i]['available'] = $s[3];
                $data[$i]['percent'] = $s[4];
                $data[$i]['mounted'] = $s[5];
                /*
                 * calculate the state
                 */
                $usePercent = floatval($data[$i]['percent']);
                if ($usePercent > 75) $state = $this->_setState($state, 'info');
                if ($usePercent > 80) $state = $this->_setState($state, 'warning');
                if ($usePercent > 90) $state = $this->_setState($state, 'critical');
                if ($usePercent > 95) $state = $this->_setState($state, 'error');
            }
        }


        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function monitorMemUsage()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'mem_usage';

        /* Delete Data older than 10 minutes */
        $this->_delOldRecords($type, 10);

        /*
        Fetch the data into a array
        */
        $miData = shell_exec("cat /proc/meminfo");

        $memInfo = explode("\n", $miData);

        foreach($memInfo as $line){
            $part = split(":", $line);
            $key = trim($part[0]);
            $tmp = explode(" ", trim($part[1]));
            $value = 0;
            if ($tmp[1] == 'kB') $value = $tmp[0] * 1024;
            $data[$key] = $value;
        }

        /*
         * actually this info has no state.
         * maybe someone knows better...???...
         */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function monitorCpu()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'cpu_info';

        /* There is only ONE CPU-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /*
        Fetch the data into a array
        */
        $cpuData = shell_exec("cat /proc/cpuinfo");
        $cpuInfo = explode("\n", $cpuData);

        foreach($cpuInfo as $line){
            $part = split(":", $line);
            $key = trim($part[0]);
            $value = trim($part[1]);
            $data[$key] = $value;
        }

        /* the cpu has no state. It is, what it is */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function monitorServices()
    {
        global $app;
        global $conf;

        /** the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** get the "active" Services of the server from the DB */
        $services = $app->db->queryOneRecord("SELECT * FROM server WHERE server_id = " . $server_id);

        /* The type of the Monitor-data */
        $type = 'services';

        /* There is only ONE Service-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /** the State of the monitoring */
        /* ok, if ALL aktive services are running,
         * error, if not
         * There is no other state!
         */
        $state = 'ok';

        /* Monitor Webserver */
        $data['webserver'] = -1; // unknown - not needed
        if ($services['web_server'] == 1)
        {
            if($this->_checkTcp('localhost', 80)) {
                $data['webserver'] = 1;
            } else {
                $data['webserver'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor FTP-Server */
        $data['ftpserver'] = -1; // unknown - not needed
        if ($services['file_server'] == 1)
        {
            if($this->_checkFtp('localhost', 21)) {
                $data['ftpserver'] = 1;
            } else {
                $data['ftpserver'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor SMTP-Server */
        $data['smtpserver'] = -1; // unknown - not needed
        if ($services['mail_server'] == 1)
        {
            if($this->_checkTcp('localhost', 25)) {
                $data['smtpserver'] = 1;
            } else {
                $data['smtpserver'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor POP3-Server */
        $data['pop3server'] = -1; // unknown - not needed
        if ($services['mail_server'] == 1)
        {
            if($this->_checkTcp('localhost', 110)) {
                $data['pop3server'] = 1;
            } else {
                $data['pop3server'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor IMAP-Server */
        $data['imapserver'] = -1; // unknown - not needed
        if ($services['mail_server'] == 1)
        {
            if($this->_checkTcp('localhost', 143)) {
                $data['imapserver'] = 1;
            } else {
                $data['imapserver'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor BIND-Server */
        $data['bindserver'] = -1; // unknown - not needed
        if ($services['dns_server'] == 1)
        {
            if($this->_checkTcp('localhost', 53)) {
                $data['bindserver'] = 1;
            } else {
                $data['bindserver'] = 0;
                $state = 'error'; // because service is down
            }
        }

        /* Monitor MYSQL-Server */
        $data['mysqlserver'] = -1; // unknown - not needed
        if ($services['db_server'] == 1)
        {
            if($this->_checkTcp('localhost', 3306)) {
                $data['mysqlserver'] = 1;
            } else {
                $data['mysqlserver'] = 0;
                $state = 'error'; // because service is down
            }
        }


        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);

    }


    function monitorSystemUpdate(){
        /*
         *  This monitoring is expensive, so do it only once a day (at 5:00)
         */
        $hour = date('G');
        $min = date('i');
        if (($min != 0) && ($hour != 5)) return;

        /*
         * OK - here we go...
         */
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'system_update';

        /* There is only ONE Update-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* This monitoring is only available at debian or Ubuntu */
        if(file_exists('/etc/debian_version')){

            /*
             * first update the "update-database"
             */
            shell_exec('apt-get update');

            /*
             * Then test the upgrade.
             * if there is any output, then there is a needed update
             */
            $aptData = shell_exec('apt-get -s -qq dist-upgrade');
            if ($aptData == '')
            {
                /* There is nothing to update! */
                $state = 'ok';
            }
            else
            {
                /* There is something to update! */
                $state = 'warning';
            }

            /*
             * Fetch the output
             */
            $data['output'] = shell_exec('apt-get -s -q dist-upgrade');
        }
        else {
            /*
             * It is not debian/Ubuntu, so there is no data and no state
             *
             * no_state, NOT unknown, because "unknown" is shown as state
             * inside the GUI. no_state is hidden.
             *
             * We have to write NO DATA inside the DB, because the GUI
             * could not know, if there is any dat, or not...
             */
            $state = 'no_state';
            $data['output']= '';
        }

        /*
         * Insert the data into the database
         */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorMailQueue(){
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'mailq';

        /* There is only ONE Update-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* Get the data from the mailq */
        $data['output'] = shell_exec('mailq');

        /*
         *  The last line has more informations
         */
        $tmp = explode("\n", $data['output']);
        $more = $tmp[sizeof($tmp) - 1];
        $this->_getIntArray($more);
        $data['bytes'] = $res[0];
        $data['requests'] = $res[1];

        /** The state of the mailq. */
        $state = 'ok';
        if ($data['requests'] > 2000 ) $state = 'info';
        if ($data['requests'] > 5000 ) $state = 'warning';
        if ($data['requests'] > 8000 ) $state = 'critical';
        if ($data['requests'] > 10000 ) $state = 'error';

        /*
         * Insert the data into the database
         */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function monitorRaid(){
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'raid_state';

        /* There is only ONE RAID-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* This monitoring is only available if mdadm is installed */
        $location = shell_exec('which mdadm');
        if($location != ''){
            /*
             * Fetch the output
             */
            $data['output'] = shell_exec('cat /proc/mdstat');

            /*
             * Then calc the state.
             */
            $tmp = explode("\n", $data['output']);
            $state = 'ok';
            foreach($tmp as $line) {
                if (strpos($line, '[U_]' !== false))
                {
                    /* One Disk is not working */
                    $state = $this->_setState($state, 'critical');
                }
                if (strpos($line, '[_U]' !== false))
                {
                    /* One Disk is not working */
                    $state = $this->_setState($state, 'critical');
                }
                if (strpos($line, '[__]' !== false))
                {
                    /* both Disk are not working */
                    $state = $this->_setState($state, 'error');
                }
                if (strpos($line, '[=' !== false))
                {
                    /* the raid is in resync */
                    $state = $this->_setState($state, 'information');
                }
            }

        }
        else {
            /*
             * mdadm is not installed, so there is no data and no state
             *
             * no_state, NOT unknown, because "unknown" is shown as state
             * inside the GUI. no_state is hidden.
             *
             * We have to write NO DATA inside the DB, because the GUI
             * could not know, if there is any dat, or not...
             */
            $state = 'no_state';
            $data['output']= '';
        }

        /*
         * Insert the data into the database
         */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorRkHunter(){
    }

    function monitorMailLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_mail';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* Get the data of the log */
        $data = $this->_getLogData($type);

        /*
         * actually this info has no state.
         * maybe someone knows better...???...
         */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorMailWarnLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_mail_warn';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);


        /* Get the data of the log */
        $data = $this->_getLogData($type);

        /*
         * actually this info has no state.
         * maybe someone knows better...???...
         */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorMailErrLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_mail_err';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);


        /* Get the data of the log */
        $data = $this->_getLogData($type);

        /*
         * actually this info has no state.
         * maybe someone knows better...???...
         */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function monitorMessagesLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_messages';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* Get the data of the log */
        $data = $this->_getLogData($type);

        /*
         * actually this info has no state.
         * maybe someone knows better...???...
         */
        $state = 'no_state';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorFreshClamLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_freshclam';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);


        /* Get the data of the log */
        $data = $this->_getLogData($type);

        // Todo: the state should be calculated.
        $state = 'ok';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }

    function monitorClamAvLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_clamav';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);

        /* Get the data of the log */
        $data = $this->_getLogData($type);

        // Todo: the state should be calculated.
        $state = 'ok';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);

/* for later (to detect that the version is outdated)
--------------------------------------
Received signal: wake up
ClamAV update process started at Sun Nov 23 12:03:49 2008
main.cvd is up to date (version: 49, sigs: 437972, f-level: 35, builder: sven)
Trying host db.local.clamav.net (85.214.20.182)...
Downloading daily-8675.cdiff [100%]
Downloading daily-8676.cdiff [100%]
daily.cld updated (version: 8676, sigs: 26800, f-level: 35, builder: ccordes)
Database updated (464772 signatures) from db.local.clamav.net (IP: 85.214.20.182)
Clamd successfully notified about the update.
--------------------------------------
--------------------------------------
freshclam daemon 0.90.1 (OS: linux-gnu, ARCH: i386, CPU: i486)
ClamAV update process started at Sun Nov 23 12:37:49 2008
WARNING: Your ClamAV installation is OUTDATED!
WARNING: Local version: 0.90.1 Recommended version: 0.94.1
DON'T PANIC! Read http://www.clamav.net/support/faq
Downloading main-43.cdiff [0%]
Downloading main-44.cdiff [0%]
Downloading main-45.cdiff [0%]
Downloading main-46.cdiff [0%]
Downloading main-47.cdiff [0%]
Downloading main-48.cdiff [0%]
Downloading main-49.cdiff [0%]
main.cvd updated (version: 49, sigs: 437972, f-level: 35, builder: sven)
WARNING: Your ClamAV installation is OUTDATED!
WARNING: Current functionality level = 14, recommended = 35
DON'T PANIC! Read http://www.clamav.net/support/faq
ERROR: getfile: daily-2692.cdiff not found on remote server (IP: 62.75.166.141)
ERROR: getpatch: Can't download daily-2692.cdiff from db.local.clamav.net
ERROR: getfile: daily-2692.cdiff not found on remote server (IP: 62.26.160.3)
ERROR: getpatch: Can't download daily-2692.cdiff from db.local.clamav.net
ERROR: getfile: daily-2692.cdiff not found on remote server (IP: 213.174.32.130)
ERROR: getpatch: Can't download daily-2692.cdiff from db.local.clamav.net
ERROR: getfile: daily-2692.cdiff not found on remote server (IP: 212.1.60.18)
ERROR: getpatch: Can't download daily-2692.cdiff from db.local.clamav.net
ERROR: getfile: daily-2692.cdiff not found on remote server (IP: 193.27.50.222)
ERROR: getpatch: Can't download daily-2692.cdiff from db.local.clamav.net
WARNING: Incremental update failed, trying to download daily.cvd
Downloading daily.cvd [0%]
daily.cvd updated (version: 8676, sigs: 26800, f-level: 35, builder: ccordes)
WARNING: Your ClamAV installation is OUTDATED!
WARNING: Current functionality level = 14, recommended = 35
DON'T PANIC! Read http://www.clamav.net/support/faq
Database updated (464772 signatures) from db.local.clamav.net (IP: 91.198.238.33)
--------------------------------------
--------------------------------------
freshclam daemon 0.94.1 (OS: linux-gnu, ARCH: i386, CPU: i486)
ClamAV update process started at Sun Nov 23 13:01:17 2008
Trying host db.local.clamav.net (193.27.50.222)...
Downloading main.cvd [100%]
main.cvd updated (version: 49, sigs: 437972, f-level: 35, builder: sven)
daily.cvd is up to date (version: 8676, sigs: 26800, f-level: 35, builder: ccordes)
Database updated (464772 signatures) from db.local.clamav.net (IP: 193.27.50.222)
--------------------------------------
--------------------------------------
freshclam daemon 0.94.1 (OS: linux-gnu, ARCH: i386, CPU: i486)
ClamAV update process started at Tue Nov 25 19:11:42 2008
main.cvd is up to date (version: 49, sigs: 437972, f-level: 35, builder: sven)
Trying host db.local.clamav.net (85.214.44.186)...
Downloading daily-8677.cdiff [100%]
Downloading daily-8678.cdiff [100%]
Downloading daily-8679.cdiff [100%]
daily.cld updated (version: 8679, sigs: 26975, f-level: 35, builder: ccordes)
Database updated (464947 signatures) from db.local.clamav.net (IP: 85.214.44.186)
--------------------------------------
--------------------------------------
freshclam daemon 0.94.1 (OS: linux-gnu, ARCH: i386, CPU: i486)
ClamAV update process started at Tue Nov 25 19:16:18 2008
main.cvd is up to date (version: 49, sigs: 437972, f-level: 35, builder: sven)
daily.cld is up to date (version: 8679, sigs: 26975, f-level: 35, builder: ccordes)
--------------------------------------
Received signal: wake up
ClamAV update process started at Tue Nov 25 20:16:25 2008
main.cvd is up to date (version: 49, sigs: 437972, f-level: 35, builder: sven)
daily.cld is up to date (version: 8679, sigs: 26975, f-level: 35, builder: ccordes)
--------------------------------------
 */
    }

    function monitorIspConfigLog()
    {
        global $app;
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf["server_id"]);

        /** The type of the data */
        $type = 'log_ispconfig';

        /* There is only ONE Log-Data, so delete the old one */
        $this->_delOldRecords($type, 0);


        /* Get the data of the log */
        $data = $this->_getLogData($type);

        // Todo: the state should be calculated.
        $state = 'ok';

        /*
        Insert the data into the database
        */
        $sql = "INSERT INTO monitor_data (server_id, type, created, data, state) " .
            "VALUES (".
        $server_id . ", " .
            "'" . $app->db->quote($type) . "', " .
        time() . ", " .
            "'" . $app->db->quote(serialize($data)) . "', " .
            "'" . $state . "'" .
            ")";
        $app->db->query($sql);
    }


    function _getLogData($log){
        switch($log) {
            case 'log_mail':
                $logfile = '/var/log/mail.log';
                break;
            case 'log_mail_warn':
                $logfile = '/var/log/mail.warn';
                break;
            case 'log_mail_err':
                $logfile = '/var/log/mail.err';
                break;
            case 'log_messages':
                $logfile = '/var/log/messages';
                break;
            case 'log_freshclam':
                $logfile = '/var/log/clamav/freshclam.log';
                break;
            case 'log_clamav':
                $logfile = '/var/log/clamav/clamav.log';
                break;
            case 'log_ispconfig':
                $logfile = '/var/log/ispconfig/ispconfig.log';
                break;
            default:
                $logfile = '';
                break;
        }

        // Getting the logfile content
        if($logfile != '') {
            $logfile = escapeshellcmd($logfile);
            if(stristr($logfile, ';')) {
                $log = 'Logfile path error.';
            }
            else
            {
                $log = '';
                if(is_readable($logfile)) {
                    if($fd = popen("tail -n 100 $logfile", 'r')) {
                        while (!feof($fd)) {
                            $log .= fgets($fd, 4096);
                            $n++;
                            if($n > 1000) break;
                        }
                        fclose($fd);
                    }
                } else {
                    $log = 'Unable to read '.$logfile;
                }
            }
        }

        return $log;
    }

    function _checkTcp ($host,$port) {

        $fp = @fsockopen ($host, $port, &$errno, &$errstr, 2);

        if ($fp) {
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    function _checkUdp ($host,$port) {

        $fp = @fsockopen ('udp://'.$host, $port, &$errno, &$errstr, 2);

        if ($fp) {
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    function _checkFtp ($host,$port){

        $conn_id = @ftp_connect($host, $port);

        if($conn_id){
            @ftp_close($conn_id);
            return true;
        } else {
            return false;
        }
    }

    /*
     Deletes Records older than n.
    */
    function _delOldRecords($type, $min, $hour=0, $days=0) {
        global $app;

        $now = time();
        $old = $now - ($min * 60) - ($hour * 60 * 60) - ($days * 24 * 60 * 60);
        $sql = "DELETE FROM monitor_data " .
            "WHERE " .
            "type =" . "'" . $app->db->quote($type) . "' " .
            "AND " .
            "created < " . $old;
        $app->db->query($sql);
    }

    /*
     * Set the state to the given level (or higher, but not lesser).
     * * If the actual state is critical and you call the method with ok,
     *   then the state is critical.
     *
     * * If the actual state is critical and you call the method with error,
     *   then the state is error.
     */
    function _setState($oldState, $newState)
    {
        /*
         * Calculate the weight of the old state
         */
        switch ($oldState) {
            case 'no_state': $oldInt = 0;
                break;
            case 'ok': $oldInt = 1;
                break;
            case 'unknown': $oldInt = 2;
                break;
            case 'info': $oldInt = 3;
                break;
            case 'warning': $oldInt = 4;
                break;
            case 'critical': $oldInt = 5;
                break;
            case 'error': $oldInt = 6;
                break;
        }
        /*
         * Calculate the weight of the new state
         */
        switch ($newState) {
            case 'no_state': $newInt = 0 ;
                break;
            case 'ok': $newInt = 1 ;
                break;
            case 'unknown': $newInt = 2 ;
                break;
            case 'info': $newInt = 3 ;
                break;
            case 'warning': $newInt = 4 ;
                break;
            case 'critical': $newInt = 5 ;
                break;
            case 'error': $newInt = 6 ;
                break;
        }

        /*
         * Set to the higher level
         */
        if ($newInt > $oldInt){
            return $newState;
        }
        else
        {
            return $oldState;
        }
    }

    function _getIntArray($line){
        /** The array of float found */
        $res = array();
        /* First build a array from the line */
        $data = explode(' ', $line);
        /* then check if any item is a float */
        foreach ($data as $item) {
            if ($item . '' == (int)$item . ''){
                $res[] = $item;
            }
        }
        return $res;
    }


} // end class

?>
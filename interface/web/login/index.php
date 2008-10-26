<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

//

class login_index {

	public $status = '';
	private $target = '';
	private $app;
	private $conf;
	
	public function __construct()
	{
		global $app, $conf;
		$this->app  = $app;
		$this->conf = $conf;
	}
	
	public function render() {
		
		if(isset($_SESSION['s']['user']) && is_array($_SESSION['s']['user']) && is_array($_SESSION['s']['module'])) {
			die('HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage']);
		}
		
		$this->app->uses('tpl');
		$this->app->tpl->newTemplate('form.tpl.htm');
	    
	    $error = '';    
	
	
		//* Login Form was send
		if(count($_POST) > 0) {
	
	        // iporting variables
	        $ip 	  = $this->app->db->quote(ip2long($_SERVER['REMOTE_ADDR']));
	        $username = $this->app->db->quote($_POST['username']);
	        $passwort = $this->app->db->quote($_POST['passwort']); 
	
	        if($username != '' and $passwort != '') {
	        	//* Check if there already wrong logins
	        	$sql = "SELECT * FROM `attempts_login` WHERE `ip`= '{$ip}' AND  `login_time` < NOW() + INTERVAL 15 MINUTE LIMIT 1";
	        	$alreadyfailed = $this->app->db->queryOneRecord($sql);
	        	//* login to much wrong
	        	if($alreadyfailed['times'] > 5) {
	        		$error = $this->app->lng(1004);
	        	} else {
		        	$sql = "SELECT * FROM sys_user WHERE USERNAME = '$username' and ( PASSWORT = '".md5($passwort)."' or PASSWORT = password('$passwort') )";
		            $user = $this->app->db->queryOneRecord($sql);
		            if($user) {
		                if($user['active'] == 1) {
		                	// User login right, so attempts can be deleted
		                	$sql = "DELETE FROM `attempts_login` WHERE `ip`='{$ip}'";
		                	$this->app->db->query($sql);
		                	$user = $this->app->db->toLower($user);
		                    $_SESSION = array();
		                    $_SESSION['s']['user'] = $user;
		                    $_SESSION['s']['user']['theme'] = isset($user['app_theme']) ? $user['app_theme'] : 'default';
		                    $_SESSION['s']['language'] = $user['language'];
							$_SESSION["s"]['theme'] = $_SESSION['s']['user']['theme'];
										
							if(is_file($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php')) {
								include_once($_SESSION['s']['user']['startmodule'].'/lib/module.conf.php');
								$_SESSION['s']['module'] = $module;
							}
							echo 'HEADER_REDIRECT:'.$_SESSION['s']['module']['startpage'];
										
		                   	exit;
		             	} else {
		                	$error = $this->app->lng(1003);
		                }
		        	} else {
		        		if(!$alreadyfailed['times'] )
		        		{
		        			//* user login the first time wrong
		        			$sql = "INSERT INTO `attempts_login` (`ip`, `times`, `login_time`) VALUES ('{$ip}', 1, NOW())";
		        			$this->app->db->query($sql);
		        		} elseif($alreadyfailed['times'] >= 1) {
		        			//* update times wrong
		        			$sql = "UPDATE `attempts_login` SET `times`=`times`+1, `login_time`=NOW() WHERE `login_time` >= '{$time}' LIMIT 1";
		        			$this->app->db->query($sql);
		        		}
		            	//* Incorrect login - Username and password incorrect
		                $error = $this->app->lng(1002);
		                if($this->app->db->errorMessage != '') $error .= '<br />'.$this->app->db->errorMessage != '';
		           	}
	        	}
	      	} else {
	       		//* Username or password empty
	            $error = $this->app->lng(1001);
	        }
		}
		if($error != ''){
	  		$error = '<div class="box box_error"><h1>Error</h1>'.$error.'</div>';
		}
	
	
	
		$this->app->tpl->setVar('error', $error);
		$this->app->tpl->setInclude('content_tpl','login/templates/index.htm');
		$this->app->tpl_defaults();
		
		$this->status = 'OK';
		
		return $this->app->tpl->grab();
		
	} // << end function

} // << end class

?>
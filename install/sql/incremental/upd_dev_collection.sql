ALTER TABLE `mail_mailinglist` ADD `list_type` enum('open','closed') NOT NULL DEFAULT 'open';
ALTER TABLE `mail_mailinglist` ADD `subject_prefix` varchar(50) NOT NULL DEFAULT '';
ALTER TABLE `mail_mailinglist` ADD `admins` mediumtext;
ALTER TABLE `mail_mailinglist` ADD `digestinterval` int(11) NOT NULL DEFAULT '7';
ALTER TABLE `mail_mailinglist` ADD `digestmaxmails` int(11) NOT NULL DEFAULT '50';
ALTER TABLE `mail_mailinglist` ADD `archive` enum('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `digesttext` ENUM('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `digestsub` ENUM('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `mail_footer` mediumtext;
ALTER TABLE `mail_mailinglist` ADD `subscribe_policy` enum('disabled','confirm','approval','both','none') NOT NULL DEFAULT 'confirm';
ALTER TABLE `mail_mailinglist` ADD `posting_policy` enum('closed','moderated','free') NOT NULL DEFAULT 'free';
ALTER TABLE `sys_user` ADD `last_login_ip` VARCHAR(50) NULL AFTER `lost_password_reqtime`;
ALTER TABLE `sys_user` ADD `last_login_at` BIGINT(20) NULL AFTER `last_login_ip`;
ALTER TABLE `sys_remoteaction` CHANGE `action_state` `action_state` ENUM('pending','processing','ok','warning','error') NOT NULL DEFAULT 'pending';
ALTER TABLE `web_domain` CHANGE `folder_directive_snippets` `folder_directive_snippets` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `web_domain` ADD `log_retention` INT NOT NULL DEFAULT '30' AFTER `https_port`;
ALTER TABLE `web_domain` CHANGE `stats_type` `stats_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'awstats';
ALTER TABLE `spamfilter_policy` 
CHANGE `virus_lover` `virus_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `spam_lover` `spam_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `banned_files_lover` `banned_files_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bad_header_lover` `bad_header_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_virus_checks` `bypass_virus_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_spam_checks` `bypass_spam_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_banned_checks` `bypass_banned_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_header_checks` `bypass_header_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `spam_modifies_subj` `spam_modifies_subj` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnvirusrecip` `warnvirusrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnbannedrecip` `warnbannedrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnbadhrecip` `warnbadhrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N';

CREATE TABLE IF NOT EXISTS `dns_ssl_ca` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `ca_name` varchar(255) NOT NULL DEFAULT '',
  `ca_issue` varchar(255) NOT NULL DEFAULT '',
  `ca_wildcard` enum('Y','N') NOT NULL DEFAULT 'N',
  `ca_iodef` text NOT NULL,
  `ca_critical` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`ca_issue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `dns_ssl_ca` ADD UNIQUE(`ca_issue`);

UPDATE `dns_ssl_ca` SET `ca_issue` = 'comodo.com' WHERE `ca_issue` = 'comodoca.com';
UPDATE `dns_ssl_ca` SET `ca_issue` = 'geotrust.com' WHERE `ca_issue` = 'symantec.com';
UPDATE `dns_ssl_ca` SET `ca_issue` = 'thawte.com' WHERE `ca_issue` = 'symantec.com';

INSERT IGNORE INTO `dns_ssl_ca` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `active`, `ca_name`, `ca_issue`, `ca_wildcard`, `ca_iodef`, `ca_critical`) VALUES
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'AC Camerfirma', 'camerfirma.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'ACCV', 'accv.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Actalis', 'actalis.it', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Amazon', 'amazon.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Asseco', 'certum.pl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Buypass', 'buypass.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CA Disig', 'disig.sk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CATCert', 'aoc.cat', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certinomis', 'www.certinomis.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certizen', 'hongkongpost.gov.hk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'certSIGN', 'certsign.ro', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CFCA', 'cfca.com.cn', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Chunghwa Telecom', 'cht.com.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Comodo', 'comodoca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'D-TRUST', 'd-trust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DigiCert', 'digicert.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DocuSign', 'docusign.fr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'e-tugra', 'e-tugra.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'EDICOM', 'edicomgroup.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Entrust', 'entrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Firmaprofesional', 'firmaprofesional.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'FNMT', 'fnmt.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GeoTrust (Symantec)', 'symantec.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GlobalSign', 'globalsign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GoDaddy', 'godaddy.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Google Trust Services', 'pki.goog', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GRCA', 'gca.nat.gov.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'HARICA', 'harica.gr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'IdenTrust', 'identrust.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Izenpe', 'izenpe.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Kamu SM', 'kamusm.gov.tr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Let''s Encrypt', 'letsencrypt.org', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Microsec e-Szigno', 'e-szigno.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'NetLock', 'netlock.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PKIoverheid', 'www.pkioverheid.nl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PROCERT', 'procert.net.ve', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'QuoVadis', 'quovadisglobal.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SECOM', 'secomtrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Sertifitseerimiskeskuse', 'sk.ee', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'StartCom', 'startcomca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SwissSign', 'swisssign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Symantec', 'symantec.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'T-Systems', 'telesec.de', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Telia', 'telia.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Thawte (Symantec)', 'symantec.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Trustwave', 'trustwave.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Web.com', 'web.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WISeKey', 'wisekey.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WoSign', 'wosign.com', 'Y', '', 0);

ALTER TABLE `dns_rr` CHANGE `type` `type` ENUM('A','AAAA','ALIAS','CAA','CNAME','DS','HINFO','LOC','MX','NAPTR','NS','PTR','RP','SRV','TXT','TLSA','DNSKEY') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `dns_rr` CHANGE `data` `data` TEXT NOT NULL;
ALTER TABLE `web_database` CHANGE `database_quota` `database_quota` INT(11) NULL DEFAULT NULL;
ALTER TABLE `web_domain` ADD `log_retention` INT NOT NULL DEFAULT '30' ;
ALTER TABLE spamfilter_policy CHANGE spam_tag_level spam_tag_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_tag2_level spam_tag2_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_kill_level spam_kill_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_dsn_cutoff_level spam_dsn_cutoff_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_quarantine_cutoff_level spam_quarantine_cutoff_level DECIMAL(5,2) NULL DEFAULT NULL;
UPDATE `web_database` as d LEFT JOIN `web_domain` as w ON (w.domain_id = d.parent_domain_id) SET d.parent_domain_id = 0 WHERE w.domain_id IS NULL AND d.parent_domain_id != 0;

ALTER TABLE `mail_user` ADD `disablelmtp` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `disablelda` ;
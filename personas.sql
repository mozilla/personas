CREATE TABLE `personas` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(32) default NULL,
  `header` varchar(64) default NULL,
  `footer` varchar(64) default NULL,
  `category` varchar(32) default NULL,
  `status` tinyint(4) default NULL,
  `submit` varchar(32) default NULL,
  `approve` varchar(32) default NULL,
  `author` varchar(32) default NULL,
  `accentcolor` varchar(10) default NULL,
  `textcolor` varchar(10) default NULL,
  `popularity` int(11) default NULL,
  `description` text,
  `license` varbinary(10) default NULL,
  `reason` varbinary(24) default NULL,
  `reason_other` varbinary(256) default NULL,
  `featured` tinyint(4) default NULL,
  `locale` varchar(2) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=3142 DEFAULT CHARSET=latin1 | 


CREATE TABLE `categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1


CREATE TABLE `edits` (
  `id` int(11) NOT NULL,
  `author` varchar(32) default NULL,
  `name` varchar(32) default NULL,
  `header` varchar(64) default NULL,
  `footer` varchar(64) default NULL,
  `category` varchar(32) default NULL,
  `accentcolor` varchar(10) default NULL,
  `textcolor` varchar(10) default NULL,
  `description` text,
  `reason` varbinary(24) default NULL,
  `reason_other` varbinary(256) default NULL,
  `submit` varbinary(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1

CREATE TABLE `log` (
  `id` int(11) default NULL,
  `username` varchar(32) default NULL,
  `action` text,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1

CREATE TABLE `users` (
  `username` varchar(32) NOT NULL,
  `md5` varchar(32) default NULL,
  `email` varchar(64) default NULL,
  `privs` tinyint(4) default '1',
  `change_code` varbinary(20) default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
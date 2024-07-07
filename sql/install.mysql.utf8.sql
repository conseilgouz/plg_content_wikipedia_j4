CREATE TABLE IF NOT EXISTS `#__cgwiki` (
`id` int(12) NOT NULL AUTO_INCREMENT,
`text` text NOT NULL,
`definition` text NOT NULL,
`language` char(7) NOT NULL DEFAULT '',
`url` varchar(255) NOT NULL DEFAULT '',
`created` datetime NULL default '1980-01-01 00:00:00' ,
`created_by` int(10) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='CG Wikipedia dictionary';

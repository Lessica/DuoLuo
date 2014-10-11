-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `tickets` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `tid` varchar(16) NOT NULL,
  `section` varchar(128) NOT NULL,
  `server` varchar(128) NOT NULL,
  `charactername` varchar(128) NOT NULL,
  `camp` varchar(128) NOT NULL,
  `occupation` varchar(128) NOT NULL,
  `card` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `subaccount` varchar(128) NOT NULL,
  `mobile` varchar(128) NOT NULL,
  `qq` varchar(128) NOT NULL,
  `aliim` varchar(128) NOT NULL,
  `gold` int(16) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `description` varchar(1024) NOT NULL,
  `requirements` varchar(1024) NOT NULL,
  `stat` int(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `users` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password` varchar(40) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `works` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `tid` varchar(16) NOT NULL,
  `type` int(8) NOT NULL,
  `statments` varchar(256) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- 2014-10-10 07:02:21

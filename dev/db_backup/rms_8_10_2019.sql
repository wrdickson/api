-- Adminer 4.7.2 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lastName` varchar(48) NOT NULL,
  `firstName` varchar(48) NOT NULL,
  `address1` varchar(64) NOT NULL,
  `address2` varchar(64) NOT NULL,
  `city` varchar(48) NOT NULL,
  `region` varchar(48) NOT NULL,
  `country` varchar(48) NOT NULL,
  `postalCode` varchar(48) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `folios`;
CREATE TABLE `folios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer` int(11) NOT NULL,
  `reservation` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `payment_types`;
CREATE TABLE `payment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_title` varchar(144) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `is_active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_posted` datetime NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `payment_type` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `folio` int(11) NOT NULL,
  `shift` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_type` (`payment_type`),
  KEY `folio` (`folio`),
  KEY `shift` (`shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `space_code` text NOT NULL,
  `space_id` int(11) NOT NULL,
  `checkin` date NOT NULL,
  `checkout` date NOT NULL,
  `customer` int(11) NOT NULL,
  `people` int(11) NOT NULL,
  `beds` int(11) NOT NULL,
  `folio` int(11) NOT NULL,
  `history` text NOT NULL,
  `status` int(11) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `reshistory`;
CREATE TABLE `reshistory` (
  `res_id` int(11) NOT NULL,
  `history` text,
  PRIMARY KEY (`res_id`),
  UNIQUE KEY `resId` (`res_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `sale_types`;
CREATE TABLE `sale_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(144) NOT NULL,
  `is_current` bit(1) NOT NULL,
  `tax_type` int(11) NOT NULL,
  `display_order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_type` (`tax_type`),
  CONSTRAINT `sale_types_ibfk_1` FOREIGN KEY (`tax_type`) REFERENCES `tax_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` datetime NOT NULL,
  `sales_item` int(11) NOT NULL,
  `sales_quantity` int(11) NOT NULL,
  `net` decimal(18,2) NOT NULL,
  `tax` decimal(18,2) NOT NULL,
  `total` decimal(18,2) NOT NULL,
  `sold_by` int(11) NOT NULL,
  `folio` int(11) NOT NULL,
  `shift` int(11) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `shift` (`shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `sales` (`id`, `sale_date`, `sales_item`, `sales_quantity`, `net`, `tax`, `total`, `sold_by`, `folio`, `shift`, `notes`) VALUES
(84,	'2019-08-09 16:45:50',	8,	2,	25.52,	1.86,	27.38,	1,	61,	24,	'[]');

DROP TABLE IF EXISTS `sales_item_groups`;
CREATE TABLE `sales_item_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_order` int(11) NOT NULL,
  `group_title` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `sales_item_groups` (`id`, `group_order`, `group_title`) VALUES
(3,	30,	'Merchandise'),
(4,	23,	'Dorm Charge');

DROP TABLE IF EXISTS `sales_items`;
CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_group` int(11) NOT NULL,
  `group_order` int(11) NOT NULL,
  `sales_item_code` varchar(64) NOT NULL,
  `sales_item_title` varchar(48) NOT NULL,
  `is_fixed_price` tinyint(4) NOT NULL,
  `price` decimal(18,2) NOT NULL,
  `tax_type` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_item_code` (`sales_item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `sales_items` (`id`, `sales_group`, `group_order`, `sales_item_code`, `sales_item_title`, `is_fixed_price`, `price`, `tax_type`) VALUES
(7,	4,	22,	'111',	'Room Charge',	0,	0.00,	1),
(8,	3,	2,	'12-9',	'T Shirt',	1,	12.76,	2);

DROP TABLE IF EXISTS `select_groups`;
CREATE TABLE `select_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(48) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_open` tinyint(4) NOT NULL,
  `user` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `space_types`;
CREATE TABLE `space_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `spaces`;
CREATE TABLE `spaces` (
  `description` varchar(244) NOT NULL,
  `child_of` int(11) NOT NULL,
  `show_subspaces` tinyint(4) NOT NULL,
  `show_order` int(11) NOT NULL,
  `space_id` int(11) NOT NULL,
  `space_code` text NOT NULL,
  `subspaces` text NOT NULL,
  `space_type` int(11) NOT NULL,
  `beds` int(11) NOT NULL,
  `people` int(11) NOT NULL,
  `select_group` int(11) NOT NULL,
  `select_order` int(11) NOT NULL,
  UNIQUE KEY `space_id` (`space_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `tax_types`;
CREATE TABLE `tax_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_title` varchar(48) NOT NULL,
  `tax_rate` decimal(19,4) NOT NULL,
  `is_current` tinyint(4) NOT NULL,
  `display_order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  `permission` int(11) NOT NULL,
  `registered` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `last_login` datetime NOT NULL,
  `user_key` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2019-08-10 05:36:14

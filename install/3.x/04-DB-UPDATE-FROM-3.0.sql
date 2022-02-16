/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


ALTER TABLE `config` ADD `conf_isolate_areas` INT(1) NOT NULL DEFAULT '0' COMMENT 'Visibilidade entre areas para consultas e relatorios' AFTER `conf_sla_tolerance`; 


ALTER TABLE `hw_alter` CHANGE `hwa_item` `hwa_item` INT(4) NULL; 

ALTER TABLE `mailconfig` ADD `mail_send` TINYINT(1) NOT NULL DEFAULT '1' AFTER `mail_from_name`; 

ALTER TABLE `modelos_itens` ADD `mdit_manufacturer` INT(6) NULL AFTER `mdit_cod`, ADD INDEX (`mdit_manufacturer`); 

ALTER TABLE `modelos_itens` CHANGE `mdit_fabricante` `mdit_fabricante` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 

ALTER TABLE `estoque` ADD `estoq_assist` INT(2) NULL DEFAULT NULL AFTER `estoq_partnumber`, ADD `estoq_warranty_type` INT(2) NULL DEFAULT NULL AFTER `estoq_assist`, ADD INDEX (`estoq_assist`), ADD INDEX (`estoq_warranty_type`); 


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

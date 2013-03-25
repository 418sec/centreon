ALTER TABLE `hostgroup` ADD COLUMN `hg_rrd_retention` INT(11) DEFAULT NULL AFTER `hg_map_icon_image`;

UPDATE `topology` SET `readonly` = '0' WHERE `topology_parent` = '608' AND `topology_url` IS NOT NULL;

-- /!\ WARNING /!\
-- This file must be renamed and the query below must be updated once we know the exact source and target versions.
-- /!\ WARNING /!\
UPDATE `informations` SET `value` = '2.5.x' WHERE CONVERT( `informations`.`key` USING utf8 )  = 'version' AND CONVERT ( `informations`.`value` USING utf8 ) = '2.4.x' LIMIT 1;

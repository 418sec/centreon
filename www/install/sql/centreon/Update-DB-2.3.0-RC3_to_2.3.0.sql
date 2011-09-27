
DELETE FROM topology WHERE topology_url LIKE './include/monitoring/mysql_log/viewLog.php' AND topology_name LIKE 'All Logs' AND topology_show = '0';
DELETE FROM topology WHERE topology_url LIKE './include/monitoring/mysql_log/viewErrors.php' AND topology_name LIKE 'Warnings' AND topology_show = '0';
DELETE FROM topology WHERE topology_page = '20313' OR topology_parent = '20313';
DELETE FROM topology WHERE topology_page = '20312' OR topology_parent = '20312';

INSERT INTO options (`key`, `value`) VALUES ('centstorage', '1');

ALTER TABLE `acl_topology` ADD `acl_comments` text DEFAULT NULL AFTER acl_topo_alias ;

UPDATE `informations` SET `value` = '2.3.0' WHERE CONVERT( `informations`.`key` USING utf8 )  = 'version' AND CONVERT ( `informations`.`value` USING utf8 ) = '2.3.0-RC3' LIMIT 1;

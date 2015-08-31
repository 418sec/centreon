<?php
/*
 * Copyright 2015 Centreon (http://www.centreon.com/)
 * 
 * Centreon is a full-fledged industry-strength solution that meets 
 * the needs in IT infrastructure and application monitoring for 
 * service performance.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0  
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * For more information : contact@centreon.com
 * 
 */

if (!isset($_POST['poller'])) {
    exit;
}

require_once "@CENTREON_ETC@/centreon.conf.php";
require_once $centreon_path.'/www/class/centreonDB.class.php';
require_once $centreon_path.'/www/class/centreonXML.class.php';
require_once $centreon_path.'/www/class/centreonInstance.class.php';

$poller = $_POST['poller'];
$db = new CentreonDB();
$xml = new CentreonXML();

$res = $db->query("SELECT `name`, `id`, `localhost` 
    FROM `nagios_server` 
    WHERE `ns_activate` = '1' 
    ORDER BY `name` ASC");
$xml->startElement('response');
$str = sprintf("<br/><b>%s</b><br/>", _("Post execution command results"));
$ok = true;
$instanceObj = new CentreonInstance($db);
while ($row = $res->fetchRow()) {
    if ($poller == 0 || $poller == $row['id']) {
        $commands = $instanceObj->getCommandData($row['id']);
        if (!count($commands)) {
            continue;
        }
        $str .= "<br/><strong>{$row['name']}</strong><br/>";
        foreach ($commands as $command) {
            $output = array();
            exec($command['command_line'], $output, $result);
            $resultColor = "green";
            if ($result != 0) {
                $resultColor = "red";
                $ok = false;
            }
            $str .= $command['command_name'] . ": <font color='$resultColor'>".implode(";", $output)."</font><br/>";
        }
    }
}
if ($ok === false) {
    $statusStr = "<b><font color='red'>NOK</font></b>";
} else {
    $statusStr = "<b><font color='green'>OK</font></b>";
}

$xml->writeElement('result', $str);
$xml->writeElement('status', $statusStr);
$xml->endElement();
header('Content-Type: application/xml');
header('Cache-Control: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');
$xml->output();
?>

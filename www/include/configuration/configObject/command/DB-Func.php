<?php
/*
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

	if (!isset ($oreon))
		exit ();

	if (!function_exists("myDecodeCommand")) {
		function myDecodeCommand($arg) {
			$arg = html_entity_decode($arg, ENT_QUOTES, "UTF-8");
			$arg = str_replace('#BR#', "\\n", $arg);
			$arg = str_replace('#T#', "\\t", $arg);
			$arg = str_replace('#R#', "\\r", $arg);
			$arg = str_replace('#S#', "/", $arg);
			$arg = str_replace('#BS#', "\\", $arg);
			$arg = str_replace('#P#', "|", $arg);
			return($arg);
		}
	}

	function testCmdExistence ($name = null)
	{
		global $pearDB, $form, $oreon;
		$id = null;

		if (isset($form)) {
			$id = $form->getSubmitValue('command_id');
		}

		$DBRESULT = $pearDB->query("SELECT `command_name`, `command_id` FROM `command` WHERE `command_name` = '".$oreon->checkIllegalChar($pearDB->escape($name))."'");
		$command = $DBRESULT->fetchRow();
		if ($DBRESULT->numRows() >= 1 && $command["command_id"] == $id)	{
			/*
			 * Mofication case
			 */
			return true;
		} elseif ($DBRESULT->numRows() >= 1 && $command["command_id"] != $id) {
			/*
			 * Duplicate case
			 */
			return false;
		} else {
			return true;
		}
	}

	function deleteCommandInDB ($commands = array())
	{
		global $pearDB, $oreon;

		foreach ($commands as $key => $value) {
			$DBRESULT2 = $pearDB->query("SELECT command_name FROM `command` WHERE `command_id` = '".$key."' LIMIT 1");
			$row = $DBRESULT2->fetchRow();
			$DBRESULT = $pearDB->query("DELETE FROM `command` WHERE `command_id` = '".$key."'");
			$oreon->CentreonLogAction->insertLog("command", $key, $row['command_name'], "d");
		}
	}

	function multipleCommandInDB ($commands = array(), $nbrDup = array())
	{
		global $pearDB, $oreon;

		foreach($commands as $key => $value) {
			$DBRESULT = $pearDB->query("SELECT * FROM `command` WHERE `command_id` = '".$key."' LIMIT 1");

			$row = $DBRESULT->fetchRow();
			$row["command_id"] = '';

			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;

				foreach ($row as $key2=>$value2) {
					$key2 == "command_name" ? ($command_name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2 != NULL?(", '".$pearDB->escape($value2)."'"):", NULL") : $val .= ($value2 != NULL?("'".$pearDB->escape($value2)."'"):"NULL");
					if ($key2 != "command_id") {
						$fields[$key2] = $pearDB->escape($value2);
					}
					if (isset($command_name)) {
					    $fields["command_name"] = $command_name;
					}
				}

				if (isset($command_name) && testCmdExistence($command_name)) {
					$val ? $rq = "INSERT INTO `command` VALUES (".$val.")" : $rq = null;
					$DBRESULT = $pearDB->query($rq);
					/*
		 			* Get Max ID
		 			*/
					$DBRESULT = $pearDB->query("SELECT MAX(command_id) FROM `command`");
					$cmd_id = $DBRESULT->fetchRow();
					$oreon->CentreonLogAction->insertLog("command", $cmd_id["MAX(command_id)"], $command_name, "a", $fields);
				}

				/*
				 * Duplicate Arguments
				 */
				duplicateArgDesc($cmd_id["MAX(command_id)"], $key);
			}
		}
	}

	function updateCommandInDB ($cmd_id = null)
	{
		if (!$cmd_id) {
		    return;
		}
		updateCommand($cmd_id);
	}

	function updateCommand($cmd_id = null, $params = array())
	{
		global $form, $pearDB, $oreon;


		if (!$cmd_id) {
		    return;
		}

		$ret = array();
                if (count($params)) {
                    $ret = $params;
                } else {
		    $ret = $form->getSubmitValues();
                }
		//set_magic_quotes_runtime(1);

		$ret["command_name"] = $oreon->checkIllegalChar($ret["command_name"]);
		if (!isset($ret['enable_shell'])) {
		    $ret['enable_shell'] = 0;
		}

		$rq = "UPDATE `command` SET `command_name` = '".$pearDB->escape($ret["command_name"])."', " .
				"`command_line` = '".$pearDB->escape($ret["command_line"])."', " .
				"`enable_shell` = '".$pearDB->escape($ret["enable_shell"])."', " .
				"`command_example` = '".$pearDB->escape($ret["command_example"])."', " .
				"`command_type` = '".$pearDB->escape($ret["command_type"]["command_type"])."', " .
				"`command_comment` = '".$pearDB->escape($ret["command_comment"])."', " .
				"`graph_id` = '".$pearDB->escape($ret["graph_id"])."', " .
                "`connector_id` = " . (isset($ret["connectors"]) && !empty($ret["connectors"]) ? "'".$ret['connectors']."'" : "NULL") . " " .
				"WHERE `command_id` = '".$cmd_id."'";
		$DBRESULT = $pearDB->query($rq);

		$fields["command_name"] = $pearDB->escape($ret["command_name"]);
		$fields["command_line"] = $pearDB->escape($ret["command_line"]);
		$fields["enable_shell"] = $pearDB->escape($ret["enable_shell"]);
		$fields["command_example"] = $pearDB->escape($ret["command_example"]);
		$fields["command_comment"] = $pearDB->escape($ret["command_comment"]);
		$fields["command_type"] = $ret["command_type"]["command_type"];

		$fields["graph_id"] = $ret["graph_id"];
		$fields["connector_id"] = $ret["connectors"];
		$oreon->CentreonLogAction->insertLog("command", $cmd_id, $pearDB->escape($ret["command_name"]), "c", $fields);
		insertArgDesc($cmd_id, $ret);
	}

	function insertCommandInDB ($ret = array())
	{
		$cmd_id = insertCommand($ret);
		return ($cmd_id);
	}

	function insertCommand($ret = array())
	{
		global $form, $pearDB, $oreon;

		if (!count($ret)) {
			$ret = $form->getSubmitValues();
		}
		//set_magic_quotes_runtime(1);

		$ret["command_name"] = $oreon->checkIllegalChar($ret["command_name"]);
	    if (!isset($ret['enable_shell'])) {
		    $ret['enable_shell'] = 0;
		}

		/*
		 * Insert
		 */

        $rq = "INSERT INTO `command` (`command_name`, `command_line`, `enable_shell`, `command_example`, `command_type`, `graph_id`, `connector_id`) ";
        $rq .= "VALUES ('".$pearDB->escape($ret["command_name"])."', '".$pearDB->escape($ret["command_line"]) . "', '" . $pearDB->escape($ret['enable_shell']) . "', '".$pearDB->escape($ret["command_example"])."', '".$ret["command_type"]["command_type"]."', '".$ret["graph_id"]."', ".
              (isset($ret["connectors"]) && !empty($ret["connectors"])? "'".$ret['connectors']."'" : "NULL").")";

        $DBRESULT = $pearDB->query($rq);
		$fields["command_name"] = $pearDB->escape($ret["command_name"]);
		$fields["command_line"] = $pearDB->escape($ret["command_line"]);
		$fields['enable_shell'] = $pearDB->escape($ret['enable_shell']);
		$fields["command_example"] = $pearDB->escape($ret["command_example"]);
		$fields["command_type"] = $ret["command_type"]["command_type"];
		$fields["graph_id"] = $ret["graph_id"];
        $fields["connector_id"] = $ret["connectors"];

		/*
		 * Get Max ID
		 */
		$DBRESULT = $pearDB->query("SELECT MAX(command_id) FROM `command`");
		$cmd_id = $DBRESULT->fetchRow();

		$oreon->CentreonLogAction->insertLog("command", $cmd_id["MAX(command_id)"], $pearDB->escape($ret["command_name"]), "a", $fields);
		insertArgDesc($cmd_id["MAX(command_id)"], $ret);
		return ($cmd_id["MAX(command_id)"]);
	}

	function return_plugin($rep)
	{
		global $oreon;

		$plugins = array();
		$is_not_a_plugin = array("." => 1, ".." => 1, "oreon.conf" => 1, "oreon.pm" => 1, "utils.pm" => 1, "negate" => 1, "centreon.conf" => 1, "centreon.pm" => 1);
		$handle[$rep] = opendir($rep);
		while (false != ($filename = readdir($handle[$rep]))){
			if ($filename != "." && $filename != ".."){
				if (is_dir($rep.$filename)){
					$plg_tmp = return_plugin($rep."/".$filename, $handle[$rep]);
					$plugins = array_merge($plugins, $plg_tmp);
					unset($plg_tmp);
				} elseif (!isset($is_not_a_plugin[$filename]) && substr($filename, -1)!= "~" && substr($filename, -1) != "#") {
					$key = substr($rep."/".$filename, strlen($oreon->optGen["nagios_path_plugins"]));
					$plugins[$key] = $key;
				}
			}
		}
		closedir($handle[$rep]);
		return ($plugins);
	}

	/*
	 *  Inserts descriptions of arguments
	 */
	function insertArgDesc($cmd_id, $ret = null)
	{
		global $oreon, $pearDB;

		if (!count($ret)) {
			$ret = $form->getSubmitValues();
		}

		$pearDB->query("DELETE FROM `command_arg_description` WHERE cmd_id = '".$cmd_id."'");
		$query = "INSERT INTO `command_arg_description` (cmd_id, macro_name, macro_description) VALUES ";
		if (isset($ret['listOfArg']) && $ret['listOfArg']) {
			$tab1 = preg_split("/\\n/", $ret['listOfArg']);
			foreach ($tab1 as $key => $value) {
				$tab2 = preg_split("/\ \:\ /", $value, 2);
				$query .= "('" . $pearDB->escape($cmd_id) . "', '" . $pearDB->escape($tab2[0]) . "', '" .$pearDB->escape($tab2[1]). "'),";
			}
			$query = trim($query, ",");
			$pearDB->query($query);
		}
	}

	/**
	 * Duplicate The argument description of a command
	 * @param $cmd_id
	 * @param $ret
	 * @return unknown_type
	 */
	function duplicateArgDesc($new_cmd_id, $cmd_id) {
		global $pearDB;

		$query = "INSERT INTO `command_arg_description` (cmd_id, macro_name, macro_description) SELECT '$new_cmd_id', macro_name, macro_description FROM command_arg_description WHERE cmd_id = '$cmd_id'";
		$pearDB->query($query);
	}

	/**
	 * Return the number of time a command is used as a host command check
	 * @param $command_id
	 * @return unknown_type
	 */
	function getHostNumberUse($command_id) {
		global $pearDB;

		$DBRESULT = $pearDB->query("SELECT count(*) AS number FROM host WHERE command_command_id = '$command_id' AND host_register = '1'");
		$data = $DBRESULT->fetchRow();
		return $data['number'];
	}

	/**
	 * Return the number of time a command is used as a service command check
	 * @param $command_id
	 * @return unknown_type
	 */
	function getServiceNumberUse($command_id) {
		global $pearDB;

		$DBRESULT = $pearDB->query("SELECT count(*) AS number FROM service WHERE command_command_id = '$command_id' AND service_register = '1'");
		$data = $DBRESULT->fetchRow();
		return $data['number'];
	}

		/**
	 * Return the number of time a command is used as a host command check
	 * @param $command_id
	 * @return unknown_type
	 */
	function getHostTPLNumberUse($command_id) {
		global $pearDB;

		$DBRESULT = $pearDB->query("SELECT count(*) AS number FROM host WHERE command_command_id = '$command_id' AND host_register = '0'");
		$data = $DBRESULT->fetchRow();
		return $data['number'];
	}

	/**
	 * Return the number of time a command is used as a service command check
	 * @param $command_id
	 * @return unknown_type
	 */
	function getServiceTPLNumberUse($command_id) {
		global $pearDB;

		$DBRESULT = $pearDB->query("SELECT count(*) AS number FROM service WHERE command_command_id = '$command_id' AND service_register = '0'");
		$data = $DBRESULT->fetchRow();
		return $data['number'];
	}

        /**
         * Get command ID by name
         * 
         * @param string $name
         * @return int
         */
        function getCommandIdByName($name) {
            global $pearDB;

            $id = 0;
            $res = $pearDB->query("SELECT command_id FROM command WHERE command_name = '".$pearDB->escape($name)."'");
            if ($res->numRows()) {
                $row = $res->fetchRow();
                $id = $row['command_id'];
            }
            return $id;
        }
?>

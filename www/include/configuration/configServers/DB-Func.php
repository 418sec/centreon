<?php
/**
Centreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Julien Mathis - Romain Le Merlus

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/
	if (!isset($oreon))
		exit();
	
	function testExistence ($name = NULL)	{
		global $pearDB, $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('id');
		$DBRESULT =& $pearDB->query("SELECT name, id FROM `nagios_server` WHERE `name` = '".htmlentities($name, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$ndomod =& $DBRESULT->fetchRow();
		if ($DBRESULT->numRows() >= 1 && $ndomod["id"] == $id)#Modif case	
			return true;
		else if ($DBRESULT->numRows() >= 1 && $ndomod["id"] != $id)#Duplicate entry
			return false;
		else
			return true;
	}	
	
	function enableServerInDB ($id = null)	{
		if (!$id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE `nagios_server` SET `ns_activate` = '1' WHERE id = '".$id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	}
	
	function disableServerInDB ($id = null)	{
		if (!$id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE `nagios_server` SET `ns_activate` = '0' WHERE id = '".$id."'");
	}
	
	function deleteServerInDB ($server = array())	{
		global $pearDB;
		foreach($server as $key => $value)	{
			$DBRESULT =& $pearDB->query("DELETE FROM `nagios_server` WHERE id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
	
	function multipleServerInDB ($server = array(), $nbrDup = array())	{
		foreach($server as $key => $value)	{
			global $pearDB;
			$DBRESULT =& $pearDB->query("SELECT * FROM `nagios_server` WHERE id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$row = $DBRESULT->fetchRow();
			$row["id"] = '';
			$row["ns_activate"] = '0';
			$DBRESULT->free();
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2=>$value2)	{
					$key2 == "name" ? ($server_name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testExistence($server_name))	{
					$val ? $rq = "INSERT INTO `nagios_server` VALUES (".$val.")" : $rq = null;
					$DBRESULT =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
				}
			}
		}
	}
	
	function updateServerInDB ($id = NULL)	{
		if (!$id) return;
		updateServer($id);
	}	
	
	function insertServerInDB ()	{
		$id = insertServer();
		return ($id);
	}
	
	function insertServer($ret = array())	{
		global $form, $pearDB, $oreon;
		if (!count($ret))
			$ret = $form->getSubmitValues();
		$rq = "INSERT INTO `nagios_server` (`name` , `localhost` , `ns_ip_address`, `nagios_bin` , `init_script` , `ns_activate`) ";
		$rq .= "VALUES (";
		isset($ret["name"]) && $ret["name"] != NULL ? $rq .= "'".htmlentities($ret["name"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		isset($ret["localhost"]["localhost"]) && $ret["localhost"]["localhost"] != NULL ? $rq .= "'".htmlentities($ret["localhost"]["localhost"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["ns_ip_address"]) && $ret["ns_ip_address"] != NULL ? $rq .= "'".htmlentities($ret["ns_ip_address"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["nagios_bin"]) && $ret["nagios_bin"] != NULL ? $rq .= "'".htmlentities($ret["nagios_bin"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["init_script"]) && $ret["init_script"] != NULL ? $rq .= "'".htmlentities($ret["init_script"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["ns_activate"]["ns_activate"]) && $ret["ns_activate"]["ns_activate"] != 2 ? $rq .= "'".$ret["ns_activate"]["ns_activate"]."'  "  : $rq .= "NULL)";
       	$rq .= ")";
       	$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$DBRESULT =& $pearDB->query("SELECT MAX(id) FROM `nagios_server`");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$ndomod_id = $DBRESULT->fetchRow();
		$DBRESULT->free();
		return ($ndomod_id["MAX(id)"]);
	}
	
	function updateServer($id = null)	{
		if (!$id) return;
		global $form, $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		if ($ret["localhost"]["localhost"] == 1){
			$DBRESULT =& $pearDB->query("UPDATE `nagios_server` SET `localhost` = '0'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
		$rq = "UPDATE `nagios_server` SET ";
        isset($ret["name"]) && $ret["name"] != NULL ? $rq .= "name = '".htmlentities($ret["name"], ENT_QUOTES)."', " : $rq .= "name = NULL, ";
        isset($ret["localhost"]["localhost"]) && $ret["localhost"]["localhost"] != NULL ? $rq .= "localhost = '".htmlentities($ret["localhost"]["localhost"], ENT_QUOTES)."', " : $rq .= "localhost = NULL, ";
		isset($ret["ns_ip_address"]) && $ret["ns_ip_address"] != NULL ? $rq .= "ns_ip_address = '".htmlentities($ret["ns_ip_address"], ENT_QUOTES)."',  " : $rq .= "ns_ip_address = NULL, ";
        isset($ret["init_script"]) && $ret["init_script"] != NULL ? $rq .= "init_script = '".htmlentities($ret["init_script"], ENT_QUOTES)."',  " : $rq .= "init_script = NULL, ";
        isset($ret["nagios_bin"]) && $ret["nagios_bin"] != NULL ? $rq .= "nagios_bin = '".htmlentities($ret["nagios_bin"], ENT_QUOTES)."',  " : $rq .= "nagios_bin = NULL, ";
        $rq .= "ns_activate = '".$ret["ns_activate"]["ns_activate"]."' ";
		$rq .= "WHERE id = '".$id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	}
?>
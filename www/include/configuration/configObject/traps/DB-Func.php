<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@oreon-project.org
 */
 

	function testTrapExistence ($oid = NULL)	{
		global $pearDB, $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('traps_id');
		$DBRESULT =& $pearDB->query("SELECT traps_oid, traps_id FROM traps WHERE traps_oid = '".htmlentities($oid, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$trap =& $DBRESULT->fetchRow();
		#Modif case
		if ($DBRESULT->numRows() >= 1 && $trap["traps_id"] == $id)	
			return true;
		#Duplicate entry
		else if ($DBRESULT->numRows() >= 1 && $trap["traps_id"] != $id)
			return false;
		else
			return true;
	}

	function deleteTrapInDB ($traps = array())	{
		global $pearDB;
		foreach($traps as $key=>$value)		{
			$DBRESULT =& $pearDB->query("DELETE FROM traps WHERE traps_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
	
	function multipleTrapInDB ($traps = array(), $nbrDup = array())	{
		global $pearDB;
		foreach($traps as $key=>$value)	{
			$DBRESULT =& $pearDB->query("SELECT * FROM traps WHERE traps_id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$row = $DBRESULT->fetchRow();
			$row["traps_id"] = '';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2=>$value2)	{
					$key2 == "traps_oid" ? ($traps_oid = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testTrapExistence($traps_oid))	{
					$val ? $rq = "INSERT INTO traps VALUES (".$val.")" : $rq = null;
					$DBRESULT =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
				}
			}
		}
	}
	
	function updateTrapInDB ($traps_id = NULL)	{
		if (!$traps_id) return;
		updateTrap($traps_id);
	}
	
	function updateTrap($traps_id = null)	{
		global $form, $pearDB;
		if (!$traps_id) return;
		$ret = array();
		$ret = $form->getSubmitValues();
		if (!isset($ret["traps_reschedule_svc_enable"]) || !$ret["traps_reschedule_svc_enable"])
			$ret["traps_reschedule_svc_enable"] = 0;
		if (!isset($ret["traps_submit_result_enable"]) || !$ret["traps_submit_result_enable"])
			$ret["traps_submit_result_enable"] = 0;
		if (!isset($ret["traps_execution_command_enable"])|| !$ret["traps_execution_command_enable"])
			$ret["traps_execution_command_enable"] = 0;
		$rq = "UPDATE traps ";
		$rq .= "SET `traps_name` = '".htmlentities($ret["traps_name"], ENT_QUOTES)."', ";
		$rq .= "`traps_oid` = '".htmlentities($ret["traps_oid"], ENT_QUOTES)."', ";
		$rq .= "`traps_args` = '".htmlentities($ret["traps_args"], ENT_QUOTES)."', ";
		$rq .= "`traps_status` = '".htmlentities($ret["traps_status"], ENT_QUOTES)."', ";
		$rq .= "`traps_submit_result_enable` = '".$ret["traps_submit_result_enable"]."', ";
		$rq .= "`traps_reschedule_svc_enable` = '".$ret["traps_reschedule_svc_enable"]."', ";
		$rq .= "`traps_execution_command` = '".htmlentities($ret["traps_execution_command"], ENT_QUOTES)."', ";
		$rq .= "`traps_execution_command_enable` = '".$ret["traps_execution_command_enable"]."', ";
		$rq .= "`traps_comments` = '".htmlentities($ret["traps_comments"], ENT_QUOTES)."', ";
		$rq .= "`manufacturer_id` = '".htmlentities($ret["manufacturer_id"], ENT_QUOTES)."' ";
		$rq .= "WHERE `traps_id` = '".$traps_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	}
	
	function insertTrapInDB ($ret = array())	{
		$traps_id = insertTrap($ret);
		return ($traps_id);
	}
	
	function insertTrap($ret = array())	{
		global $form, $pearDB;
		if (!count($ret))
			$ret = $form->getSubmitValues();
		$rq = "INSERT INTO traps ";
		$rq .= "(traps_name, traps_oid, traps_args, traps_status, traps_submit_result_enable, traps_reschedule_svc_enable, traps_execution_command, traps_execution_command_enable, traps_comments, manufacturer_id) ";
		$rq .= "VALUES ";
		$rq .= "('".htmlentities($ret["traps_name"], ENT_QUOTES)."',";
		$rq .= "'".htmlentities($ret["traps_oid"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_args"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_status"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_submit_result_enable"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_reschedule_svc_enable"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_execution_command"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_execution_command_enable"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["traps_comments"], ENT_QUOTES)."', ";
		$rq .= "'".htmlentities($ret["manufacturer_id"], ENT_QUOTES)."')";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$DBRESULT =& $pearDB->query("SELECT MAX(traps_id) FROM traps");
		$traps_id = $DBRESULT->fetchRow();
		return ($traps_id["MAX(traps_id)"]);
	}
?>

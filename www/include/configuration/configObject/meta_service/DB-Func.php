<?
/** 
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/gpl.txt
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
	if (!isset ($oreon))
		exit ();
			
	function testExistence ($name = NULL)	{
		global $pearDB;
		global $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('meta_id');
		$DBRESULT =& $pearDB->query("SELECT meta_id FROM meta_service WHERE meta_name = '".htmlentities($name, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$meta =& $DBRESULT->fetchRow();
		#Modif case
		if ($DBRESULT->numRows() >= 1 && $meta["meta_id"] == $id)	
			return true;
		#Duplicate entry
		else if ($DBRESULT->numRows() >= 1 && $meta["meta_id"] != $id)
			return false;
		else
			return true;
	}
	
	function enableMetaServiceInDB ($meta_id = null)	{
		if (!$meta_id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE meta_service SET meta_activate = '1' WHERE meta_id = '".$meta_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
	
	function disableMetaServiceInDB ($meta_id = null)	{
		if (!$meta_id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE meta_service SET meta_activate = '0' WHERE meta_id = '".$meta_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
	
	function deleteMetaServiceInDB ($metas = array())	{
		global $pearDB;
		foreach($metas as $key=>$value)	{
			$DBRESULT =& $pearDB->query("DELETE FROM meta_service WHERE meta_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getMessage()."<br>";
		}
	}
	
	function enableMetricInDB ($msr_id = null)	{
		if (!$msr_id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE meta_service_relation SET activate = '1' WHERE msr_id = '".$msr_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
	
	function disableMetricInDB ($msr_id = null)	{
		if (!$msr_id) return;
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE meta_service_relation SET activate = '0' WHERE msr_id = '".$msr_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
	
	function deleteMetricInDB ($metrics = array())	{
		global $pearDB;
		foreach($metrics as $key=>$value)	{
			$DBRESULT =& $pearDB->query("DELETE FROM meta_service_relation WHERE msr_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getMessage()."<br>";
		}
	}	
	
	function multipleMetaServiceInDB ($metas = array(), $nbrDup = array())	{
		# Foreach Meta Service
		foreach($metas as $key=>$value)	{
			global $pearDB;
			# Get all information about it
			$DBRESULT =& $pearDB->query("SELECT * FROM meta_service WHERE meta_id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getMessage()."<br>";
			$row = $DBRESULT->fetchRow();
			$row["meta_id"] = '';
			# Loop on the number of MetaService we want to duplicate
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				# Create a sentence which contains all the value
				foreach ($row as $key2=>$value2)	{
					$key2 == "meta_name" ? ($meta_name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testExistence($meta_name))	{
					$val ? $rq = "INSERT INTO meta_service VALUES (".$val.")" : $rq = null;
					$DBRESULT =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getMessage()."<br>";
					$DBRESULT =& $pearDB->query("SELECT MAX(meta_id) FROM meta_service");
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getMessage()."<br>";
					$maxId =& $DBRESULT->fetchRow();
					if (isset($maxId["MAX(meta_id)"]))	{
						$DBRESULT =& $pearDB->query("SELECT DISTINCT cg_cg_id FROM meta_contactgroup_relation WHERE meta_id = '".$key."'");
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getMessage()."<br>";
						while($DBRESULT->fetchInto($Cg))	{
							$DBRESULT2 =& $pearDB->query("INSERT INTO meta_contactgroup_relation VALUES ('', '".$maxId["MAX(meta_id)"]."', '".$Cg["cg_cg_id"]."')");
							if (PEAR::isError($DBRESULT2))
								print "DB Error : ".$DBRESULT2->getMessage()."<br>";
						}
						$DBRESULT =& $pearDB->query("SELECT * FROM meta_service_relation WHERE meta_id = '".$key."'");
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getMessage()."<br>";
						while($DBRESULT->fetchInto($metric))	{
							$val = null;
							$metric["msr_id"] = '';
							foreach ($metric as $key2=>$value2)	{
								$key2 == "meta_id" ? $value2 = $maxId["MAX(meta_id)"] : null;
								$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
							}
							$DBRESULT2 =& $pearDB->query("INSERT INTO meta_service_relation VALUES (".$val.")");
							if (PEAR::isError($DBRESULT2))
								print "DB Error : ".$DBRESULT2->getMessage()."<br>";
						}
					}
				}
			}
		}
	}
	
	function updateMetaServiceInDB ($meta_id = NULL)	{
		if (!$meta_id) return;
		updateMetaService($meta_id);
		updateMetaServiceContactGroup($meta_id);
	}	
	
	function insertMetaServiceInDB ()	{
		$meta_id = insertMetaService();
		updateMetaServiceContactGroup($meta_id);
		return ($meta_id);
	}
	
	function multipleMetricInDB ($metrics = array(), $nbrDup = array())	{
		# Foreach Meta Service
		foreach($metrics as $key=>$value)	{
			global $pearDB;
			# Get all information about it
			$DBRESULT =& $pearDB->query("SELECT * FROM meta_service_relation WHERE msr_id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getMessage()."<br>";
			$row = $DBRESULT->fetchRow();
			$row["msr_id"] = '';
			# Loop on the number of Metric we want to duplicate
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				# Create a sentence which contains all the value
				foreach ($row as $key2=>$value2)
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				$val ? $rq = "INSERT INTO meta_service_relation VALUES (".$val.")" : $rq = null;
				$DBRESULT =& $pearDB->query($rq);
				if (PEAR::isError($DBRESULT))
					print "DB Error : ".$DBRESULT->getMessage()."<br>";
			}
		}
	}
		
	function insertMetaService($ret = array())	{
		global $form;
		global $pearDB;
		if (count($ret))
			;	
		else
			$ret = $form->getSubmitValues();
		$rq = "INSERT INTO meta_service " .
				"(meta_name, check_period, max_check_attempts, normal_check_interval, retry_check_interval, notification_interval, " .
				"notification_period, notification_options, notifications_enabled, calcul_type, meta_select_mode, regexp_str, metric, warning, critical, " .
				"graph_id, meta_comment, meta_activate) " .
				"VALUES ( ";
				isset($ret["meta_name"]) && $ret["meta_name"] != NULL ? $rq .= "'".htmlentities($ret["meta_name"], ENT_QUOTES)."', ": $rq .= "NULL, ";
				isset($ret["check_period"]) && $ret["check_period"] != NULL ? $rq .= "'".$ret["check_period"]."', ": $rq .= "NULL, ";
				isset($ret["max_check_attempts"]) && $ret["max_check_attempts"] != NULL ? $rq .= "'".$ret["max_check_attempts"]."', " : $rq .= "NULL, ";
				isset($ret["normal_check_interval"]) && $ret["normal_check_interval"] != NULL ? $rq .= "'".$ret["normal_check_interval"]."', ": $rq .= "NULL, ";
				isset($ret["retry_check_interval"]) && $ret["retry_check_interval"] != NULL ? $rq .= "'".$ret["retry_check_interval"]."', ": $rq .= "NULL, ";
				isset($ret["notification_interval"]) && $ret["notification_interval"] != NULL ? $rq .= "'".$ret["notification_interval"]."', " : $rq .= "NULL, ";
				isset($ret["notification_period"]) && $ret["notification_period"] != NULL ? $rq .= "'".$ret["notification_period"]."', ": $rq .= "NULL, ";
				isset($ret["ms_notifOpts"]) && $ret["ms_notifOpts"] != NULL ? $rq .= "'".implode(",", array_keys($ret["ms_notifOpts"]))."', " : $rq .= "NULL, ";
				isset($ret["notifications_enabled"]["notifications_enabled"]) && $ret["notifications_enabled"]["notifications_enabled"] != 2 ? $rq .= "'".$ret["notifications_enabled"]["notifications_enabled"]."', " : $rq .= "'2', ";
				isset($ret["calcul_type"]) ? $rq .= "'".$ret["calcul_type"]."', " : $rq .= "NULL, ";
				isset($ret["meta_select_mode"]["meta_select_mode"]) ? $rq .= "'".$ret["meta_select_mode"]["meta_select_mode"]."', " : $rq .= "NULL, ";
				isset($ret["regexp_str"]) && $ret["regexp_str"] != NULL ? $rq .= "'".htmlentities($ret["regexp_str"])."', " : $rq .= "NULL, ";
				isset($ret["metric"]) && $ret["metric"] != NULL ? $rq .= "'".htmlentities($ret["metric"])."', " : $rq .= "NULL, ";
				isset($ret["warning"]) && $ret["warning"] != NULL ? $rq .= "'".htmlentities($ret["warning"])."', " : $rq .= "NULL, ";
				isset($ret["critical"]) && $ret["critical"] != NULL ? $rq .= "'".htmlentities($ret["critical"])."', " : $rq .= "NULL, ";
				isset($ret["graph_id"]) && $ret["graph_id"] != NULL ? $rq .= "'".$ret["graph_id"]."', " : $rq .= "NULL, ";
				isset($ret["meta_comment"]) && $ret["meta_comment"] != NULL ? $rq .= "'".htmlentities($ret["meta_comment"])."', " : $rq .= "NULL, ";
				isset($ret["meta_activate"]["meta_activate"]) && $ret["meta_activate"]["meta_activate"] != NULL ? $rq .= "'".$ret["meta_activate"]["meta_activate"]."'" : $rq .= "NULL";
				$rq .= ")";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$DBRESULT =& $pearDB->query("SELECT MAX(meta_id) FROM meta_service");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$meta_id = $DBRESULT->fetchRow();
		return ($meta_id["MAX(meta_id)"]);
	}
	
	function updateMetaService($meta_id = null)	{
		if (!$meta_id) return;
		global $form;
		global $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "UPDATE meta_service SET " ;
		$rq .= "meta_name = ";
		$ret["meta_name"] != NULL ? $rq .= "'".htmlentities($ret["meta_name"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		$rq .= "check_period = ";
		$ret["check_period"] != NULL ? $rq .= "'".$ret["check_period"]."', ": $rq .= "NULL, ";
		$rq .= "max_check_attempts = ";
		$ret["max_check_attempts"] != NULL ? $rq .= "'".$ret["max_check_attempts"]."', " : $rq .= "NULL, ";
		$rq .= "normal_check_interval = ";
		$ret["normal_check_interval"] != NULL ? $rq .= "'".$ret["normal_check_interval"]."', ": $rq .= "NULL, ";
		$rq .= "retry_check_interval = ";
		$ret["retry_check_interval"] != NULL ? $rq .= "'".$ret["retry_check_interval"]."', ": $rq .= "NULL, ";
		$rq .= "notification_interval = ";
		$ret["notification_interval"] != NULL ? $rq .= "'".$ret["notification_interval"]."', " : $rq .= "NULL, ";
		$rq .= "notification_period = ";
		$ret["notification_period"] != NULL ? $rq .= "'".$ret["notification_period"]."', " : $rq .= "NULL, ";
		$rq .= "notification_options = ";
		isset($ret["ms_notifOpts"]) && $ret["ms_notifOpts"] != NULL ? $rq .= "'".implode(",", array_keys($ret["ms_notifOpts"]))."', " : $rq .= "NULL, ";
		$rq .= "notifications_enabled = ";
		$ret["notifications_enabled"]["notifications_enabled"] != 2 ? $rq .= "'".$ret["notifications_enabled"]["notifications_enabled"]."', " : $rq .= "'2', ";
		$rq .= "calcul_type = ";
		$ret["calcul_type"] ? $rq .= "'".$ret["calcul_type"]."', " : $rq .= "NULL, ";
		$rq .= "meta_select_mode = ";
		$ret["meta_select_mode"]["meta_select_mode"] != NULL ? $rq .= "'".$ret["meta_select_mode"]["meta_select_mode"]."', " : $rq .= "NULL, ";
		$rq .= "regexp_str = ";
		$ret["regexp_str"] != NULL ? $rq .= "'".htmlentities($ret["regexp_str"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "metric = ";
		$ret["metric"] != NULL ? $rq .= "'".htmlentities($ret["metric"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "warning = ";
		$ret["warning"] != NULL ? $rq .= "'".htmlentities($ret["warning"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "critical = ";
		$ret["critical"] != NULL ? $rq .= "'".htmlentities($ret["critical"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "graph_id = ";
		$ret["graph_id"] != NULL ? $rq .= "'".$ret["graph_id"]."', " : $rq .= "NULL, ";
		$rq .= "meta_comment = ";
		$ret["meta_comment"] != NULL ? $rq .= "'".htmlentities($ret["meta_comment"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "meta_activate = ";
		$ret["meta_activate"]["meta_activate"] != NULL ? $rq .= "'".$ret["meta_activate"]["meta_activate"]."' " : $rq .= "NULL ";
		$rq .= " WHERE meta_id = '".$meta_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
		
	function updateMetaServiceContactGroup($meta_id = null)	{
		if (!$meta_id) return;
		global $form;
		global $pearDB;
		$rq = "DELETE FROM meta_contactgroup_relation ";
		$rq .= "WHERE meta_id = '".$meta_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$ret = array();
		$ret = $form->getSubmitValue("ms_cgs");
		for($i = 0; $i < count($ret); $i++)	{
			$rq = "INSERT INTO meta_contactgroup_relation ";
			$rq .= "(meta_id, cg_cg_id) ";
			$rq .= "VALUES ";
			$rq .= "('".$meta_id."', '".$ret[$i]."')";
			$DBRESULT =& $pearDB->query($rq);
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getMessage()."<br>";
		}
	}
	
	function updateMetricInDB ($msr_id = NULL)	{
		if (!$msr_id) return;
		updateMetric($msr_id);
	}	
	
	function insertMetricInDB ()	{
		$msr_id = insertMetric();
		updateMetricContactGroup($msr_id);
		return ($msr_id);
	}
	
	function insertMetric($ret = array())	{
		global $form;
		global $pearDB;
		global $oreon;
		$ret = $form->getSubmitValues();
		$rq = "INSERT INTO meta_service_relation " .
				"(meta_id, host_id, metric_id, msr_comment, activate) " .
				"VALUES ( ";
				isset($ret["meta_id"]) && $ret["meta_id"] != NULL ? $rq .= "'".$ret["meta_id"]."', ": $rq .= "NULL, ";
				isset($ret["host_id"]) && $ret["host_id"] != NULL ? $rq .= "'".$ret["host_id"]."', ": $rq .= "NULL, ";
				isset($ret["metric_sel"][1]) && $ret["metric_sel"][1] != NULL ? $rq .= "'".$ret["metric_sel"][1]."', ": $rq .= "NULL, ";
				isset($ret["msr_comment"]) && $ret["msr_comment"] != NULL ? $rq .= "'".htmlentities($ret["msr_comment"])."', " : $rq .= "NULL, ";
				isset($ret["activate"]["activate"]) && $ret["activate"]["activate"] != NULL ? $rq .= "'".$ret["activate"]["activate"]."'" : $rq .= "NULL";
				$rq .= ")";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$DBRESULT =& $pearDB->query("SELECT MAX(msr_id) FROM meta_service_relation");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
		$msr_id = $DBRESULT->fetchRow();
		return ($msr_id["MAX(msr_id)"]);
	}
	
	function updateMetric($msr_id = null)	{
		if (!$msr_id) return;
		global $form;
		global $pearDB;
		global $oreon;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "UPDATE meta_service_relation SET " ;
		$rq .= "meta_id = ";
		$ret["meta_id"] != NULL ? $rq .= "'".$ret["meta_id"]."', ": $rq .= "NULL, ";
		$rq .= "host_id = ";
		$ret["host_id"] != NULL ? $rq .= "'".$ret["host_id"]."', ": $rq .= "NULL, ";
		$rq .= "metric_id = ";
		$ret["metric_id"] != NULL ? $rq .= "'".$ret["metric_id"]."', ": $rq .= "NULL, ";
		$rq .= "msr_comment = ";
		$ret["msr_comment"] != NULL ? $rq .= "'".htmlentities($ret["msr_comment"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "activate = ";
		$ret["activate"]["activate"] != NULL ? $rq .= "'".$ret["activate"]["activate"]."' " : $rq .= "NULL ";
		$rq .= " WHERE msr_id = '".$msr_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getMessage()."<br>";
	}
?>
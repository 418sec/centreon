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

	if (!isset ($oreon))
		exit ();

	function testHostGroupExistence ($name = NULL)	{
		global $pearDB, $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('hg_id');
		$DBRESULT =& $pearDB->query("SELECT hg_name, hg_id FROM hostgroup WHERE hg_name = '".htmlentities($name, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$hg =& $DBRESULT->fetchRow();
		#Modif case
		if ($DBRESULT->numRows() >= 1 && $hg["hg_id"] == $id)	
			return true;
		#Duplicate entry
		else if ($DBRESULT->numRows() >= 1 && $hg["hg_id"] != $id)
			return false;
		else
			return true;
	}

	function enableHostGroupInDB ($hg_id = NULL, $hg_arr = array())	{
		if (!$hg_id && !count($hg_arr)) return;
		global $pearDB;
		if ($hg_id)
			$hg_arr = array($hg_id=>"1");
		foreach($hg_arr as $key=>$value)	{
			$DBRESULT =& $pearDB->query("UPDATE hostgroup SET hg_activate = '1' WHERE hg_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
	
	function disableHostGroupInDB ($hg_id = NULL, $hg_arr = array())	{
		if (!$hg_id && !count($hg_arr)) return;
		global $pearDB;
		if ($hg_id)
			$hg_arr = array($hg_id=>"1");
		foreach($hg_arr as $key=>$value)	{
			$DBRESULT =& $pearDB->query("UPDATE hostgroup SET hg_activate = '0' WHERE hg_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
	
	function deleteHostGroupInDB ($hostGroups = array())	{
		global $pearDB;
		foreach($hostGroups as $key=>$value)	{
			$rq = "SELECT @nbr := (SELECT COUNT( * ) FROM host_service_relation WHERE service_service_id = hsr.service_service_id GROUP BY service_service_id ) AS nbr, hsr.service_service_id FROM host_service_relation hsr WHERE hsr.hostgroup_hg_id = '".$key."'";
			$DBRESULT =& $pearDB->query($rq);
			while ($DBRESULT->fetchInto($row))
				if ($row["nbr"] == 1)	{
					$DBRESULT2 =& $pearDB->query("DELETE FROM service WHERE service_id = '".$row["service_service_id"]."'");
					if (PEAR::isError($DBRESULT2))
						print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
				}
			$DBRESULT =& $pearDB->query("DELETE FROM hostgroup WHERE hg_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
	
	function multipleHostGroupInDB ($hostGroups = array(), $nbrDup = array())	{
		global $pearDB, $oreon, $is_admin;
		foreach($hostGroups as $key=>$value)	{
			$DBRESULT =& $pearDB->query("SELECT * FROM hostgroup WHERE hg_id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$row = $DBRESULT->fetchRow();
			$row["hg_id"] = '';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = NULL;
				$rq = NULL;
				foreach ($row as $key2=>$value2)	{
					$key2 == "hg_name" ? ($hg_name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testHostGroupExistence($hg_name))	{
					$val ? $rq = "INSERT INTO hostgroup VALUES (".$val.")" : $rq = null;
					$DBRESULT =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
					$DBRESULT =& $pearDB->query("SELECT MAX(hg_id) FROM hostgroup");
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
					$maxId =& $DBRESULT->fetchRow();
					if (isset($maxId["MAX(hg_id)"]))	{
						if (!$is_admin){
							$group_list = getGroupListofUser($pearDB);
							$resource_list = getResourceACLList($group_list);
							if (count($resource_list)){
								foreach ($resource_list as $res_id)	{			
									$DBRESULT3 =& $pearDB->query("INSERT INTO `acl_resources_hg_relations` (acl_res_id, hg_hg_id) VALUES ('".$res_id."', '".$maxId["MAX(hg_id)"]."')");
									if (PEAR::isError($DBRESULT3))
										print "DB Error : ".$DBRESULT3->getDebugInfo()."<br />";
								}
								unset($resource_list);
							}
						}
						#
						$DBRESULT =& $pearDB->query("SELECT DISTINCT hgr.host_host_id FROM hostgroup_relation hgr WHERE hgr.hostgroup_hg_id = '".$key."'");
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
						while($DBRESULT->fetchInto($host)){
							$DBRESULT2 =& $pearDB->query("INSERT INTO hostgroup_relation VALUES ('', '".$maxId["MAX(hg_id)"]."', '".$host["host_host_id"]."')");
							if (PEAR::isError($DBRESULT2))
								print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
						}
						$DBRESULT =& $pearDB->query("SELECT DISTINCT cghgr.contactgroup_cg_id FROM contactgroup_hostgroup_relation cghgr WHERE cghgr.hostgroup_hg_id = '".$key."'");
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
						while($DBRESULT->fetchInto($cg)){
							$DBRESULT2 =& $pearDB->query("INSERT INTO contactgroup_hostgroup_relation VALUES ('', '".$cg["contactgroup_cg_id"]."', '".$maxId["MAX(hg_id)"]."')");
							if (PEAR::isError($DBRESULT2))
								print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
						}
					}
				}
			}
		}
	}
		
	function insertHostGroupInDB ($ret = array())	{
		$hg_id = insertHostGroup($ret);
		updateHostGroupHosts($hg_id, $ret);
		updateHostGroupContactGroups($hg_id, $ret);
		return $hg_id;
	}
	
	function updateHostGroupInDB ($hg_id = NULL)	{
		if (!$hg_id) return;
		updateHostGroup($hg_id);
		updateHostGroupHosts($hg_id);
		updateHostGroupContactGroups($hg_id);
	}
		
	function insertHostGroup($ret = array())	{
		global $form;
		global $pearDB;
		global $oreon, $is_admin;
		if (!count($ret))
		$ret = $form->getSubmitValues();
		$rq = "INSERT INTO hostgroup ";
		$rq .= "(hg_name, hg_alias, hg_snmp_community, hg_snmp_version, hg_comment, hg_activate) ";
		$rq .= "VALUES (";
		isset($ret["hg_name"]) && $ret["hg_name"] ? $rq .= "'".htmlentities($ret["hg_name"], ENT_QUOTES)."', " : $rq .= "NULL,";
		isset($ret["hg_alias"]) && $ret["hg_alias"] ? $rq .= "'".htmlentities($ret["hg_alias"], ENT_QUOTES)."', " : $rq .= "NULL,";
		isset($ret["hg_snmp_community"]) && $ret["hg_snmp_community"] ? $rq .= "'".htmlentities($ret["hg_snmp_community"], ENT_QUOTES)."', " : $rq .= "NULL,";
		isset($ret["hg_snmp_version"]) && $ret["hg_snmp_version"] ? $rq .= "'".htmlentities($ret["hg_snmp_version"], ENT_QUOTES)."', " : $rq .= "NULL,";
		isset($ret["hg_comment"]) && $ret["hg_comment"] ? $rq .= "'".htmlentities($ret["hg_comment"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		isset($ret["hg_activate"]["hg_activate"]) && $ret["hg_activate"]["hg_activate"] ? $rq .= "'".$ret["hg_activate"]["hg_activate"]."'" : $rq .= "'0'";
		$rq .= ")";
		$pearDB->query($rq);
		$DBRESULT =& $pearDB->query("SELECT MAX(hg_id) FROM hostgroup");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$hg_id = $DBRESULT->fetchRow();
		
		if (!$is_admin){
			$group_list = getGroupListofUser($pearDB);
			$resource_list = getResourceACLList($group_list);
			if (count($resource_list)){
				foreach ($resource_list as $res_id)	{			
					$DBRESULT3 =& $pearDB->query("INSERT INTO `acl_resources_hg_relations` (acl_res_id, hg_hg_id) VALUES ('".$res_id."', '".$hg_id["MAX(hg_id)"]."')");
					if (PEAR::isError($DBRESULT3))
						print "DB Error : ".$DBRESULT3->getDebugInfo()."<br />";
				}
				unset($resource_list);
			}
		}
		#
		return ($hg_id["MAX(hg_id)"]);
	}
	
	function updateHostGroup($hg_id)	{
		if (!$hg_id) return;
		global $form, $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "UPDATE hostgroup SET ";
		$rq .= "hg_name = ";
		isset($ret["hg_name"]) && $ret["hg_name"] != NULL ? $rq .= "'".htmlentities($ret["hg_name"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "hg_alias = ";
		isset($ret["hg_alias"]) && $ret["hg_alias"] != NULL ? $rq .= "'".htmlentities($ret["hg_alias"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "hg_snmp_community = ";
		isset($ret["hg_snmp_community"]) && $ret["hg_snmp_community"] != NULL ? $rq .= "'".htmlentities($ret["hg_snmp_community"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "hg_snmp_version = ";
		isset($ret["hg_snmp_version"]) && $ret["hg_snmp_version"] != NULL ? $rq .= "'".htmlentities($ret["hg_snmp_version"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "hg_comment = ";
		isset($ret["hg_comment"]) && $ret["hg_comment"] != NULL ? $rq .= "'".htmlentities($ret["hg_comment"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		$rq .= "hg_activate = ";
		isset($ret["hg_activate"]["hg_activate"]) && $ret["hg_activate"]["hg_activate"] != NULL ? $rq .= "'".$ret["hg_activate"]["hg_activate"]."'" : $rq .= "NULL ";
		$rq .= "WHERE hg_id = '".$hg_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	}
	
	function updateHostGroupHosts($hg_id, $ret = array())	{
		if (!$hg_id) return;
		global $form, $pearDB;
		# Special Case, delete relation between host/service, when service is linked to hostgroup in escalation, dependencies, osl
		# Get initial Host list to make a diff after deletion
		$rq = "SELECT host_host_id FROM hostgroup_relation ";
		$rq .= "WHERE hostgroup_hg_id = '".$hg_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$hostsOLD = array();
		while ($DBRESULT->fetchInto($host))
			$hostsOLD[$host["host_host_id"]] = $host["host_host_id"];
		# Get service lists linked to hostgroup
		$rq = "SELECT service_service_id FROM host_service_relation ";
		$rq .= "WHERE hostgroup_hg_id = '".$hg_id."' AND host_host_id IS NULL";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$hgSVS = array();
		while ($DBRESULT->fetchInto($sv))
			$hgSVS[$sv["service_service_id"]] = $sv["service_service_id"];
		#
		$rq = "DELETE FROM hostgroup_relation ";
		$rq .= "WHERE hostgroup_hg_id = '".$hg_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		isset($ret["hg_hosts"]) ? $ret = $ret["hg_hosts"] : $ret = $form->getSubmitValue("hg_hosts");
		$hostsNEW = array();
		for($i = 0; $i < count($ret); $i++)	{
			$rq = "INSERT INTO hostgroup_relation ";
			$rq .= "(hostgroup_hg_id, host_host_id) ";
			$rq .= "VALUES ";
			$rq .= "('".$hg_id."', '".$ret[$i]."')";
			$DBRESULT =& $pearDB->query($rq);
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$hostsNEW[$ret[$i]] = $ret[$i];
		}
		# Special Case, delete relation between host/service, when service is linked to hostgroup in escalation, dependencies, osl
		if (count($hgSVS))
			foreach ($hostsOLD as $host)
				if (!isset($hostsNEW[$host]))	{
					foreach ($hgSVS as $sv)	{
						# Delete in escalation
						$rq = "DELETE FROM escalation_service_relation ";
						$rq .= "WHERE host_host_id = '".$host."' AND service_service_id = '".$sv."'";
						$DBRESULT =& $pearDB->query($rq);
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";						
						# Delete in dependencies
						$rq = "DELETE FROM dependency_serviceChild_relation ";
						$rq .= "WHERE host_host_id = '".$host."' AND service_service_id = '".$sv."'";
						$DBRESULT =& $pearDB->query($rq);
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
						$rq = "DELETE FROM dependency_serviceParent_relation ";
						$rq .= "WHERE host_host_id = '".$host."' AND service_service_id = '".$sv."'";
						$DBRESULT =& $pearDB->query($rq);
						if (PEAR::isError($DBRESULT))
							print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
						# Delete in OSL
						$rq = "DELETE FROM osl_indicator ";
						$rq .= "WHERE host_id = '".$host."' AND service_id = '".$sv."'";
						$DBRESULT =& $pearDB->query($rq);
						//if (PEAR::isError($DBRESULT))
						//	print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";	
					}
				}
		#
	}
	
	function updateHostGroupContactGroups($hg_id, $ret = array())	{
		if (!$hg_id) return;
		global $form, $pearDB;
		$rq = "DELETE FROM contactgroup_hostgroup_relation ";
		$rq .= "WHERE hostgroup_hg_id = '".$hg_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		isset($ret["hg_cgs"]) ? $ret = $ret["hg_cgs"]: $ret = $form->getSubmitValue("hg_cgs");
		for($i = 0; $i < count($ret); $i++)	{
			$rq = "INSERT INTO contactgroup_hostgroup_relation ";
			$rq .= "(contactgroup_cg_id, hostgroup_hg_id) ";
			$rq .= "VALUES ";
			$rq .= "('".$ret[$i]."', '".$hg_id."')";
			$DBRESULT =& $pearDB->query($rq);
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		}
	}
?>
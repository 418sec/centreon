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
 * For information : contact@centreon.com
 */
	
	/*
	 * Set flag for updating ACL to true
	 *
	 * <code>
	 * updateACL();
	 * </code>
	 *
	 */
	
	function updateACL(){
		global $pearDB;

		$DBRESULT = $pearDB->query("UPDATE `acl_resources` SET `changed` = '1'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	}
	
	
	/*
	 * get Service group list in array
	 *
	 * <code>
	 * $aclSG = getLCASG($pearDB) 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$pearDB{TAB}pear DB connector
	 * @return{TAB}array{TAB}list SG
	 */
	
	function getLCASG($pearDB){
		if (!$pearDB)
			return ;
		
		/*
		 * Get Groups List
		 */	
		$groups = getGroupListofUser($pearDB);
		$str 	= groupsListStr($groups);
				
		$condition = "";
		if ($str != "")
			$condition = " WHERE `acl_group_id` IN (".$str.")";		

		$DBRESULT =& $pearDB->query("SELECT `acl_res_id` FROM `acl_res_group_relations` $condition");
		$lcaServiceGroup = array();
		while ($res =& $DBRESULT->fetchRow()){

			$DBRESULT2 =& $pearDB->query("SELECT `acl_resources_sg_relations`.`sg_id`, `sg_alias` FROM `servicegroup`, `acl_resources_sg_relations` WHERE `acl_res_id` = '".$res["acl_res_id"]."' AND `acl_resources_sg_relations`.`sg_id` = `servicegroup`.`sg_id`");	
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
			while ($serviceGroup =& $DBRESULT2->fetchRow())
				$lcaServiceGroup[$serviceGroup["sg_id"]] = $serviceGroup["sg_alias"];
			$DBRESULT2->free();
		
		}
		$DBRESULT->free();
		return $lcaServiceGroup;
	}
	
	
	/*
	 * Get list by name of host authorized by ACL  
	 *
	 * <code>
	 * $aclHostByName = getLCAHostByName($pearDB);
	 * </code>
	 *
	 * @param{TAB}int{TAB}$pearDB{TAB}Pear DB connector
	 * @return{TAB}array{TAB}List of hosts
	 */
	
	function getLCAHostByName($pearDB){
		if (!$pearDB)
			return ;
		/*
		 * Get Groups list
		 */	
		$groups = getGroupListofUser($pearDB);
		$str 	= groupsListStr($groups);
		
		$condition = "";
		if ($str != "")
			$condition = " WHERE acl_group_id IN (".$str.")";		
		$DBRESULT2 =& $pearDB->query("SELECT acl_res_id FROM acl_res_group_relations $condition");
		
		while ($res =& $DBRESULT2->fetchRow()){
  			/*
  			 * Hosts
  			 */
  			$host = array();
  			$DBRESULT3 =& $pearDB->query("SELECT host_name, host_id FROM `host`, `acl_resources_host_relations` WHERE acl_res_id = '".$res["acl_res_id"]."' AND acl_resources_host_relations.host_host_id = host.host_id");
	  		while ($host =& $DBRESULT3->fetchRow())
				if ($host["host_name"] != "")
					$lcaHost[$host["host_name"]] = $host["host_id"];
			unset($DBRESULT3);
			/*
			 * Hosts Groups Inclus
			 */
			$hostgroup =  array();
			$DBRESULT3 =& $pearDB->query(	"SELECT hg_id, hg_alias " .
											"FROM `hostgroup`, `acl_resources_hg_relations` " .
											"WHERE acl_res_id = '".$res["acl_res_id"]."' " .
											"AND acl_resources_hg_relations.hg_hg_id = hostgroup.hg_id");
	  		while ($hostgroup =& $DBRESULT3->fetchRow()){
	  			$DBRESULT4 =& $pearDB->query("SELECT host.host_id, host.host_name FROM `host`, `hostgroup_relation` WHERE host.host_id = hostgroup_relation.host_host_id AND hostgroup_relation.hostgroup_hg_id = '".$hostgroup["hg_id"]."'");
	  			while ($host_hostgroup =& $DBRESULT4->fetchRow())
					$lcaHost[$host_hostgroup["host_name"]] = $host_hostgroup["host_id"];
				$lcaHostGroup[$hostgroup["hg_alias"]] = $hostgroup["hg_id"];	
	  		}
			/*
			 * Hosts Exclus
			 */
			$host = array();
			$DBRESULT3 =& $pearDB->query("SELECT host_name FROM `host`, `acl_resources_hostex_relations` WHERE acl_res_id = '".$res["acl_res_id"]."' AND host.host_id = acl_resources_hostex_relations.host_host_id");
	  		if ($DBRESULT3->numRows())
		  		while ($host =& $DBRESULT3->fetchRow())
					if (isset($lcaHost[$host["host_name"]]))
						unset($lcaHost[$host["host_name"]]);
			unset($DBRESULT3);
  		}
  		if (isset($host) && isset($host["host_name"]))
			$lcaHost[$host["host_name"]] = $host["host_id"];
		unset($DBRESULT2);
  		$LcaHHG = array();
		isset($lcaHost) ? $LcaHHG["LcaHost"] = $lcaHost : $LcaHHG["LcaHost"] = array();
		isset($lcaHostGroup) ? $LcaHHG["LcaHostGroup"] = $lcaHostGroup : $LcaHHG["LcaHostGroup"] = array();
		return $LcaHHG;
	}
		
	/*
	 * Get Group list of an user
	 *
	 * <code>
	 * $grouplist = getGroupListofUser($pearDB)
	 * </code>
	 *
	 * @param{TAB}int{TAB}$pearDB{TAB}pear db connector
	 * @return{TAB}array{TAB}group list
	 */
	
	function getGroupListofUser($pearDB){
		if (!$pearDB)
			return ;
		/*
		 * Get session ID
		 */
		if (session_id() != "")
			$uid = session_id();
		if (isset($_GET["sid"])) 
			$uid = $_GET["sid"];
		else if (isset($_POST["sid"]))
			$uid = $_POST["sid"];
		else if (isset($_GET["uid"])) 
			$uid = $_GET["uid"];
		else if (isset($_POST["uid"]))
			$uid = $_POST["uid"]; 
		/*
		 * Get User
		 */			
		$DBRESULT =& $pearDB->query("SELECT user_id FROM session WHERE session_id = '".$uid."'");
		$user =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		/*
		 * Get Groups
		 */
		$groups = array();
		$DBRESULT =& $pearDB->query("SELECT acl_group_id FROM acl_group_contacts_relations WHERE acl_group_contacts_relations.contact_contact_id = '".$user["user_id"]."'");
  		if ($num = $DBRESULT->numRows()){
			while ($group =& $DBRESULT->fetchRow())
				$groups[$group["acl_group_id"]] = $group["acl_group_id"];
			$DBRESULT->free();
  		}
  		/*
  		 * Free
  		 */
  		unset($user);
  		unset($res1);
		return $groups;
	}

	/*
	 * return group list in str list separated by ","
	 *
	 * <code>
	 * $grouplist = getGroupListStrofUser($pearDB);
	 * </code>
	 *
	 * @param{TAB}int{TAB}$pearDB{TAB}pear db connector
	 * @return{TAB}str{TAB}group list
	 */
	

	function getGroupListStrofUser($pearDB){
		if (!$pearDB)
			return ;
		getGroupListStrofUser($pearDB);
		return groupsListStr($groups);
	}
	
	/*
	 * return a group list in array to a group list in str
	 *
	 * <code>
	 * $grouplistStr = groupsListStr($groups)
	 * </code>
	 *
	 * @param{TAB}array{TAB}$group{TAB}group list array
	 * @return{TAB}str{TAB}group list
	 */
	
	function groupsListStr($groups){
		$str = '';
		if (count($groups))
			foreach ($groups as $group_id){
				if ($str != "")
					$str .= ", ";
				$str .= $group_id;
			}
		else
			$str = "'-1'";
		return $str;	
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCAHostByID($pearDB){
		if (!$pearDB)
			return ;
		/*
		 * Get Groups list
		 */	
		$groups = getGroupListofUser($pearDB);
		$str 	= groupsListStr($groups);
		
		$str_topo = "";
		$condition = "";
		if ($str != "")
			$condition = " WHERE acl_group_id IN (".$str.")";		
		$DBRESULT2 =& $pearDB->query("SELECT acl_res_id FROM acl_res_group_relations $condition");
		
		while ($res =& $DBRESULT2->fetchRow()){
  			/*
  			 * Hosts
  			 */
  			$host = array();
  			$DBRESULT3 =& $pearDB->query("SELECT host_name, host_id FROM `host`, `acl_resources_host_relations` WHERE acl_res_id = '".$res["acl_res_id"]."' AND acl_resources_host_relations.host_host_id = host.host_id");
	  		while ($host =& $DBRESULT3->fetchRow())
				if ($host["host_id"] != "")
					$lcaHost[$host["host_id"]] = $host["host_id"];
			unset($DBRESULT3);
			/*
			 * Hosts Groups Inclus
			 */
			$hostgroup = array();
			$DBRESULT3 =& $pearDB->query(	"SELECT hg_id, hg_alias " .
											"FROM `hostgroup`, `acl_resources_hg_relations` " .
											"WHERE acl_res_id = '".$res["acl_res_id"]."' " .
											"AND acl_resources_hg_relations.hg_hg_id = hostgroup.hg_id");
	  		while ($hostgroup =& $DBRESULT3->fetchRow()){
	  			$DBRESULT4 =& $pearDB->query("SELECT host.host_id, host.host_name FROM `host`, `hostgroup_relation` WHERE host.host_id = hostgroup_relation.host_host_id AND hostgroup_relation.hostgroup_hg_id = '".$hostgroup["hg_id"]."'");
	  			while ($host_hostgroup =& $DBRESULT4->fetchRow())
					$lcaHost[$host_hostgroup["host_id"]] = $host_hostgroup["host_id"];
				$lcaHostGroup[$hostgroup["hg_id"]] = $hostgroup["hg_id"];	
	  		}
			/*
			 * Hosts Exclus
			 */
			$host = array();
			$DBRESULT3 =& $pearDB->query("SELECT host_id FROM `host`, `acl_resources_hostex_relations` WHERE acl_res_id = '".$res["acl_res_id"]."' AND host.host_id = acl_resources_hostex_relations.host_host_id");
	  		if ($DBRESULT3->numRows())
		  		while ($host =& $DBRESULT3->fetchRow())
					if (isset($lcaHost[$host["host_id"]]))
						unset($lcaHost[$host["host_id"]]);
			unset($DBRESULT3);
			/*
			 * Service group hosts
			 */
			$DBRESULT3 =& $pearDB->query(	"SELECT host_host_id FROM `acl_resources_sg_relations`,  `servicegroup_relation`  " .
											"WHERE acl_res_id = '".$res["acl_res_id"]."' " .
													"AND servicegroup_relation.servicegroup_sg_id = acl_resources_sg_relations.sg_id");
	  		if ($DBRESULT3->numRows())
		  		while ($host =& $DBRESULT3->fetchRow()){
					$lcaHost[$host["host_host_id"]] = $host["host_host_id"];
		  		}
			unset($DBRESULT3);

  		}
  		if (isset($host) && isset($host["host_name"]))
			$lcaHost[$host["host_name"]] = $host["host_id"];
		unset($DBRESULT2);
  		$LcaHHG = array();
		isset($lcaHost) ? $LcaHHG["LcaHost"] = $lcaHost : $LcaHHG["LcaHost"] = array();
		isset($lcaHostGroup) ? $LcaHHG["LcaHostGroup"] = $lcaHostGroup : $LcaHHG["LcaHostGroup"] = array();
		return $LcaHHG;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getAuthorizedCategories($groupstr){
		global $pearDB;
		
		if (strlen($groupstr) == 0)
			return array();
			
		$tab_categories = array();
		$DBRESULT =& $pearDB->query("SELECT sc_id " .
									"FROM acl_resources_sc_relations, acl_res_group_relations " .
									"WHERE acl_resources_sc_relations.acl_res_id = acl_res_group_relations.acl_res_id " .
									"AND acl_res_group_relations.acl_group_id IN (".$groupstr.")");
		while ($res =& $DBRESULT->fetchRow())
			$tab_categories[$res["sc_id"]] = $res["sc_id"];
	  	unset($res);
	  	unset($DBRESULT);
	  	return $tab_categories;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getServiceTemplateList2($service_id = NULL)	{
		if (!$service_id) 
			return;
		global $pearDB;
		/*
		 * Init Table of template
		 */
		$strTemplate = "'$service_id'";
		while (1)	{
			/*
			 * Get template Informations
			 */
			$DBRESULT =& $pearDB->query("SELECT service_template_model_stm_id FROM service WHERE service_id = '".$service_id."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$row =& $DBRESULT->fetchRow();
			if (isset($row["service_template_model_stm_id"]) && $row["service_template_model_stm_id"]){
				if ($strTemplate)
					$strTemplate .= ', ';
				$strTemplate .= "'".$row["service_template_model_stm_id"]."'";
				$service_id = $row["service_template_model_stm_id"];
			} else
				return $strTemplate;
		}
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getServicesCategories($str){
		global $pearDB;
		
		$tab = array();
		$DBRESULT =& $pearDB->query("SELECT sc_id FROM `service_categories_relation` WHERE service_service_id IN (".$str.")");
		while ($res =& $DBRESULT->fetchRow())
			$tab[$res["sc_id"]] = $res["sc_id"];
		unset($res);		
		unset($DBRESULT);
		return $tab;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCASGForHost($pearDB, $host_id = NULL, $groupstr = NULL){
		if (!$pearDB || !isset($host_id))
			return ;

		if ($groupstr == NULL){
			$groups = getGroupListofUser($pearDB);
			$groupstr = groupsListStr($groups);
		}		
		/*
		 * Init Table
		 */
		$svc = array();
		
		$str_topo = "";
		$condition = "";
		if ($groupstr != "")
			$condition = " WHERE acl_group_id IN (".$groupstr.")";		
		$DBRESULT =& $pearDB->query("SELECT acl_res_id FROM acl_res_group_relations $condition");
		while ($res =& $DBRESULT->fetchRow()){
			$DBRESULT2 =& $pearDB->query(	"SELECT service_service_id " .
											"FROM servicegroup, acl_resources_sg_relations, servicegroup_relation " .
											"WHERE acl_res_id = '".$res["acl_res_id"]."' " .
													"AND acl_resources_sg_relations.sg_id = servicegroup.sg_id " .
													"AND servicegroup_relation.servicegroup_sg_id = servicegroup.sg_id " .
													"AND servicegroup_relation.host_host_id = '".$host_id."'");	
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
			while ($service =& $DBRESULT2->fetchRow())
				$svc[getMyServiceName($service["service_service_id"])] = $service["service_service_id"];
			$DBRESULT2->free();
		}
		$DBRESULT->free();
		return $svc;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getAuthorizedServicesHost($host_id, $groupstr){
		global $pearDB;
		
		$tab_svc 	= getMyHostServicesByName($host_id);
		/*
		 * Get categories
		 */
		$tab_cat    = getAuthorizedCategories($groupstr);
		/*
		 * Get Service Groups
		 */
		$svc_SG 	= getLCASGForHost($pearDB, $host_id, $groupstr);
		
		$tab_services = array();
		if (count($tab_cat) || count($svc_SG)){
			if ($tab_svc)
				foreach ($tab_svc as $svc_descr => $svc_id){
					$tmp = getServiceTemplateList2($svc_id);
					$tab = getServicesCategories($tmp);
					foreach ($tab as $t){
						if (isset($tab_cat[$t]))
							$tab_services[$svc_descr] = $svc_id;
					}
				}
			if ($svc_SG)
				foreach ($svc_SG as $key => $value)
					$tab_services[$key] = $value;
		} else {
			$tab_services = $tab_svc;	
		}
	  	return $tab_services;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCASVC($lca = NULL){
		global $pearDB;
		
		if (!$lca)
			return array();
		
		$groups 	= getGroupListofUser($pearDB);
		$groupstr 	= groupsListStr($groups);
		
		foreach ($lca["LcaHost"] as $key => $value){
			$host = array();
			$host["id"] = $value;
			$host["svc"] = getAuthorizedServicesHost($value, $groupstr);
			$lca["LcaHost"][$key] =	$host;	
		}
		$SG = getLCASG($pearDB);
		
		$str = "";
		foreach ($SG as $key => $value){
			if (strlen($str))
				$str .= ", ";
			$str .= "'".$key."'";
		}
		if (strlen($str)){
			$DBRESULT =& $pearDB->query("SELECT host_host_id, service_service_id FROM servicegroup_relation WHERE servicegroup_sg_id IN ($str) ");
			if (PEAR::isError($DBRESULT))
					print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
				while ($service =& $DBRESULT->fetchRow()){
					//print "TEST : ".getMyHostName($service["host_host_id"]). " _> " .getMyServiceName($service["service_service_id"])."\n"; 
					if (isset($lca["LcaHost"][getMyHostName($service["host_host_id"])])){
						$lca["LcaHost"][getMyHostName($service["host_host_id"])]["svc"][getMyServiceName($service["service_service_id"])] = $service["service_service_id"];
					} else {
						$lca["LcaHost"][getMyHostName($service["host_host_id"])] = array();
						$lca["LcaHost"][getMyHostName($service["host_host_id"])]["id"] = $service["host_host_id"];
						$lca["LcaHost"][getMyHostName($service["host_host_id"])]["svc"][getMyServiceName($service["service_service_id"])] = $service["service_service_id"];
					}
				}
			}
		return $lca;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCASVCStr($lca = NULL){
		global $pearDB;
		
		if (!$lca)
			return array();
		
		$groups 	= getGroupListofUser($pearDB);
		$groupstr 	= groupsListStr($groups);
		$str = "";
		foreach ($lca["LcaHost"] as $key => $value){
			$host = array();
			$host["id"] = $value;
			$svc_list = getAuthorizedServicesHost($value, $groupstr);
			if (count($svc_list))
				foreach ($svc_list as $service_id){
					if ($str)
						$str .= ", ";
					$str .= $service_id;
				}			
		}
		return $str;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCAHostStr($lcaHost){
		$lcaHStr = "";
	  	foreach ($lcaHost as $key => $value){
	  		if ($lcaHStr)
	  			$lcaHStr .= ", ";
	  		$lcaHStr .= "'".$key."'";
	  	}
	  	if (!$lcaHStr) 
	  		$lcaHStr = '\'\'';
  	  	return $lcaHStr;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
		
	function getLCAHGStr($lcaHostGroup){
		$lcaHGStr = "";
		foreach ($lcaHostGroup as $key => $value){
	  		if ($lcaHGStr) 
	  			$lcaHGStr .= ", ";
	  		$lcaHGStr .= "'".$key."'";
		}
	  	if (!$lcaHGStr) 
	  		$lcaHGStr = '\'\'';
	  	return $lcaHGStr;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
		
	function getLCASGStr($lcaServiceGroup){
		$lcaSGStr = "";
	  	foreach ($lcaServiceGroup as $key => $value){
	  		if ($lcaSGStr) 
	  			$lcaSGStr .= ", ";
	  		$lcaSGStr .= "'".$key."'";
	  	}
	  	if (!$lcaSGStr) 
	  		$lcaSGStr = '\'\'';
		return $lcaSGStr;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getLCASGStrByName($lcaServiceGroup){
		$lcaSGStr = "";
	  	foreach ($lcaServiceGroup as $key => $value){
	  		if ($lcaSGStr) 
	  			$lcaSGStr .= ", ";
	  		$lcaSGStr .= "'".$value."'";
	  	}
	  	if (!$lcaSGStr) 
	  		$lcaSGStr = '\'\'';
		return $lcaSGStr;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function isUserAdmin($sid = NULL){
		if (!isset($sid))
			return ;
		global $pearDB;
		$DBRESULT =& $pearDB->query("SELECT contact_admin, contact_id FROM session, contact WHERE session.session_id = '".$sid."' AND contact.contact_id = session.user_id");
		$admin =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		
		$DBRESULT =& $pearDB->query("SELECT count(*) FROM `acl_group_contacts_relations` WHERE contact_contact_id = '".$admin["contact_id"]."'");
		$admin2 =& $DBRESULT->fetchRow();
		$DBRESULT->free();

		if ($admin["contact_admin"])
			return 1 ;
		else if (!$admin2["count(*)"])
			return 1;
		return 0;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getUserIdFromSID($sid = NULL){
		if (!isset($sid))
			return ;
		global $pearDB;
		$DBRESULT =& $pearDB->query("SELECT contact_id FROM session, contact WHERE session.session_id = '".$sid."' AND contact.contact_id = session.user_id");
		$admin =& $DBRESULT->fetchRow();
		unset($DBRESULT);
		if (isset($admin["contact_id"]))
			return $admin["contact_id"];
		return 0;
	}
	
	/*
	 * 
	 *
	 * <code>
	 * 
	 * </code>
	 *
	 * @param{TAB}int{TAB}$argument1{TAB}Mon premier argument
	 * @param{TAB}string{TAB}$argument2{TAB}Mon deuxi�me argument
	 * @return{TAB}int{TAB}Ma valeur de retour
	 */
	
	function getResourceACLList($group_list){
		if (!isset($group_list))
			return ;
		global $pearDB;
		$str = "";
		foreach ($group_list as $gl){
			if ($str)
				$str .= ", ";
			$str .= $gl; 
		}	
		$tab_res = array();
		$DBRESULT =& $pearDB->query("SELECT `acl_res_id` FROM `acl_res_group_relations` WHERE `acl_group_id` IN ($str)");
		while ($res =& $DBRESULT->fetchRow())
			$tab_res[$res["acl_res_id"]] = $res["acl_res_id"];
		$DBRESULT->free();
		unset($str);
		if (count($tab_res))
			return $tab_res;
		return array();
	}
?>
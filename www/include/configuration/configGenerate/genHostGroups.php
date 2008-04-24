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
 
	if (!isset($oreon))
		exit();
	
	if (!is_dir($nagiosCFGPath.$tab['id']."/")) {
		mkdir($nagiosCFGPath.$tab['id']."/");
	}
	
	/*
	 * Create table to liste generated hostGroups
	 */
	 
	$generatedHG = array();
	$handle = create_file($nagiosCFGPath.$tab['id']."/hostgroups.cfg", $oreon->user->get_name());
	$DBRESULT =& $pearDB->query("SELECT * FROM hostgroup ORDER BY `hg_name`");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	$hostGroup = array();
	$i = 1;
	$str = NULL;
	while ($DBRESULT->fetchInto($hostGroup))	{
		$BP = false;
		$strDef = NULL;
		$HGLinkedToHost = 0;
		array_key_exists($hostGroup["hg_id"], $gbArr[3]) ? $BP = true : NULL;
		
		/*
		 * Generate a new Hostgroup
		 */
		
		$ret["comment"] ? ($strDef .= "# '" . $hostGroup["hg_name"] . "' hostgroup definition " . $i . "\n") : NULL;
		if ($ret["comment"] && $hostGroup["hg_comment"])	{
			$comment = array();
			$comment = explode("\n", $hostGroup["hg_comment"]);
			foreach ($comment as $cmt)
				$strDef .= "# ".$cmt."\n";
		}
		$strDef .= "define hostgroup{\n";
		if ($hostGroup["hg_name"])	$strDef .= print_line("hostgroup_name", $hostGroup["hg_name"]);
		if ($hostGroup["hg_alias"]) $strDef .= print_line("alias", $hostGroup["hg_alias"]);
		
		/*
		 * Host Members
		 */
		
		$host = array();
		$strTemp = NULL;
		$DBRESULT2 =& $pearDB->query("SELECT host.host_id, host.host_name FROM hostgroup_relation hgr, host WHERE hgr.hostgroup_hg_id = '".$hostGroup["hg_id"]."' AND hgr.host_host_id = host.host_id ORDER BY `host_name`");
		if (PEAR::isError($DBRESULT2))
			print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
		while($DBRESULT2->fetchInto($host))	{
			$BP = false;
			array_key_exists($host["host_id"], $gbArr[2]) ? $BP = true : NULL;
			
			if ($BP && isHostOnThisInstance($host["host_id"], $tab['id'])){
				$HGLinkedToHost++;
				$strTemp != NULL ? $strTemp .= ", ".$host["host_name"] : $strTemp = $host["host_name"];
			}
		}
		$DBRESULT2->free();
		unset($host);
		if ($strTemp) $strDef .= print_line("members", $strTemp);
		unset($strTemp);
	
		/*
		 * Only for Nagios V1 : Contactgroups
		 */ 
	
		if ($oreon->user->get_version() == 1)	{
			$contactGroup = array();
			$strTemp = NULL;
			$DBRESULT2 =& $pearDB->query("SELECT cg.cg_name, cg.cg_id FROM contactgroup_hostgroup_relation cghgr, contactgroup cg WHERE cghgr.hostgroup_hg_id = '".$hostGroup["hg_id"]."' AND cghgr.contactgroup_cg_id = cg.cg_id ORDER BY `cg_name`");
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br />";
			while($DBRESULT2->fetchInto($contactGroup))	{
				$BP = false;
				array_key_exists($contactGroup["cg_id"], $gbArr[1]) ? $BP = true : NULL;
				
				if ($BP)
					$strTemp != NULL ? $strTemp .= ", ".$contactGroup["cg_name"] : $strTemp = $contactGroup["cg_name"];
			}
			$DBRESULT2->free();
			unset($contactGroup);
			if ($strTemp) {
				$strDef .= print_line("contact_groups", $strTemp);
				
			}
			unset($strTemp);
		}
		
		/*
		 * Generate only if this hostgroup had a host generate on this nagios instance
		 */
		 
		if ($HGLinkedToHost){
			$generatedHG[$hostGroup["hg_id"]] = $hostGroup["hg_name"];
			$str .= $strDef;
			$str .= "}\n\n";
		}
		$strDef = "";
		$HGLinkedToHost = 0;
		unset($strDef);
		$i++;
		unset($hostGroup);
	}
	write_in_file($handle, html_entity_decode($str, ENT_QUOTES), $nagiosCFGPath.$tab['id']."/hostgroups.cfg");
	fclose($handle);
	$DBRESULT->free();
	unset($str);
	unset($i);
	?>
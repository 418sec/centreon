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

	if (!is_dir($nagiosCFGPath.$tab['id']."/")) {
		mkdir($nagiosCFGPath.$tab['id']."/");
	}

	$handle = create_file($nagiosCFGPath.$tab['id']."/contactgroups.cfg", $oreon->user->get_name());
	$DBRESULT =& $pearDB->query("SELECT * FROM contactgroup ORDER BY `cg_name`");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
	$contactGroup = array();
	$i = 1;
	$str = NULL;
	while($DBRESULT->fetchInto($contactGroup))	{
		$BP = false;
		if ($ret["level"]["level"] == 1)
			array_key_exists($contactGroup["cg_id"], $gbArr[1]) ? $BP = true : NULL;
		else if ($ret["level"]["level"] == 2)
			array_key_exists($contactGroup["cg_id"], $gbArr[1]) ? $BP = true : NULL;
		else if ($ret["level"]["level"] == 3)
			$BP = true;
		if ($BP)	{
			$ret["comment"]["comment"] ? ($str .= "# '" . $contactGroup["cg_name"] . "' contactgroup definition " . $i . "\n") : NULL ;
			if ($ret["comment"]["comment"] && $contactGroup["cg_comment"])	{
				$comment = array();
				$comment = explode("\n", $contactGroup["cg_comment"]);
				foreach ($comment as $cmt)
					$str .= "# ".$cmt."\n";
			}
			$str .= "define contactgroup{\n";
			if ($contactGroup["cg_name"]) $str .= print_line("contactgroup_name", $contactGroup["cg_name"]);
			if ($contactGroup["cg_alias"]) $str .= print_line("alias", $contactGroup["cg_alias"]);
			$contact = array();
			$strTemp = NULL;
			$DBRESULT2 =& $pearDB->query("SELECT cct.contact_id, cct.contact_name FROM contactgroup_contact_relation ccr, contact cct WHERE ccr.contactgroup_cg_id = '".$contactGroup["cg_id"]."' AND ccr.contact_contact_id = cct.contact_id ORDER BY `contact_name`");
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
			while($DBRESULT2->fetchInto($contact))	{
				$BP = false;				
				if ($ret["level"]["level"] == 1)
					array_key_exists($contact["contact_id"], $gbArr[0]) ? $BP = true : $BP = false;
				else if ($ret["level"]["level"] == 2)
					array_key_exists($contact["contact_id"], $gbArr[0]) ? $BP = true : $BP = false;
				else if ($ret["level"]["level"] == 3)
					$BP = true;
				if ($BP)
					$strTemp != NULL ? $strTemp .= ", ".$contact["contact_name"] : $strTemp = $contact["contact_name"];
			}
			$DBRESULT2->free();
			$str .= print_line("members", $strTemp);
			unset($contact);
			unset($strTemp);
			$str .= "}\n\n";
			$i++;
		}
		unset($contactGroup);
	}
	write_in_file($handle, html_entity_decode($str, ENT_QUOTES), $nagiosCFGPath.$tab['id']."/contactgroups.cfg");
	fclose($handle);
	$DBRESULT->free();
	unset($str);
	unset($i);
?>
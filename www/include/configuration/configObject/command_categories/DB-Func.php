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

	function testCommandCategorieExistence ($name = NULL)	{
		global $pearDB, $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('cmd_category_id');
		$DBRESULT =& $pearDB->query("SELECT `category_name`, `cmd_category_id` FROM `command_categories` WHERE `category_name` = '".htmlentities($name, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$DBRESULT->fetchInto($cat);
		if ($DBRESULT->numRows() >= 1 && $cat["cmd_category_id"] == $id)
			return true;
		else if ($DBRESULT->numRows() >= 1 && $cat["cmd_category_id"] != $id)
			return false;
		else
			return true;
	}

	function multipleCommandCategorieInDB ($sc = array(), $nbrDup = array())	{
		foreach($sc as $key => $value)	{
			global $pearDB;
			$DBRESULT =& $pearDB->query("SELECT * FROM `command_categories` WHERE `cmd_category_id` = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
			$row = $DBRESULT->fetchRow();
			$row["cmd_category_id"] = '';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2 => $value2)	{
					$key2 == "category_name" ? ($sc_name = $value2 = $value2."_".$i) : null;
					$key2 == "category_alias" ? ($sc_alias = $value2 = $value2) : null;
					$val ? $val .= ($value2 != NULL?(", '".$value2."'"):", NULL") : $val .= ($value2 != NULL?("'".$value2."'"):"NULL");
				}
				if (testCommandCategorieExistence($sc_name))	{
					$val ? $rq = "INSERT INTO `command_categories` VALUES (".$val.")" : $rq = null;
					$DBRESULT =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
					$DBRESULT =& $pearDB->query("SELECT MAX(cmd_category_id) FROM `command_categories`");
					if (PEAR::isError($DBRESULT))
						print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
					$maxId =& $DBRESULT->fetchRow();
				}
			}
		}
	}
	
	function insertCommandCategorieInDB(){
		global $pearDB;
		if (testCommandCategorieExistence($_POST["category_name"])){
			$DBRESULT =& $pearDB->query("INSERT INTO `command_categories` (`category_name` , `category_alias`, `category_order`) VALUES ('".$_POST["category_name"]."', '".$_POST["category_alias"]."', '1')");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		}
	}
	
	function updateCommandCategorieInDB(){
		global $pearDB;
		$DBRESULT =& $pearDB->query("UPDATE `command_categories` SET `category_name` = '".$_POST["category_name"]."' , `category_alias` = '".$_POST["category_alias"]."' , `category_order` = '".$_POST["category_order"]."' WHERE `cmd_category_id` = '".$_POST["cmd_category_id"]."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
	}
	
	function deleteCommandCategorieInDB($sc_id = NULL){
		global $pearDB;
		$select = $_POST["select"];
		foreach ($select as $key => $value){
			$DBRESULT =& $pearDB->query("DELETE FROM `command_categories` WHERE `cmd_category_id` = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		}
	}

?>
<?php
/**
Oreon is developped with GPL Licence 2.0 :
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
		exit;

	function testExistence ($name = NULL)	{
		global $pearDB, $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('compo_id');
		$DBRESULT =& $pearDB->query("SELECT compo_id, name FROM giv_components_template WHERE name = '".htmlentities($name, ENT_QUOTES)."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		$compo =& $DBRESULT->fetchRow();
		#Modif case
		if ($DBRESULT->numRows() >= 1 && $compo["compo_id"] == $id)	
			return true;
		#Duplicate entry
		else if ($DBRESULT->numRows() >= 1 && $compo["compo_id"] != $id)
			return false;
		else
			return true;
	}
	
	function deleteComponentTemplateInDB ($compos = array())	{
		global $pearDB;
		foreach($compos as $key => $value){
			$DBRESULT =& $pearDB->query("DELETE FROM giv_components_template WHERE compo_id = '".$key."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo();
		}
		defaultOreonGraph();
	}	
	
	function defaultOreonGraph ()	{
		global $pearDB;
		$DBRESULT =& $pearDB->query("SELECT DISTINCT compo_id FROM giv_components_template WHERE default_tpl1 = '1'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		if (!$DBRESULT->numRows())	{
			$DBRESULT2 =& $pearDB->query("UPDATE giv_components_template SET default_tpl1 = '1' LIMIT 1");
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo();
		}
	}

	function noDefaultOreonGraph ()	{
		global $pearDB;
		$rq = "UPDATE giv_components_template SET default_tpl1 = '0'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
	}
	
	function multipleComponentTemplateInDB ($compos = array(), $nbrDup = array())	{
		global $pearDB;
		foreach($compos as $key=>$value) {
			$DBRESULT =& $pearDB->query("SELECT * FROM giv_components_template WHERE compo_id = '".$key."' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo();
			$DBRESULT->fetchInto($row);
			$row["compo_id"] = '';
			$row["default_tpl1"] = '0';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2=>$value2)	{
					$key2 == "name" ? ($name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testExistence($name))	{
					$val ? $rq = "INSERT INTO giv_components_template VALUES (".$val.")" : $rq = null;
					$DBRESULT2 =& $pearDB->query($rq);
					if (PEAR::isError($DBRESULT2))
						print "DB Error : ".$DBRESULT2->getDebugInfo();
				}
			}
		}
	}
	
	function updateComponentTemplateInDB ($compo_id = NULL)	{
		if (!$compo_id) return;
		updateComponentTemplate($compo_id);
		updateGraphParents($compo_id);
	}	
	
	function insertComponentTemplateInDB ()	{
		$compo_id = insertComponentTemplate();
		updateGraphParents($compo_id);
		return ($compo_id);
	}
	
	function insertComponentTemplate()	{
		global $form, $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		if ($ret["default_tpl1"]["default_tpl1"])
			noDefaultOreonGraph();
		$rq = "INSERT INTO `giv_components_template` ( `compo_id` , `name` , `ds_order` , `ds_name` , " .
				" `ds_color_line` , `ds_color_area` , `ds_filled` , `ds_max` , `ds_min` , `ds_average` , `ds_last` , `ds_tickness` , `ds_transparency`, `ds_invert`," .
				"`default_tpl1`, `comment` ) ";
		$rq .= "VALUES ( NULL, ";
		isset($ret["name"]) && $ret["name"] != NULL ? $rq .= "'".htmlentities($ret["name"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_order"]) && $ret["ds_order"] != NULL ? $rq .= "'".htmlentities($ret["ds_order"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_name"]) && $ret["ds_name"] != NULL ? $rq .= "'".$ret["ds_name"]."', ": $rq .= "NULL, ";
		isset($ret["ds_color_line"]) && $ret["ds_color_line"] != NULL ? $rq .= "'".htmlentities($ret["ds_color_line"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_color_area"]) && $ret["ds_color_area"] != NULL ? $rq .= "'".htmlentities($ret["ds_color_area"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_filled"]["ds_filled"]) && $ret["ds_filled"]["ds_filled"] != NULL ? $rq .= "'".htmlentities($ret["ds_filled"]["ds_filled"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_max"]["ds_max"]) && $ret["ds_max"]["ds_max"] != NULL ? $rq .= "'".htmlentities($ret["ds_max"]["ds_max"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_min"]["ds_min"]) && $ret["ds_min"]["ds_min"] != NULL ? $rq .= "'".htmlentities($ret["ds_min"]["ds_min"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_average"]["ds_average"]) && $ret["ds_average"]["ds_average"] != NULL ? $rq .= "'".htmlentities($ret["ds_average"]["ds_average"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_last"]["ds_last"]) && $ret["ds_last"]["ds_last"] != NULL ? $rq .= "'".htmlentities($ret["ds_last"]["ds_last"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_tickness"]) && $ret["ds_tickness"] != NULL ? $rq .= "'".htmlentities($ret["ds_tickness"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_transparency"]) && $ret["ds_transparency"] != NULL ? $rq .= "'".htmlentities($ret["ds_transparency"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		isset($ret["ds_invert"]) && $ret["ds_invert"] != NULL ? $rq .= "'".$ret["ds_invert"]["ds_invert"]."', ": $rq .= "NULL, ";
		isset($ret["default_tpl1"]["default_tpl1"]) && $ret["default_tpl1"]["default_tpl1"] != NULL ? $rq .= "'".$ret["default_tpl1"]["default_tpl1"]."', ": $rq .= "NULL, ";
		isset($ret["comment"]) && $ret["comment"] != NULL ? $rq .= "'".htmlentities($ret["comment"], ENT_QUOTES)."'": $rq .= "NULL";
		$rq .= ")";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		defaultOreonGraph();
		$DBRESULT =& $pearDB->query("SELECT MAX(compo_id) FROM giv_components_template");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		$compo_id = $DBRESULT->fetchRow();
		return ($compo_id["MAX(compo_id)"]);
	}
	
	function updateComponentTemplate($compo_id = null)	{
		if (!$compo_id) return;
		global $form, $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		if ($ret["default_tpl1"]["default_tpl1"])
			noDefaultOreonGraph();
		$rq = "UPDATE giv_components_template ";
		$rq .= "SET name = ";
		isset($ret["name"]) && $ret["name"] != NULL ? $rq .= "'".htmlentities($ret["name"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		$rq .= "`ds_order` = ";
		isset($ret["ds_order"]) && $ret["ds_order"] != NULL ? $rq .= "'".htmlentities($ret["ds_order"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		$rq .=	"ds_name = ";
		isset($ret["ds_name"]) && $ret["ds_name"] != NULL ? $rq .= "'".htmlentities($ret["ds_name"], ENT_QUOTES)."', ": $rq .= "NULL, ";
		$rq .= "ds_color_line = ";
		isset($ret["ds_color_line"]) && $ret["ds_color_line"] != NULL ? $rq .= "'".$ret["ds_color_line"]."', ": $rq .= "NULL, ";
		$rq .= "ds_color_area = ";
		isset($ret["ds_color_area"]) && $ret["ds_color_area"] != NULL ? $rq .= "'".$ret["ds_color_area"]."', ": $rq .= "NULL, ";
		$rq .= "ds_filled = ";
		isset($ret["ds_filled"]["ds_filled"]) && $ret["ds_filled"]["ds_filled"] != NULL ? $rq .= "'".$ret["ds_filled"]["ds_filled"]."', ": $rq .= "NULL, ";
		$rq .= "ds_max = ";
		isset($ret["ds_max"]["ds_max"]) && $ret["ds_max"]["ds_max"] != NULL ? $rq .= "'".$ret["ds_max"]["ds_max"]."', ": $rq .= "NULL, ";
		$rq .= "ds_min = ";
		isset($ret["ds_min"]["ds_min"]) && $ret["ds_min"]["ds_min"] != NULL ? $rq .= "'".$ret["ds_min"]["ds_min"]."', ": $rq .= "NULL, ";
		$rq .= "ds_average = ";
		isset($ret["ds_average"]["ds_average"]) && $ret["ds_average"]["ds_average"] != NULL ? $rq .= "'".$ret["ds_average"]["ds_average"]."', ": $rq .= "NULL, ";
		$rq .= "ds_last = ";
		isset($ret["ds_last"]["ds_last"]) && $ret["ds_last"]["ds_last"] != NULL ? $rq .= "'".$ret["ds_last"]["ds_last"]."', ": $rq .= "NULL, ";
		$rq .= 	"ds_tickness = ";
		isset($ret["ds_tickness"]) && $ret["ds_tickness"] != NULL ? $rq .= "'".$ret["ds_tickness"]."', ": $rq .= "NULL, ";
		$rq .= 	"ds_transparency = ";
		isset($ret["ds_transparency"]) && $ret["ds_transparency"] != NULL ? $rq .= "'".$ret["ds_transparency"]."', ": $rq .= "NULL, ";
		$rq .= 	"ds_invert = ";
		isset($ret["ds_invert"]) && $ret["ds_invert"] != NULL ? $rq .= "'".$ret["ds_invert"]["ds_invert"]."', ": $rq .= "NULL, ";
		$rq .= "default_tpl1 = ";
		isset($ret["default_tpl1"]["default_tpl1"]) && $ret["default_tpl1"]["default_tpl1"] != NULL ? $rq .= "'".$ret["default_tpl1"]["default_tpl1"]."', ": $rq .= "NULL, ";
		$rq .= "comment = ";
		isset($ret["comment"]) && $ret["comment"] != NULL ? $rq .= "'".htmlentities($ret["comment"], ENT_QUOTES)."' ": $rq .= "NULL ";
		$rq .= "WHERE compo_id = '".$compo_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		defaultOreonGraph();
	}		
	
	function updateGraphParents($compo_id = null)	{
		if (!$compo_id) return;
		global $form, $pearDB;
		$rq = "DELETE FROM giv_graphT_componentT_relation WHERE gc_compo_id = '".$compo_id."'";
		$DBRESULT =& $pearDB->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo();
		$ret = array();
		$ret = $form->getSubmitValue("compo_graphs");
		for($i = 0; $i < count($ret); $i++)	{
			$rq = "INSERT INTO giv_graphT_componentT_relation (gg_graph_id, gc_compo_id) VALUES ('".$ret[$i]."', '".$compo_id."')";
			$DBRESULT =& $pearDB->query($rq);
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo();
		}
	}
?>
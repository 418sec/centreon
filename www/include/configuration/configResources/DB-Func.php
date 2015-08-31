<?php
/*
 * Copyright 2015 Centreon (http://www.centreon.com/)
 * 
 * Centreon is a full-fledged industry-strength solution that meets 
 * the needs in IT infrastructure and application monitoring for 
 * service performance.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0  
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * For more information : contact@centreon.com
 * 
 */

	if (!isset($centreon))
		exit();

	function testExistence ($name = null, $instanceId = null) {
		global $pearDB, $form;

		$id = 0;
		$instances = array();
		if (isset($form)) {
			$id = $form->getSubmitValue('resource_id');
			$instances = $form->getSubmitValue('instance_id');
		} elseif (!is_null($instanceId) && $instanceId) {
                    $instances = array($instanceId);
                }
        $instances = array_filter($instances);
		if (!count($instances)) {
		    return true;
		}
		$DBRESULT = $pearDB->query("SELECT cr.resource_name, crir.resource_id, crir.instance_id
									FROM cfg_resource cr, cfg_resource_instance_relations crir
									WHERE cr.resource_id = crir.resource_id
									AND crir.instance_id IN (".implode(",", $instances).")
									AND cr.resource_name = '".$pearDB->escape($name)."'");
		$res = $DBRESULT->fetchRow();
		if ($DBRESULT->numRows() >= 1 && $res["resource_id"] == $id) {
			return true;
		} elseif ($DBRESULT->numRows() >= 1 && $res["resource_id"] != $id) {
			return false;
		} else {
			return true;
		}
	}

	function deleteResourceInDB ($DBRESULT = array())	{
		global $pearDB;
		foreach($DBRESULT as $key=>$value){
			$DBRESULT = $pearDB->query("DELETE FROM cfg_resource WHERE resource_id = '".$key."'");
		}
	}

	function enableResourceInDB ($resource_id = null)	{
		if (!$resource_id) exit();
		global $pearDB;
		$DBRESULT = $pearDB->query("UPDATE cfg_resource SET resource_activate = '1' WHERE resource_id = '".$resource_id."'");
	}

	function disableResourceInDB ($resource_id = null)	{
		if (!$resource_id) return;
		global $pearDB;
		$DBRESULT = $pearDB->query("UPDATE cfg_resource SET resource_activate = '0' WHERE resource_id = '".$resource_id."'");
	}

	function multipleResourceInDB ($DBRESULT = array(), $nbrDup = array())	{
		foreach($DBRESULT as $key=>$value)	{
			global $pearDB;
			$DBRESULT = $pearDB->query("SELECT * FROM cfg_resource WHERE resource_id = '".$key."' LIMIT 1");
			$row = $DBRESULT->fetchRow();
			$row["resource_id"] = '';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2=>$value2)	{
					$key2 == "resource_name" ? ($resource_name = $value2 = $value2."_".$i) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testExistence($resource_name))	{
					$DBRESULT = $pearDB->query($val ? $rq = "INSERT INTO cfg_resource VALUES (".$val.")" : $rq = null);
				}
			}
		}
	}

	function updateResourceInDB ($resource_id = NULL)	{
		if (!$resource_id) return;
		updateResource($resource_id);
		insertInstanceRelations($resource_id);
	}

	function updateResource($resource_id)	{
		if (!$resource_id) return;
		global $form, $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "UPDATE cfg_resource ";
		$rq .= "SET resource_name = '".$pearDB->escape($ret["resource_name"])."', " .
				"resource_line = '".$pearDB->escape($ret["resource_line"])."', " .
				"resource_comment= '".$pearDB->escape($ret["resource_comment"])."', " .
				"resource_activate= '".$ret["resource_activate"]["resource_activate"]."' " .
				"WHERE resource_id = '".$resource_id."'";
		$DBRESULT = $pearDB->query($rq);
	}

	function insertResourceInDB ()	{
		$resource_id = insertResource();
		insertInstanceRelations($resource_id);
		return ($resource_id);
	}

	function insertResource($ret = array())	{
		global $form, $pearDB;
		if (!count($ret))
			$ret = $form->getSubmitValues();
		$rq = "INSERT INTO cfg_resource ";
		$rq .= "(resource_name, resource_line, resource_comment, resource_activate) ";
		$rq .= "VALUES (";
		isset($ret["resource_name"]) && $ret["resource_name"] != NULL ? $rq .= "'".$pearDB->escape($ret["resource_name"])."', " : $rq .= "NULL, ";
		isset($ret["resource_line"]) && $ret["resource_line"] != NULL ? $rq .= "'".$pearDB->escape($ret["resource_line"])."', " : $rq .= "NULL, ";
		isset($ret["resource_comment"]) && $ret["resource_comment"] != NULL ? $rq .= "'".$pearDB->escape($ret["resource_comment"])."', " : $rq .= "NULL, ";
		isset($ret["resource_activate"]["resource_activate"]) && $ret["resource_activate"]["resource_activate"] != NULL ? $rq .= "'".$ret["resource_activate"]["resource_activate"]."'" : $rq .= "NULL";
		$rq .= ")";
		$DBRESULT = $pearDB->query($rq);
		$DBRESULT = $pearDB->query("SELECT MAX(resource_id) FROM cfg_resource");
		$resource_id = $DBRESULT->fetchRow();
		return ($resource_id["MAX(resource_id)"]);
	}

	function insertInstanceRelations($resourceId, $instanceId = null) {
        global $form, $pearDB;

        $pearDB->query("DELETE FROM cfg_resource_instance_relations WHERE resource_id = " . $pearDB->escape($resourceId));
        $query = "INSERT INTO cfg_resource_instance_relations (resource_id, instance_id) VALUES ";

        if (!is_null($instanceId)) {
            $instances = array($instanceId);
        } else {
            $instances = CentreonUtils::mergeWithInitialValues($form, 'instance_id');
        }
        $query2 = "";
        foreach ($instances as $instanceId) {
            if ($query2 != "") {
                $query2 .= ", ";
            }
            $query2 .= "(" . $pearDB->escape($resourceId) .", ".$pearDB->escape($instanceId).")";
        }
        if ($query2) {
            $pearDB->query($query . $query2);
        }
	}

    function getLinkedPollerList($resource_id) {
        global $pearDB;

        $str = "";
        $DBRESULT = $pearDB->query("SELECT ns.name, ns.id FROM cfg_resource_instance_relations nsr, cfg_resource r, nagios_server ns WHERE nsr.resource_id = r.resource_id AND nsr.instance_id = ns.id AND nsr.resource_id = '".$resource_id."'");
        while ($data = $DBRESULT->fetchRow()) {
            $str .= "<a href='main.php?p=60901&o=c&server_id=".$data["id"]."'>".$data["name"]."</a> ";
        }
        unset($DBRESULT);
        return $str;
    }
?>

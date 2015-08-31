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

	if (!isset($oreon))
 		exit();

	$str = NULL;
	$i = 1;
	$handle = create_file($nagiosCFGPath.$tab['id']."/meta_escalations.cfg", $oreon->user->get_name());

	$DBRESULT = $pearDB->query("SELECT DISTINCT meta_service_meta_id FROM escalation_meta_service_relation");
	while ($service = $DBRESULT->fetchRow()) {
		if (isset($gbArr[7][$service["meta_service_meta_id"]]))	{
			$DBRESULT2 = $pearDB->query("SELECT esc.* FROM escalation esc, escalation_meta_service_relation emsr WHERE emsr.meta_service_meta_id = '".$service["meta_service_meta_id"]."' AND esc.esc_id = emsr.escalation_esc_id ORDER BY esc.esc_name");
			$escalation = array();
			while ($escalation = $DBRESULT2->fetchRow())	{
				$ret["comment"]["comment"] ? ($str .= "# '".$escalation["esc_name"]."' service escalation definition ".$i."\n") : NULL;
				if ($ret["comment"]["comment"] && $escalation["esc_comment"])	{
					$comment = array();
					$comment = explode("\n", $escalation["esc_comment"]);
					foreach ($comment as $cmt)
						$str .= "# ".$cmt."\n";
				}
				$str .= "define serviceescalation{\n";
				$str .= print_line("host_name", "_Module_Meta");
				$str .= print_line("service_description", "meta_".$service["meta_service_meta_id"]);
				$cg = array();
				$strTemp = NULL;
				$DBRESULT3 = $pearDB->query("SELECT DISTINCT cg.cg_id, cg.cg_name FROM escalation_contactgroup_relation ecgr, contactgroup cg WHERE ecgr.escalation_esc_id = '".$escalation["esc_id"]."' AND ecgr.contactgroup_cg_id = cg.cg_id ORDER BY cg.cg_name");
				while($cg = $DBRESULT3->fetchRow()) {
					if (isset($gbArr[1][$cg["cg_id"]]))
						$strTemp != NULL ? $strTemp .= ", ".$cg["cg_name"] : $strTemp = $cg["cg_name"];
				}
				$DBRESULT3->free();
				if ($strTemp) 
					$str .= print_line("contact_groups", $strTemp);			
				if ($escalation["first_notification"] != NULL) 
					$str .= print_line("first_notification", $escalation["first_notification"]);
				if ($escalation["last_notification"] != NULL) 
					$str .= print_line("last_notification", $escalation["last_notification"]);
				if ($escalation["notification_interval"] != NULL) 
					$str .= print_line("notification_interval", $escalation["notification_interval"]);
				
				$DBRESULT4 = $pearDB->query("SELECT tp_name FROM timeperiod WHERE tp_id = '".$escalation["escalation_period"]."'");
				$tp = $DBRESULT4->fetchRow();
				$DBRESULT4->free();		
				if ($tp["tp_name"]) 
					$str .= print_line("escalation_period", $tp["tp_name"]);
				if ($escalation["escalation_options2"]) 
					$str .= print_line("escalation_options", $escalation["escalation_options2"]);
				$str .= "}\n\n";
				$i++;
			}
			unset($escalation);
			$DBRESULT2->free();
		}
	}
	unset($service);
	$DBRESULT->free();
	write_in_file($handle, html_entity_decode($str, ENT_QUOTES, 'UTF-8'), $nagiosCFGPath.$tab['id']."/meta_escalations.cfg");
	fclose($handle);
	unset($str);
?>

<?php
/*
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

	include_once "@CENTREON_ETC@/centreon.conf.php";
	include_once $centreon_path . "www/class/centreonXMLBGRequest.class.php";
	include_once $centreon_path . "www/class/centreonInstance.class.php";
        include_once $centreon_path . "www/class/centreonCriticality.class.php";
        include_once $centreon_path . "www/class/centreonMedia.class.php";
	include_once $centreon_path . "www/include/common/common-Func.php";

	/*
	 * Create XML Request Objects
	 */
	$obj = new CentreonXMLBGRequest($_GET["sid"], 1, 1, 0, 1);
	CentreonSession::start();

        $criticality = new CentreonCriticality($obj->DB);
	$instanceObj = new CentreonInstance($obj->DB);
        $media = new CentreonMedia($obj->DB);

	if (isset($obj->session_id) && CentreonSession::checkSession($obj->session_id, $obj->DB)) {
		;
	} else {
		print "Bad Session ID";
		exit();
	}

	/*
	 * Set Default Poller
	 */
	$obj->getDefaultFilters();

	/*
	 *  Check Arguments from GET
	 */
	$o 			= $obj->checkArgument("o", $_GET, "h");
	$p			= $obj->checkArgument("p", $_GET, "2");
	$num 		= $obj->checkArgument("num", $_GET, 0);
	$limit 		= $obj->checkArgument("limit", $_GET, 20);
	$instance 	= $obj->checkArgument("instance", $_GET, $obj->defaultPoller);
	$hostgroups = $obj->checkArgument("hostgroups", $_GET, $obj->defaultHostgroups);
	$search 	= $obj->checkArgument("search", $_GET, "");
	if ($o == "hpb" || $o == "h_unhandled") {
	    $sort_type 	= $obj->checkArgument("sort_type", $_GET, "");
	} else {
	    $sort_type 	= $obj->checkArgument("sort_type", $_GET, "criticality_id");
	}

	$order 		= $obj->checkArgument("order", $_GET, "ASC");
	$dateFormat = $obj->checkArgument("date_time_format_status", $_GET, "d/m/Y H:i:s");
        $criticality_id = $obj->checkArgument('criticality', $_GET, $obj->defaultCriticality);


	/*
	 * Backup poller selection
	 */
	$obj->setInstanceHistory($instance);
	$obj->setHostGroupsHistory($hostgroups);
    $obj->setCriticality($criticality_id);

	/*
	 * Get Host status
	 */
	$rq1 = 	" SELECT SQL_CALC_FOUND_ROWS DISTINCT nhs.current_state," .
			" nhs.problem_has_been_acknowledged, " .
			" nhs.passive_checks_enabled," .
			" nhs.active_checks_enabled," .
			" nhs.notifications_enabled," .
			" unix_timestamp(nhs.last_state_change) as last_state_change," .
			" unix_timestamp(nhs.last_hard_state_change) as last_hard_state_change," .
			" nhs.output," .
			" unix_timestamp(nhs.last_check) as last_check," .
			" nh.address," .
			" no.name1 as host_name," .
			" nh.action_url," .
			" nh.notes_url," .
			" nh.notes," .
			" nh.icon_image," .
			" nh.icon_image_alt," .
			" nhs.max_check_attempts," .
			" nhs.state_type," .
			" nhs.current_check_attempt, " .
			" nhs.scheduled_downtime_depth, " .
			" nh.host_object_id, " .
			" nhs.is_flapping, " .
	        " hph.host_parenthost_id as is_parent, ".
	        " i.instance_name, " .
                "cv.varvalue as criticality,".
                "cv.varvalue IS NULL as isnull ".
			" FROM ".$obj->ndoPrefix."hoststatus nhs, ".$obj->ndoPrefix."instances i ";
	if (!$obj->is_admin) {
		$rq1 .= ", centreon_acl ";
	}
	if ($hostgroups) {
		$rq1 .= ", ".$obj->ndoPrefix."hostgroup_members hm ";
	}

	$rq1 .= ", (" . $obj->ndoPrefix."hosts nh " ;
	$rq1 .= " LEFT JOIN " . $obj->ndoPrefix . "host_parenthosts hph ";
        $rq1 .= " ON hph.parent_host_object_id = nh.host_object_id) ";
        $rq1 .= ", (" .$obj->ndoPrefix."objects no  LEFT JOIN ".
                $obj->ndoPrefix."customvariablestatus cv ON no.object_id = cv.object_id AND cv.varname = 'CRITICALITY_LEVEL' )";

        if ($criticality_id) {
            $rq1 .= ", ".$obj->ndoPrefix . "customvariablestatus cvs ";
        }

	$rq1 .= " WHERE no.object_id = nhs.host_object_id AND nh.host_object_id = no.object_id " .
			" AND no.is_active = 1 AND no.objecttype_id = 1 " .
			" AND no.name1 NOT LIKE '_Module_%' " .
	        " AND i.instance_id = no.instance_id ";

        if ($criticality_id) {
            $rq1 .= " AND cvs.object_id = no.object_id
                      AND cvs.varname = 'CRITICALITY_ID'
                      AND cvs.varvalue = '".$obj->DBNdo->escape($criticality_id)."' ";
        }

	if (!$obj->is_admin) {
		$rq1 .= $obj->access->queryBuilder("AND", "no.name1", "centreon_acl.host_name") . $obj->access->queryBuilder("AND", "centreon_acl.group_id", $obj->grouplistStr);
	}
	if ($search != "") {
		$rq1 .= " AND (no.name1 LIKE '%" . $search . "%' OR nh.alias LIKE '%" . $search . "%' OR nh.address LIKE '%" . $search . "%') ";
	}

	if ($o == "hpb") {
		$rq1 .= " AND nhs.current_state != 0 ";
	} elseif ($o == "h_up") {
        $rq1 .= " AND nhs.current_state = 0 ";
	} elseif ($o == "h_down") {
        $rq1 .= " AND nhs.current_state = 1 ";
	} elseif ($o == "h_unreachable") {
        $rq1 .= " AND nhs.current_state = 2 ";
	}
    elseif ($o == "h_pending") {
        $rq1 .= " AND nhs.current_state = 4 ";
	}

	if (preg_match("/^h_unhandled/", $o)) {
	    if (preg_match("/^h_unhandled_(down|unreachable)\$/", $o, $matches)) {
	        if (isset($matches[1]) && $matches[1] == 'down') {
				$rq1 .= " AND nhs.current_state = 1 ";
			} elseif (isset($matches[1]) && $matches[1] == 'unreachable') {
                $rq1 .= " AND nhs.current_state = 2 ";
			} elseif (isset($matches[1]) && $matches[1] == 'pending') {
                $rq1 .= " AND nhs.current_state = 4 ";
			}
	    } else {
	        $rq1 .= " AND nhs.current_state != 0 ";
	    }
		$rq1 .= " AND nhs.state_type = '1'";
		$rq1 .= " AND nhs.problem_has_been_acknowledged = 0";
		$rq1 .= " AND nhs.scheduled_downtime_depth = 0";
	}
	if ($hostgroups) {
		$rq1 .= " AND nh.host_object_id = hm.host_object_id AND hm.hostgroup_id IN
				(SELECT hostgroup_id FROM ".$obj->ndoPrefix."hostgroups WHERE alias LIKE '".$hostgroups."') ";
	}

	if ($instance != -1) {
		$rq1 .= " AND no.instance_id = ".$instance;
	}
	$rq1 .= " GROUP BY host_name ";
	switch ($sort_type) {
		case 'host_name' :
                    $rq1 .= " ORDER BY no.name1 ". $order;
                    break;
		case 'current_state' :
                    $rq1 .= " ORDER BY nhs.current_state ". $order.",no.name1 ";
                    break;
		case 'last_state_change' :
                    $rq1 .= " ORDER BY nhs.last_state_change ". $order.",no.name1 ";
                    break;
		case 'last_hard_state_change' :
                    $rq1 .= " ORDER BY nhs.last_hard_state_change ". $order.",no.name1 ";
                    break;
		case 'last_check' :
                    $rq1 .= " ORDER BY nhs.last_check ". $order.",no.name1 ";
                    break;
		case 'current_check_attempt' :
                    $rq1 .= " ORDER BY nhs.current_check_attempt ". $order.",no.name1 ";
                    break;
		case 'ip' :
            # Not SQL portable
                    $rq1 .= " ORDER BY IFNULL(inet_aton(nh.address), nh.address) ". $order.",no.name1 ";
                    break;
		case 'plugin_output' :
                    $rq1 .= " ORDER BY nhs.output ". $order.",no.name1 ";
                    break;
                case 'criticality_id':
                    $rq1 .= " ORDER BY isnull $order, criticality $order, no.name1 ";
                    break;
		default :
                    $rq1 .= " ORDER BY isnull $order, criticality $order, no.name1 ";
                    break;
	}
	$rq1 .= " LIMIT ".($num * $limit).",".$limit;

	$ct = 0;
	$flag = 0;
	$DBRESULT = $obj->DBNdo->query($rq1);
	$numRows = $obj->DBNdo->numberRows();


        /**
         * Get criticality ids
         */
        $critRes = $obj->DBNdo->query("SELECT varvalue, object_id
                                    FROM ". $obj->ndoPrefix . "customvariablestatus
                                    WHERE varname = 'CRITICALITY_ID'");
        $criticalityUsed = 0;
        $critCache = array();
        if ($critRes->numRows()) {
            $criticalityUsed = 1;
            while ($critRow = $critRes->fetchRow()) {
                $critCache[$critRow['object_id']] = $critRow['varvalue'];
            }
        }

	$obj->XML->startElement("reponse");
	$obj->XML->startElement("i");
	$obj->XML->writeElement("numrows", $numRows);
	$obj->XML->writeElement("num", $num);
	$obj->XML->writeElement("limit", $limit);
	$obj->XML->writeElement("p", $p);
	$obj->XML->writeElement("o", $o);
	$obj->XML->writeElement("sort_type", $sort_type);
	$obj->XML->writeElement("hard_state_label", _("Hard State Duration"));
	$obj->XML->writeElement("parent_host_label", _("Top Priority Hosts"));
	$obj->XML->writeElement("regular_host_label", _("Secondary Priority Hosts"));
        $obj->XML->writeElement("use_criticality", $criticalityUsed);
	$obj->XML->endElement();

	$delimInit = 0;
	while ($ndo = $DBRESULT->fetchRow()) {

		if ($ndo["last_state_change"] > 0 && time() > $ndo["last_state_change"]) {
			$duration = CentreonDuration::toString(time() - $ndo["last_state_change"]);
		} else {
			$duration = "N/A";
		}

		if (($ndo["last_hard_state_change"] > 0) && ($ndo["last_hard_state_change"] >= $ndo["last_state_change"])) {
			$hard_duration = CentreonDuration::toString(time() - $ndo["last_hard_state_change"]);
		} else if ($ndo["last_hard_state_change"] > 0) {
			$hard_duration = " N/A ";
		} else {
			$hard_duration = "N/A";
		}

		if ($ndo['is_parent']) {
		    $delimInit = 1;
		}

	    $class = null;
        if ($ndo["scheduled_downtime_depth"] > 0) {
            $class = "line_downtime";
        } else if ($ndo["current_state"] == 1) {
            $ndo["problem_has_been_acknowledged"] == 1 ? $class = "line_ack" : $class = "list_down";
        } else {
            if ($ndo["problem_has_been_acknowledged"] == 1)
                $class = "line_ack";
        }

		$obj->XML->startElement("l");
	    $trClass = $obj->getNextLineClass();
        if (isset($class)) {
            $trClass = $class;
        }
		$obj->XML->writeAttribute("class", $trClass);
		$obj->XML->writeElement("o", 	$ct++);
		$obj->XML->writeElement("hc", 	$obj->colorHost[$ndo["current_state"]]);
		$obj->XML->writeElement("f", 	$flag);
		$obj->XML->writeElement("hid",	$ndo["host_object_id"]);
		$obj->XML->writeElement("hn",	$ndo['host_name'], false);
		$obj->XML->writeElement("hnl",	urlencode($ndo["host_name"]));
		$obj->XML->writeElement("a", 	($ndo["address"] ? $ndo["address"] : "N/A"));
		$obj->XML->writeElement("ou", 	($ndo["output"] ? $ndo["output"] : "N/A"));
		$obj->XML->writeElement("lc", 	($ndo["last_check"] != 0 ? $obj->GMT->getDate($dateFormat, $ndo["last_check"]) : "N/A"));
		$obj->XML->writeElement("cs", 	_($obj->statusHost[$ndo["current_state"]]), false);
		$obj->XML->writeElement("s", 	$ndo["current_state"]);
		$obj->XML->writeElement("pha", 	$ndo["problem_has_been_acknowledged"]);
        $obj->XML->writeElement("pce", 	$ndo["passive_checks_enabled"]);
        $obj->XML->writeElement("ace", 	$ndo["active_checks_enabled"]);
        $obj->XML->writeElement("lsc", 	($duration ? $duration : "N/A"));
        $obj->XML->writeElement("lhs", 	($hard_duration ? $hard_duration : "N/A"));
        $obj->XML->writeElement("ha", 	$ndo["problem_has_been_acknowledged"]);
        $obj->XML->writeElement("hdtm", $ndo["scheduled_downtime_depth"]);
        $obj->XML->writeElement("hdtmXml", "./include/monitoring/downtime/xml/ndo/makeXMLForDowntime.php?sid=".$obj->session_id."&hid=".$ndo["host_object_id"]);
        $obj->XML->writeElement("hdtmXsl", "./include/monitoring/downtime/xsl/popupForDowntime.xsl");
        $obj->XML->writeElement("hackXml", "./include/monitoring/acknowlegement/xml/ndo/makeXMLForAck.php?sid=".$obj->session_id."&hid=".$ndo["host_object_id"]);
        $obj->XML->writeElement("hackXsl", "./include/monitoring/acknowlegement/xsl/popupForAck.xsl");
        $obj->XML->writeElement("hae", 	$ndo["active_checks_enabled"]);
        $obj->XML->writeElement("hpe", 	$ndo["passive_checks_enabled"]);
        $obj->XML->writeElement("ne", 	$ndo["notifications_enabled"]);
        $obj->XML->writeElement("tr", 	$ndo["current_check_attempt"]."/".$ndo["max_check_attempts"]." (".$obj->stateType[$ndo["state_type"]].")");
        if ($ndo['criticality'] && isset($critCache[$ndo['host_object_id']])) {
            $obj->XML->writeElement("hci", 1); // has criticality
            $critData = $criticality->getData($critCache[$ndo['host_object_id']]);
            $obj->XML->writeElement("ci", $media->getFilename($critData['icon_id']));
            $obj->XML->writeElement("cih", $critData['name']);
        } else {
            $obj->XML->writeElement("hci", 0); // has no criticality
        }
        $obj->XML->writeElement("ico", 	$ndo["icon_image"]);
        $obj->XML->writeElement("isp", 	$ndo["is_parent"] ? 1 : 0);
        $obj->XML->writeElement("isf",  	$ndo["is_flapping"]);
        $parenth = 0;
        if ($ct === 1 && $ndo['is_parent']) {
            $parenth = 1;
        }
        if (!$sort_type && $delimInit && !$ndo['is_parent']) {
            $delim = 1;
            $delimInit = 0;
        } else {
            $delim = 0;
        }
        $obj->XML->writeElement("parenth", $parenth);
        $obj->XML->writeElement("delim", $delim);

        $hostObj = new CentreonHost($obj->DB);
		if ($ndo["notes"] != "") {
			$obj->XML->writeElement("hnn", $hostObj->replaceMacroInString($ndo["host_name"], str_replace("\$HOSTNAME\$", $ndo["host_name"], str_replace("\$HOSTADDRESS\$", $ndo["address"], $ndo["notes"]))));
		} else {
			$obj->XML->writeElement("hnn", "none");
		}

		if ($ndo["notes_url"] != "") {
			$str = $ndo['notes_url'];
			$str = str_replace("\$HOSTNAME\$", $ndo['host_name'], $str);
			$str = str_replace("\$HOSTADDRESS\$", $ndo['address'], $str);
			$str = str_replace("\$HOSTNOTES\$", $ndo['notes'], $str);
			$str = str_replace("\$INSTANCENAME\$", $ndo['instance_name'], $str);
			$str = str_replace("\$INSTANCEADDRESS\$", $instanceObj->getParam($ndo['instance_name'], 'ns_ip_address'), $str);
		    $obj->XML->writeElement("hnu", $hostObj->replaceMacroInString($ndo["host_name"], $str));
		} else {
			$obj->XML->writeElement("hnu", "none");
		}

	    if ($ndo["action_url"] != "") {
			$str = $ndo['action_url'];
			$str = str_replace("\$HOSTNAME\$", $ndo['host_name'], $str);
			$str = str_replace("\$HOSTADDRESS\$", $ndo['address'], $str);
			$str = str_replace("\$HOSTNOTES\$", $ndo['notes'], $str);
			$str = str_replace("\$INSTANCENAME\$", $ndo['instance_name'], $str);
			$str = str_replace("\$INSTANCEADDRESS\$", $instanceObj->getParam($ndo['instance_name'], 'ns_ip_address'), $str);
	        $obj->XML->writeElement("hau", $hostObj->replaceMacroInString($ndo["host_name"], $str));
		} else {
			$obj->XML->writeElement("hau", "none");
		}

		$obj->XML->endElement();
	}
	$DBRESULT->free();

	if (!$ct) {
		$obj->XML->writeElement("infos", "none");
	}
	$obj->XML->endElement();

	$obj->header();
	$obj->XML->output();
?>

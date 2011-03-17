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
	include_once $centreon_path . "www/include/common/common-Func.php";

	/*
	 * Create XML Request Objects
	 */
	$obj = new CentreonXMLBGRequest($_GET["sid"], 1, 1, 0, 1);
	CentreonSession::start();

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
	$sort_type 	= $obj->checkArgument("sort_type", $_GET, "name");
	$order 		= $obj->checkArgument("order", $_GET, "ASC");
	$dateFormat = $obj->checkArgument("date_time_format_status", $_GET, "d/m/Y H:i:s");

	/*
	 * Backup poller selection
	 */
	$obj->setInstanceHistory($instance);
	$obj->setHostGroupsHistory($hostgroups);

	/*
	 * Get Host status
	 */
	$rq1 = 	" SELECT SQL_CALC_FOUND_ROWS DISTINCT h.state," .
			" h.acknowledged, " .
			" h.passive_checks," .
			" h.active_checks," .
			" h.notify," .
			" h.last_state_change," .
			" h.last_hard_state_change," .
			" h.output," .
			" h.last_check, " .
			" h.address," .
			" h.name," .
			" h.action_url," .
			" h.notes_url," .
			" h.notes," .
			" h.icon_image," .
			" h.icon_image_alt," .
			" h.max_check_attempts," .
			" h.state_type," .
			" h.check_attempt, " .
			" h.scheduled_downtime_depth, " .
			" h.host_id " .
			" FROM `hosts` h";
	if (!$obj->is_admin) {
		$rq1 .= ", centreon_acl ";
	}
	if ($hostgroups) {
		$rq1 .= ", hosts_hostgroups hhg ";
	}
	$rq1 .= " WHERE h.name NOT LIKE '_Module_%'";

	if (!$obj->is_admin) {
		"AND h.host_id = centreon_acl.host_id " . $obj->access->queryBuilder("AND", "centreon_acl.group_id", $obj->grouplistStr);
	}
	if ($search != "") {
		$rq1 .= " AND (h.name LIKE '%" . $search . "%' OR h.alias LIKE '%" . $search . "%' OR h.address LIKE '%" . $search . "%') ";
	}
	if ($o == "hpb") {
		$rq1 .= " AND h.state != 0 ";
	} elseif ($o == "h_up") {
        $rq1 .= " AND h.state = 0 ";
	} elseif ($o == "h_down") {
        $rq1 .= " AND h.state = 1 ";
	} elseif ($o == "h_unreachable") {
        $rq1 .= " AND h.state = 2 ";
	}
	if (preg_match("/^h_unhandled/", $o)) {
	    if (preg_match("/^h_unhandled_(down|unreachable)\$/", $o, $matches)) {
	        if (isset($matches[1]) && $matches[1] == 'down') {
				$rq1 .= " AND h.state = 1 ";
			} elseif (isset($matches[1]) && $matches[1] == 'unreachable') {
                $rq1 .= " AND h.state = 2 ";
			}
	    } else {
	        $rq1 .= " AND h.state != 0 ";
	    }
        $rq1 .= " AND h.state_type = '1'";
        $rq1 .= " AND h.acknowledged = 0";
        $rq1 .= " AND h.scheduled_downtime_depth = 0";
	}

	if ($hostgroups) {
		$rq1 .= " AND h.host_id = hhg.host_id AND hostgroup_id = '$hostgroups' ";
	}

	if ($instance != -1) {
		$rq1 .= " AND h.instance_id = ".$instance;
	}
	switch ($sort_type) {
		case 'name' :
			$rq1 .= " ORDER BY h.name ". $order;
			break;
		case 'current_state' :
			$rq1 .= " ORDER BY h.state ". $order.",h.name ";
			break;
		case 'last_state_change' :
			$rq1 .= " ORDER BY h.last_state_change ". $order.",h.name ";
			break;
		case 'last_hard_state_change' :
			$rq1 .= " ORDER BY h.last_hard_state_change ". $order.",h.name ";
			break;
		case 'last_check' :
			$rq1 .= " ORDER BY h.last_check ". $order.",h.name ";
			break;
		case 'current_check_attempt' :
			$rq1 .= " ORDER BY h.check_attempt ". $order.",h.name ";
			break;
		case 'ip' :
			$rq1 .= " ORDER BY inet_aton(h.address) ". $order.",h.name ";
			break;
		case 'plugin_output' :
			$rq1 .= " ORDER BY h.output ". $order.",h.name ";
			break;
		default :
			$rq1 .= " ORDER BY h.name ";
			break;
	}
	$rq1 .= " LIMIT ".($num * $limit).",".$limit;

	$ct = 0;
	$flag = 0;
	$DBRESULT = $obj->DBC->query($rq1);
	$numRows = $obj->DBC->numberRows();

	$obj->XML->startElement("reponse");
	$obj->XML->startElement("i");
	$obj->XML->writeElement("numrows", $numRows);
	$obj->XML->writeElement("num", $num);
	$obj->XML->writeElement("limit", $limit);
	$obj->XML->writeElement("p", $p);
	$obj->XML->writeElement("o", $o);
	$obj->XML->writeElement("hard_state_label", _("Hard State Duration"));
	$obj->XML->endElement();

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

		$obj->XML->startElement("l");
		$obj->XML->writeAttribute("class", $obj->getNextLineClass());
		$obj->XML->writeElement("o", 	$ct++);
		$obj->XML->writeElement("hc", 	$obj->colorHost[$ndo["state"]]);
		$obj->XML->writeElement("f", 	$flag);
		$obj->XML->writeElement("hid",	$ndo["host_id"]);
		$obj->XML->writeElement("hn",	$ndo["name"], false);
		$obj->XML->writeElement("hnl",	urlencode($ndo["name"]));
		$obj->XML->writeElement("a", 	($ndo["address"] ? $ndo["address"] : "N/A"));
		$obj->XML->writeElement("ou", 	($ndo["output"] ? $ndo["output"] : "N/A"));
		$obj->XML->writeElement("lc", 	($ndo["last_check"] != 0 ? $obj->GMT->getDate($dateFormat, $ndo["last_check"]) : "N/A"));
		$obj->XML->writeElement("cs", 	_($obj->statusHost[$ndo["state"]]), false);
		$obj->XML->writeElement("pha", 	$ndo["acknowledged"]);
        $obj->XML->writeElement("pce", 	$ndo["passive_checks"]);
        $obj->XML->writeElement("ace", 	$ndo["active_checks"]);
        $obj->XML->writeElement("lsc", 	($duration ? $duration : "N/A"));
        $obj->XML->writeElement("lhs", 	($hard_duration ? $hard_duration : "N/A"));
        $obj->XML->writeElement("ha", 	$ndo["acknowledged"]);
        $obj->XML->writeElement("hdtm", $ndo["scheduled_downtime_depth"]);
        $obj->XML->writeElement("hae", 	$ndo["active_checks"]);
        $obj->XML->writeElement("hpe", 	$ndo["passive_checks"]);
        $obj->XML->writeElement("ne", 	$ndo["notify"]);
        $obj->XML->writeElement("tr", 	$ndo["check_attempt"]."/".$ndo["max_check_attempts"]." (".$obj->stateType[$ndo["state_type"]].")");
        $obj->XML->writeElement("ico", 	$ndo["icon_image"]);

        $hostObj = new CentreonHost($obj->DB);
		if ($ndo["notes"] != "") {
			$obj->XML->writeElement("hnn", $hostObj->replaceMacroInString($ndo["name"], str_replace("\$HOSTNAME\$", $ndo["name"], str_replace("\$HOSTADDRESS\$", $ndo["address"], $ndo["notes"]))));
		} else {
			$obj->XML->writeElement("hnn", "none");
		}

		if ($ndo["notes_url"] != "") {
			$obj->XML->writeElement("hnu", $hostObj->replaceMacroInString($ndo["name"], str_replace("\$HOSTNAME\$", $ndo["name"], str_replace("\$HOSTADDRESS\$", $ndo["address"], str_replace("\$HOSTNOTES\$", $ndo["notes"], $ndo["notes_url"])))));
		} else {
			$obj->XML->writeElement("hnu", "none");
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
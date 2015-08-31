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

	/*
	 * if debug == 0 => Normal,
	 * debug == 1 => get use,
	 * debug == 2 => log in file (log.xml)
	 */
	$debugXML = 0;
	$buffer = '';

	require_once "@CENTREON_ETC@/centreon.conf.php";

	include_once($centreon_path."www/class/centreonDuration.class.php");
	include_once($centreon_path."www/class/centreonACL.class.php");
	include_once($centreon_path."www/class/centreonXML.class.php");
	include_once($centreon_path."www/class/centreonDB.class.php");
	include_once($centreon_path."www/class/centreonGMT.class.php");
	include_once $centreon_path."www/include/monitoring/status/Common/common-Func.php";
	include_once($centreon_path."www/include/common/common-Func.php");

	$pearDB 	= new CentreonDB();
	$pearDBO 	= new CentreonDB("centstorage");

	$ndo_base_prefix = getNDOPrefix();
	$general_opt = getStatusColor($pearDB);

	/*
	 * security check 2/2
	 */

	if (isset($_GET["sid"])) {
		$sid = $_GET["sid"];
		$sid = htmlentities($sid, ENT_QUOTES, "UTF-8");
		$res = $pearDB->query("SELECT * FROM session WHERE session_id = '".$sid."'");
		if (!$session = $res->fetchRow())
			get_error('bad session id');
	} else
		get_error('need session identifiant !');

	/*
	 * Get Acl Group list
	 */
	$is_admin = isUserAdmin($sid);
	$user_id = getUserIdFromSID($sid);
	$access = new CentreonACL($user_id, $is_admin);

	(isset($_GET["num"])) ? $num = htmlentities($_GET["num"]) : get_error('num unknown');
	(isset($_GET["limit"])) ? $limit = htmlentities($_GET["limit"]) : get_error('limit unknown');
	(isset($_GET["instance"])) ? $instance = htmlentities($_GET["instance"]) : $instance = "ALL";
	(isset($_GET["search"])) ? $search = CentreonDB::escape($_GET["search"]) : $search = "";
	(isset($_GET["sort_type"])) ? $sort_type = htmlentities($_GET["sort_type"]) : $sort_type = "service_description";
	(isset($_GET["order"])) ? $order = htmlentities($_GET["order"]) : $oreder = "ASC";
	(isset($_GET["date_time_format_status"])) ? $date_time_format_status = htmlentities($_GET["date_time_format_status"]) : $date_time_format_status = "d/m/Y H:i:s";
	(isset($_GET["o"])) ? $o = htmlentities($_GET["o"]) : $o = "h";
	(isset($_GET["p"])) ? $p = htmlentities($_GET["p"]) : $p = "2";
	(isset($_GET["nc"])) ? $nc = htmlentities($_GET["nc"]) : $nc = "0";

	if (!$is_admin)
		$_POST["sid"] = $sid;

	$service = array();
	$host_status = array();
	$service_status = array();
	$host_services = array();
	$metaService_status = array();
	$tab_host_service = array();

	$tab_color_service = array(0 => $general_opt["color_ok"], 1 => $general_opt["color_warning"], 2 => $general_opt["color_critical"], 3 => $general_opt["color_unknown"], 4 => $general_opt["color_pending"]);
	$tab_color_host = array(0 => "normal", 1 => "#FD8B46", /* $general_opt["color_down"];*/ 2 => "normal");

	$tab_status_svc = array("0" => "OK", "1" => "WARNING", "2" => "CRITICAL", "3" => "UNKNOWN", "4" => "PENDING");
	$tab_status_host = array("0" => "UP", "1" => "DOWN", "2" => "UNREACHABLE");

	/* Get Service status */
	$rq =		" SELECT " .
				" DISTINCT h.name as host_name," .
				" s.process_perfdata," .
				" s.state," .
				" s.output as plugin_output," .
				" s.check_attempt," .
				" s.last_update," .
				" s.last_state_change," .
				" s.last_check," .
				" s.next_check," .
				" s.notify," .
				" s.acknowledged," .
				" s.passive_checks," .
				" s.active_checks," .
				" s.event_handler," .
				" s.flapping," .
				" s.flap_detection," .
				" s.service_id," .
				" s.description as service_description," .
				" s.display_name," .
				" s.host_id " .
				" FROM services s, hosts h";
	$rq .= 	" WHERE s.host_id = h.host_id ".
		" AND h.name LIKE '_Module_Meta' ".
                " AND s.enabled = 1 ";

	if (!$is_admin) {
		$ACLString = "";
		foreach ($access->getMetaServices() as $key => $empty) {
			if ($ACLString != "") {
				$ACLString .= ",";
			}
			$ACLString .= "'meta_".$key."'";
		}
		if ($ACLString == "") {
			$ACLString = "''";
		}
		$rq .= " AND s.description IN (".$ACLString.")";
	}
    if ($search){
		$rq .= " AND s.display_name LIKE '%" . $search . "%' ";
	}

	$rq_pagination = $rq;

	switch ($sort_type){
		case 'service_description' : $rq .= " ORDER BY s.display_name ". $order; break;
		case 'current_state' : $rq .= " ORDER BY s.state ". $order.", s.display_name "; break;
		case 'last_state_change' : $rq .= " ORDER BY s.last_state_change ". $order.", s.display_name "; break;
		case 'last_check' : $rq .= " ORDER BY s.last_check ". $order.",s.display_name "; break;
		case 'current_attempt' : $rq .= " ORDER BY s.check_attempt ". $order.", s.display_name "; break;
		default : $rq .= " ORDER BY s.display_name ". $order; break;
	}

	$rq .= " LIMIT ".($num * $limit).",".$limit;

	$ct = 0;
	$flag = 0;

	$DBRESULT_NDO2 = $pearDBO->query($rq_pagination);

	/*
	 * Get Pagination Rows
	 */

	$numRows = $DBRESULT_NDO2->numRows();

	/*
	 * Create Buffer
	 */
	$buffer = new CentreonXML();
	$buffer->startElement("reponse");
	$buffer->startElement("i");
	$buffer->writeElement("numrows", $numRows);
	$buffer->writeElement("num", $num);
	$buffer->writeElement("limit", $limit);
	$buffer->writeElement("p", $p);
	$buffer->writeElement("nc", $nc);
	$buffer->writeElement("o", $o);
	$buffer->endElement();

	$host_prev = "";
	$class = "list_one";

	$DBRESULT_NDO2 = $pearDBO->query($rq);

	while ($ndo = $DBRESULT_NDO2->fetchRow()) {

		$color_service = $tab_color_service[$ndo["state"]];
		$passive = 0;
		$active = 1;
		$last_check = " ";
		$duration = " ";

		if ($ndo["last_state_change"] > 0 && time() > $ndo["last_state_change"]) {
			$duration = CentreonDuration::toString(time() - $ndo["last_state_change"]);
		} elseif ($ndo["last_state_change"] > 0) {
			$duration = " - ";
		}

		$class == "list_one" ? $class = "list_two" : $class = "list_one";

		if ($tab_status_svc[$ndo["state"]] == "CRITICAL"){
			$ndo["acknowledged"] == 1 ? $class = "list_four" : $class = "list_down";
		} else {
			if ($ndo["acknowledged"] == 1)
				$class = "list_four";
		}

		$tabID = preg_split("/\_/", $ndo["service_description"]);
		$id = $tabID[1];

		$DBRESULT = $pearDB->query("SELECT `meta_name` FROM  `meta_service` WHERE `meta_id` = '$id'");
		$dataMeta = $DBRESULT->fetchRow();
		$DBRESULT->free();
        
        /**
         * Preparing CentreonGMT class for transforming dates
         */
        $gmtManager = new CentreonGMT($pearDB);
        $gmtManager->getMyGMTFromSession($sid, $pearDB);

		$buffer->startElement("l");
		$buffer->writeAttribute("class", $class);
		$buffer->writeElement("o", $ct++);
		$buffer->writeElement("f", $flag);
		$buffer->writeElement("ppd", $ndo["process_perfdata"]);
		$buffer->writeElement("sd", $ndo['display_name']);
		$buffer->writeElement("hn", $ndo['host_name']);
		$buffer->writeElement("rsd", $ndo['service_description']);
		$buffer->writeElement("svc_id", $ndo["service_id"]);
        $buffer->writeElement("hid", $ndo["host_id"]);
		$buffer->writeElement("svc_index", getMyIndexGraph4Service($ndo["host_name"], $ndo["service_description"], $pearDBO));
		$buffer->writeElement("sc", $color_service);
		$buffer->writeElement("cs", _($tab_status_svc[$ndo["state"]]));
		$buffer->writeElement("po", $ndo["plugin_output"]);
		$buffer->writeElement("ca", $ndo["check_attempt"]);
		$buffer->writeElement("ne", $ndo["notify"]);
		$buffer->writeElement("pa", $ndo["acknowledged"]);
		$buffer->writeElement("pc", $ndo["passive_checks"]);
		$buffer->writeElement("ac", $ndo["active_checks"]);
		$buffer->writeElement("eh", $ndo["event_handler"]);
		$buffer->writeElement("is", $ndo["flapping"]);
		$buffer->writeElement("fd", $ndo["flap_detection"]);
		$buffer->writeElement("ha", $ndo["acknowledged"]);
		$buffer->writeElement("hae", $ndo["active_checks"]);
        $buffer->writeElement("hpe", $ndo["passive_checks"]);
        $buffer->writeElement("nc", $gmtManager->getDate($date_time_format_status, $ndo["next_check"]));
        $buffer->writeElement("lc", $gmtManager->getDate($date_time_format_status, $ndo["last_check"]));
		$buffer->writeElement("d", $duration);
		$buffer->endElement();
	}

	if (!$ct) {
		$buffer->writeElement("infos", "none");
	}

	$buffer->writeElement("sid", $sid);
	$buffer->endElement();
	header('Content-Type: text/xml');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Cache-Control: no-cache, must-revalidate');
	$buffer->output();
?>
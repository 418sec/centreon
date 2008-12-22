<?php
/*
 * Copyright 2005-2009 MERETHIS
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
 * SVN : $URL
 * SVN : $Id: 
 * 
 */

	# if debug == 0 => Normal, debug == 1 => get use, debug == 2 => log in file (log.xml)
	$debugXML = 0;
	$buffer = '';

	include("DB.php");

	include_once("@CENTREON_ETC@/centreon.conf.php");
	include_once($centreon_path."www/class/other.class.php");
	include_once($centreon_path."www/DBconnect.php");
	include_once($centreon_path."www/DBOdsConnect.php");
	include_once($centreon_path."www/DBNDOConnect.php");	
	include_once $centreon_path."www/include/monitoring/status/Common/common-Func.php";
	include_once($centreon_path."www/include/common/common-Func-ACL.php");
	include_once($centreon_path."www/include/common/common-Func.php");

	$ndo_base_prefix = getNDOPrefix();
	$general_opt = getStatusColor($pearDB);
	
	/* 
	 * security check 2/2 
	 */
	 
	if (isset($_GET["sid"]) && !check_injection($_GET["sid"])){
		$sid = $_GET["sid"];
		$sid = htmlentities($sid);
		$res =& $pearDB->query("SELECT * FROM session WHERE session_id = '".$sid."'");
		if (!$session =& $res->fetchRow())
			get_error('bad session id');
	} else
		get_error('need session identifiant !');

	/*
	 * Get Acl Group list
	 */
	
	$grouplist = getGroupListofUser($pearDB); 
	$groupnumber = count($grouplist);	

	(isset($_GET["num"]) 		&& !check_injection($_GET["num"])) ? $num = htmlentities($_GET["num"]) : get_error('num unknown');
	(isset($_GET["limit"]) 		&& !check_injection($_GET["limit"])) ? $limit = htmlentities($_GET["limit"]) : get_error('limit unknown');
	(isset($_GET["instance"])/* && !check_injection($_GET["instance"])*/) ? $instance = htmlentities($_GET["instance"]) : $instance = "ALL";
	(isset($_GET["search"]) 	&& !check_injection($_GET["search"])) ? $search = htmlentities($_GET["search"]) : $search = "";
	(isset($_GET["sort_type"]) 	&& !check_injection($_GET["sort_type"])) ? $sort_type = htmlentities($_GET["sort_type"]) : $sort_type = "host_name";
	(isset($_GET["search_type_host"]) 		&& !check_injection($_GET["search_type_host"])) ? $search_type_host = htmlentities($_GET["search_type_host"]) : $search_type_host = 1;
	(isset($_GET["search_type_service"])	&& !check_injection($_GET["search_type_service"])) ? $search_type_service = htmlentities($_GET["search_type_service"]) : $search_type_service = 1;
	(isset($_GET["order"]) 		&& !check_injection($_GET["order"])) ? $order = htmlentities($_GET["order"]) : $oreder = "ASC";
	(isset($_GET["date_time_format_status"]) && !check_injection($_GET["date_time_format_status"])) ? $date_time_format_status = htmlentities($_GET["date_time_format_status"]) : $date_time_format_status = "d/m/Y H:i:s";
	(isset($_GET["o"]) 			&& !check_injection($_GET["o"])) ? $o = htmlentities($_GET["o"]) : $o = "h";
	(isset($_GET["p"]) 			&& !check_injection($_GET["p"])) ? $p = htmlentities($_GET["p"]) : $p = "2";
	(isset($_GET["nc"]) 		&& !check_injection($_GET["nc"])) ? $nc = htmlentities($_GET["nc"]) : $nc = "0";

	// check is admin
	$is_admin = isUserAdmin($sid);

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
				" DISTINCT no.name1 as host_name," .
				" nss.process_performance_data," . 
				" nss.current_state," .
				" nss.output as plugin_output," .
				" nss.current_check_attempt as current_attempt," .
				" nss.status_update_time as status_update_time," .
				" unix_timestamp(nss.last_state_change) as last_state_change," .
				" unix_timestamp(nss.last_check) as last_check," .
				" unix_timestamp(nss.next_check) as next_check," .
				" nss.notifications_enabled," .
				" nss.problem_has_been_acknowledged," .
				" nss.passive_checks_enabled," .
				" nss.active_checks_enabled," .
				" nss.event_handler_enabled," .
				" nss.is_flapping," .
				" nss.flap_detection_enabled," .
				" no.object_id," .
				" no.name2 as service_description" .
				" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no";
				
				if (!$is_admin)
					$rq .= ", centreon_acl ";
					
		$rq .= 	" WHERE no.object_id = nss.service_object_id".
				" AND no.name1 not like 'qos_Module'" .
				" AND no.is_active = 1" .
			  	" AND objecttype_id = 2";

	if (!$is_admin && $groupnumber){
		$rq .= 	" AND no.name1 = centreon_acl.host_name AND no.name2 = centreon_acl.service_description AND centreon_acl.group_id IN (".groupsListStr($grouplist).")";
	}

	($o == "meta") ? $rq .= " AND no.name1 = 'Meta_Module'" : $rq .= " AND no.name1 != 'Meta_Module'";

	if ($instance != "ALL")
		$rq .= " AND no.instance_id = ".$instance;

	if (isset($host_name) && $host_name != "")
		$rq .= " AND no.name1 like '%" . $host_name . "%'  ";

	if ($search_type_host && $search_type_service && $search){
		$rq .= " AND ( no.name1 like '%" . $search . "%' OR no.name2 like '%" . $search . "%' OR nss.output like '%" . $search . "%') ";
	} else if (!$search_type_service && $search_type_host && $search){
		$rq .= " AND no.name1 like '%" . $search . "%'";
	} else if ($search_type_service && !$search_type_host && $search){
		$rq .= " AND no.name2 like '%" . $search . "%'";
	}
	
	if ($o == "svcpb")
		$rq .= " AND nss.current_state != 0";//  AND nss.current_state != 3 ";
	if ($o == "svc_ok")
		$rq .= " AND nss.current_state = 0 ";
	if ($o == "svc_warning")
		$rq .= " AND nss.current_state = 1 ";
	if ($o == "svc_critical")
		$rq .= " AND nss.current_state = 2 ";
	if ($o == "svc_unknown")
		$rq .= " AND nss.current_state = 3 ";
	if ($o == "svc_unhandled") {
		$rq .= " AND nss.current_state != 0";
		$rq .= " AND nss.problem_has_been_acknowledged = 0";
		$rq .= " AND nss.scheduled_downtime_depth = 0";
	}
		
	$rq_pagination = $rq;

	switch ($sort_type){
		case 'host_name' : $rq .= " order by no.name1 ". $order.",no.name2 "; break;
		case 'service_description' : $rq .= " order by no.name2 ". $order.",no.name1 "; break;
		case 'current_state' : $rq .= " order by nss.current_state ". $order.",no.name1,no.name2 "; break;
		case 'last_state_change' : $rq .= " order by nss.last_state_change ". $order.",no.name1,no.name2 "; break;
		case 'last_check' : $rq .= " order by nss.last_check ". $order.",no.name1,no.name2 "; break;
		case 'current_attempt' : $rq .= " order by nss.current_check_attempt ". $order.",no.name1,no.name2 "; break;
		default : $rq .= " order by no.name1 ". $order; break;
	}

	$rq .= " LIMIT ".($num * $limit).",".$limit;

	$ct = 0;
	$flag = 0;
	
	$DBRESULT_NDO2 =& $pearDBndo->query($rq_pagination);
	if (PEAR::isError($DBRESULT_NDO2))
		print "DB Error : ".$DBRESULT_NDO2->getDebugInfo()."<br />";

	/* 
	 * Get Pagination Rows 
	 */
	
	$numRows = $DBRESULT_NDO2->numRows();
	
	/*
	 * Create Buffer
	 */
	$buffer .= '<reponse>';
	$buffer .= '<i>';
	$buffer .= '<numrows>'.$numRows.'</numrows>';
	$buffer .= '<num>'.$num.'</num>';
	$buffer .= '<limit>'.$limit.'</limit>';
	$buffer .= '<p>'.$p.'</p>';
	$buffer .= '<nc>'.$nc.'</nc>';
	$buffer .= '<o>'.$o.'</o>';
	$buffer .= '</i>';
	

	$host_prev = "";
	$class = "list_one";
	
	$DBRESULT_NDO2 =& $pearDBndo->query($rq);
	if (PEAR::isError($DBRESULT_NDO2))
		print "DB Error : ".$DBRESULT_NDO2->getDebugInfo()."<br />";
	
	while ($ndo =& $DBRESULT_NDO2->fetchRow()) {

		$color_service = $tab_color_service[$ndo["current_state"]];
		$passive = 0;
		$active = 1;
		$last_check = " ";
		$duration = " ";

		if ($ndo["last_state_change"] > 0 && time() > $ndo["last_state_change"])
			$duration = Duration::toString(time() - $ndo["last_state_change"]);
		else if ($ndo["last_state_change"] > 0)
			$duration = " - ";

		$class == "list_one" ? $class = "list_two" : $class = "list_one";

		if ($tab_status_svc[$ndo["current_state"]] == "CRITICAL"){
			$ndo["problem_has_been_acknowledged"] == 1 ? $class = "list_four" : $class = "list_down";
		} else {
			if ($ndo["problem_has_been_acknowledged"] == 1)
				$class = "list_four";
		}
		
		$tabID = split("_", $ndo["service_description"]);
		$id = $tabID[1];
		
		$DBRESULT=& $pearDB->query("SELECT `meta_name` FROM  `meta_service` WHERE `meta_id` = '$id'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$dataMeta =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		
		$buffer .= '<l class="'.$class.'">';
		$buffer .= '<o>'. $ct++ . '</o>';
		$buffer .= '<f>'. $flag . '</f>';

		$buffer .= '<ppd>'. $ndo["process_performance_data"]  . '</ppd>';
		$buffer .= '<sd><![CDATA['. $dataMeta['meta_name'] . ']]></sd>';
		$buffer .= '<svc_id>'. $ndo["object_id"] . '</svc_id>';
		
		$ndo["service_description"] = str_replace("/", "#S#", $ndo["service_description"]);
		$ndo["service_description"] = str_replace("\\", "#BS#", $ndo["service_description"]);
		
		$buffer .= '<svc_index>'.getMyIndexGraph4Service($ndo["host_name"],$ndo["service_description"], $pearDBO).'</svc_index>';
		$buffer .= '<sc>'.$color_service.'</sc>';
		$buffer .= '<cs>'. $tab_status_svc[$ndo["current_state"]].'</cs>';
		$buffer .= '<po><![CDATA['. $ndo["plugin_output"].']]></po>';
		$buffer .= '<ca>'. $ndo["current_attempt"] . '</ca>';
		$buffer .= '<ne>'. $ndo["notifications_enabled"] . '</ne>';
		$buffer .= '<pa>'. $ndo["problem_has_been_acknowledged"] . '</pa>';
		$buffer .= '<pc>'. $ndo["passive_checks_enabled"] . '</pc>';
		$buffer .= '<ac>'. $ndo["active_checks_enabled"] . '</ac>';
		$buffer .= '<eh>'. $ndo["event_handler_enabled"] . '</eh>';
		$buffer .= '<is>'. $ndo["is_flapping"] . '</is>';
		$buffer .= '<fd>'. $ndo["flap_detection_enabled"] . '</fd>';
        $buffer .= '<ha>'. $ndo["problem_has_been_acknowledged"]  .'</ha>';
        $buffer .= '<hae>'. $ndo["active_checks_enabled"] .'</hae>';
        $buffer .= '<hpe>'. $ndo["passive_checks_enabled"]  .'</hpe>';
		$buffer .= '<nc>'. date($date_time_format_status, $ndo["next_check"]) . '</nc>';
		$buffer .= '<lc>'. date($date_time_format_status, $ndo["last_check"]) . '</lc>';
		$buffer .= '<d>'. $duration . '</d>';
		$buffer .= '</l>';
	}

	if (!$ct)
		$buffer .= '<infos>none</infos>';

	$buffer .= '<sid>'.$sid.'</sid>';
	$buffer .= '</reponse>';
	header('Content-Type: text/xml');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Cache-Control: no-cache, must-revalidate');
	echo $buffer;
?>
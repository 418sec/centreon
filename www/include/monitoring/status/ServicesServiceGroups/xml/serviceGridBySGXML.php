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
 * For information : contact@centreon.com
 */

	# if debug == 0 => Normal, debug == 1 => get use, debug == 2 => log in file (log.xml)
	$debugXML = 0;
	$buffer = '';

	include_once("@CENTREON_ETC@/centreon.conf.php");
	include_once($centreon_path."www/class/other.class.php");
	include_once($centreon_path."www/DBconnect.php");
	include_once($centreon_path."www/DBNDOConnect.php");
	include_once $centreon_path . "www/include/monitoring/status/Common/common-Func.php";
	include_once($centreon_path."www/include/common/common-Func-ACL.php");
	include_once($centreon_path."www/include/common/common-Func.php");

	$ndo_base_prefix = getNDOPrefix();
	$general_opt = getStatusColor($pearDB);
	
	if (isset($_GET["sid"]) && !check_injection($_GET["sid"])){
		$sid = $_GET["sid"];
		$sid = htmlentities($sid);
		$res =& $pearDB->query("SELECT * FROM session WHERE session_id = '".$sid."'");
		if (!$session =& $res->fetchRow())
			get_error('bad session id');
	} else
		get_error('need session identifiant !');


	/* requisit */
	(isset($_GET["instance"]) && !check_injection($_GET["instance"])) ? $instance = htmlentities($_GET["instance"]) : $instance = "ALL";
	(isset($_GET["num"]) && !check_injection($_GET["num"])) ? $num = htmlentities($_GET["num"]) : get_error('num unknown');
	(isset($_GET["limit"]) && !check_injection($_GET["limit"])) ? $limit = htmlentities($_GET["limit"]) : get_error('limit unknown');

	/* 
	 * options
	 */

	(isset($_GET["search"]) && !check_injection($_GET["search"])) ? $search = htmlentities($_GET["search"]) : $search = "";
	(isset($_GET["sort_type"]) && !check_injection($_GET["sort_type"])) ? $sort_type = htmlentities($_GET["sort_type"]) : $sort_type = "host_name";
	(isset($_GET["order"]) && !check_injection($_GET["order"])) ? $order = htmlentities($_GET["order"]) : $oreder = "ASC";
	(isset($_GET["date_time_format_status"]) && !check_injection($_GET["date_time_format_status"])) ? $date_time_format_status = htmlentities($_GET["date_time_format_status"]) : $date_time_format_status = "d/m/Y H:i:s";
	(isset($_GET["o"]) && !check_injection($_GET["o"])) ? $o = htmlentities($_GET["o"]) : $o = "h";
	(isset($_GET["p"]) && !check_injection($_GET["p"])) ? $p = htmlentities($_GET["p"]) : $p = "2";

	// check is admin
	$is_admin = isUserAdmin($sid);

	// if is admin -> lca
	if (!$is_admin){
		$_POST["sid"] = $sid;
		$lca =  getLCAHostByName($pearDB);
		$lcaSTR = getLCAHostStr($lca["LcaHost"]);
		$lcaSG = getLCASG($pearDB);
		$lcaSGStr = getLCASGStrByName($lcaSG);
	}

	function get_services($host_name){
		global $pearDBndo,$ndo_base_prefix, $lcaSGStr;
		global $general_opt;
		global $o;

		$rq = "SELECT no.name1, no.name2 as service_name, nss.current_state" .
				" FROM `" .$ndo_base_prefix."servicestatus` nss, `" .$ndo_base_prefix."objects` no" .
				" WHERE no.object_id = nss.service_object_id" ;
		if ($instance != "ALL")
			$rq .= " AND no.instance_id = ".$instance;

		if($o == "svcgridSG_pb" || $o == "svcOVSG_pb")
			$rq .= " AND nss.current_state != 0" ;

		if($o == "svcgridSG_ack_0" || $o == "svcOVSG_ack_0")
			$rq .= " AND nss.problem_has_been_acknowledged = 0 AND nss.current_state != 0" ;

		if($o == "svcgridSG_ack_1" || $o == "svcOVSG_ack_1")
			$rq .= " AND nss.problem_has_been_acknowledged = 1" ;


		$rq .= " AND no.object_id" .
				" IN (" .

				" SELECT nno.object_id" .
				" FROM ".$ndo_base_prefix."objects nno" .
				" WHERE nno.objecttype_id =2" .
				" AND nno.name1 = '".$host_name."'" ;
/*
	if($instance != "ALL")
		$rq .= " AND nno.instance_id = ".$instance;
*/

				$rq .= " )";

		$DBRESULT =& $pearDBndo->query($rq);
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$tab = array();
		while($svc =& $DBRESULT->fetchRow()){
			$tab[$svc["service_name"]] = $svc["current_state"];
		}
		return($tab);
	}



	$service = array();
	$host_status = array();
	$service_status = array();
	$host_services = array();
	$metaService_status = array();
	$tab_host_service = array();


	$tab_color_service = array();
	$tab_color_service[0] = $general_opt["color_ok"];
	$tab_color_service[1] = $general_opt["color_warning"];
	$tab_color_service[2] = $general_opt["color_critical"];
	$tab_color_service[3] = $general_opt["color_unknown"];
	$tab_color_service[4] = $general_opt["color_pending"];

	$tab_color_host = array();
	$tab_color_host[0] = $general_opt["color_up"];
	$tab_color_host[1] = $general_opt["color_down"];
	$tab_color_host[2] = $general_opt["color_unreachable"];
	$tab_color_host[3] = $general_opt["color_unreachable"];

	$tab_status_svc = array("0" => "OK", "1" => "WARNING", "2" => "CRITICAL", "3" => "UNKNOWN", "4" => "PENDING");
	$tab_status_host = array("0" => "UP", "1" => "DOWN", "2" => "UNREACHABLE");




	$rq1 = "SELECT sg.alias, no.name1 as host_name, no.name2 as service_description, sgm.servicegroup_id, sgm.service_object_id, ss.current_state".
			" FROM " .$ndo_base_prefix."servicegroups sg," .$ndo_base_prefix."servicegroup_members sgm, " .$ndo_base_prefix."servicestatus ss, " .$ndo_base_prefix."objects no".
			" WHERE sg.config_type = 1 " .
			" AND ss.service_object_id = sgm.service_object_id".
			" AND no.object_id = sgm.service_object_id" .
			" AND sgm.servicegroup_id = sg.servicegroup_id" .
			" AND no.is_active = 1 AND no.objecttype_id = 2";

		if (!$is_admin && $lcaSGStr != "")
			$rq1 .= " AND sg.alias IN ($lcaSGStr) ";

/*
	if(!$is_admin)
		$rq1 .= " AND no.name1 IN (".$lcaSTR." )";
*/

	if ($instance != "ALL")
		$rq1 .= " AND no.instance_id = ".$instance;


	if ($o == "svcgridSG_pb" || $o == "svcOVSG_pb")
		$rq1 .= " AND ss.current_state != 0" ;
/*
		$rq1 .= " AND no.name1 IN (" .
					" SELECT nno.name1 FROM " .$ndo_base_prefix."objects nno," .$ndo_base_prefix."servicestatus nss " .
					" WHERE nss.service_object_id = nno.object_id AND nss.current_state != 0" .
				")";
*/
	if($o == "svcgridSG_ack_0" || $o == "svcOVSG_ack_0")
		$rq1 .= " AND ss.current_state != 0 AND ss.problem_has_been_acknowledged = 0" ;
/*
		$rq1 .= " AND no.name1 IN (" .
					" SELECT nno.name1 FROM " .$ndo_base_prefix."objects nno," .$ndo_base_prefix."servicestatus nss " .
					" WHERE nss.service_object_id = nno.object_id AND nss.problem_has_been_acknowledged = 0 AND nss.current_state != 0" .
				")";
*/
	if($o == "svcgridSG_ack_1" || $o == "svcOVSG_ack_1")
		$rq1 .= " AND no.name1 IN (" .
					" SELECT nno.name1 FROM " .$ndo_base_prefix."objects nno," .$ndo_base_prefix."servicestatus nss " .
					" WHERE nss.service_object_id = nno.object_id AND nss.problem_has_been_acknowledged = 1" .
				")";
	if($search != ""){
		$rq1 .= " AND no.name1 like '%" . $search . "%' ";
	}


	$rq_pagination = $rq1;
	/* Get Pagination Rows */
	$DBRESULT_PAGINATION =& $pearDBndo->query($rq_pagination);
	if (PEAR::isError($DBRESULT_PAGINATION))
		print "DB Error : ".$DBRESULT_PAGINATION->getDebugInfo()."<br />";
	$numRows = $DBRESULT_PAGINATION->numRows();
	/* End Pagination Rows */


	$rq1 .= " ORDER BY sg.alias, host_name " . $order;

	$rq1 .= " LIMIT ".($num * $limit).",".$limit;



	$buffer .= '<reponse>';

	$buffer .= '<i>';
	$buffer .= '<numrows>'.$numRows.'</numrows>';
	$buffer .= '<num>'.$num.'</num>';
	$buffer .= '<limit>'.$limit.'</limit>';
	$buffer .= '<host_name>'._("Hosts").'</host_name>';
	$buffer .= '<services>'._("Services").'</services>';
	$buffer .= '<p>'.$p.'</p>';

	if($o == "svcOVSG")
		$buffer .= '<s>1</s>';
	else
		$buffer .= '<s>0</s>';

	$buffer .= '</i>';


	$DBRESULT_NDO1 =& $pearDBndo->query($rq1);
	if (PEAR::isError($DBRESULT_NDO1))
		print "DB Error : ".$DBRESULT_NDO1->getDebugInfo()."<br />";
	$class = "list_one";
	$ct = 0;
	$flag = 0;

	$sg = "";
	$h = "";
	$flag = 0;

	while ($tab =& $DBRESULT_NDO1->fetchRow()){
		$class == "list_one" ? $class = "list_two" : $class = "list_one";

		if ($sg != $tab["alias"]){
			$flag = 0;
			if($sg != "")
				$buffer .= '</h></sg>';

			$sg = $tab["alias"];
			$buffer .= '<sg>';
			$buffer .= '<sgn><![CDATA['. $tab["alias"]  .']]></sgn>';
			$buffer .= '<o><![CDATA['. $ct . ']]></o>';
		}
		$ct++;

		if ($h != $tab["host_name"]){
			if ($h != "" && $flag)
				$buffer .= '</h>';
			$flag = 1;
			$h = $tab["host_name"];
			$hs = get_Host_Status($tab["host_name"],$pearDBndo,$general_opt);
			$buffer .= '<h class="'.$class.'">';
			$buffer .= '<hn><![CDATA['. $tab["host_name"]  . ']]></hn>';
			$buffer .= '<hs><![CDATA['. $tab_status_host[$hs] . ']]></hs>';
			$buffer .= '<hc><![CDATA['. $tab_color_host[$hs]  . ']]></hc>';
		}

		$buffer .= '<svc>';
		$buffer .= '<sn><![CDATA['. $tab["service_description"] . ']]></sn>';
		$buffer .= '<sc><![CDATA['. $tab_color_service[$tab["current_state"]] . ']]></sc>';
		$buffer .= '</svc>';

	}
	if ($sg != "")
		$buffer .= '</h></sg>';

	$buffer .= '</reponse>';
	header('Content-Type: text/xml');
	echo $buffer;

?>
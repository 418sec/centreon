<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Cedrick Facon

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

	# if debug == 0 => Normal, debug == 1 => get use, debug == 2 => log in file (log.xml)
	$debugXML = 0;
	$buffer = '';
	$oreonPath = '/srv/oreon/';


	function get_error($motif){
		$buffer = null;
		$buffer .= '<reponse>';	
		$buffer .= $motif;
		$buffer .= '</reponse>';
		header('Content-Type: text/xml');
		echo $buffer;
		exit(0);
	}

	function check_injection(){
		if ( eregi("(<|>|;|UNION|ALL|OR|AND|ORDER|SELECT|WHERE)", $_GET["sid"])) {
			get_error('sql injection detected');
			return 1;
		}
		return 0;
	}

	/* security check 1/2*/
	if($oreonPath == '@INSTALL_DIR_OREON@')
		get_error('please set your oreonPath');
	/* security end 1/2 */

	include_once($oreonPath . "etc/centreon.conf.php");
	include_once($oreonPath . "www/DBconnect.php");
	include_once($oreonPath . "www/DBndoConnect.php");


	/* LCA */
	include_once($oreonPath . "www/include/common/common-Func-ACL.php");
//	$lcaHostName =  getLCAHostByName();
	
//	$lcaHostStr = getLCAHostStr($lcaHost["LcaHost"]);





	/* security check 2/2*/
	if(isset($_GET["sid"]) && !check_injection($_GET["sid"])){

		$sid = $_GET["sid"];
		$sid = htmlentities($sid);
		$res =& $pearDB->query("SELECT * FROM session WHERE session_id = '".$sid."'");
		if($res->fetchInto($session)){
			;
		}else
			get_error('bad session id');
	}
	else
		get_error('need session identifiant !');
	/* security end 2/2 */




	/* requisit */
	if(isset($_GET["num"]) && !check_injection($_GET["num"])){
		$num = htmlentities($_GET["num"]);
	}else
		get_error('num unknown');
	if(isset($_GET["limit"]) && !check_injection($_GET["limit"])){
		$limit = htmlentities($_GET["limit"]);
	}else
		get_error('limit unknown');



	/* options */
	if(isset($_GET["enable"]) && !check_injection($_GET["enable"])){
		$enable = urldecode($_GET["enable"]);
	}else
		$enable = "enable";
	if(isset($_GET["disable"]) && !check_injection($_GET["disable"])){
		$disable = urldecode($_GET["disable"]);
	}else
		$disable = "disable";
		

	$enable = str_replace('&eacute', '&#233', $enable);
	$disable = str_replace('&eacute', '&#233', $disable);


	if(isset($_GET["nc"]) && !check_injection($_GET["nc"])){
		$nc = htmlentities($_GET["nc"]);
	}else
		$nc = "0";
	if(isset($_GET["instance"]) && !check_injection($_GET["instance"])){
		$instance = htmlentities($_GET["instance"]);
	}else
		$instance = "ALL";
	if(isset($_GET["search"]) && !check_injection($_GET["search"])){
		$search = htmlentities($_GET["search"]);
	}else
		$search = "";
	if(isset($_GET["host_name"]) && !check_injection($_GET["host_name"])){
		$host_name = htmlentities($_GET["host_name"]);
	}else
		$host_name = "";
	if(isset($_GET["search_type_host"]) && !check_injection($_GET["search_type_host"])){
		$search_type_host = htmlentities($_GET["search_type_host"]);
	}else
		$search_type_host = 1;
	if(isset($_GET["search_type_service"]) && !check_injection($_GET["search_type_service"])){
		$search_type_service = htmlentities($_GET["search_type_service"]);
	}else
		$search_type_service = 1;
	if(isset($_GET["sort_type"]) && !check_injection($_GET["sort_type"])){
		$sort_type = htmlentities($_GET["sort_type"]);
	}else
		$sort_type = "host_name";

	if(isset($_GET["order"]) && !check_injection($_GET["order"])){
		$order = htmlentities($_GET["order"]);
	}else
		$oreder = "ASC";

	if(isset($_GET["date_time_format_status"]) && !check_injection($_GET["date_time_format_status"])){
		$date_time_format_status = htmlentities($_GET["date_time_format_status"]);
	}else
		$date_time_format_status = "d/m/Y H:i:s";

	if(isset($_GET["o"]) && !check_injection($_GET["o"])){
		$o = htmlentities($_GET["o"]);
	}else
		$o = "svc";
	if(isset($_GET["p"]) && !check_injection($_GET["p"])){
		$p = htmlentities($_GET["p"]);
	}else
		$p = "2";



	/* security end*/

	# class init
	class Duration
	{
		function toString ($duration, $periods = null)
	    {
	        if (!is_array($duration)) {
	            $duration = Duration::int2array($duration, $periods);
	        }
	        return Duration::array2string($duration);
	    }
	    function int2array ($seconds, $periods = null)
	    {
	        // Define time periods
	        if (!is_array($periods)) {
	            $periods = array (
	                    'y'	=> 31556926,
	                    'M' => 2629743,
	                    'w' => 604800,
	                    'd' => 86400,
	                    'h' => 3600,
	                    'm' => 60,
	                    's' => 1
	                    );
	        }
	        // Loop
	        $seconds = (int) $seconds;
	        foreach ($periods as $period => $value) {
	            $count = floor($seconds / $value);
	            if ($count == 0) {
	                continue;
	            }
	            $values[$period] = $count;
	            $seconds = $seconds % $value;
	        }
	        // Return
	        if (empty($values)) {
	            $values = null;
	        }
	        return $values;
	    }

	    function array2string ($duration)
	    {
	        if (!is_array($duration)) {
	            return false;
	        }
	        foreach ($duration as $key => $value) {
	            $segment = $value . '' . $key;
	            $array[] = $segment;
	        }
	        $str = implode(' ', $array);
	        return $str;
	    }
	}


	$service = array();
	$host_status = array();
	$service_status = array();
	$host_services = array();
	$metaService_status = array();
	$tab_host_service = array();

	$DBRESULT_OPT =& $pearDB->query("SELECT ndo_base_prefix,color_ok,color_warning,color_critical,color_unknown,color_pending,color_up,color_down,color_unreachable FROM general_opt");
	if (PEAR::isError($DBRESULT_OPT))
		print "DB Error : ".$DBRESULT_OPT->getDebugInfo()."<br>";	
	$DBRESULT_OPT->fetchInto($general_opt);
	
	$tab_color_service = array();
	$tab_color_service[0] = $general_opt["color_ok"];
	$tab_color_service[1] = $general_opt["color_warning"];
	$tab_color_service[2] = $general_opt["color_critical"];
	$tab_color_service[3] = $general_opt["color_unknown"];
	$tab_color_service[4] = $general_opt["color_pending"];

	$tab_color_host = array();
	$tab_color_host[0] = "normal";
	$tab_color_host[1] = "#FD8B46";//$general_opt["color_down"];
	$tab_color_host[2] = "normal";
	
	$tab_status_svc = array("0" => "OK", "1" => "WARNING", "2" => "CRITICAL", "3" => "UNKNOWN", "4" => "PENDING");
	$tab_status_host = array("0" => "UP", "1" => "DOWN", "2" => "UNREACHABLE");



	/* Get Host status */
	$rq1 = "SELECT nhs.current_state," .
			" nhs.problem_has_been_acknowledged, " .
			" nhs.passive_checks_enabled," .
			" nhs.active_checks_enabled," .
			" no.name1 as host_name" .
			" FROM ".$general_opt["ndo_base_prefix"]."_hoststatus nhs, ".$general_opt["ndo_base_prefix"]."_objects no" .
			" WHERE no.object_id = nhs.host_object_id AND no.objecttype_id = 1";

	if($instance != "ALL")
		$rq1 .= " AND no.instance_id = ".$instance;

	if($o == "meta")
		$rq1 .= " AND no.name1 = 'Meta_Module'";
	else
		$rq1 .= " AND no.name1 != 'Meta_Module'";


	$DBRESULT_NDO1 =& $pearDBndo->query($rq1);
	if (PEAR::isError($DBRESULT_NDO1))
		print "DB Error : ".$DBRESULT_NDO1->getDebugInfo()."<br>";	
	while($DBRESULT_NDO1->fetchInto($ndo))
	{
		$host_status[$ndo["host_name"]] = $ndo;
	}
	/* end */

	/* Get Service status */
	$rq ="SELECT " .
			"nss.current_state," .
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
			" nss. is_flapping," .
			" nss.flap_detection_enabled," .
			" no.name1 as host_name," .
			" no.name2 as service_description" .
			" FROM ".$general_opt["ndo_base_prefix"]."_servicestatus nss, ".$general_opt["ndo_base_prefix"]."_objects no" .
			" WHERE no.object_id = nss.service_object_id".
			" AND no.name1 not like 'OSL_Module'".
			" AND no.is_active = 0 AND objecttype_id = 2";

	if($o == "meta")
		$rq .= " AND no.name1 = 'Meta_Module'";
	else
		$rq .= " AND no.name1 != 'Meta_Module'";


	if($instance != "ALL")
		$rq .= " AND no.instance_id = ".$instance;


	if($host_name != ""){
		$rq .= " AND no.name1 like '%" . $host_name . "%'  ";
	}
	

	if($search_type_host && $search_type_service && $search){
		$rq .= " AND ( no.name1 like '%" . $search . "%' OR no.name2 like '%" . $search . "%' OR nss.output like '%" . $search . "%') ";
	}
	else if(!$search_type_service && $search_type_host && $search){
		$rq .= " AND no.name1 like '%" . $search . "%'";
	}
	else if($search_type_service && !$search_type_host && $search){
		$rq .= " AND no.name2 like '%" . $search . "%'";
	}
	else
	;
	if($o == "svcpb")
		$rq .= " AND nss.current_state != 0 ";
	if($o == "svc_ok")
		$rq .= " AND nss.current_state = 0 ";
	if($o == "svc_warning")
		$rq .= " AND nss.current_state = 1 ";
	if($o == "svc_critical")
		$rq .= " AND nss.current_state = 2 ";
	if($o == "svc_unknown")
		$rq .= " AND nss.current_state = 3 ";
	$rq_pagination = $rq;

	switch($sort_type){
			case 'host_name' : $rq .= " order by no.name1,no.name2 ". $order; break;
			case 'service_description' : $rq .= " order by no.name2,no.name1 ". $order; break;
			case 'last_check' : $rq .= " order by nss.last_check,no.name1,no.name2 ". $order; break;
			case 'next_check' : $rq .= " order by nss.next_check,no.name1,no.name2 ". $order; break;
			case 'active_check' : $rq .= " order by nss.active_check,no.name1,no.name2 ". $order; break;
			default : $rq .= " order by no.name1 ". $order; break;
	}
	
	$rq .= " LIMIT ".($num * $limit).",".$limit;
	$DBRESULT_NDO =& $pearDBndo->query($rq);
	if (PEAR::isError($DBRESULT_NDO))
		print "DB Error : ".$DBRESULT_NDO->getDebugInfo()."<br>";	
	$buffer .= '<reponse>';
	$ct = 0;
	$flag = 0;
	
	/* Get Pagination Rows */
	$DBRESULT_PAGINATION =& $pearDBndo->query($rq_pagination);
	if (PEAR::isError($DBRESULT_PAGINATION))
		print "DB Error : ".$DBRESULT_PAGINATION->getDebugInfo()."<br>";	
	$numRows = $DBRESULT_PAGINATION->numRows();
	$buffer .= '<i>';
	$buffer .= '<numrows>'.$numRows.'</numrows>';
	$buffer .= '<num>'.$num.'</num>';
	$buffer .= '<limit>'.$limit.'</limit>';
	$buffer .= '<p>'.$p.'</p>';
	$buffer .= '<nc>'.$nc.'</nc>';
	$buffer .= '<o>'.$o.'</o>';
	$buffer .= '</i>';
	/* End Pagination Rows */

	$host_prev = "";
	$class = "list_one";

	$color_en = array("1" => "#00ff00", "0" => "#ff0000");
	$color_en_label = array("1" => $enable, "0" => $disable);
	
	while($DBRESULT_NDO->fetchInto($ndo))
	{
		if( isset($host_status[$ndo["host_name"]]) ){
			$color_host = $tab_color_host[$host_status[$ndo["host_name"]]["current_state"]]; //"#FF0000";
			$color_service = $tab_color_service[$ndo["current_state"]];
			$passive = 0;
			$active = 1;
			$last_check = " ";
			$duration = " ";
			if($ndo["last_state_change"] > 0)
				$duration = Duration::toString(time() - $ndo["last_state_change"]);
	
			if($class == "list_one")
				$class = "list_two";
			else
				$class = "list_one";
	
			if($tab_status_svc[$ndo["current_state"]] == "CRITICAL"){
				if($ndo["problem_has_been_acknowledged"] == 1)
					$class = "list_four";
				else
					$class = "list_down";
			}else{
				if( $ndo["problem_has_been_acknowledged"] == 1)
					$class = "list_four";
			}
	
	
			$buffer .= '<l class="'.$class.'">';
			$buffer .= '<o>'. $ct++ . '</o>';
			$buffer .= '<f>'. $flag . '</f>';
	
			if($host_prev == $ndo["host_name"]){
				$buffer .= '<hc>transparent</hc>';
				$buffer .= '<hn none="1">'. $ndo["host_name"] . '</hn>';			
			}else{			
				$host_prev = $ndo["host_name"];
				$buffer .= '<hc>'.$color_host.'</hc>';
				$buffer .= '<hn none="0">'. $ndo["host_name"] . '</hn>';			
			}
	
			$buffer .= '<hs><![CDATA['. $host_status[$ndo["host_name"]]["current_state"]  . ']]></hs>';///
			$buffer .= '<sd><![CDATA['. $ndo["service_description"] . ']]></sd>';
			$buffer .= '<ac>'. $color_en_label[$ndo["active_checks_enabled"]] . '</ac>';
			$buffer .= '<sc>'.$color_service.'</sc>';
			$buffer .= '<cs>'. $tab_status_svc[$ndo["current_state"]].'</cs>';
			$buffer .= '<po><![CDATA['. $ndo["plugin_output"].']]></po>';
			$buffer .= '<ca>'. $ndo["current_attempt"] . '</ca>';
			$buffer .= '<ne>'. $ndo["notifications_enabled"] . '</ne>';
			$buffer .= '<pa>'. $ndo["problem_has_been_acknowledged"] . '</pa>';
			$buffer .= '<pc>'. $passive . '</pc>';
			$buffer .= '<ac>'. $active . '</ac>';
			$buffer .= '<eh>'. $ndo["event_handler_enabled"] . '</eh>';
			$buffer .= '<is>'. $ndo["is_flapping"] . '</is>';
			$buffer .= '<fd>'. $ndo["flap_detection_enabled"] . '</fd>';
	        $buffer .= '<ha>'.$host_status[$ndo["host_name"]]["problem_has_been_acknowledged"]  .'</ha>';///
	        $buffer .= '<hae>'.$host_status[$ndo["host_name"]]["active_checks_enabled"] .'</hae>';///
	        $buffer .= '<hpe>'.$host_status[$ndo["host_name"]]["passive_checks_enabled"]  .'</hpe>';///
	//		$buffer .= '<lsc>'. $ndo["last_state_change"] . '</lsc>';

			$buffer .= '<nc>'. date($date_time_format_status, $ndo["next_check"]) . '</nc>';
			$buffer .= '<lc>'. date($date_time_format_status, $ndo["last_check"]) . '</lc>';
			$buffer .= '<d>'. $duration . '</d>';
			$buffer .= '</l>';
		}
	}
	/* end */
	
	if(!$ct){
		$buffer .= '<infos>';
		$buffer .= 'none';
		$buffer .= '</infos>';
	}

	$buffer .= '</reponse>';
	header('Content-Type: text/xml');
	echo $buffer;
?>
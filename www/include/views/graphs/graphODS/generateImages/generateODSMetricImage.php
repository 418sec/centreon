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
 
 	header('Content-Type: image/png');
 	
	function escape_command($command) {
		return ereg_replace("(\\\$|`)", "", $command);
	}
	
	include("@CENTREON_ETC@/centreon.conf.php");
	
	require_once ('DB.php');
	require_once ('./DB-Func.php');
	require_once ($centreon_path."www/class/Session.class.php");
	require_once ($centreon_path."www/class/Oreon.class.php");
	
	Session::start();
	$oreon =& $_SESSION["oreon"];

	require_once $centreon_path."www/include/common/common-Func.php";

	$dsn = array(
	    'phptype'  => 'mysql',
	    'username' => $conf_centreon['user'],
	    'password' => $conf_centreon['password'],
	    'hostspec' => $conf_centreon['hostCentreon'],
	    'database' => $conf_centreon['db'],
	);

	$options = array(
	    'debug'       => 2,
	    'portability' => DB_PORTABILITY_ALL ^ DB_PORTABILITY_LOWERCASE,
	);

	$pearDB =& DB::connect($dsn, $options);
	if (PEAR::isError($pearDB))
	    die("Unable to connect : " . $pearDB->getMessage());

	$pearDB->setFetchMode(DB_FETCHMODE_ASSOC);

	$session =& $pearDB->query("SELECT * FROM `session` WHERE session_id = '".$_GET["session_id"]."'");
	if (!$session->numRows()){
		exit;
	} else {
		$session->free();
		/*
		 * Connect to ods
		 */
		 
		include_once($centreon_path."www/DBOdsConnect.php");

		$RRDdatabase_path = getRRDToolPath($pearDBO);
	
		/*
		 * Get index information to have acces to graph
		 */
		
		$DBRESULT =& $pearDBO->query("SELECT index_id, metric_name FROM metrics WHERE metric_id = '".$_GET["metric"]."' LIMIT 1");
		$metric_ODS =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		
		$DBRESULT =& $pearDBO->query("SELECT * FROM metrics WHERE index_id = '".$metric_ODS["index_id"]."'");
		$metricnumber = $DBRESULT->numRows();
		
		$DBRESULT =& $pearDBO->query("SELECT * FROM index_data WHERE id = '".$metric_ODS["index_id"]."' LIMIT 1");
		$index_data_ODS =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		
		if (!isset($_GET["template_id"])|| !$_GET["template_id"]){
			$host_id = getMyHostID($index_data_ODS["host_name"]);
			$svc_id = getMyServiceID($index_data_ODS["service_description"], $host_id);
			$template_id = getDefaultGraph($svc_id, 1);
		} else
			$template_id = $_GET["template_id"];			
		$command_line = " graph - --start=".$_GET["start"]. " --end=".$_GET["end"];

		# get all template infos
		$DBRESULT =& $pearDB->query("SELECT * FROM giv_graphs_template WHERE graph_id = '".$template_id."' LIMIT 1");
		$GraphTemplate =& $DBRESULT->fetchRow();

		if (preg_match("/meta_([0-9]*)/", $index_data_ODS["service_description"], $matches)){
			$DBRESULT_meta =& $pearDB->query("SELECT meta_name FROM meta_service WHERE `meta_id` = '".$matches[1]."'");
			if (PEAR::isError($DBRESULT_meta))
				print "Mysql Error : ".$DBRESULT_meta->getDebugInfo();
			$meta =& $DBRESULT_meta->fetchRow();
			$index_data_ODS["service_description"] = $meta["meta_name"];
		}

		$index_data_ODS["service_description"] = str_replace("#S#", "/", $index_data_ODS["service_description"]);
		$index_data_ODS["service_description"] = str_replace("#BS#", "\\", $index_data_ODS["service_description"]);
				
		$metric_ODS["metric_name"] = str_replace("#S#", "/", $metric_ODS["metric_name"]);
		$metric_ODS["metric_name"] = str_replace("#BS#", "\\", $metric_ODS["metric_name"]);
				
		$base = "";
		if (isset($GraphTemplate["base"]) && $GraphTemplate["base"])
			$base = "-b ".$GraphTemplate["base"];
				
		$command_line .= " --interlaced $base --imgformat PNG --width=500 --height=120 --title='".$index_data_ODS["service_description"]." graph on ".$index_data_ODS["host_name"]." metric ".$metric_ODS["metric_name"] ."' --vertical-label='".$GraphTemplate["vertical_label"]."' ";
		if ($oreon->optGen["rrdtool_version"] == "1.2")
			$command_line .= " --slope-mode ";
		
		# Init Graph Template Value
		if (isset($GraphTemplate["bg_grid_color"]) && $GraphTemplate["bg_grid_color"])
			$command_line .= "--color CANVAS".$GraphTemplate["bg_grid_color"]." ";
		if (isset($GraphTemplate["bg_color"]) && $GraphTemplate["bg_color"])
			$command_line .= "--color BACK".$GraphTemplate["bg_color"]." ";
		if (isset($GraphTemplate["police_color"]) && $GraphTemplate["police_color"])
			$command_line .= "--color FONT".$GraphTemplate["police_color"]." ";
		if (isset($GraphTemplate["grid_main_color"]) && $GraphTemplate["grid_main_color"])
			$command_line .= "--color MGRID".$GraphTemplate["grid_main_color"]." ";
		if (isset($GraphTemplate["grid_sec_color"]) && $GraphTemplate["grid_sec_color"])
			$command_line .= "--color GRID".$GraphTemplate["grid_sec_color"]." ";
		if (isset($GraphTemplate["contour_cub_color"]) && $GraphTemplate["contour_cub_color"])
			$command_line .= "--color FRAME".$GraphTemplate["contour_cub_color"]." ";
		if (isset($GraphTemplate["col_arrow"]) && $GraphTemplate["col_arrow"])
			$command_line .= "--color ARROW".$GraphTemplate["col_arrow"]." ";
		if (isset($GraphTemplate["col_top"]) && $GraphTemplate["col_top"])
			$command_line .= "--color SHADEA".$GraphTemplate["col_top"]." ";
		if (isset($GraphTemplate["col_bot"]) && $GraphTemplate["col_bot"])
			$command_line .= "--color SHADEB".$GraphTemplate["col_bot"]." ";
		
		if (isset($GraphTemplate["lower_limit"]) && $GraphTemplate["lower_limit"] != NULL)
			$command_line .= "--lower-limit ".$GraphTemplate["lower_limit"]." ";
		if (isset($GraphTemplate["upper_limit"]) && $GraphTemplate["upper_limit"] != NULL)
			$command_line .= "--upper-limit ".$GraphTemplate["upper_limit"]." ";
		if ((isset($GraphTemplate["lower_limit"]) && $GraphTemplate["lower_limit"] != NULL) || (isset($GraphTemplate["upper_limit"]) && $GraphTemplate["upper_limit"] != NULL))
			$command_line .= "--rigid --alt-autoscale-max ";

		# Init DS template For each curv
		$metrics = array();
		$DBRESULT =& $pearDBO->query("SELECT metric_id, metric_name, unit_name, warn, crit, min, max FROM metrics WHERE metric_id = '".$_GET["metric"]."' AND `hidden` = '0' ORDER BY metric_name");
		if (PEAR::isError($DBRESULT))
			print "Mysql Error : ".$DBRESULT->getDebugInfo();
		$cpt = 1;
		$order = ($_GET["cpt"] - 1);
		$order = $order % $metricnumber;
		$metrics = array();		
		while ($metric =& $DBRESULT->fetchRow()){
			$metric["metric_name"] = str_replace("#S#", "slash_", $metric["metric_name"]);
			$metrics[$metric["metric_id"]]["metric_id"] = $metric["metric_id"];
			$metrics[$metric["metric_id"]]["metric"] = str_replace("#S#", "slash_", $metric["metric_name"]);
			$metrics[$metric["metric_id"]]["unit"] = $metric["unit_name"];
			
			$res_ds =& $pearDB->query("SELECT * FROM giv_components_template WHERE `ds_name` = '".$metric["metric_name"]."'");
			$ds_data =& $res_ds->fetchRow();
			
			if (!$ds_data){
				$ds = getDefaultDS();						
				$res_ds =& $pearDB->query("SELECT * FROM giv_components_template WHERE compo_id = '".$ds."'");
				$ds_data =& $res_ds->fetchRow();
				$metrics[$metric["metric_id"]]["ds_id"] = $ds;
			}
			
			/*
			 * Fetch Datas
			 */
			
			foreach ($ds_data as $key => $ds_d){
				if ($key == "ds_transparency"){
					$transparency = dechex(255-($ds_d*255)/100);
					if (strlen($transparency) == 1)
						$transparency = "0" . $transparency;
					$metrics[$metric["metric_id"]][$key] = $transparency;
				} else
					$metrics[$metric["metric_id"]][$key] = $ds_d ;
				
			}
			$res_ds->free();
			
			
			if (preg_match('/DS/', $ds_data["ds_name"], $matches)){
				$metrics[$metric["metric_id"]]["legend"] = str_replace("slash_", "/", $metric["metric_name"]);
				$metrics[$metric["metric_id"]]["legend"] = str_replace("#S#", "/", $metrics[$metric["metric_id"]]["legend"]);
				$metrics[$metric["metric_id"]]["legend"] = str_replace("#BS#", "\/", $metrics[$metric["metric_id"]]["legend"]);
			} else {
				$metrics[$metric["metric_id"]]["legend"] = $ds_data["ds_name"];
			}
			if (strcmp($metric["unit_name"], "")){
				$metrics[$metric["metric_id"]]["legend"] .= " (".$metric["unit_name"].") ";
			}
			$metrics[$metric["metric_id"]]["legend_len"] = strlen($metrics[$metric["metric_id"]]["legend"]);
			$cpt++;
		}
		$DBRESULT->free();
		
		$cpt = 0;
		$longer = 0;
		foreach ($metrics as $key => $tm){
			if (isset($tm["ds_invert"]) && $tm["ds_invert"])
				$command_line .= " DEF:va".$cpt."=".$RRDdatabase_path.$key.".rrd:".substr($metrics[$key]["metric"],0 , 19).":AVERAGE CDEF:v".$cpt."=va".$cpt.",-1,*";
			else
				$command_line .= " DEF:v".$cpt."=".$RRDdatabase_path.$key.".rrd:".substr($metrics[$key]["metric"],0 , 19).":AVERAGE ";
			if ($tm["legend_len"] > $longer)
				$longer = $tm["legend_len"];
			$cpt++;
		}

		# Create Legende
		$cpt = 1;
		foreach ($metrics as $key => $tm){
			if ($metrics[$key]["ds_filled"])
				$command_line .= " AREA:v".($cpt-1)."".$tm["ds_color_area"].$tm["ds_transparency"]." ";
			$command_line .= " LINE".$tm["ds_tickness"].":v".($cpt-1);
			$command_line .= $tm["ds_color_line"].":\"";
			$command_line .= $metrics[$key]["legend"];
			for ($i = $metrics[$key]["legend_len"]; $i != $longer + 1; $i++)
				$command_line .= " ";
			$command_line .= "\"";
			if ($tm["ds_last"]){
				$command_line .= " GPRINT:v".($cpt-1).":LAST:\"Last\:%7.2lf%s";
				$tm["ds_min"] || $tm["ds_max"] || $tm["ds_average"] ? $command_line .= "\"" : $command_line .= "\\l\" ";
			}
			if ($tm["ds_min"]){
				$command_line .= " GPRINT:v".($cpt-1).":MIN:\"Min\:%7.2lf%s";
				$tm["ds_max"] || $tm["ds_average"] ? $command_line .= "\"" : $command_line .= "\\l\" ";
			}
			if ($tm["ds_max"]){
				$command_line .= " GPRINT:v".($cpt-1).":MAX:\"Max\:%7.2lf%s";
				$tm["ds_average"] ? $command_line .= "\"" : $command_line .= "\\l\" ";
			}
			if ($tm["ds_average"]){
				$command_line .= " GPRINT:v".($cpt-1).":AVERAGE:\"Average\:%7.2lf%s\\l\"";
			}
			$cpt++;
			if (isset($tm["warn"]) && $tm["warn"] != 0)
				$command_line .= " HRULE:".$tm["warn"]."#00FF00:\"Warning \: ".$tm["warn"]."\\l\" "; 
			if (isset($tm["crit"]) && $tm["crit"] != 0)	
				$command_line .= " HRULE:".$tm["crit"]."#FF0000:\"Critical \: ".$tm["crit"]."\""; 
		}

		$command_line = $oreon->optGen["rrdtool_path_bin"].$command_line." 2>&1";
		$command_line = escape_command("$command_line");
		if ( $oreon->optGen["debug_rrdtool"] == "1" )
			error_log("[" . date("d/m/Y H:s") ."] RDDTOOL : $command_line \n", 3, $oreon->optGen["debug_path"]."rrdtool.log");

		//print $command_line;
		$fp = popen($command_line  , 'r');
		if (isset($fp) && $fp ) {
			$str ='';
			while (!feof ($fp)) {
		  		$buffer = fgets($fp, 4096);
		 		$str = $str . $buffer ;
			}
			print $str;
		}
	}
?>
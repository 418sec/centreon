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

	function check_injection(){
		if ( eregi("(<|>|;|UNION|ALL|OR|AND|ORDER|SELECT|WHERE)", $_GET["sid"])) {
			get_error('sql injection detected');
			return 1;
		}
		return 0;
	}

	function get_error($str){
		echo $str."<br />";
		exit(0);
	}

	include_once("@CENTREON_ETC@/centreon.conf.php");
	include_once($centreon_path."www/class/centreonDB.class.php");
	include_once($centreon_path."www/class/centreonXML.class.php");

	$pearDB = new CentreonDB();
	$pearDBO = new CentreonDB("centstorage");

	if (isset($_GET["sid"]) && !check_injection($_GET["sid"])){
		$sid = $_GET["sid"];
		$sid = htmlentities($sid);
		$res =& $pearDB->query("SELECT * FROM session WHERE session_id = '".$sid."'");
		if (!$session =& $res->fetchRow())
			get_error('bad session id');
	} else
		get_error('need session identifiant !');

	isset($_GET["index"]) ? $index = $_GET["index"] : $index = NULL;
	isset($_POST["index"]) ? $index = $_POST["index"] : $index = $index;

	$path = "./include/views/graphs/graphODS/";

	$period = (isset($_POST["period"])) ? $_POST["period"] : "today"; 
	$period = (isset($_GET["period"])) ? $_GET["period"] : $period;

	$DBRESULT =& $pearDBO->query("SELECT host_name, service_description FROM index_data WHERE id = '$index'");
	while ($res =& $DBRESULT->fetchRow()){
		$hName = $res["host_name"];
		$sName = $res["service_description"];
	}	

	header("Content-Type: application/xml");
	if (isset($hName) && isset($sName))
		header("Content-disposition: filename=".$hName."_".$sName.".xml");
	else
		header("Content-disposition: filename=".$index.".xml");

	$DBRESULT =& $pearDBO->query("SELECT metric_id FROM metrics, index_data WHERE metrics.index_id = index_data.id AND id = '$index'");
	while ($index_data =& $DBRESULT->fetchRow()){	
		$DBRESULT2 =& $pearDBO->query("SELECT ctime,value FROM data_bin WHERE id_metric = '".$index_data["metric_id"]."' AND ctime >= '".$_GET["start"]."' AND ctime < '".$_GET["end"]."'");
		while ($data =& $DBRESULT2->fetchRow()){
			if (!isset($datas[$data["ctime"]]))
				$datas[$data["ctime"]] = array();
			$datas[$data["ctime"]][$index_data["metric_id"]] = $data["value"];
		}
	}
	$buffer = new CentreonXML();
	$buffer->startElement("root");
	$buffer->startElement("datas");	
	foreach ($datas as $key => $tab){
		$buffer->startElement("data");
		$buffer->writeAttribute("no", $key);		
		foreach($tab as $value)
			$buffer->writeElement("metric", $value);			
		$buffer->endElement();		
	}
	$buffer->endElement();
	$buffer->endElement();
	$buffer->output();
	exit();
?>
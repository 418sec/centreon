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
 * SVN : $URL: http://svn.centreon.com/trunk/centreon/www/class/centreonXMLBGRequest.class.php $
 * SVN : $Id: centreon.class.php 9656 2010-01-04 09:05:23Z jmathis $
 * 
 */

/*
 * Need Centreon Configuration file
 */
require_once "@CENTREON_ETC@/centreon.conf.php";
//require_once "/etc/centreon/centreon.conf.php";

/*
 * this class need also others classes
 */
require_once $centreon_path."www/class/centreonDuration.class.php";
require_once $centreon_path."www/class/centreonGMT.class.php";
require_once $centreon_path."www/class/centreonACL.class.php";
require_once $centreon_path."www/class/centreonDB.class.php";
require_once $centreon_path."www/class/centreonHost.class.php";
require_once $centreon_path."www/class/centreonService.class.php";
require_once $centreon_path."www/class/centreonSession.class.php";

/*
 * Class for XML/Ajax request
 * 
 */	
class CentreonGraph	{

	/*
	 * Objects
	 */
	var $DB;
	var $DBC;
	
	var $XML;
	var $GMT;
	
	var $hostObj;
	var $serviceObj;
	
	var $session_id;
	
	/*
	 * Variables
	 */
	var $debug;
	var $compress;
	var $user_id;
	var $general_opt;
	var $filename;
	var $commandLine;
	var $dbPath;
	var $width;
	var $height;
	var $end;
	var $start;
	var $index;
	var $indexData;
	var $template_id;
	var $templateInformations;
	var $gprintScaleOption;
	var $title;
	var $graphID;
	var $metricsActivate;
	
	/*
	 * Class constructor
	 *
	 * <code>
	 * $obj = new CentreonBGRequest($_GET["session_id"], 1, 1, 0, 1);
	 * </code>
	 *
	 * $session_id 	char 	session id
	 * $dbneeds		bool 	flag for enable ndo connexion
	 * $headType	bool 	send XML header
	 * $debug		bool 	debug flag.
	 */
	function CentreonGraph($session_id, $index, $debug, $compress = NULL) {
		if (!isset($debug))
			$this->debug = 0;
		
		(!isset($compress)) ? $this->compress = 1 : $this->compress = $compress;
		
		if (!isset($session_id)) {
			print "Your might check your session id";
			exit(1);
		} else {
			$this->session_id = htmlentities($session_id, ENT_QUOTES);
		}
		
		$this->index = htmlentities($index, ENT_QUOTES);
		
		/*
		 * Enable Database Connexions
		 */
		$this->DB 		= new CentreonDB();
		$this->DBC 		= new CentreonDB("centstorage");
		
		/*
		 * Init Objects
		 */
		$this->hostObj		= new CentreonHost($this->DB);
		$this->serviceObj	= new CentreonService($this->DB);
	
		/*
		 * Timezone management
		 */
		$this->GMT = new CentreonGMT($this->DB);
		$this->GMT->getMyGMTFromSession($this->session_id, $this->DB);		
		
		/*
		 * Set Command line
		 */
		$this->commandLine = "";
		
		/*
		 * Set parameters
		 */
		$this->width = 500;
		$this->height = 120;

		/*
		 * Get index data
		 */
		$this->getIndexData();
		$this->setFilename();

		$this->getRRDToolPath();
		
		$this->templateInformations = array();
	}

	public function addRRDToolProperties() {
		if ($this->general_opt["rrdtool_version"] != "1.0")
			$this->commandLine .= " --slope-mode ";
		
		if ($this->general_opt["rrdtool_version"] == "1.3") {
	       if (isset($this->general_opt["rrdtool_title_font"]) && isset($this->general_opt["rrdtool_title_fontsize"]))
	          $this->commandLine .= " --font TITLE:".$this->general_opt["rrdtool_title_fontsize"].":".$this->general_opt["rrdtool_title_font"]." ";
	       if (isset($this->general_opt["rrdtool_unit_font"]) && isset($this->general_opt["rrdtool_unit_fontsize"]))
	          $this->commandLine .= " --font UNIT:".$this->general_opt["rrdtool_unit_fontsize"].":".$this->general_opt["rrdtool_unit_font"]." ";
	       if (isset($this->general_opt["rrdtool_axis_font"]) && isset($this->general_opt["rrdtool_axis_fontsize"]))
	          $this->commandLine .= " --font AXIS:".$this->general_opt["rrdtool_axis_fontsize"].":".$this->general_opt["rrdtool_axis_font"]." ";
	       if (isset($this->general_opt["rrdtool_title_font"]) && isset($this->general_opt["rrdtool_title_fontsize"]))
	          $this->commandLine .= " --font WATERMARK:".$this->general_opt["rrdtool_title_fontsize"].":".$this->general_opt["rrdtool_title_font"]." ";
	       if (isset($this->general_opt["rrdtool_legend_title"]) && isset($this->general_opt["rrdtool_legend_fontsize"]))
	          $this->commandLine .= " --font LEGEND:".$this->general_opt["rrdtool_legend_fontsize"].":".$this->general_opt["rrdtool_legend_title"]." ";
	    }
	}

	public function setTemplate($template_id = NULL) {
		if (!isset($template_id)|| !$template_id){
			if ($this->indexData["host_name"] != "_Module_Meta") {
				/*
				 * graph is based on real host/service
				 */
				$this->getDefaultGraphTemplate();
				$this->setTemplateInformations();			
			} else {
				/*
				 * Graph is based on a module check point
				 */
				$tab = split("_", $this->indexData["service_description"]);
				$DBRESULT =& $this->DB->query("SELECT graph_id FROM meta_service WHERE meta_id = '".$tab[1]."'");
				$tempRes =& $DBRESULT->fetchRow();
				$DBRESULT->free();
				$this->template_id = $tempRes["graph_id"];
				$this->setTemplateInformations();
				unset($tempRes);
				unset($tab);
			}
		} else {
			$this->template_id = htmlentities($_GET["template_id"], ENT_QUOTES);
			$this->setTemplateInformations();
		}
	}
	
	public function getServiceGraphID()	{
		$service_id = $this->indexData["service_id"];
		while (1) {
			$DBRESULT =& $this->DB->query("SELECT esi.graph_id, service_template_model_stm_id FROM service, extended_service_information esi WHERE service_id = '".$service_id."' AND esi.service_service_id = service_id LIMIT 1");
			$row =& $DBRESULT->fetchRow();
			if ($row["graph_id"]) {
				$this->graphID = $row["graph_id"];
				return $this->graphID;
			} else if ($row["service_template_model_stm_id"])
				$service_id = $row["service_template_model_stm_id"];
			else
				break;
		}
		return $this->graphID;
	}

	public function getDefaultGraphTemplate() {
		$tmp_id = $this->getServiceGraphID();
		if ($tmp_id != "") {
			$this->template_id = $tmp_id;
			return;
		} else {
			$command_id = getMyServiceField($this->indexData["service_id"], "command_command_id");
			$DBRESULT =& $this->DB->query("SELECT graph_id FROM command WHERE `command_id` = '".$command_id."'");
			if ($DBRESULT->numRows())	{
				$gt =& $DBRESULT->fetchRow();
				$this->template_id = $gt["graph_id"];
				unset($gt);
				return;
			}
			$DBRESULT->free();
			unset($command_id);
		}
		$DBRESULT =& $this->DB->query("SELECT graph_id FROM giv_graphs_template WHERE default_tpl1 = '1' LIMIT 1");
		if ($DBRESULT->numRows())	{
			$gt =& $DBRESULT->fetchRow();
			$this->template_id = $gt["graph_id"];
			unset($gt);
			$DBRESULT->free();
			return;
		}
	}
	
	public function setTemplateInformations() {
		$DBRESULT =& $this->DB->query("SELECT * FROM giv_graphs_template WHERE graph_id = '".$this->template_id."' LIMIT 1");
		$this->templateInformations =& $DBRESULT->fetchRow();
		$DBRESULT->free();
	}
	
	public function addInformationToCommandLine() {
		/*
		 * Init Graph Template Value
		 */
		if (isset($this->templateInformations["bg_grid_color"]) && $this->templateInformations["bg_grid_color"])
			$this->commandLine .= "--color CANVAS".$this->templateInformations["bg_grid_color"]." ";
	
		if (isset($this->templateInformations["bg_color"]) && $this->templateInformations["bg_color"])
			$this->commandLine .= "--color BACK".$this->templateInformations["bg_color"]." ";
		else
			$this->commandLine .= "--color BACK#F0F0F0 ";
	
		if (isset($this->templateInformations["police_color"]) && $this->templateInformations["police_color"])
			$this->commandLine .= "--color FONT".$this->templateInformations["police_color"]." ";
		if (isset($this->templateInformations["grid_main_color"]) && $this->templateInformations["grid_main_color"])
			$this->commandLine .= "--color MGRID".$this->templateInformations["grid_main_color"]." ";
		if (isset($this->templateInformations["grid_sec_color"]) && $this->templateInformations["grid_sec_color"])
			$this->commandLine .= "--color GRID".$this->templateInformations["grid_sec_color"]." ";
		if (isset($this->templateInformations["contour_cub_color"]) && $this->templateInformations["contour_cub_color"])
			$this->commandLine .= "--color FRAME".$this->templateInformations["contour_cub_color"]." ";
		if (isset($this->templateInformations["col_arrow"]) && $this->templateInformations["col_arrow"])
			$this->commandLine .= "--color ARROW".$this->templateInformations["col_arrow"]." ";
		if (isset($this->templateInformations["col_top"]) && $this->templateInformations["col_top"])
			$this->commandLine .= "--color SHADEA".$this->templateInformations["col_top"]." ";
		if (isset($this->templateInformations["col_bot"]) && $this->templateInformations["col_bot"])
			$this->commandLine .= "--color SHADEB".$this->templateInformations["col_bot"]." ";
		
		if (isset($this->templateInformations["lower_limit"]) && $this->templateInformations["lower_limit"] != NULL)
			$this->commandLine .= "--lower-limit ".$this->templateInformations["lower_limit"]." ";
		if (isset($this->templateInformations["upper_limit"]) && $this->templateInformations["upper_limit"] != NULL)
			$this->commandLine .= "--upper-limit ".$this->templateInformations["upper_limit"]." ";
		if ((isset($this->templateInformations["lower_limit"]) && $this->templateInformations["lower_limit"] != NULL) || (isset($this->templateInformations["upper_limit"]) && $this->templateInformations["upper_limit"] != NULL))
			$this->commandLine .= "--rigid --alt-autoscale-max ";
		
		$this->gprintScaleOption = "%s"; 
		if ($this->templateInformations["scaled"] == "0"){ 
	    	# Disable y-axis scaling 
			$this->commandLine .= " -X0 "; 
	        # Suppress Scaling in Text Output 
	        $this->gprintScaleOption = ""; 
	    } 
	}

	private function setFilename() {
		$this->filename = $this->indexData["host_name"]. "-".$this->indexData["service_description"];
		$this->filename = str_replace("#S#", "/", $this->filename);
		$this->filename = str_replace("#BS#", "\\", $this->filename);
	}

	/*
	 * Get index Data
	 */
	private function getIndexData() {
		$DBRESULT =& $this->DBC->query("SELECT * FROM index_data WHERE id = '".$this->index."' LIMIT 1");
		if (!$DBRESULT->numRows()) {
			$this->indexData = 0;
		} else {
			$this->indexData =& $DBRESULT->fetchRow();
			/*
			 * Check Meta Service description
			 */
			if (preg_match("/meta_([0-9]*)/", $this->indexData["service_description"], $matches)){
				$DBRESULT_meta =& $this->DB->query("SELECT meta_name FROM meta_service WHERE `meta_id` = '".$matches[1]."'");
				$meta =& $DBRESULT_meta->fetchRow();
				$this->indexData["service_description"] = $meta["meta_name"];
				unset($meta);
				$DBRESULT_meta->free();
			}
			$this->indexData["host_name"] = str_replace("#S#", "/", $this->indexData["host_name"]);
			$this->indexData["host_name"] = str_replace("#BS#", "\\", $this->indexData["host_name"]);
			$this->indexData["service_description"] = str_replace("#S#", "/", $this->indexData["service_description"]);
			$this->indexData["service_description"] = str_replace("#BS#", "\\", $this->indexData["service_description"]);
		}
		$DBRESULT->free();	
	}
	
	/*
	 * Set General options 
	 */
	public function setGeneralOption() {
		$DBRESULT =& $this->DB->query("SELECT * FROM options");
		while ($opt =& $DBRESULT->fetchRow()) {
			$this->general_opt[$opt['key']] = $opt['value'];  
		}
		$DBRESULT->free();
		unset($opt);
	}
	
	/*
	 * Get user id from session_id
	 */
	private function getUserIdFromSID() {
		$DBRESULT =& $this->DB->query("SELECT user_id FROM session WHERE session_id = '".$this->session_id."' LIMIT 1");
		$admin =& $DBRESULT->fetchRow();
		unset($DBRESULT);
		if (isset($admin["user_id"])) {
			$this->user_id = $admin["user_id"];
		}
	}
	
	/*
	 * Send headers information for web server
	 */
	public function header() {
		global $HTTP_ACCEPT_ENCODING;
	   
		if (headers_sent()){
	        $encoding = false;
	    } else if (strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false){
	        $encoding = 'x-gzip';
	    } else if (strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false){
	        $encoding = 'gzip';
	    } else {
	        $encoding = false;
	    }
 		
		header("Content-Type: image/png");
		header("Content-Transfer-Encoding: binary");
		//header("Content-Disposition: attachment; filename=\"$this->filename\";");
		if ($this->compress && $encoding)
			header('Content-Encoding: '.$encoding);
	}
	
	/*
	 * Display Start and end time on graph
	 */
	public function addCommentTime() {
		
		$rrd_time  = addslashes($this->GMT->getDate("Y\/m\/d G:i", $this->start));
		$rrd_time = str_replace(":", "\:", $rrd_time);
		$rrd_time2 = addslashes($this->GMT->getDate("Y\/m\/d G:i", $this->end)) ;
		$rrd_time2 = str_replace(":", "\:", $rrd_time2);
		
		$this->fillCommandLine("COMMENT:\" From $rrd_time to $rrd_time2 \\c\"");
	}
	
	public function displayError() {
		$image = imagecreate(250,100);
		$fond = imagecolorallocate($image,0xEF,0xF2,0xFB);		
		header("Content-Type: image/gif");
		imagegif($image);
		exit;
	}
	
	public function setTitle() {
		if ($this->indexData["host_name"] != "_Module_Meta")
			$this->title = $this->indexData["service_description"]." "._("graph on")." ".$this->indexData["host_name"];
		else
			$this->title = _("Graph")." ".$this->indexData["service_description"] ;
	}
	
	public function initCommandLine() {
		$this->setTitle();
		$this->commandLine = $this->general_opt["rrdtool_path_bin"];
		$this->commandLine .= " graph - ";
		$this->fillCommandLine("--interlaced");
		if (isset($this->templateInformations["base"]) && $this->templateInformations["base"])
			$this->fillCommandLine("-b ".$this->templateInformations["base"]);
		$this->fillCommandLine("--imgformat PNG");
		$this->fillCommandLine("--width=".$this->width);
		$this->fillCommandLine("--height=".$this->height);
		$this->fillCommandLine("--title='".$this->title."'");
		$this->fillCommandLine("--vertical-label='".$this->templateInformations["vertical_label"]."'");
		$this->addRRDToolProperties();
		$this->addInformationToCommandLine();
	}
	
	public function endCommandLine() {
		$this->commandLine .= " 2>&1"; 
	}
	
	public function addCommandLineTimeLimit($flag) {
		if (isset($flag) && $flag == 0) {
			if ($this->GMT->used()) {
				$this->start 	= $this->GMT->getUTCDate($this->start);
				$this->end 		= $this->GMT->getUTCDate($this->end);
			}
		}
		$this->commandLine .= " --start=".$this->start." --end=".$this->end." ";
	}
	
	/*
	 * Concat command line parameters
	 */
	public function fillCommandLine($args) {
		$this->commandLine .= " ".$args." ";
	}
	
	public function displayImageFlow() {
		$this->escapeCommand();
		$this->logCommandLine();
		/*
		 * Send header
		 */
		$this->header();
		
		/*
		 * Send Binary Data
		 */
		$fp = popen($this->commandLine  , 'r');
		if (isset($fp) && $fp ) {
			$str ='';
			while (!feof ($fp)) {
		  		$buffer = fgets($fp, 4096);
		 		$str = $str . $buffer ;
			}
			print $str;
		}
	}
	
	public function logCommandLine() {
		if ($this->general_opt['debug_rrdtool'])
			error_log("[" . date("d/m/Y H:s") ."] RDDTOOL : ".$this->commandLine." \n", 3, $this->general_opt["debug_path"]."rrdtool.log");
	}
	
	public function checkArgument($name, $tab, $defaultValue) {
		if (isset($name) && isset($tab)) {
			if (isset($tab[$name]))
				return htmlentities($tab[$name], ENT_QUOTES);
			else
				return htmlentities($defaultValue, ENT_QUOTES);
		}
	}
	
	public function setTimezone() {
		if ($this->GMT->used())
			$this->commandLine = "export TZ='CMT".$this->GMT->getMyGMTForRRD()."' ; ".$this->commandLine;
	}
	
	public function escapeCommand() {
		$this->commandLine = ereg_replace("(\\\$|`)", "", $this->commandLine);
	}
	
	public function getRRDToolPath(){
		$DBRESULT =& $this->DBC->query("SELECT RRDdatabase_path FROM config LIMIT 1");
		$config =& $DBRESULT->fetchRow();
		$DBRESULT->free();
		$this->dbPath = $config["RRDdatabase_path"];
		unset($config);
	}
	
	public function setActivateMetrics() {
		$DBRESULT =& $this->DB->query("SELECT `metric_id` FROM `ods_view_details` WHERE `index_id` = '".$this->index."' AND `contact_id` = '".$this->user_id."'");
		while ($metric_activate =& $DBRESULT->fetchRow()){
			$this->metricsActivate[$metric_activate["metric_id"]] = $metric_activate["metric_id"];
		}
		$DBRESULT->free();
		unset($metric_activate);
	}

	public function setWidth($width) {
		$this->width = $width;
	}

	public function setHeight($height) {
		$this->height = $height;
	}
}
?>
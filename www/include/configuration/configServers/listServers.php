<?php
/*
 * Copyright 2005-2010 MERETHIS
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
	
	if (!isset($oreon))
		exit();
		
	/*
	 * Connect to NDO database.
	 */
	$pearDBNdo = new CentreonDB("ndo");
		
	include("./include/common/autoNumLimit.php");

	/*
	 * start quickSearch form
	 */
	include_once("./include/common/quickSearch.php");
	
	$LCASearch = "";
	if (isset($search))
		$LCASearch = " WHERE name LIKE '%".htmlentities($search, ENT_QUOTES)."%'";

	$DBRESULT = & $pearDB->query("SELECT COUNT(*) FROM `nagios_server`");
	
	$tmp = & $DBRESULT->fetchRow();
	$rows = $tmp["COUNT(*)"];

	/*
	 * nagios servers comes from DB 
	 */
	$nagios_servers = array();
	$DBRESULT =& $pearDB->query("SELECT * FROM `nagios_server` ORDER BY name");
	while ($nagios_server = $DBRESULT->fetchRow()) {
		$nagios_servers[$nagios_server["id"]] = $nagios_server["name"];
	}
	$DBRESULT->free();
	
	/*
	 * Get information info RTM
	 */
	$ndoPrefix = getNDOPrefix();
	$nagiosInfo = array();
	$DBRESULT =& $pearDBNdo->query("SELECT program_start_time, is_currently_running, process_id, p.instance_id, instance_name FROM `".$ndoPrefix."programstatus` p, ".$ndoPrefix."instances i WHERE p.instance_id = i.instance_id");
	while ($info = $DBRESULT->fetchRow()) {
		$nagiosInfo[$info["instance_name"]] = $info;
	}
	$DBRESULT->free();
	
	/*
	 * Get Nagios / Icinga version
	 */	
	$pollerNumber = count($nagios_servers);
	if ($pollerNumber == 0) {
		$pollerNumber = 1;
	}
	$DBRESULT =& $pearDBNdo->query("SELECT p.instance_id, program_version, program_name, instance_name FROM `".$ndoPrefix."processevents` p, ".$ndoPrefix."instances i WHERE p.instance_id = i.instance_id ORDER BY processevent_id DESC LIMIT $pollerNumber");
	while ($info = $DBRESULT->fetchRow()) {
		$nagiosInfo[$info["instance_name"]]["version"] = $info["program_name"] . " " . $info["program_version"];
	}
	$DBRESULT->free();
	
	include("./include/common/checkPagination.php");

	/*
	 * Smarty template Init
	 */
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	/*
	 * start header menu
	 */
	$tpl->assign("headerMenu_icone", "<img src='./img/icones/16x16/pin_red.gif'>");
	$tpl->assign("headerMenu_name", _("Name"));
	$tpl->assign("headerMenu_ip_address", _("IP Address"));
	$tpl->assign("headerMenu_localisation", _("Localhost"));
	$tpl->assign("headerMenu_is_running", _("Is running ?"));
	$tpl->assign("headerMenu_pid", _("PID"));
	$tpl->assign("headerMenu_version", _("Version"));
	$tpl->assign("headerMenu_startTime", _("Start time"));
	$tpl->assign("headerMenu_status", _("Status"));
	$tpl->assign("headerMenu_options", _("Options"));
	
	/*
	 * Nagios list
	 */
	$rq = "SELECT id, name, ns_activate, ns_ip_address, localhost FROM `nagios_server` $LCASearch ORDER BY name LIMIT ".$num * $limit.", ".$limit;
	$DBRESULT =& $pearDB->query($rq);
	
	$form = new HTML_QuickForm('select_form', 'POST', "?p=".$p);
	
	/*
	 * Different style between each lines
	 */
	$style = "one";
	
	/*
	 * Fill a tab with a mutlidimensionnal Array we put in $tpl
	 */
	$elemArr = array();
	for ($i = 0; $config = $DBRESULT->fetchRow(); $i++) {		
		$moptions = "";
		$selectedElements =& $form->addElement('checkbox', "select[".$config['id']."]");	
		if ($config["ns_activate"])
			$moptions .= "<a href='main.php?p=".$p."&server_id=".$config['id']."&o=u&limit=".$limit."&num=".$num."&search=".$search."'><img src='img/icones/16x16/element_previous.gif' border='0' alt='"._("Disabled")."'></a>&nbsp;&nbsp;";
		else
			$moptions .= "<a href='main.php?p=".$p."&server_id=".$config['id']."&o=s&limit=".$limit."&num=".$num."&search=".$search."'><img src='img/icones/16x16/element_next.gif' border='0' alt='"._("Enabled")."'></a>&nbsp;&nbsp;";
		$moptions .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$moptions .= "<input onKeypress=\"if(event.keyCode > 31 && (event.keyCode < 45 || event.keyCode > 57)) event.returnValue = false; if(event.which > 31 && (event.which < 45 || event.which > 57)) return false;\" maxlength=\"3\" size=\"3\" value='1' style=\"margin-bottom:0px;\" name='dupNbr[".$config['id']."]'></input>";
		$elemArr[$i] = array("MenuClass"=>"list_".$style, 
						"RowMenu_select"=>$selectedElements->toHtml(),
						"RowMenu_name"=>$config["name"],
						"RowMenu_ip_address"=>$config["ns_ip_address"],
						"RowMenu_link"=>"?p=".$p."&o=c&server_id=".$config['id'],
						"RowMenu_localisation"=>$config["localhost"] ? _("Yes") : "-",
						"RowMenu_is_running" => ($nagiosInfo[$config["name"]]["is_currently_running"] == 1) ? _("Yes") : _("No"),
						"RowMenu_version" => $nagiosInfo[$config["name"]]["version"],
						"RowMenu_startTime" => ($nagiosInfo[$config["name"]]["is_currently_running"] == 1) ? $nagiosInfo[$config["name"]]["program_start_time"] : "-",
						"RowMenu_pid" => ($nagiosInfo[$config["name"]]["is_currently_running"] == 1) ? $nagiosInfo[$config["name"]]["process_id"] : "-",
						"RowMenu_status"=>$config["ns_activate"] ? _("Enabled") : _("Disabled"),
						"RowMenu_options"=>$moptions);
		$style != "two" ? $style = "two" : $style = "one";	
	}
	$tpl->assign("elemArr", $elemArr);
	
	/*
	 * Different messages we put in the template
	 */
	$tpl->assign('msg', array ("addL"=>"?p=".$p."&o=a", "addT"=>_("Add"), "delConfirm"=>_("Do you confirm the deletion ?")));

	/*
	 * Toolbar select 
	 */
	?>
	<script type="text/javascript">
	function setO(_i) {
		document.forms['form'].elements['o'].value = _i;
	}
	</SCRIPT>
	<?php
	$attrs = array(
		'onchange'=>"javascript: " .
				"if (this.form.elements['o1'].selectedIndex == 1 && confirm('"._("Do you confirm the duplication ?")."')) {" .
				" 	setO(this.form.elements['o1'].value); submit();} " .
				"else if (this.form.elements['o1'].selectedIndex == 2 && confirm('"._("Do you confirm the deletion ?")."')) {" .
				" 	setO(this.form.elements['o1'].value); submit();} " .
				"else if (this.form.elements['o1'].selectedIndex == 3) {" .
				" 	setO(this.form.elements['o1'].value); submit();} " .
				"");	  
    $form->addElement('select', 'o1', NULL, array(NULL=>_("More actions..."), "m"=>_("Duplicate"), "d"=>_("Delete")), $attrs);
	$form->setDefaults(array('o1' => NULL));
	$o1 =& $form->getElement('o1');
	$o1->setValue(NULL);
	
	$attrs = array(
		'onchange'=>"javascript: " .
				"if (this.form.elements['o2'].selectedIndex == 1 && confirm('"._("Do you confirm the duplication ?")."')) {" .
				" 	setO(this.form.elements['o2'].value); submit();} " .
				"else if (this.form.elements['o2'].selectedIndex == 2 && confirm('"._("Do you confirm the deletion ?")."')) {" .
				" 	setO(this.form.elements['o2'].value); submit();} " .
				"else if (this.form.elements['o2'].selectedIndex == 3) {" .
				" 	setO(this.form.elements['o2'].value); submit();} " .
				"");
    $form->addElement('select', 'o2', NULL, array(NULL=>_("More actions..."), "m"=>_("Duplicate"), "d"=>_("Delete")), $attrs);
	$form->setDefaults(array('o2' => NULL));

	$o2 =& $form->getElement('o2');
	$o2->setValue(NULL);
	
	$tpl->assign('limit', $limit);

	/*
	 * Apply a template definition
	 */
	$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$form->accept($renderer);	
	$tpl->assign('form', $renderer->toArray());
	$tpl->display("listServers.ihtml");
?>
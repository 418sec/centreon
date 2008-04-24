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
	if (!isset($oreon))
		exit();

	$pagination = "maxViewMonitoring";
	include("./include/common/autoNumLimit.php");

	# set limit & num
	$DBRESULT =& $pearDB->query("SELECT maxViewMonitoring FROM general_opt LIMIT 1");
	if (PEAR::isError($DBRESULT))
		print "Mysql Error : ".$DBRESULT->getMessage();
	$gopt = array_map("myDecode", $DBRESULT->fetchRow());

	!isset($_GET["sort_types"]) ? $sort_types = 0 : $sort_types = $_GET["sort_types"];
	!isset($_GET["order"]) ? $order = 'ASC' : $order = $_GET["order"];

	!isset($_GET["num"]) ? $num = 0 : $num = $_GET["num"];
//	!isset($_GET["limit"]) ? $limit = 0 : $limit = $_GET["limit"];
	!isset($_GET["search_type_host"]) ? $search_type_host = 1 : $search_type_host = $_GET["search_type_host"];
	!isset($_GET["search_type_service"]) ? $search_type_service = 1 : $search_type_service = $_GET["search_type_service"];
	!isset($_GET["sort_type"]) ? $sort_type = "host_name" : $sort_type = $_GET["sort_type"];

	# start quickSearch form
	include_once("./include/common/quickSearch.php");
	# end quickSearch form

	$tab_class = array("0" => "list_one", "1" => "list_two");
	$rows = 10;

	include_once("makeJS_serviceGridByHG.php");

	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl, "/templates/");

	$tpl->assign("p", $p);
	$tpl->assign('o', $o);
	$tpl->assign("sort_types", $sort_types);
	$tpl->assign("num", $num);
	$tpl->assign("limit", $limit);
	$tpl->assign("mon_host", _("Hosts"));
	$tpl->assign("mon_status", _("Status"));
	$tpl->assign("mon_ip", _("IP"));
	$tpl->assign("mon_last_check", _("Last Check"));
	$tpl->assign("mon_duration", _("Duration"));
	$tpl->assign("mon_status_information", _("Status information"));

	$form = new HTML_QuickForm('select_form', 'GET', "?p=".$p);

	$tpl->assign("order", strtolower($order));
	$tab_order = array("sort_asc" => "sort_desc", "sort_desc" => "sort_asc");
	$tpl->assign("tab_order", $tab_order);

	?>
	<script type="text/javascript">
	function setO(_i) {
		document.forms['form'].elements['cmd'].value = _i;
		document.forms['form'].elements['o1'].selectedIndex = 0;
		document.forms['form'].elements['o2'].selectedIndex = 0;
	}
	</SCRIPT>
	<?php

	$attrs = array(	'onchange'=>"javascript: setO(this.form.elements['o1'].value); submit();");
    $form->addElement('select', 'o1', NULL, array(	NULL	=>	_("More actions..."), 
													"3"		=>	_("Verification Check"), 
													"4"		=>	_("Verification Check (Forced)"), 
													"70" 	=> 	_("Services : Acknowledge"), 
													"71" 	=> 	_("Services : Disacknowledge"),
													"80" 	=> 	_("Services : Enable Notification"), 
													"81" 	=> 	_("Services : Disable Notification"),
													"90" 	=> 	_("Services : Enable Check"), 
													"91" 	=> 	_("Services : Disable Check"),
													"72" 	=> 	_("Hosts : Acknowledge"),
													"73" 	=> 	_("Hosts : Disacknowledge"), 
													"82" 	=> 	_("Hosts : Enable Notification"),
													"83" 	=> 	_("Hosts : Disable Notification"),
													"92" 	=> 	_("Hosts : Enable Check"),
													"93" 	=> 	_("Hosts : Disable Check")), $attrs);

	$form->setDefaults(array('o1' => NULL));
	$o1 =& $form->getElement('o1');
	$o1->setValue(NULL);

	$attrs = array('onchange'=>"javascript: setO(this.form.elements['o2'].value); submit();");
    $form->addElement('select', 'o2', NULL, array(	NULL	=>	_("More actions..."), 
													"3"		=>	_("Verification Check"), 
													"4"		=>	_("Verification Check (Forced)"), 
													"70" 	=> 	_("Services : Acknowledge"), 
													"71" 	=> 	_("Services : Disacknowledge"),
													"80" 	=> 	_("Services : Enable Notification"), 
													"81" 	=> 	_("Services : Disable Notification"),
													"90" 	=> 	_("Services : Enable Check"), 
													"91" 	=> 	_("Services : Disable Check"),
													"72" 	=> 	_("Hosts : Acknowledge"),
													"73" 	=> 	_("Hosts : Disacknowledge"), 
													"82" 	=> 	_("Hosts : Enable Notification"),
													"83" 	=> 	_("Hosts : Disable Notification"),
													"92" 	=> 	_("Hosts : Enable Check"),
													"93" 	=> 	_("Hosts : Disable Check")), $attrs);
	$form->setDefaults(array('o2' => NULL));
	$o2 =& $form->getElement('o2');
	$o2->setValue(NULL);
	$o2->setSelected(NULL);
	$tpl->assign('limit', $limit);

	$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$form->accept($renderer);

	$tpl->assign('form', $renderer->toArray());
	$tpl->display("serviceGrid.ihtml");

	$tpl = new Smarty();
	$tpl = initSmartyTpl("./", $tpl);

	if ($oreon->optGen["nagios_version"] == 2 && isset($pgr_nagios_stat["created"]))
		$pgr_nagios_stat["created"] = date("d/m/Y G:i", $pgr_nagios_stat["created"]);
	else
		$pgr_nagios_stat["created"] = 0;
?>
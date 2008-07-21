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
	#
	## Database retrieve information for Service
	#

	function myDecodeService($arg)	{
		$arg = str_replace('#BR#', "\\n", $arg);
		$arg = str_replace('#T#', "\\t", $arg);
		$arg = str_replace('#R#', "\\r", $arg);
		$arg = str_replace('#S#', "/", $arg);
		$arg = str_replace('#BS#', "\\", $arg);
		return html_entity_decode($arg, ENT_QUOTES);
	}

	$service = array();
	if (($o == "c" || $o == "w") && $service_id)	{
		
		$DBRESULT =& $pearDB->query("SELECT * FROM service, extended_service_information esi WHERE service_id = '".$service_id."' AND esi.service_service_id = service_id LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		# Set base value
		$service = array_map("myDecodeService", $DBRESULT->fetchRow());
		
		# Grab hostgroup || host
		$DBRESULT =& $pearDB->query("SELECT * FROM host_service_relation hsr WHERE hsr.service_service_id = '".$service_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		while ($parent = $DBRESULT->fetchRow())	{
			if ($parent["host_host_id"])
				$service["service_hPars"][$parent["host_host_id"]] = $parent["host_host_id"];
			else if ($parent["hostgroup_hg_id"])
				$service["service_hgPars"][$parent["hostgroup_hg_id"]] = $parent["hostgroup_hg_id"];
		}
		
		# Set Service Notification Options
		$tmp = explode(',', $service["service_notification_options"]);
		foreach ($tmp as $key => $value)
			$service["service_notifOpts"][trim($value)] = 1;
		
		# Set Stalking Options
		$tmp = explode(',', $service["service_stalking_options"]);
		foreach ($tmp as $key => $value)
			$service["service_stalOpts"][trim($value)] = 1;
		$DBRESULT->free();
		
		# Set Contact Group
		$DBRESULT =& $pearDB->query("SELECT DISTINCT contactgroup_cg_id FROM contactgroup_service_relation WHERE service_service_id = '".$service_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $notifCg = $DBRESULT->fetchRow(); $i++)
			$service["service_cgs"][$i] = $notifCg["contactgroup_cg_id"];
		$DBRESULT->free();
		
		# Set Service Group Parents
		$DBRESULT =& $pearDB->query("SELECT DISTINCT servicegroup_sg_id FROM servicegroup_relation WHERE service_service_id = '".$service_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $sg = $DBRESULT->fetchRow(); $i++)
			$service["service_sgs"][$i] = $sg["servicegroup_sg_id"];
		$DBRESULT->free();
		
		# Set Traps
		$DBRESULT =& $pearDB->query("SELECT DISTINCT traps_id FROM traps_service_relation WHERE service_id = '".$service_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $trap = $DBRESULT->fetchRow(); $i++)
			$service["service_traps"][$i] = $trap["traps_id"];
		$DBRESULT->free();
		
		# Set Categories
		$DBRESULT =& $pearDB->query("SELECT DISTINCT sc_id FROM service_categories_relation WHERE service_service_id = '".$service_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $service_category = $DBRESULT->fetchRow(); $i++)
			$service["service_categories"][$i] = $service_category["sc_id"];
		$DBRESULT->free();
	}
	#
	## Database retrieve information for differents elements list we need on the page
	#
	# Hosts comes from DB -> Store in $hosts Array
	$hosts = array();
	if ($is_admin)
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name FROM host WHERE host_register = '1' ORDER BY host_name");
	else
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name FROM host WHERE host_id IN (".$lcaHostStr.") AND host_register = '1' ORDER BY host_name");		
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($host = $DBRESULT->fetchRow())
		$hosts[$host["host_id"]] = $host["host_name"];
	$DBRESULT->free();
	
	# Service Templates comes from DB -> Store in $svTpls Array
	$svTpls = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT service_id, service_description, service_template_model_stm_id FROM service WHERE service_register = '0' AND service_id != '".$service_id."' ORDER BY service_description");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($svTpl = $DBRESULT->fetchRow())	{
		if (!$svTpl["service_description"])
			$svTpl["service_description"] = getMyServiceName($svTpl["service_template_model_stm_id"])."'";
		else	{
			$svTpl["service_description"] = str_replace('#S#', "/", $svTpl["service_description"]);
			$svTpl["service_description"] = str_replace('#BS#', "\\", $svTpl["service_description"]);
		}
		$svTpls[$svTpl["service_id"]] = $svTpl["service_description"];
	}
	$DBRESULT->free();
	
	# HostGroups comes from DB -> Store in $hgs Array
	$hgs = array();
	$lcaSTR = "";
	if (!$is_admin)
		$lcaSTR = " WHERE hg_id IN (".$lcaHGStr.") ";
	$DBRESULT =& $pearDB->query("SELECT hg_id, hg_name FROM hostgroup $lcaSTR ORDER BY hg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($hg = $DBRESULT->fetchRow())
		$hgs[$hg["hg_id"]] = $hg["hg_name"];
	$DBRESULT->free();

	# Timeperiods comes from DB -> Store in $tps Array
	$tps = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT tp_id, tp_name FROM timeperiod ORDER BY tp_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($tp = $DBRESULT->fetchRow())
		$tps[$tp["tp_id"]] = $tp["tp_name"];
	$DBRESULT->free();

	# Check commands comes from DB -> Store in $checkCmds Array
	$checkCmds = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT command_id, command_name FROM command WHERE command_type = '2' ORDER BY command_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($checkCmd = $DBRESULT->fetchRow())
		$checkCmds[$checkCmd["command_id"]] = $checkCmd["command_name"];
	$DBRESULT->free();

	# Check commands comes from DB -> Store in $checkCmdEvent Array
	$checkCmdEvent = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT command_id, command_name FROM command WHERE command_type = '2' OR command_type = '3' ORDER BY command_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($checkCmd = $DBRESULT->fetchRow())
		$checkCmdEvent[$checkCmd["command_id"]] = $checkCmd["command_name"];
	$DBRESULT->free();

	# Contact Groups comes from DB -> Store in $notifCcts Array
	$notifCgs = array();
	$DBRESULT =& $pearDB->query("SELECT cg_id, cg_name FROM contactgroup ORDER BY cg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($notifCg = $DBRESULT->fetchRow())
		$notifCgs[$notifCg["cg_id"]] = $notifCg["cg_name"];
	$DBRESULT->free();

	# Service Groups comes from DB -> Store in $sgs Array
	$sgs = array();
//	$DBRESULT =& $pearDB->query("SELECT sg_id, sg_name FROM servicegroup WHERE sg_id IN (".$lcaServiceGroupStr.") ORDER BY sg_name");
	$DBRESULT =& $pearDB->query("SELECT sg_id, sg_name FROM servicegroup ORDER BY sg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($sg = $DBRESULT->fetchRow())
		$sgs[$sg["sg_id"]] = $sg["sg_name"];
	$DBRESULT->free();

	# Graphs Template comes from DB -> Store in $graphTpls Array
	$graphTpls = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT graph_id, name FROM giv_graphs_template ORDER BY name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($graphTpl = $DBRESULT->fetchRow())
		$graphTpls[$graphTpl["graph_id"]] = $graphTpl["name"];
	$DBRESULT->free();

	# service categories comes from DB -> Store in $service_categories Array
	$service_categories = array();
	$DBRESULT =& $pearDB->query("SELECT sc_name, sc_id FROM service_categories ORDER BY sc_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($service_categorie = $DBRESULT->fetchRow())
		$service_categories[$service_categorie["sc_id"]] = $service_categorie["sc_name"];
	$DBRESULT->free();

	# Traps definition comes from DB -> Store in $traps Array
	$traps = array();
	$DBRESULT =& $pearDB->query("SELECT traps_id, traps_name FROM traps ORDER BY traps_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($trap = $DBRESULT->fetchRow())
		$traps[$trap["traps_id"]] = $trap["traps_name"];
	$DBRESULT->free();
	
	# IMG comes from DB -> Store in $extImg Array
	$extImg = array();
	$extImg = return_image_list(1);
	
	
	/*
	 *  Service on demand macro stored in DB
	 */
	$j = 0;		
	$DBRESULT =& $pearDB->query("SELECT svc_macro_id, svc_macro_name, svc_macro_value, svc_svc_id FROM on_demand_macro_service WHERE svc_svc_id = '". $service_id ."' ORDER BY `svc_macro_id`");
	while($od_macro = $DBRESULT->fetchRow())
	{
		$od_macro_id[$j] = $od_macro["svc_macro_id"];
		$od_macro_name[$j] = str_replace("\$_SERVICE", "", $od_macro["svc_macro_name"]);
		$od_macro_name[$j] = str_replace("\$", "", $od_macro_name[$j]);		
		$od_macro_value[$j] = $od_macro["svc_macro_value"];
		$od_macro_svc_id[$j] = $od_macro["svc_svc_id"];
		$j++;		
	}
	$DBRESULT->free();
	
	#
	# End of "database-retrieved" information
	##########################################################
	##########################################################
	# Var information to format the element
	#
	$attrsText 				= array("size"=>"30");
	$attrsText2				= array("size"=>"6");
	$attrsTextURL 			= array("size"=>"50");
	$attrsAdvSelect_small 	= array("style" => "width: 200px; height: 70px;");
	$attrsAdvSelect 		= array("style" => "width: 200px; height: 100px;");
	$attrsAdvSelect2 		= array("style" => "width: 200px; height: 200px;");
	$attrsTextarea 			= array("rows"=>"5", "cols"=>"40");
	$template 				= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	#
	## Form begin
	#
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add a Service"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify a Service"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View a Service"));
	else if ($o == "mc")
		$form->addElement('header', 'title', _("Massive Change"));

	# Sort 1
	#
	## Service basic information
	#
	$form->addElement('header', 'information', _("General Information"));

	# No possibility to change name and alias, because there's no interest
	if ($o != "mc")
		$form->addElement('text', 'service_description', _("Description"), $attrsText);
	$form->addElement('text', 'service_alias', _("Alias"), $attrsText);
	
	$form->addElement('select', 'service_template_model_stm_id', _("Service Template"), $svTpls);
	$form->addElement('static', 'tplText', _("Using a Template exempts you to fill required fields"));
	
	#
	## Check information
	#
	$form->addElement('header', 'check', _("Service State"));

	$serviceIV[] = &HTML_QuickForm::createElement('radio', 'service_is_volatile', null, _("Yes"), '1');
	$serviceIV[] = &HTML_QuickForm::createElement('radio', 'service_is_volatile', null, _("No"), '0');
	$serviceIV[] = &HTML_QuickForm::createElement('radio', 'service_is_volatile', null, _("Default"), '2');
	$form->addGroup($serviceIV, 'service_is_volatile', _("Is Volatile"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_is_volatile' => '2'));

	$form->addElement('select', 'command_command_id', _("Check Command"), $checkCmds, 'onchange=setArgument(this.form,"command_command_id","example1")');	
	$form->addElement('text', 'command_command_id_arg', _("Args"), $attrsText);
	$form->addElement('text', 'service_max_check_attempts', _("Max Check Attempts"), $attrsText2);
	$form->addElement('text', 'service_normal_check_interval', _("Normal Check Interval"), $attrsText2);
	$form->addElement('text', 'service_retry_check_interval', _("Retry Check Interval"), $attrsText2);

	$serviceEHE[] = &HTML_QuickForm::createElement('radio', 'service_event_handler_enabled', null, _("Yes"), '1');
	$serviceEHE[] = &HTML_QuickForm::createElement('radio', 'service_event_handler_enabled', null, _("No"), '0');
	$serviceEHE[] = &HTML_QuickForm::createElement('radio', 'service_event_handler_enabled', null, _("Default"), '2');
	$form->addGroup($serviceEHE, 'service_event_handler_enabled', _("Event Handler Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_event_handler_enabled' => '2'));
	$form->addElement('select', 'command_command_id2', _("Event Handler"), $checkCmdEvent, 'onchange=setArgument(this.form,"command_command_id2","example2")');
	$form->addElement('text', 'command_command_id_arg2', _("Args"), $attrsText);

	$serviceACE[] = &HTML_QuickForm::createElement('radio', 'service_active_checks_enabled', null, _("Yes"), '1');
	$serviceACE[] = &HTML_QuickForm::createElement('radio', 'service_active_checks_enabled', null, _("No"), '0');
	$serviceACE[] = &HTML_QuickForm::createElement('radio', 'service_active_checks_enabled', null, _("Default"), '2');
	$form->addGroup($serviceACE, 'service_active_checks_enabled', _("Active Checks Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_active_checks_enabled' => '2'));

	$servicePCE[] = &HTML_QuickForm::createElement('radio', 'service_passive_checks_enabled', null, _("Yes"), '1');
	$servicePCE[] = &HTML_QuickForm::createElement('radio', 'service_passive_checks_enabled', null, _("No"), '0');
	$servicePCE[] = &HTML_QuickForm::createElement('radio', 'service_passive_checks_enabled', null, _("Default"), '2');
	$form->addGroup($servicePCE, 'service_passive_checks_enabled', _("Passive Checks Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_passive_checks_enabled' => '2'));

	$form->addElement('select', 'timeperiod_tp_id', _("Check Period"), $tps);

	##
	## Notification informations
	##
	$form->addElement('header', 'notification', _("Notification"));
	$serviceNE[] = &HTML_QuickForm::createElement('radio', 'service_notifications_enabled', null, _("Yes"), '1');
	$serviceNE[] = &HTML_QuickForm::createElement('radio', 'service_notifications_enabled', null, _("No"), '0');
	$serviceNE[] = &HTML_QuickForm::createElement('radio', 'service_notifications_enabled', null, _("Default"), '2');
	$form->addGroup($serviceNE, 'service_notifications_enabled', _("Notification Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_notifications_enabled' => '2'));
	
	if ($o == "mc")	{
		$mc_mod_cgs = array();
		$mc_mod_cgs[] = &HTML_QuickForm::createElement('radio', 'mc_mod_cgs', null, _("Incremental"), '0');
		$mc_mod_cgs[] = &HTML_QuickForm::createElement('radio', 'mc_mod_cgs', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_cgs, 'mc_mod_cgs', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_cgs'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'service_cgs', _("Implied ContactGroups"), $notifCgs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

	$form->addElement('text', 'service_notification_interval', _("Notification Interval"), $attrsText2);
	$form->addElement('select', 'timeperiod_tp_id2', _("Notification Period"), $tps);

 	$serviceNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', 'Warning');
	$serviceNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unknown');
	$serviceNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', 'Critical');
	$serviceNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', 'Recovery');
	if ($oreon->user->get_version() == 2)
		$serviceNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'f', '&nbsp;', 'Flapping');
	$form->addGroup($serviceNotifOpt, 'service_notifOpts', _("Notification Type"), '&nbsp;&nbsp;');

 	$serviceStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'o', '&nbsp;', 'Ok');
	$serviceStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', 'Warning');
	$serviceStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unknown');
	$serviceStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', 'Critical');
	$form->addGroup($serviceStalOpt, 'service_stalOpts', _("Stalking Options"), '&nbsp;&nbsp;');

	#
	## Further informations
	#
	$form->addElement('header', 'furtherInfos', _("Additional Information"));
	$serviceActivation[] = &HTML_QuickForm::createElement('radio', 'service_activate', null, _("Enabled"), '1');
	$serviceActivation[] = &HTML_QuickForm::createElement('radio', 'service_activate', null, _("Disabled"), '0');
	$form->addGroup($serviceActivation, 'service_activate', _("Status"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_activate' => '1'));
	$form->addElement('textarea', 'service_comment', _("Comments"), $attrsTextarea);
	
	#
	## Sort 2 - Service Relations
	#
	if ($o == "a")
		$form->addElement('header', 'title2', _("Add relations"));
	else if ($o == "c")
		$form->addElement('header', 'title2', _("Modify relations"));
	else if ($o == "w")
		$form->addElement('header', 'title2', _("View relations"));
	else if ($o == "mc")
		$form->addElement('header', 'title2', _("Massive Change"));

	if ($o == "mc")	{
		$mc_mod_Pars = array();
		$mc_mod_Pars[] = &HTML_QuickForm::createElement('radio', 'mc_mod_Pars', null, _("Incremental"), '0');
		$mc_mod_Pars[] = &HTML_QuickForm::createElement('radio', 'mc_mod_Pars', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_Pars, 'mc_mod_Pars', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_Pars'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'service_hPars', _("Linked with Hosts"), $hosts, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

    $ams3 =& $form->addElement('advmultiselect', 'service_hgPars', _("Linked with HostGroups"), $hgs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);
	
	# Service relations
	$form->addElement('header', 'links', _("Relations"));
	if ($o == "mc")	{
		$mc_mod_sgs = array();
		$mc_mod_sgs[] = &HTML_QuickForm::createElement('radio', 'mc_mod_sgs', null, _("Incremental"), '0');
		$mc_mod_sgs[] = &HTML_QuickForm::createElement('radio', 'mc_mod_sgs', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_sgs, 'mc_mod_sgs', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_sgs'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'service_sgs', _("Parent ServiceGroups"), $sgs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

	$form->addElement('header', 'traps', _("SNMP Traps"));
 	if ($o == "mc")	{
		$mc_mod_traps = array();
		$mc_mod_traps[] = &HTML_QuickForm::createElement('radio', 'mc_mod_traps', null, _("Incremental"), '0');
		$mc_mod_traps[] = &HTML_QuickForm::createElement('radio', 'mc_mod_traps', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_traps, 'mc_mod_traps', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_traps'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'service_traps', _("Service Trap Relation"), $traps, $attrsAdvSelect2);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);


	# trap vendor
	$mnftr = array(NULL=>NULL);	
	$DBRESULT =& $pearDB->query("SELECT id, alias FROM traps_vendor order by alias");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while ($rmnftr = $DBRESULT->fetchRow())
		$mnftr[$rmnftr["id"]] =  html_entity_decode($rmnftr["alias"], ENT_QUOTES);
	$mnftr[""] = "_"._("ALL")."_";
	$DBRESULT->free();
	$attrs2 = array(
		'onchange'=>"javascript: " .
				" 	getTrap(this.form.elements['mnftr'].value); return false; ");
	$form->addElement('select', 'mnftr', _("Vendor Name"), $mnftr, $attrs2);
	include("./include/configuration/configObject/traps/ajaxTrap_js.php");
	
	#
	## Sort 3 - Data treatment
	#
	if ($o == "a")
		$form->addElement('header', 'title3', _("Add Data Processing"));
	else if ($o == "c")
		$form->addElement('header', 'title3', _("Modify Data Processing"));
	else if ($o == "w")
		$form->addElement('header', 'title3', _("View Data Processing"));
	else if ($o == "mc")
		$form->addElement('header', 'title2', _("Massive Change"));

	$form->addElement('header', 'treatment', _("Data Processing"));

	$servicePC[] = &HTML_QuickForm::createElement('radio', 'service_parallelize_check', null, _("Yes"), '1');
	$servicePC[] = &HTML_QuickForm::createElement('radio', 'service_parallelize_check', null, _("No"), '0');
	$servicePC[] = &HTML_QuickForm::createElement('radio', 'service_parallelize_check', null, _("Default"), '2');
	$form->addGroup($servicePC, 'service_parallelize_check', _("Parallel Check"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_parallelize_check' => '2'));

	$serviceOOS[] = &HTML_QuickForm::createElement('radio', 'service_obsess_over_service', null, _("Yes"), '1');
	$serviceOOS[] = &HTML_QuickForm::createElement('radio', 'service_obsess_over_service', null, _("No"), '0');
	$serviceOOS[] = &HTML_QuickForm::createElement('radio', 'service_obsess_over_service', null, _("Default"), '2');
	$form->addGroup($serviceOOS, 'service_obsess_over_service', _("Obsess Over Service"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_obsess_over_service' => '2'));

	$serviceCF[] = &HTML_QuickForm::createElement('radio', 'service_check_freshness', null, _("Yes"), '1');
	$serviceCF[] = &HTML_QuickForm::createElement('radio', 'service_check_freshness', null, _("No"), '0');
	$serviceCF[] = &HTML_QuickForm::createElement('radio', 'service_check_freshness', null, _("Default"), '2');
	$form->addGroup($serviceCF, 'service_check_freshness', _("Check Freshness"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_check_freshness' => '2'));

	$serviceFDE[] = &HTML_QuickForm::createElement('radio', 'service_flap_detection_enabled', null, _("Yes"), '1');
	$serviceFDE[] = &HTML_QuickForm::createElement('radio', 'service_flap_detection_enabled', null, _("No"), '0');
	$serviceFDE[] = &HTML_QuickForm::createElement('radio', 'service_flap_detection_enabled', null, _("Default"), '2');
	$form->addGroup($serviceFDE, 'service_flap_detection_enabled', _("Flap Detection Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_flap_detection_enabled' => '2'));

	$form->addElement('text', 'service_freshness_threshold', _("Freshness Threshold"), $attrsText2);
	$form->addElement('text', 'service_low_flap_threshold', _("Low Flap Threshold"), $attrsText2);
	$form->addElement('text', 'service_high_flap_threshold', _("High Flap Threshold"), $attrsText2);

	$servicePPD[] = &HTML_QuickForm::createElement('radio', 'service_process_perf_data', null, _("Yes"), '1');
	$servicePPD[] = &HTML_QuickForm::createElement('radio', 'service_process_perf_data', null, _("No"), '0');
	$servicePPD[] = &HTML_QuickForm::createElement('radio', 'service_process_perf_data', null, _("Default"), '2');
	$form->addGroup($servicePPD, 'service_process_perf_data', _("Process Perf Data"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_process_perf_data' => '2'));

	$serviceRSI[] = &HTML_QuickForm::createElement('radio', 'service_retain_status_information', null, _("Yes"), '1');
	$serviceRSI[] = &HTML_QuickForm::createElement('radio', 'service_retain_status_information', null, _("No"), '0');
	$serviceRSI[] = &HTML_QuickForm::createElement('radio', 'service_retain_status_information', null, _("Default"), '2');
	$form->addGroup($serviceRSI, 'service_retain_status_information', _("Retain Status Information"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_retain_status_information' => '2'));

	$serviceRNI[] = &HTML_QuickForm::createElement('radio', 'service_retain_nonstatus_information', null, _("Yes"), '1');
	$serviceRNI[] = &HTML_QuickForm::createElement('radio', 'service_retain_nonstatus_information', null, _("No"), '0');
	$serviceRNI[] = &HTML_QuickForm::createElement('radio', 'service_retain_nonstatus_information', null, _("Default"), '2');
	$form->addGroup($serviceRNI, 'service_retain_nonstatus_information', _("Retain Non Status Information"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('service_retain_nonstatus_information' => '2'));

	#
	## Sort 4 - Extended Infos
	#
	if ($o == "a")
		$form->addElement('header', 'title4', _("Add an Extended Info"));
	else if ($o == "c")
		$form->addElement('header', 'title4', _("Modify an Extended Info"));
	else if ($o == "w")
		$form->addElement('header', 'title4', _("View an Extended Info"));
	else if ($o == "mc")
		$form->addElement('header', 'title3', _("Massive Change"));

	$form->addElement('header', 'nagios', _("Nagios"));
	if ($oreon->user->get_version() >= 2)
		$form->addElement('text', 'esi_notes', _("Notes"), $attrsText);
	$form->addElement('text', 'esi_notes_url', _("URL"), $attrsTextURL);
	if ($oreon->user->get_version() >= 2)
		$form->addElement('text', 'esi_action_url', _("Action URL"), $attrsTextURL);
	$form->addElement('select', 'esi_icon_image', _("Icon"), $extImg, array("onChange"=>"showLogo('esi_icon_image',this.form.elements['esi_icon_image'].value)"));
	$form->addElement('text', 'esi_icon_image_alt', _("Alt icon"), $attrsText);

	$form->addElement('header', 'oreon', _("Centreon"));
	$form->addElement('select', 'graph_id', _("Graph Template"), $graphTpls);

	$ams3 =& $form->addElement('advmultiselect', 'service_categories', _("Categories"), $service_categories, $attrsAdvSelect_small);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

	#
	## Sort 5 - Macros - Nagios 3
	#
	
	if ($oreon->user->get_version() == 3) {
		if ($o == "a")
			$form->addElement('header', 'title5', _("Add macros"));
		else if ($o == "c")
			$form->addElement('header', 'title5', _("Modify macros"));
		else if ($o == "w")
			$form->addElement('header', 'title5', _("View macros"));
		else if ($o == "mc")
			$form->addElement('header', 'title5', _("Massive Change"));
	
		$form->addElement('header', 'macro', _("Macros"));
		
		$form->addElement('text', 'add_new', _("Add a new macro"), $attrsText2);
		$form->addElement('text', 'macroName', _("Macro name"), $attrsText2);
		$form->addElement('text', 'macroValue', _("Macro value"), $attrsText2);
		$form->addElement('text', 'macroDelete', _("Delete"), $attrsText2);
		
		include_once("makeJS_formService.php");	
		if ($o == "c" || $o == "a" || $o == "mc")
		{			
			for($k=0; isset($od_macro_id[$k]); $k++) {?>				
				<script type="text/javascript">
				globalMacroTabId[<?php echo$k;?>] = <?php echo$od_macro_id[$k];?>;		
				globalMacroTabName[<?php echo$k;?>] = '<?php echo$od_macro_name[$k];?>';
				globalMacroTabValue[<?php echo$k;?>] = '<?php echo$od_macro_value[$k];?>';
				globalMacroTabSvcId[<?php echo$k;?>] = <?php echo$od_macro_svc_id[$k];?>;				
				</script>			
		<?php
			}
		}
	}


	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
	$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
	$form->setDefaults(array('action' => '1'));

	$form->addElement('hidden', 'service_id');
	$reg =& $form->addElement('hidden', 'service_register');
	$reg->setValue("1");
	$service_register = 1;
	$page =& $form->addElement('hidden', 'p');
	$page->setValue($p);
	$redirect =& $form->addElement('hidden', 'o');
	$redirect->setValue($o);
	if (is_array($select))	{
		$select_str = NULL;
		foreach ($select as $key => $value)
			$select_str .= $key.",";
		$select_pear =& $form->addElement('hidden', 'select');
		$select_pear->setValue($select_str);
	}
	
	#
	## Form Rules
	#
	function myReplace()	{
		global $form;
		return (str_replace(" ", "_", $form->getSubmitValue("service_description")));
	}
	$form->applyFilter('__ALL__', 'myTrim');
	$from_list_menu = false;
	if ($o != "mc")	{
		$form->addRule('service_description', _("Compulsory Name"), 'required');
		# If we are using a Template, no need to check the value, we hope there are in the Template
		if (!$form->getSubmitValue("service_template_model_stm_id"))	{
			$form->addRule('command_command_id', _("Compulsory Command"), 'required');
			$form->addRule('service_max_check_attempts', _("Required Field"), 'required');
			$form->addRule('service_normal_check_interval', _("Required Field"), 'required');
			$form->addRule('service_retry_check_interval', _("Required Field"), 'required');
			$form->addRule('timeperiod_tp_id', _("Compulsory Period"), 'required');
			$form->addRule('service_cgs', _("Compulsory Contact Group"), 'required');
			$form->addRule('service_notification_interval', _("Required Field"), 'required');
			$form->addRule('timeperiod_tp_id2', _("Compulsory Period"), 'required');
			$form->addRule('service_notifOpts', _("Compulsory Option"), 'required');
			if (!$form->getSubmitValue("service_hPars"))
				$form->addRule('service_hgPars', _("HostGroup or Host Required"), 'required');
			if (!$form->getSubmitValue("service_hgPars"))
				$form->addRule('service_hPars', _("HostGroup or Host Required"), 'required');
		}
		if (!$form->getSubmitValue("service_hPars"))
			$form->addRule('service_hgPars', _("HostGroup or Host Required"), 'required');
		if (!$form->getSubmitValue("service_hgPars"))
			$form->addRule('service_hPars', _("HostGroup or Host Required"), 'required');
		$form->registerRule('exist', 'callback', 'testServiceExistence');
		$form->addRule('service_description', _("This description is in conflict with another one that is already defined in the selected relation(s)"), 'exist');
		$form->setRequiredNote("<font style='color: red;'>*</font>". _(" Required fields"));
	} else if ($o == "mc")	{
		if ($form->getSubmitValue("submitMC"))
			$from_list_menu = false;
		else
			$from_list_menu = true;
	}
	
	#
	##End of form definition
	#

	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	# Just watch a host information
	if ($o == "w")	{
		if (!$min)
			$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&service_id=".$service_id."'"));
	    $form->setDefaults($service);
		$form->freeze();
	} else if ($o == "c")	{
		# Modify a service information
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($service);
	} else if ($o == "a")	{
		# Add a service information
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	} else if ($o == "mc")	{
		# Massive Change
		$subMC =& $form->addElement('submit', 'submitMC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}

	$tpl->assign('msg', array ("nagios"=>$oreon->user->get_version(), "tpl"=>0/*, "perfparse"=>$oreon->optGen["perfparse_installed"]*/));
	$tpl->assign("sort1", _("Service Configuration"));
	$tpl->assign("sort2", _("Relations"));
	$tpl->assign("sort3", _("Data Processing"));
	$tpl->assign("sort4", _("Service Extended Info"));
	$tpl->assign("sort5", _("Macros"));
	$tpl->assign('javascript', "<script type='text/javascript'>function showLogo(_img_dst, _value) {var _img = document.getElementById(_img_dst + '_img');_img.src = 'include/common/getHiddenImage.php?path=' + _value + '&logo=1' ; }</script>" );		
	$tpl->assign('time_unit', " * ".$oreon->Nagioscfg["interval_length"]." "._(" seconds "));
	$tpl->assign("p", $p);
	
	$valid = false;
	if ($form->validate() && $from_list_menu == false)	{
		$serviceObj =& $form->getElement('service_id');
		if ($form->getSubmitValue("submitA"))
			$serviceObj->setValue(insertServiceInDB());
		else if ($form->getSubmitValue("submitC"))
			updateServiceInDB($serviceObj->getValue());	
		else if ($form->getSubmitValue("submitMC"))	{
			$select = explode(",", $select);
			foreach ($select as $key=>$value)
				if ($value)
					updateServiceInDB($value, true);
		}
		if (count($form->getSubmitValue("service_hgPars")))	{
			$hPars =& $form->getElement('service_hPars');
			$hPars->setValue(array());
		}
		$o = NULL;
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&service_id=".$serviceObj->getValue()."'"));
		$form->freeze();
		$valid = true;
	}
	$action = $form->getSubmitValue("action");
	if ($valid && $action["action"]["action"])	{
		if ($p == "60201")
			require_once($path."listServiceByHost.php");
		else if ($p == "60202")
			require_once($path."listServiceByHostGroup.php");
		else if ($p == "602")
			require_once($path."listServiceByHost.php");
	} else {
		#Apply a template definition
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);
		$tpl->assign('is_not_template', $service_register);
		$tpl->assign('form', $renderer->toArray());
		$tpl->assign('o', $o);
		
		$tpl->assign("Freshness_Control_options", _("Freshness Control options"));
		$tpl->assign("Flapping_Options", _("Flapping options"));
		$tpl->assign("Perfdata_Options", _("Perfdata Options"));
		$tpl->assign("History_Options", _("History Options"));
		$tpl->assign("Event_Handler", _("Event Handler"));
		$tpl->assign("topdoc", _("Documentation"));
		$tpl->assign("seconds", _("seconds"));
		
		$tpl->assign('v', $oreon->user->get_version());		
		$tpl->display("formService.ihtml");
	}
?>
<script type="text/javascript">		
		displayExistingMacroSvc(<?php echo$k;?>);
</script>

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
 

	#
	## Database retrieve information for Escalation
	#
	$esc = array();
	if (($o == "c" || $o == "w") && $esc_id)	{	
		$DBRESULT =& $pearDB->query("SELECT * FROM escalation WHERE esc_id = '".$esc_id."' LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		# Set base value
		$esc = array_map("myDecode", $DBRESULT->fetchRow());
		# Set Host Options
		$esc["escalation_options1"] =& explode(',', $esc["escalation_options1"]);
		foreach ($esc["escalation_options1"] as $key => $value)
			$esc["escalation_options1"][trim($value)] = 1;
		# Set Service Options
		$esc["escalation_options2"] =& explode(',', $esc["escalation_options2"]);
		foreach ($esc["escalation_options2"] as $key => $value)
			$esc["escalation_options2"][trim($value)] = 1;
		# Set Host Groups relations
		$DBRESULT =& $pearDB->query("SELECT DISTINCT hostgroup_hg_id FROM escalation_hostgroup_relation WHERE escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $DBRESULT->fetchInto($hg); $i++)
			$esc["esc_hgs"][$i] = $hg["hostgroup_hg_id"];
		$DBRESULT->free();
		# Set Service Groups relations
		$DBRESULT =& $pearDB->query("SELECT DISTINCT servicegroup_sg_id FROM escalation_servicegroup_relation WHERE escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $DBRESULT->fetchInto($sg); $i++)
			$esc["esc_sgs"][$i] = $sg["servicegroup_sg_id"];
		$DBRESULT->free();
		# Set Host relations
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_host_id FROM escalation_host_relation WHERE escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $DBRESULT->fetchInto($host); $i++)
			$esc["esc_hosts"][$i] = $host["host_host_id"];
		$DBRESULT->free();
		# Set Meta Service
		$DBRESULT =& $pearDB->query("SELECT DISTINCT emsr.meta_service_meta_id FROM escalation_meta_service_relation emsr WHERE emsr.escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $DBRESULT->fetchInto($metas); $i++)
			$esc["esc_metas"][$i] = $metas["meta_service_meta_id"];
		$DBRESULT->free();
		# Set Host Service
		$DBRESULT =& $pearDB->query("SELECT DISTINCT * FROM escalation_service_relation esr WHERE esr.escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for ($i = 0; $DBRESULT->fetchInto($services); $i++)
			$esc["esc_hServices"][$i] = $services["host_host_id"]."_".$services["service_service_id"];
		$DBRESULT->free();
		# Set Contact Groups relations
		$DBRESULT =& $pearDB->query("SELECT DISTINCT contactgroup_cg_id FROM escalation_contactgroup_relation WHERE escalation_esc_id = '".$esc_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $DBRESULT->fetchInto($cg); $i++)
			$esc["esc_cgs"][$i] = $cg["contactgroup_cg_id"];
		$DBRESULT->free();		
	}
	#
	## Database retrieve information for differents elements list we need on the page
	#
	# Host Groups comes from DB -> Store in $hgs Array
	$hgs = array();
	if ($oreon->user->admin || !HadUserLca($pearDB))		
		$DBRESULT =& $pearDB->query("SELECT hg_id, hg_name FROM hostgroup ORDER BY hg_name");
	else
		$DBRESULT =& $pearDB->query("SELECT hg_id, hg_name FROM hostgroup WHERE hg_id IN (".$lcaHostGroupstr.") ORDER BY hg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($hg))
		$hgs[$hg["hg_id"]] = $hg["hg_name"];
	$DBRESULT->free();
	#
	# Service Groups comes from DB -> Store in $sgs Array
	$sgs = array();
	if ($oreon->user->admin || !HadUserLca($pearDB))		
		$DBRESULT =& $pearDB->query("SELECT sg_id, sg_name FROM servicegroup ORDER BY sg_name");
	else
		$DBRESULT =& $pearDB->query("SELECT sg_id, sg_name FROM servicegroup WHERE sg_id IN (".$lcaServiceGroupstr.") ORDER BY sg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($sg))
		$sgs[$sg["sg_id"]] = $sg["sg_name"];
	$DBRESULT->free();
	#
	# Host comes from DB -> Store in $hosts Array
	$hosts = array();
	if ($oreon->user->admin || !HadUserLca($pearDB))		
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name FROM host WHERE host_register = '1' ORDER BY host_name");
	else
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name FROM host WHERE host_register = '1' AND host_id IN (".$lcaHoststr.") ORDER BY host_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($host))
		$hosts[$host["host_id"]] = $host["host_name"];
	$DBRESULT->free();
	#
	# Services comes from DB -> Store in $hServices Array	
	$hServices = array();
	if ($oreon->user->admin || !HadUserLca($pearDB))		
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_id, host_name FROM host WHERE host_register = '1' ORDER BY host_name");
	else
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_id, host_name FROM host WHERE host_register = '1' AND host_id IN (".$lcaHoststr.") ORDER BY host_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($elem))	{
		$services = getMyHostServices($elem["host_id"]);
		foreach ($services as $key=>$index)	{
			$index = str_replace('#S#', "/", $index);
			$index = str_replace('#BS#', "\\", $index);
			$hServices[$elem["host_id"]."_".$key] = $elem["host_name"]." / ".$index;
		}
	}
	$DBRESULT->free();
	# Meta Services comes from DB -> Store in $metas Array
	$metas = array();
	$DBRESULT =& $pearDB->query("SELECT meta_id, meta_name FROM meta_service ORDER BY meta_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($meta))
		$metas[$meta["meta_id"]] = $meta["meta_name"];
	$DBRESULT->free();
	# Contact Groups comes from DB -> Store in $cgs Array
	$cgs = array();
	$DBRESULT =& $pearDB->query("SELECT cg_id, cg_name FROM contactgroup ORDER BY cg_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($cg))
		$cgs[$cg["cg_id"]] = $cg["cg_name"];
	$DBRESULT->free();
	#
	# TimePeriods comes from DB -> Store in $tps Array
	$tps = array();
	$DBRESULT =& $pearDB->query("SELECT tp_id, tp_name FROM timeperiod ORDER BY tp_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($DBRESULT->fetchInto($tp))
		$tps[$tp["tp_id"]] = $tp["tp_name"];
	$DBRESULT->free();
	#
	# End of "database-retrieved" information
	##########################################################
	##########################################################
	# Var information to format the element
	#
	$attrsText 		= array("size"=>"30");
	$attrsText2 	= array("size"=>"10");
	$attrsAdvSelect = array("style" => "width: 250px; height: 150px;");
	$attrsAdvSelect2 = array("style" => "width: 250px; height: 400px;");
	$attrsTextarea 	= array("rows"=>"3", "cols"=>"30");
	$template 		= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	#
	## Form begin
	#
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add an Escalation"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify an Escalation"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View an Escalation"));

	#
	## Escalation basic information
	#
	$form->addElement('header', 'information', _("Information"));
	$form->addElement('text', 'esc_name', _("Escalation Name"), $attrsText);
	$form->addElement('text', 'esc_alias', _("Alias"), $attrsText);
	$form->addElement('text', 'first_notification', _("First Notification"), $attrsText2);
	$form->addElement('text', 'last_notification', _("Last Notification"), $attrsText2);
	$form->addElement('text', 'notification_interval', _("Notification Interval"), $attrsText2);
	if ($oreon->user->get_version() == 2)	{
		$form->addElement('select', 'escalation_period', _("Escalation Period"), $tps);
		$tab = array();
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'd', '&nbsp;', 'd');
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'u');
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', 'r');
		$form->addGroup($tab, 'escalation_options1', _("Hosts Escalation Options"), '&nbsp;&nbsp;');
		$tab = array();
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', 'w');
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'u');
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', 'c');
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', 'r');
		$form->addGroup($tab, 'escalation_options2', _("Services Escalation Options"), '&nbsp;&nbsp;');
	}
	$form->addElement('textarea', 'esc_comment', _("Comments"), $attrsTextarea);
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_cgs', _("Implied Contact Groups"), $cgs, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);
	
	#
	## Sort 2
	#
	$form->addElement('header', 'hosts', _("Implied Hosts"));
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_hosts', _("Hosts"), $hosts, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);
	
	#
	## Sort 3
	#
	$form->addElement('header', 'services', _("Implied Services"));
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_hServices', _("Services by Hosts"), $hServices, $attrsAdvSelect2);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);
	
	#
	## Sort 4
	#
	$form->addElement('header', 'hgs', _("Implied HostGroups"));
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_hgs', _("HostGroup"), $hgs, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);
	
	#
	## Sort 5
	#
	$form->addElement('header', 'metas', _("Implied Meta Services"));
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_metas', _("Meta Service"), $metas, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);
	
	#
	## Sort 6
	#
	$form->addElement('header', 'sgs', _("Implied Servicegroups"));
	
    $ams1 =& $form->addElement('advmultiselect', 'esc_sgs', _("ServiceGroup"), $sgs, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);

	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
	$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
	$form->setDefaults(array('action'=>'1'));
	
	$form->addElement('hidden', 'esc_id');
	$redirect =& $form->addElement('hidden', 'o');
	$redirect->setValue($o);
	
	#
	## Form Rules
	#
	$form->applyFilter('__ALL__', 'myTrim');
	$form->addRule('esc_name', _("Compulsory Name"), 'required');
	$form->addRule('first_notification', _("Required Field"), 'required');
	$form->addRule('last_notification', _("Required Field"), 'required');
	$form->addRule('notification_interval', _("Required Field"), 'required');
	$form->addRule('esc_cgs', _("Required Field"), 'required');
	$form->addRule('dep_hostChilds', _("Required Field"), 'required');
	$form->registerRule('exist', 'callback', 'testExistence');
	$form->addRule('esc_name', _("Name is already in use"), 'exist');
	$form->setRequiredNote("<font style='color: red;'>*</font>". _(" Required fields"));
	
	# 
	##End of form definition
	#
	
	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);
		
	# Just watch a Escalation information
	if ($o == "w")	{
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&esc_id=".$esc_id."'"));
	    $form->setDefaults($esc);
		$form->freeze();
	}
	# Modify a Escalation information
	else if ($o == "c")	{
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($esc);
	}
	# Add a Escalation information
	else if ($o == "a")	{
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}
	$tpl->assign("nagios", $oreon->user->get_version());
	
	$tpl->assign("sort1", _("Information"));
	$tpl->assign("sort2", _("Hosts Escalation"));
	$tpl->assign("sort3", _("Services Escalation"));
	$tpl->assign("sort4", _("Hostgroups Escalation"));
	$tpl->assign("sort5", _("Meta Services Escalation"));
	$tpl->assign("sort6", _("Servicegroups Escalation"));
	
	$tpl->assign('time_unit', " * ".$oreon->Nagioscfg["interval_length"]." "._(" seconds "));
	
	$valid = false;
	if ($form->validate())	{
		$escObj =& $form->getElement('esc_id');
		if ($form->getSubmitValue("submitA"))
			$escObj->setValue(insertEscalationInDB());
		else if ($form->getSubmitValue("submitC"))
			updateEscalationInDB($escObj->getValue("esc_id"));
		$o = NULL;
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&esc_id=".$escObj->getValue()."'"));
		$form->freeze();
		$valid = true;
	}
	$action = $form->getSubmitValue("action");
	if ($valid && $action["action"]["action"])
		require_once("listEscalation.php");
	else	{
		#Apply a template definition	
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);	
		$tpl->assign('form', $renderer->toArray());	
		$tpl->assign('o', $o);		
		$tpl->display("formEscalation.ihtml");
	}
?>
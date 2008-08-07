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
	## Database retrieve information for Dependency
	#
	$dep = array();
	if (($o == "c" || $o == "w") && $dep_id)	{
		$DBRESULT =& $pearDB->query("SELECT * FROM dependency WHERE dep_id = '".$dep_id."' LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		
		# Set base value
		$dep = array_map("myDecode", $DBRESULT->fetchRow());
		
		# Set Notification Failure Criteria
		$dep["notification_failure_criteria"] =& explode(',', $dep["notification_failure_criteria"]);
		foreach ($dep["notification_failure_criteria"] as $key => $value)
			$dep["notification_failure_criteria"][trim($value)] = 1;
		
		# Set Execution Failure Criteria
		$dep["execution_failure_criteria"] =& explode(',', $dep["execution_failure_criteria"]);
		foreach ($dep["execution_failure_criteria"] as $key => $value)
			$dep["execution_failure_criteria"][trim($value)] = 1;
		
		# Set Host Service Childs
		$DBRESULT =& $pearDB->query("SELECT * FROM dependency_serviceChild_relation dscr WHERE dscr.dependency_dep_id = '".$dep_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $service =& $DBRESULT->fetchRow(); $i++)
			$dep["dep_hSvChi"][$i] = $service["host_host_id"]."_".$service["service_service_id"];
		$DBRESULT->free();
		
		# Set Host Service Parents
		$DBRESULT =& $pearDB->query("SELECT * FROM dependency_serviceParent_relation dspr WHERE dspr.dependency_dep_id = '".$dep_id."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		for($i = 0; $service =& $DBRESULT->fetchRow(); $i++)
			$dep["dep_hSvPar"][$i] = $service["host_host_id"]."_".$service["service_service_id"];
		$DBRESULT->free();
	}

	/*
	 * Database retrieve information for differents elements list we need on the page
	 */

	/*
	 * Services comes from DB -> Store in $hServices Array
	 */
	
	$hServices = array();
	if ($is_admin)
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_id, host_name FROM host WHERE host_register = '1'  ORDER BY host_name");
	else
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_id, host_name FROM host WHERE host_register = '1' AND host_id IN (".$lcaHostStr.") ORDER BY host_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($elem =& $DBRESULT->fetchRow())	{
		$services = getMyHostServices($elem["host_id"]);
		foreach ($services as $key=>$index)	{						
			$index = str_replace('#S#', "/", $index);
			$index = str_replace('#BS#', "\\", $index);
			$hServices[$elem["host_id"]."_".$key] = $elem["host_name"]." / ".$index;
		}
	}

	/*
	 * Var information to format the element
	 */
	$attrsText 		= array("size"=>"30");
	$attrsText2 	= array("size"=>"10");
	$attrsAdvSelect = array("style" => "width: 260px; height: 200px;");
	$attrsTextarea 	= array("rows"=>"3", "cols"=>"30");
	$template 		= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	/*
	 * Form begin
	 */
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add a Dependency"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify a Dependency"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View a Dependency"));

	/*
	 * Dependency basic information
	 */
	$form->addElement('header', 'information', _("Information"));
	$form->addElement('text', 'dep_name', _("Name"), $attrsText);
	$form->addElement('text', 'dep_description', _("Description"), $attrsText);

	if ($oreon->user->get_version() >= 2)	{
		$tab = array();
		$tab[] = &HTML_QuickForm::createElement('radio', 'inherits_parent', null, _("Yes"), '1');
		$tab[] = &HTML_QuickForm::createElement('radio', 'inherits_parent', null, _("No"), '0');
		$form->addGroup($tab, 'inherits_parent', _("Parent relationship"), '&nbsp;');
	}
	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'o', '&nbsp;', 'Ok');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', 'Warning');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unknown');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', 'Critical');

	if ($oreon->user->get_version() >= 2)
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'p', '&nbsp;', 'Pending');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'n', '&nbsp;', 'None');
	$form->addGroup($tab, 'notification_failure_criteria', _("Notification Failure Criteria"), '&nbsp;&nbsp;');
	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'o', '&nbsp;', 'Ok');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', 'Warning');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unknown');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', 'Critical');

	if ($oreon->user->get_version() >= 2)
		$tab[] = &HTML_QuickForm::createElement('checkbox', 'p', '&nbsp;', 'Pending');
	$tab[] = &HTML_QuickForm::createElement('checkbox', 'n', '&nbsp;', 'None');
	$form->addGroup($tab, 'execution_failure_criteria', _("Execution Failure Criteria"), '&nbsp;&nbsp;');

	$form->addElement('textarea', 'dep_comment', _("Comments"), $attrsTextarea);
	
	/*
	 * Sort 2 Host Service Dependencies
	 */
	$ams1 =& $form->addElement('advmultiselect', 'dep_hSvPar', _("Hosts Services Description"), $hServices, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);

    $ams1 =& $form->addElement('advmultiselect', 'dep_hSvChi', _("Dependent Hosts Services Description"), $hServices, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);

	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
	$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
	$form->setDefaults(array('action'=>'1'));

	$form->addElement('hidden', 'dep_id');
	$redirect =& $form->addElement('hidden', 'o');
	$redirect->setValue($o);

	/*
	 * Form Rules
	 */
	$form->applyFilter('__ALL__', 'myTrim');
	$form->addRule('dep_name', _("Compulsory Name"), 'required');
	$form->addRule('dep_description', _("Required Field"), 'required');
	$form->addRule('dep_hSvPar', _("Required Field"), 'required');
	$form->addRule('dep_hSvChi', _("Required Field"), 'required');
	$form->registerRule('cycleH', 'callback', 'testCycleH');
	$form->addRule('dep_hSvChi', _("Circular Definition"), 'cycleH');
	$form->registerRule('exist', 'callback', 'testServiceDependencyExistence');
	$form->addRule('dep_name', _("Name is already in use"), 'exist');
	$form->setRequiredNote("<font style='color: red;'>*</font>". _(" Required fields"));


	/*
	 * Smarty template Init
	 */
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	$tpl->assign("sort1", _("Information"));
	$tpl->assign("sort2", _("Host Service Description"));

	# Just watch a Dependency information
	if ($o == "w")	{
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&dep_id=".$dep_id."'"));
	    $form->setDefaults($dep);
		$form->freeze();
	}
	# Modify a Dependency information
	else if ($o == "c")	{
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($dep);
	}
	# Add a Dependency information
	else if ($o == "a")	{
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}
	$tpl->assign("nagios", $oreon->user->get_version());

	$valid = false;
	if ($form->validate())	{
		$depObj =& $form->getElement('dep_id');
		if ($form->getSubmitValue("submitA"))
			$depObj->setValue(insertServiceDependencyInDB());
		else if ($form->getSubmitValue("submitC"))
			updateServiceDependencyInDB($depObj->getValue("dep_id"));
		$o = NULL;
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&dep_id=".$depObj->getValue()."'"));
		$form->freeze();
		$valid = true;
	}
	$action = $form->getSubmitValue("action");
	if ($valid && $action["action"]["action"]){
		require_once("listServiceDependency.php");
	} else {
		/*
		 * Apply a template definition
		 */
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);
		$tpl->assign('form', $renderer->toArray());
		$tpl->assign('o', $o);
		$tpl->display("formServiceDependency.ihtml");
	}
?>
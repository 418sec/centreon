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
	## Database retrieve information for Nagios
	#
	$nagios = array();
	if (($o == "c" || $o == "w") && $id)	{	
		$DBRESULT =& $pearDB->query("SELECT * FROM cfg_ndo2db WHERE id = '".$id."' LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		# Set base value
		$cfg_ndo2db = array_map("myDecode", $DBRESULT->fetchRow());
		$DBRESULT->free();
	}
	
	# Database retrieve information for differents elements list we need on the page
	
	# Check commands comes from DB -> Store in $checkCmds Array
	$checkCmds = array();
	$DBRESULT =& $pearDB->query("SELECT command_id, command_name FROM command ORDER BY command_name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	$checkCmds = array(NULL=>NULL);
	while($checkCmd =& $DBRESULT->fetchRow())
		$checkCmds[$checkCmd["command_id"]] = $checkCmd["command_name"];
	$DBRESULT->free();
	
	# nagios servers comes from DB 
	$nagios_servers = array();
	$DBRESULT =& $pearDB->query("SELECT * FROM nagios_server ORDER BY name");
	if (PEAR::isError($DBRESULT))
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($nagios_server = $DBRESULT->fetchRow())
		$nagios_servers[$nagios_server["id"]] = $nagios_server["name"];
	$DBRESULT->free();
	#
	
	# End of "database-retrieved" information
	##########################################################
	
	##########################################################
	# Var information to format the element
	#
	$attrsText		= array("size"=>"30");
	$attrsText2 	= array("size"=>"50");
	$attrsText3 	= array("size"=>"10");
	$attrsTextarea 	= array("rows"=>"5", "cols"=>"40");
	$template 		= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	#
	## Form begin
	#
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add a ndo2db Configuration File"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify a ndo2db Configuration File"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View a ndo2db Configuration File"));

	#
	## Nagios Configuration basic information
	#
	$form->addElement('header', 'information', _("ndo2db configuration"));
	$form->addElement('text', 'description', _("Description"), $attrsText);
	$form->addElement('select', 'ns_nagios_server', _("Requester"), $nagios_servers);
	$form->addElement('select', 'socket_type', _("Socket Type"), array("unix"=>"unix","tcp"=>"tcp"));
	$form->addElement('text', 'socket_name', _("Socket Name"), $attrsText2);
	$form->addElement('text', 'tcp_port', _("TCP Port"), $attrsText3);
	
	$form->addElement('text', 'ndo2db_user', _("User ndo2db"), $attrsText);
	$form->addElement('text', 'ndo2db_group', _("Group ndo2db"), $attrsText);
	
	# DB configuration
	$form->addElement('select', 'db_servertype', _("Database Type"), array("mysql"=>"MySQL","pgsql"=>"PostgreSQL"));
	$form->addElement('text', 'db_host', _("Database Hoster"), $attrsText);
	$form->addElement('text', 'db_name', _("Database Name"), $attrsText);
	$form->addElement('text', 'db_port', _("Listening Port"), $attrsText);
	$form->addElement('text', 'db_prefix', _("Prefix"), $attrsText);
	$form->addElement('text', 'db_user', _("User"), $attrsText);
	$form->addElement('password', 'db_pass', _("Password"), $attrsText);
	
	# DB retention
	$form->addElement('text', 'max_timedevents_age', _("Event retention"), $attrsText3);
	$form->addElement('text', 'max_systemcommands_age', _("Command history retention"), $attrsText3);
	$form->addElement('text', 'max_servicechecks_age', _("Service check retention"), $attrsText3);
	$form->addElement('text', 'max_hostchecks_age', _("Host check retention"), $attrsText3);
	$form->addElement('text', 'max_eventhandlers_age', _("Event history retention"), $attrsText3);
	
	$Tab = array();
	$Tab[] = &HTML_QuickForm::createElement('radio', 'activate', null, _("Enabled"), '1');
	$Tab[] = &HTML_QuickForm::createElement('radio', 'activate', null, _("Disabled"), '0');
	$form->addGroup($Tab, 'activate', _("Status"), '&nbsp;');	
		
	if (isset($_GET["o"]) && $_GET["o"] == 'a'){
		$form->setDefaults(array(
		"description"=>'',
		"socket_type"=>'unix',
		"socket_name"=>"/usr/local/nagios/var/ndo.sock",
		"tcp_port"=>"5668",
		"db_type"=>'mysql',
		"db_host"=>'',
		"db_port"=>'3306',
		"db_name"=>'nagios',
		"db_prefix"=>'ndo_',
		"db_user"=>'ndo',
		"db_pass"=>'ndo',
		"max_timedevents_age"=>'1440',
		"max_systemcommands_age"=>'10080',
		"max_servicechecks_age"=>'10080',
		"max_hostchecks_age"=>'10080',
		"max_eventhandlers_age"=>'44640',
		"activate"=>'1'));
	} else {
		if (isset($cfg_ndo2db))
			$form->setDefaults($cfg_ndo2db);
	}	
	$form->addElement('hidden', 'id');
	$redirect =& $form->addElement('hidden', 'o');
	$redirect->setValue($o);
	
	# Form Rules
	$form->addRule('nagios_name', _("Name is already in use"), 'exist');
	
	#End of form definition
	
	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);
	
	# Just watch a nagios information
	if ($o == "w")	{
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&id=".$ndo2db_id."'"));
	    $form->setDefaults($nagios);
		$form->freeze();
	} else if ($o == "c")	{# Modify a nagios information
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($nagios);
	} else if ($o == "a")	{# Add a nagios information
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}
	
	$valid = false;
	if ($form->validate())	{
		$nagiosObj =& $form->getElement('id');
		if ($form->getSubmitValue("submitA"))
			insertNdo2dbInDB();
		else if ($form->getSubmitValue("submitC"))
			updateNdo2dbInDB($nagiosObj->getValue());
		$o = NULL;
		$valid = true;
	}
	if ($valid)
		require_once($path."listNdo2db.php");
	else	{
		#Apply a template definition
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);	
		$tpl->assign('form', $renderer->toArray());	
		$tpl->assign('o', $o);
		$tpl->assign('ndo2db_configuration', _("Ndo2db Informations"));
		$tpl->assign('ndo2db_access', _("Ndo2db execution access"));
		$tpl->assign('Database_Information_for_ndo2db', _("Database Information for ndo2db"));
		$tpl->assign('Retention_Informations_For_Ndo2db', _("Retention Informations For Ndo2db"));
		$tpl->assign('seconds', _("seconds"));
		$tpl->assign('sort1', _("General"));		
		$tpl->assign('sort2', _("Database"));		
		$tpl->assign('sort3', _("Retention"));
		$tpl->display("formNdo2db.ihtml");
	}
?>
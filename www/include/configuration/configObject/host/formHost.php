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
	/*
	 * Database retrieve information for Host
	 */

	$host = array();
	if (($o == "c" || $o == "w") && $host_id)	{
		!$is_admin ? $restriction = "AND host_id IN (".$lcaHoststr.") " : $restriction = "";
		$restriction = "";
		$DBRESULT =& $pearDB->query("SELECT * FROM host, extended_host_information ehi WHERE host_id = '".$host_id."' AND ehi.host_host_id = host.host_id $restriction LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";

		/*
		 * Set base value
		 */
		$host_list =& $DBRESULT->fetchRow();
		$host = array_map("myDecode", $host_list);
		
		/*
		 * Set Host Notification Options
		 */
		$tmp = explode(',', $host["host_notification_options"]);
		foreach ($tmp as $key => $value)
			$host["host_notifOpts"][trim($value)] = 1;
		
		/*
		 * Set Stalking Options
		 */
		$tmp = explode(',', $host["host_stalking_options"]);
		foreach ($tmp as $key => $value)
			$host["host_stalOpts"][trim($value)] = 1;
		$DBRESULT->free();
		
		/*
		 * Set Contact Group
		 */
		$DBRESULT =& $pearDB->query("SELECT DISTINCT contactgroup_cg_id FROM contactgroup_host_relation WHERE host_host_id = '".$host_id."'");
		for ($i = 0; $notifCg = $DBRESULT->fetchRow(); $i++)
			$host["host_cgs"][$i] = $notifCg["contactgroup_cg_id"];
		$DBRESULT->free();
		
		/*
		 * Set Host Parents
		 */
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_parent_hp_id FROM host_hostparent_relation WHERE host_host_id = '".$host_id."'");
		for ($i = 0; $parent = $DBRESULT->fetchRow(); $i++)
			$host["host_parents"][$i] = $parent["host_parent_hp_id"];
		$DBRESULT->free();
		
		/*
		 * Set Host Childs
		 */
		$DBRESULT =& $pearDB->query("SELECT DISTINCT host_host_id FROM host_hostparent_relation WHERE host_parent_hp_id = '".$host_id."'");
		for ($i = 0; $child = $DBRESULT->fetchRow(); $i++)
			$host["host_childs"][$i] = $child["host_host_id"];
		$DBRESULT->free();
		
		/*
		 * Set Host Group Parents
		 */
		$DBRESULT =& $pearDB->query("SELECT DISTINCT hostgroup_hg_id FROM hostgroup_relation WHERE host_host_id = '".$host_id."'");
		for ($i = 0; $hg = $DBRESULT->fetchRow(); $i++)
			$host["host_hgs"][$i] = $hg["hostgroup_hg_id"];
		$DBRESULT->free();
		
		/*
		 * Set Host and Nagios Server Relation
		 */
		$DBRESULT =& $pearDB->query("SELECT `nagios_server_id` FROM `ns_host_relation` WHERE `host_host_id` = '".$host_id."'");
		for ($i = 0; $ns = $DBRESULT->fetchRow(); $i++)
			$host["nagios_server_id"][$i] = $ns["nagios_server_id"];
		$DBRESULT->free();
		//$host["nagios_server_id"] = $ns["nagios_server_id"];
		unset($ns);
	}

	/*
	 * Database retrieve information for differents elements list we need on the page
	 * Host Templates comes from DB -> Store in $hTpls Array
	 */

	$hTpls = array();
	$DBRESULT =& $pearDB->query("SELECT host_id, host_name, host_template_model_htm_id FROM host WHERE host_register = '0' AND host_id != '".$host_id."' ORDER BY host_name");
	$nbMaxTemplates = 0;
	while($hTpl = $DBRESULT->fetchRow())	{
		if (!$hTpl["host_name"])
			$hTpl["host_name"] = getMyHostName($hTpl["host_template_model_htm_id"])."'";		
		$hTpls[$hTpl["host_id"]] = $hTpl["host_name"];
		$nbMaxTemplates++;
	}	
	$DBRESULT->free();
	
	/*
	 * Timeperiods comes from DB -> Store in $tps Array
	 */
	$tps = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT tp_id, tp_name FROM timeperiod ORDER BY tp_name");
	while($tp = $DBRESULT->fetchRow())
		$tps[$tp["tp_id"]] = $tp["tp_name"];
	$DBRESULT->free();
	
	/*
	 * Check commands comes from DB -> Store in $checkCmds Array
	 */
	$checkCmds = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT command_id, command_name FROM command WHERE command_type = '2' ORDER BY command_name");
	while($checkCmd = $DBRESULT->fetchRow())
		$checkCmds[$checkCmd["command_id"]] = $checkCmd["command_name"];
	$DBRESULT->free();
	
	/*
	 * Check commands comes from DB -> Store in $checkCmds Array
	 */
	$checkCmdEvent = array(NULL=>NULL);
	$DBRESULT =& $pearDB->query("SELECT command_id, command_name FROM command WHERE command_type = '2' OR command_type = '3' ORDER BY command_name");
	while($checkCmd = $DBRESULT->fetchRow())
		$checkCmdEvent[$checkCmd["command_id"]] = $checkCmd["command_name"];
	$DBRESULT->free();
	
	/*
	 * Contact Groups comes from DB -> Store in $notifCcts Array
	 */
	$notifCgs = array();
	$DBRESULT =& $pearDB->query("SELECT cg_id, cg_name FROM contactgroup ORDER BY cg_name");
	while($notifCg = $DBRESULT->fetchRow())
		$notifCgs[$notifCg["cg_id"]] = $notifCg["cg_name"];
	$DBRESULT->free();
	
	/*
	 * Contact Nagios Server comes from DB -> Store in $nsServer Array
	 */
	$nsServers = array();
	$DBRESULT =& $pearDB->query("SELECT id, name FROM nagios_server ORDER BY name");
	while ($nsServer = $DBRESULT->fetchRow())
		$nsServers[$nsServer["id"]] = $nsServer["name"];
	$DBRESULT->free();
	
	/*
	 * Host Groups comes from DB -> Store in $hgs Array
	 */
	$hgs = array();
//	if ($oreon->user->admin || !HadUserLca($pearDB))		
		$DBRESULT =& $pearDB->query("SELECT hg_id, hg_name FROM hostgroup ORDER BY hg_name");
/*	else
		$DBRESULT =& $pearDB->query("SELECT hg_id, hg_name FROM hostgroup WHERE hg_id IN (".$lcaHostGroupstr.") ORDER BY hg_name");
*/
	while($hg = $DBRESULT->fetchRow())
		$hgs[$hg["hg_id"]] = $hg["hg_name"];
	$DBRESULT->free();
	
	/*
	 * Host Parents comes from DB -> Store in $hostPs Array
	 */
	$hostPs = array();
//	if ($oreon->user->admin || !HadUserLca($pearDB))
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name, host_template_model_htm_id FROM host WHERE host_id != '".$host_id."' AND host_register = '1' ORDER BY host_name");
/*	else
		$DBRESULT =& $pearDB->query("SELECT host_id, host_name, host_template_model_htm_id FROM host WHERE host_id != '".$host_id."' AND host_id IN (".$lcaHoststr.") AND host_register = '1' ORDER BY host_name");
*/	while($hostP = $DBRESULT->fetchRow())	{
		if (!$hostP["host_name"])
			$hostP["host_name"] = getMyHostName($hostP["host_template_model_htm_id"])."'";
		$hostPs[$hostP["host_id"]] = $hostP["host_name"];
	}
	$DBRESULT->free();
	
		
	/*
	 * IMG comes from DB -> Store in $extImg Array
	 */
	$extImg = array();
	$extImg = return_image_list(1);
	$extImgStatusmap = array();
	$extImgStatusmap = return_image_list(2);
	
	/*
	 *  Host multiple templates relations stored in DB
	 */	
	$mTp = array();
	$k = 0;
	$DBRESULT =& $pearDB->query("SELECT host_tpl_id FROM host_template_relation WHERE host_host_id = '". $host_id ."' ORDER BY `order`");
	while($multiTp = $DBRESULT->fetchRow())
	{
		$mTp[$k] = $multiTp["host_tpl_id"];
		$k++;
	}
	$DBRESULT->free();
	
	/*
	 *  Host on demand macro stored in DB
	 */
	$j = 0;		
	$DBRESULT =& $pearDB->query("SELECT host_macro_id, host_macro_name, host_macro_value, host_host_id FROM on_demand_macro_host WHERE host_host_id = '". $host_id ."' ORDER BY `host_macro_id`");
	while($od_macro = $DBRESULT->fetchRow())
	{
		$od_macro_id[$j] = $od_macro["host_macro_id"];
		$od_macro_name[$j] = str_replace("\$_HOST", "", $od_macro["host_macro_name"]);
		$od_macro_name[$j] = str_replace("\$", "", $od_macro_name[$j]);
		$od_macro_value[$j] = $od_macro["host_macro_value"];
		$od_macro_host_id[$j] = $od_macro["host_host_id"];
		$j++;		
	}
	$DBRESULT->free();
	
	
	#
	# End of "database-retrieved" information
	##########################################################
	##########################################################
	# Var information to format the element
	#
	$attrsText 			= array("size"=>"30");
	$attrsText2			= array("size"=>"6");
	$attrsAdvSelect 	= array("style" => "width: 220px; height: 100px;");
	$attrsAdvSelectsmall= array("style" => "width: 220px; height: 50px;");
	$attrsAdvSelectbig 	= array("style" => "width: 220px; height: 130px;");
	$attrsTextarea 		= array("rows"=>"4", "cols"=>"80");
	$template 		= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	#
	## Form begin
	#
	
	$TemplateValues = array();
	
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add a Host"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify a Host"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View a Host"));
	else if ($o == "mc")
		$form->addElement('header', 'title', _("Massive Change"));

	## Sort 1 - Host Configuration
	#
	## Host basic information
	#
	$form->addElement('header', 'information', _("General Information"));
	# No possibility to change name and alias, because there's no interest
	if ($o != "mc")	{
		$form->addElement('text', 'host_name', _("Host Name"), $attrsText);
		$form->addElement('text', 'host_alias', _("Alias"), $attrsText);
	}
	$form->addElement('text', 'host_address', _("IP Address / DNS"), $attrsText);
	$form->addElement('select', 'host_snmp_version', _("Version"), array(0=>null, 1=>"1", 2=>"2c", 3=>"3"));
	$form->addElement('text', 'host_snmp_community', _("SNMP Community"), $attrsText);
	
	$form->addElement('select', 'nagios_server_id', _("Monitored from"), $nsServers);

	$form->addElement('select', 'host_template_model_htm_id', _("Host Template"), $hTpls);	
	$form->addElement('text', 'host_parallel_template', _("Host Multiple Templates"), $hTpls);
	?>
	<script type="text/javascript" src="lib/wz_tooltip/wz_tooltip.js"></script>
	<?php
	$form->addElement('static', 'tplTextParallel', _("A host can have multiple templates, their orders have a significant importance<br><a href='#' onmouseover=\"Tip('<img src=\'img/misc/multiple-templates2.png\'>', OPACITY, 70)\" onmouseout=\"UnTip()\">Here is a self explanatory image.</a>"));	
	$form->addElement('static', 'tplText', _("Using a Template allows you to have multi-level Template connection"));	
	if ($oreon->user->get_version() == 3) {
		include_once("makeJS_formHost.php");	
		if ($o == "c" || $o == "a" || $o == "mc")
		{		
			for($k=0; isset($mTp[$k]); $k++) {?>
				<script type="text/javascript">
				tab[<?php echo $k;?>] = <?php echo $mTp[$k];?>;		
				</script> 
			<?php
			}
			for($k=0; isset($od_macro_id[$k]); $k++) {?>
				<script type="text/javascript">
				globalMacroTabId[<?php echo $k;?>] = <?php echo $od_macro_id[$k];?>;		
				globalMacroTabName[<?php echo $k;?>] = '<?php echo $od_macro_name[$k];?>';
				globalMacroTabValue[<?php echo $k;?>] = '<?php echo $od_macro_value[$k];?>';
				globalMacroTabHostId[<?php echo $k;?>] = <?php echo $od_macro_host_id[$k];?>;
				</script>				
			<?php 
			}
		}
	}
	$dupSvTpl[] = &HTML_QuickForm::createElement('radio', 'dupSvTplAssoc', null, _("Yes"), '1');
	$dupSvTpl[] = &HTML_QuickForm::createElement('radio', 'dupSvTplAssoc', null, _("No"), '0');
	$form->addGroup($dupSvTpl, 'dupSvTplAssoc', _("Checks Enabled"), '&nbsp;');
	if ($o == "c")
		$form->setDefaults(array('dupSvTplAssoc' => '0'));
	else if ($o == "w")
		; 
	else if ($o != "mc")
		$form->setDefaults(array('dupSvTplAssoc' => '1'));
	$form->addElement('static', 'dupSvTplAssocText', _("Create Services linked to the Template too"));

	#
	## Check information
	#
	$form->addElement('header', 'check', _("Host Check Properties"));
	#Nagios 1
	if ($oreon->user->get_version() == 1)	{
		$hostCE[] = &HTML_QuickForm::createElement('radio', 'host_checks_enabled', null, _("Yes"), '1');
		$hostCE[] = &HTML_QuickForm::createElement('radio', 'host_checks_enabled', null, _("No"), '0');
		$hostCE[] = &HTML_QuickForm::createElement('radio', 'host_checks_enabled', null, _("Default"), '2');
		$form->addGroup($hostCE, 'host_checks_enabled', _("Checks Enabled"), '&nbsp;');
		if ($o != "mc")
			$form->setDefaults(array('host_checks_enabled' => '2'));
	}
	$form->addElement('select', 'command_command_id', _("Check Command"), $checkCmds, 'onchange=setArgument(this.form,"command_command_id","example1")');
	$form->addElement('text', 'command_command_id_arg1', _("Args"), $attrsText);
	
	$form->addElement('text', 'host_max_check_attempts', _("Max Check Attempts"), $attrsText2);
	
	$hostEHE[] = &HTML_QuickForm::createElement('radio', 'host_event_handler_enabled', null, _("Yes"), '1');
	$hostEHE[] = &HTML_QuickForm::createElement('radio', 'host_event_handler_enabled', null, _("No"), '0');
	$hostEHE[] = &HTML_QuickForm::createElement('radio', 'host_event_handler_enabled', null, _("Default"), '2');
	$form->addGroup($hostEHE, 'host_event_handler_enabled', _("Event Handler Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_event_handler_enabled' => '2'));
	$form->addElement('select', 'command_command_id2', _("Event Handler"), $checkCmdEvent, 'onchange=setArgument(this.form,"command_command_id2","example2")');
	$form->addElement('text', 'command_command_id_arg2', _("Args"), $attrsText);
	
	# Nagios 2
	if ($oreon->user->get_version() >= 2)	{
	$form->addElement('text', 'host_check_interval', _("Normal Check Interval"), $attrsText2);

	$hostACE[] = &HTML_QuickForm::createElement('radio', 'host_active_checks_enabled', null, _("Yes"), '1');
	$hostACE[] = &HTML_QuickForm::createElement('radio', 'host_active_checks_enabled', null, _("No"), '0');
	$hostACE[] = &HTML_QuickForm::createElement('radio', 'host_active_checks_enabled', null, _("Default"), '2');
	$form->addGroup($hostACE, 'host_active_checks_enabled', _("Active Checks Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_active_checks_enabled' => '2'));

	$hostPCE[] = &HTML_QuickForm::createElement('radio', 'host_passive_checks_enabled', null, _("Yes"), '1');
	$hostPCE[] = &HTML_QuickForm::createElement('radio', 'host_passive_checks_enabled', null, _("No"), '0');
	$hostPCE[] = &HTML_QuickForm::createElement('radio', 'host_passive_checks_enabled', null, _("Default"), '2');
	$form->addGroup($hostPCE, 'host_passive_checks_enabled', _("Passive Checks Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_passive_checks_enabled' => '2'));

	$form->addElement('select', 'timeperiod_tp_id', _("Check Period"), $tps);
	}

	##
	## Notification informations
	##
	$form->addElement('header', 'notification', _("Notification"));
	$hostNE[] = &HTML_QuickForm::createElement('radio', 'host_notifications_enabled', null, _("Yes"), '1');
	$hostNE[] = &HTML_QuickForm::createElement('radio', 'host_notifications_enabled', null, _("No"), '0');
	$hostNE[] = &HTML_QuickForm::createElement('radio', 'host_notifications_enabled', null, _("Default"), '2');
	$form->addGroup($hostNE, 'host_notifications_enabled', _("Notification Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_notifications_enabled' => '2'));
	
	#Nagios 2
	if ($oreon->user->get_version() >= 2)	{
		if ($o == "mc")	{
			$mc_mod_hcg = array();
			$mc_mod_hcg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hcg', null, _("Incremental"), '0');
			$mc_mod_hcg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hcg', null, _("Replacement"), '1');
			$form->addGroup($mc_mod_hcg, 'mc_mod_hcg', _("Update options"), '&nbsp;');
			$form->setDefaults(array('mc_mod_hcg'=>'0'));
		}
	    $ams3 =& $form->addElement('advmultiselect', 'host_cgs', _("Linked ContactGroups"), $notifCgs, $attrsAdvSelect);
		$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
		$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
		$ams3->setElementTemplate($template);
		echo $ams3->getElementJs(false);
	}

	$form->addElement('text', 'host_notification_interval', _("Notification Interval"), $attrsText2);
	$form->addElement('select', 'timeperiod_tp_id2', _("Notification Period"), $tps);

 	$hostNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'd', '&nbsp;', 'Down');
	$hostNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unreachable');
	$hostNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', 'Recovery');
	if ($oreon->user->get_version() == 2)
		$hostNotifOpt[] = &HTML_QuickForm::createElement('checkbox', 'f', '&nbsp;', 'Flapping');
	$form->addGroup($hostNotifOpt, 'host_notifOpts', _("Notification Options"), '&nbsp;&nbsp;');

 	$hostStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'o', '&nbsp;', 'Ok/Up');
	$hostStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'd', '&nbsp;', 'Down');
	$hostStalOpt[] = &HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', 'Unreachable');
	$form->addGroup($hostStalOpt, 'host_stalOpts', _("Stalking Options"), '&nbsp;&nbsp;');

	#
	## Further informations
	#
	$form->addElement('header', 'furtherInfos', _("Additional Information"));
	$hostActivation[] = &HTML_QuickForm::createElement('radio', 'host_activate', null, _("Enabled"), '1');
	$hostActivation[] = &HTML_QuickForm::createElement('radio', 'host_activate', null, _("Disabled"), '0');
	$form->addGroup($hostActivation, 'host_activate', _("Status"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_activate' => '1'));
	$form->addElement('textarea', 'host_comment', _("Comments"), $attrsTextarea);

	#
	## Sort 2 - Host Relations
	#
	if ($o == "a")
		$form->addElement('header', 'title2', _("Add relations"));
	else if ($o == "c")
		$form->addElement('header', 'title2', _("Modify relations"));
	else if ($o == "w")
		$form->addElement('header', 'title2', _("View relations"));
	else if ($o == "mc")
		$form->addElement('header', 'title2', _("Massive Change"));

	$form->addElement('header', 'links', _("Relations"));
	if ($o == "mc")	{
		$mc_mod_hpar = array();
		$mc_mod_hpar[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hpar', null, _("Incremental"), '0');
		$mc_mod_hpar[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hpar', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_hpar, 'mc_mod_hpar', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_hpar'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'host_parents', _("Parent Hosts"), $hostPs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

	if ($o == "mc")	{
		$mc_mod_hch = array();
		$mc_mod_hch[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hch', null, _("Incremental"), '0');
		$mc_mod_hch[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hch', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_hch, 'mc_mod_hch', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_hch'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'host_childs', _("Child Hosts"), $hostPs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);

	if ($o == "mc")	{
		$mc_mod_hhg = array();
		$mc_mod_hhg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hhg', null, _("Incremental"), '0');
		$mc_mod_hhg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_hhg', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_hhg, 'mc_mod_hhg', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_hhg'=>'0'));
	}
    $ams3 =& $form->addElement('advmultiselect', 'host_hgs', _("Parent HostGroups"), $hgs, $attrsAdvSelect);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);
	
	if ($o == "mc")	{
		$mc_mod_hhg = array();
		$mc_mod_hhg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_nsid', null, _("Incremental"), '0');
		$mc_mod_hhg[] = &HTML_QuickForm::createElement('radio', 'mc_mod_nsid', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_hhg, 'mc_mod_nsid', _("Update options"), '&nbsp;');
		$form->setDefaults(array('mc_mod_nsid'=>'0'));
	}
	/*
    $ams3 =& $form->addElement('advmultiselect', 'nagios_server_id', _("Monitored from "), $nsServers, $attrsAdvSelectsmall);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams3->setElementTemplate($template);
	echo $ams3->getElementJs(false);
	*/
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
		$form->addElement('header', 'title3', _("Massive Change"));

	$form->addElement('header', 'treatment', _("Data Processing"));
	# Nagios 2
	if ($oreon->user->get_version() >= 2)	{
		$hostOOH[] = &HTML_QuickForm::createElement('radio', 'host_obsess_over_host', null, _("Yes"), '1');
		$hostOOH[] = &HTML_QuickForm::createElement('radio', 'host_obsess_over_host', null, _("No"), '0');
		$hostOOH[] = &HTML_QuickForm::createElement('radio', 'host_obsess_over_host', null, _("Default"), '2');
		$form->addGroup($hostOOH, 'host_obsess_over_host', _("Obsess Over Host"), '&nbsp;');
		if ($o != "mc")
			$form->setDefaults(array('host_obsess_over_host' => '2'));
	
		$hostCF[] = &HTML_QuickForm::createElement('radio', 'host_check_freshness', null, _("Yes"), '1');
		$hostCF[] = &HTML_QuickForm::createElement('radio', 'host_check_freshness', null, _("No"), '0');
		$hostCF[] = &HTML_QuickForm::createElement('radio', 'host_check_freshness', null, _("Default"), '2');
		$form->addGroup($hostCF, 'host_check_freshness', _("Check Freshness"), '&nbsp;');
		if ($o != "mc")
			$form->setDefaults(array('host_check_freshness' => '2'));
	}
	$hostFDE[] = &HTML_QuickForm::createElement('radio', 'host_flap_detection_enabled', null, _("Yes"), '1');
	$hostFDE[] = &HTML_QuickForm::createElement('radio', 'host_flap_detection_enabled', null, _("No"), '0');
	$hostFDE[] = &HTML_QuickForm::createElement('radio', 'host_flap_detection_enabled', null, _("Default"), '2');
	$form->addGroup($hostFDE, 'host_flap_detection_enabled', _("Flap Detection Enabled"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_flap_detection_enabled' => '2'));
	# Nagios 2
	if ($oreon->user->get_version() >= 2)
		$form->addElement('text', 'host_freshness_threshold', _("Freshness Threshold"), $attrsText2);

	$form->addElement('text', 'host_low_flap_threshold', _("Low Flap threshold"), $attrsText2);
	$form->addElement('text', 'host_high_flap_threshold', _("High Flap Threshold"), $attrsText2);

	$hostPPD[] = &HTML_QuickForm::createElement('radio', 'host_process_perf_data', null, _("Yes"), '1');
	$hostPPD[] = &HTML_QuickForm::createElement('radio', 'host_process_perf_data', null, _("No"), '0');
	$hostPPD[] = &HTML_QuickForm::createElement('radio', 'host_process_perf_data', null, _("Default"), '2');
	$form->addGroup($hostPPD, 'host_process_perf_data', _("Process Perf Data"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_process_perf_data' => '2'));

	$hostRSI[] = &HTML_QuickForm::createElement('radio', 'host_retain_status_information', null, _("Yes"), '1');
	$hostRSI[] = &HTML_QuickForm::createElement('radio', 'host_retain_status_information', null, _("No"), '0');
	$hostRSI[] = &HTML_QuickForm::createElement('radio', 'host_retain_status_information', null, _("Default"), '2');
	$form->addGroup($hostRSI, 'host_retain_status_information', _("Retain Satus Information"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_retain_status_information' => '2'));

	$hostRNI[] = &HTML_QuickForm::createElement('radio', 'host_retain_nonstatus_information', null, _("Yes"), '1');
	$hostRNI[] = &HTML_QuickForm::createElement('radio', 'host_retain_nonstatus_information', null, _("No"), '0');
	$hostRNI[] = &HTML_QuickForm::createElement('radio', 'host_retain_nonstatus_information', null, _("Default"), '2');
	$form->addGroup($hostRNI, 'host_retain_nonstatus_information', _("Retain Non Status Information"), '&nbsp;');
	if ($o != "mc")
		$form->setDefaults(array('host_retain_nonstatus_information' => '2'));

	#
	## Sort 4 - Extended Infos
	#
	if ($o == "a")
		$form->addElement('header', 'title4', _("Add a Host Extended Info"));
	else if ($o == "c")
		$form->addElement('header', 'title4', _("Modify a Host Extended Info"));
	else if ($o == "w")
		$form->addElement('header', 'title4', _("View a Host Extended Info"));
	else if ($o == "mc")
		$form->addElement('header', 'title4', _("Massive Change"));

	$form->addElement('header', 'nagios', _("Nagios"));
	if ($oreon->user->get_version() >= 2)
		$form->addElement('text', 'ehi_notes', _("Notes"), $attrsText);
	$form->addElement('text', 'ehi_notes_url', _("URL"), $attrsText);
	if ($oreon->user->get_version() >= 2)
		$form->addElement('text', 'ehi_action_url', _("Action URL"), $attrsText);
	$form->addElement('select', 'ehi_icon_image', _("Icon"), $extImg, array("onChange"=>"showLogo('ehi_icon_image',this.form.elements['ehi_icon_image'].value)"));
	$form->addElement('text', 'ehi_icon_image_alt', _("Alt icon"), $attrsText);
	$form->addElement('select', 'ehi_vrml_image', _("VRML Image"), $extImg, array("onChange"=>"showLogo('ehi_vrml_image',this.form.elements['ehi_vrml_image'].value)"));
	$form->addElement('select', 'ehi_statusmap_image', _("Nagios Status Map Image"), $extImgStatusmap, array("onChange"=>"showLogo('ehi_statusmap_image',this.form.elements['ehi_statusmap_image'].value)"));	
	$form->addElement('text', 'ehi_2d_coords', _("Nagios 2d Coords"), $attrsText2);
	$form->addElement('text', 'ehi_3d_coords', _("Nagios 3d Coords"), $attrsText2);

	

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
	}
	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
	$tab[] = &HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
	$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
	$form->setDefaults(array('action' => '1'));

	$form->addElement('hidden', 'host_id');
	$reg =& $form->addElement('hidden', 'host_register');
	$reg->setValue("1");
	$host_register = 1;
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
		return (str_replace(" ", "_", $form->getSubmitValue("host_name")));
	}
	$form->applyFilter('__ALL__', 'myTrim');
	$from_list_menu = false;
	if ($o != "mc")	{
		$form->applyFilter('host_name', 'myReplace');
		$form->addRule('host_name', _("Compulsory Name"), 'required');
		$form->registerRule('exist', 'callback', 'testHostExistence');
		$form->addRule('host_name', _("Name is already in use"), 'exist');		
		# If we are using a Template, no need to check the value, we hope there are in the Template
		if ((!$form->getSubmitValue("host_template_model_htm_id")) && ($oreon->user->get_version() != 3))	{
			$form->addRule('host_alias', _("Compulsory Alias"), 'required');
			$form->addRule('host_address', _("Compulsory Address"), 'required');
			$form->addRule('host_max_check_attempts', _("Required Field"), 'required');
			if ($oreon->user->get_version() == 2)	{
				$form->addRule('timeperiod_tp_id', _("Compulsory Period"), 'required');
				$form->addRule('host_cgs', _("Compulsory Contact Group"), 'required');
			}
			$form->addRule('host_notification_interval', _("Required Field"), 'required');
			$form->addRule('timeperiod_tp_id2', _("Compulsory Period"), 'required');
			$form->addRule('host_notifOpts', _("Compulsory Option"), 'required');
	
		}
	}
	else if ($o == "mc")	{
		if ($form->getSubmitValue("submitMC"))
			$from_list_menu = false;
		else
			$from_list_menu = true;
	}
	
	$form->setRequiredNote("<font style='color: red;'>*</font>". _(" Required fields"));
	#
	##End of form definition
	#
        
	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	# Just watch a host information
	if ($o == "w")	{
		if (!$min)
			$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&host_id=".$host_id."'"));
	    $form->setDefaults($host);
		$form->freeze();
	}
	# Modify a host information
	else if ($o == "c")	{
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($host);
	}
	# Add a host information
	else if ($o == "a")	{
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}
	# Massive Change
	else if ($o == "mc")	{
		$subMC =& $form->addElement('submit', 'submitMC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Reset"));
	}
	
	$tpl->assign('msg', array ("nagios"=>$oreon->user->get_version(), "tpl"=>0/*, "perfparse"=>$oreon->optGen["perfparse_installed"]*/));
	$tpl->assign('min', $min);
	$tpl->assign("sort1", _("Host Configuration"));
	$tpl->assign("sort2", _("Relations"));
	$tpl->assign("sort3", _("Data Processing"));
	$tpl->assign("sort4", _("Host Extended Infos"));
	$tpl->assign("sort5", _("Macros"));
	$tpl->assign('javascript', "<script type='text/javascript'>function showLogo(_img_dst, _value) {".
	"var _img = document.getElementById(_img_dst + '_img');".
	"_img.src = 'include/common/getHiddenImage.php?path=' + _value + '&logo=1' ; }</script>" );
	

	$tpl->assign('time_unit', " * ".$oreon->Nagioscfg["interval_length"]." "._(" seconds "));

	$valid = false;
	if ($form->validate() && $from_list_menu == false)	{
		$hostObj =& $form->getElement('host_id');
		if ($form->getSubmitValue("submitA"))
			$hostObj->setValue(insertHostInDB());
		else if ($form->getSubmitValue("submitC"))
			updateHostInDB($hostObj->getValue());
		else if ($form->getSubmitValue("submitMC"))	{
			$select = explode(",", $select);
			foreach ($select as $key=>$value)
				if ($value)
					updateHostInDB($value, true);
		}
		$o = NULL;
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&host_id=".$hostObj->getValue()."'"));
		$form->freeze();
		$valid = true;
	}
	$action = $form->getSubmitValue("action");
	if ($valid && $action["action"]["action"])
		require_once($path."listHost.php");
	else	{
		#Apply a template definition
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);
		$tpl->assign('is_not_template', $host_register);
		$tpl->assign('form', $renderer->toArray());
		$tpl->assign('o', $o);
		$tpl->assign('seconds', _("seconds"));
		$tpl->assign('p', $p);
		$tpl->assign("Freshness_Control_options", _("Freshness Control options"));
		$tpl->assign("Flapping_Options", _("Flapping options"));
		$tpl->assign("Perfdata_Options", _("Perfdata Options"));
		$tpl->assign("History_Options", _("History Options"));
		$tpl->assign("Event_Handler", _("Event Handler"));
		$tpl->assign("topdoc", _("Documentation"));
		$tpl->assign("hostID", $host_id);
		$tpl->assign("add_mtp_label", _("Add a template"));
		$tpl->assign("k", $k);
		$tpl->display("formHost.ihtml");
	}

if ($oreon->user->get_version() == 3 && !$action["action"]["action"]) {
?>

<script type="text/javascript">
		add_select_template();
		displayExistingMacroHost(<?php echo $k;?>);
</script>
<?php } ?>
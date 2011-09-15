<?php
/*
 * Copyright 2005-2011 MERETHIS
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

	if (!isset($oreon)) {
		exit();
	}

	require_once $centreon_path . 'www/class/centreonLDAP.class.php';
 	require_once $centreon_path . 'www/class/centreonContactgroup.class.php';

	$cct = array();
	if (($o == "c" || $o == "w") && $contact_id) {
		/**
		 * Init Tables informations
		 */
		$cct["contact_hostNotifCmds"] = array();
		$cct["contact_svNotifCmds"] = array();
		$cct["contact_cgNotif"] = array();

		$DBRESULT = $pearDB->query("SELECT * FROM contact WHERE contact_id = '".$contact_id."' LIMIT 1");
		$cct = array_map("myDecode", $DBRESULT->fetchRow());
		$cct["contact_passwd"] = NULL;
		$DBRESULT->free();

		/**
		 * Set Host Notification Options
		 */
		$tmp = explode(',', $cct["contact_host_notification_options"]);
		foreach ($tmp as $key => $value) {
			$cct["contact_hostNotifOpts"][trim($value)] = 1;
		}

		/**
		 * Set Service Notification Options
		 */
		$tmp = explode(',', $cct["contact_service_notification_options"]);
		foreach ($tmp as $key => $value) {
			$cct["contact_svNotifOpts"][trim($value)] = 1;
		}
		$DBRESULT->free();

		/**
		 * Set Contact Group Parents
		 */
		$DBRESULT = $pearDB->query("SELECT DISTINCT contactgroup_cg_id FROM contactgroup_contact_relation WHERE contact_contact_id = '".$contact_id."'");
		for ($i = 0; $notifCg = $DBRESULT->fetchRow(); $i++) {
			$cct["contact_cgNotif"][$i] = $notifCg["contactgroup_cg_id"];
		}
		$DBRESULT->free();

		/**
		 * Set Host Notification Commands
		 */
		$DBRESULT = $pearDB->query("SELECT DISTINCT command_command_id FROM contact_hostcommands_relation WHERE contact_contact_id = '".$contact_id."'");
		for ($i = 0; $notifCmd = $DBRESULT->fetchRow(); $i++) {
			$cct["contact_hostNotifCmds"][$i] = $notifCmd["command_command_id"];
		}
		$DBRESULT->free();

		/**
		 * Set Service Notification Commands
		 */
		$DBRESULT = $pearDB->query("SELECT DISTINCT command_command_id FROM contact_servicecommands_relation WHERE contact_contact_id = '".$contact_id."'");
		for ($i = 0; $notifCmd = $DBRESULT->fetchRow(); $i++) {
			$cct["contact_svNotifCmds"][$i] = $notifCmd["command_command_id"];
		}
		$DBRESULT->free();

		/**
		 * Get DLAP auth informations
		 */
		$DBRESULT = $pearDB->query("SELECT * FROM `options` WHERE `key` = 'ldap_auth_enable'");
		while ($ldap_auths = $DBRESULT->fetchRow()) {
			$ldap_auth[$ldap_auths["key"]] = myDecode($ldap_auths["value"]);
		}
		$DBRESULT->free();

		/**
		 * Get ACL informations for this user
		 */
		$DBRESULT = $pearDB->query("SELECT acl_group_id FROM `acl_group_contacts_relations` WHERE `contact_contact_id` = '".$contact_id."'");
		for ($i = 0; $data = $DBRESULT->fetchRow(); $i++) {
			$cct["contact_acl_groups"][$i] = $data["acl_group_id"];
		}
		$DBRESULT->free();
	}

	/**
	 * Get Langs
	 */
	$langs = array();
	$langs = getLangs();
    if ($o == "mc") {
	    array_unshift($langs, null);
	}

	/**
	 * Timeperiods comes from DB -> Store in $notifsTps Array
	 * When we make a massive change, give the possibility to not crush value
	 */
	$notifTps = array(NULL => NULL);
	$DBRESULT = $pearDB->query("SELECT tp_id, tp_name FROM timeperiod ORDER BY tp_name");
	while ($notifTp = $DBRESULT->fetchRow())
		$notifTps[$notifTp["tp_id"]] = $notifTp["tp_name"];
	$DBRESULT->free();

	/**
	 * Notification commands comes from DB -> Store in $notifsCmds Array
	 */
	$notifCmds = array();
	$DBRESULT = $pearDB->query("SELECT command_id, command_name FROM command WHERE command_type = '1' ORDER BY command_name");
	while ($notifCmd = $DBRESULT->fetchRow())
		$notifCmds[$notifCmd["command_id"]] = $notifCmd["command_name"];
	$DBRESULT->free();

	/**
	 * Contact Groups comes from DB -> Store in $notifCcts Array
	 */
	$notifCgs = array();
	/*$DBRESULT = $pearDB->query("SELECT cg_id, cg_name FROM contactgroup ORDER BY cg_name");
	while ($notifCg = $DBRESULT->fetchRow())
		$notifCgs[$notifCg["cg_id"]] = $notifCg["cg_name"];
	$DBRESULT->free();*/
	$cg = new CentreonContactgroup($pearDB);
	$notifCgs = $cg->getListContactgroup(false);

	if ($oreon->optGen['ldap_auth_enable'] == 1 && $cct['contact_auth_type'] == 'ldap') {
	    $ldap = new CentreonLDAP($pearDB, null);
	    if (false !== $ldap->connect()) {
	        $cgLdap = $ldap->listGroupsForUser($cct['contact_ldap_dn']);
	    }
	}

	/**
	 * Get ACL Groups List
	 */
	$aclGroups = array();
	$DBRESULT = $pearDB->query("SELECT acl_group_id, acl_group_name FROM acl_groups ORDER BY acl_group_name");
	while ($aclGroup = $DBRESULT->fetchRow()) {
		$aclGroups[$aclGroup["acl_group_id"]] = $aclGroup["acl_group_name"];
	}
	$DBRESULT->free();

	/**
	 * Contacts Templates
	 */
	if (isset($contact_id)) {
		$strRestrinction = " AND contact_id != '".$contact_id."'";
	} else {
		$strRestrinction = "";
	}

	$contactTpl = array(NULL => "           ");
	$DBRESULT = $pearDB->query("SELECT contact_id, contact_name FROM contact WHERE contact_register = '0' $strRestrinction ORDER BY contact_name");
	while ($contacts = $DBRESULT->fetchRow()) {
		$contactTpl[$contacts["contact_id"]] = $contacts["contact_name"];
	}
	$DBRESULT->free();

	/**
	 * Template / Style for Quickform input
	 */
	$attrsText 		= array("size"=>"30");
	$attrsText2 	= array("size"=>"60");
	$attrsTextDescr	= array("size"=>"80");
	$attrsTextMail 	= array("size"=>"90");
	$attrsAdvSelect = array("style" => "width: 300px; height: 100px;");
	$attrsTextarea 	= array("rows"=>"15", "cols"=>"100");
	$eTemplate	= '<table><tr><td><div class="ams">{label_2}</div>{unselected}</td><td align="center">{add}<br /><br /><br />{remove}</td><td><div class="ams">{label_3}</div>{selected}</td></tr></table>';

	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add a User"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify a User"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View a User"));
	else if ($o == "mc")
		$form->addElement('header', 'title', _("Massive Change"));

	/**
	 * Contact basic information
	 */
	$form->addElement('header', 'information', _("General Information"));
	$form->addElement('header', 'additional', _("Additional Information"));
	$form->addElement('header', 'centreon', _("Centreon Authentication"));
	$form->addElement('header', 'acl', _("Access lists"));

	/**
	 * No possibility to change name and alias, because there's no interest
	 */

	/**
	 * Don't change contact name and alias in massif change
	 * Don't change contact name, alias or autologin key in massive change
	 */
	if ($o != "mc")	{
		$form->addElement('text', 'contact_name', _("Full Name"), $attrsTextDescr);
		$form->addElement('text', 'contact_alias', _("Alias / Login"), $attrsText);
		$form->addElement('text', 'contact_autologin_key', _("Autologin Key"), array("size" => "90", "id" => "aKey"));
		$form->addElement('button','contact_gen_akey',_("Generate"), array( 'onclick' => 'generatePassword("aKey");'));
	}

	$form->addElement('text', 'contact_email', _("Email"), $attrsTextMail);
	$form->addElement('text', 'contact_pager', _("Pager"), $attrsText);

	/**
	 * Contact template used
	 */
	$form->addElement('select', 'contact_template_id', _("Contact template used"), $contactTpl);

	$form->addElement('header', 'furtherAddress', _("Additional Addresses"));
	$form->addElement('text', 'contact_address1', _("Address1"), $attrsText);
	$form->addElement('text', 'contact_address2', _("Address2"), $attrsText);
	$form->addElement('text', 'contact_address3', _("Address3"), $attrsText);
	$form->addElement('text', 'contact_address4', _("Address4"), $attrsText);
	$form->addElement('text', 'contact_address5', _("Address5"), $attrsText);
	$form->addElement('text', 'contact_address6', _("Address6"), $attrsText);

	/**
	 * Contact Groups Field
	 */
	$form->addElement('header', 'groupLinks', _("Group Relations"));
	if ($o == "mc")	{
		$mc_mod_cg = array();
		$mc_mod_cg[] = HTML_QuickForm::createElement('radio', 'mc_mod_cg', null, _("Incremental"), '0');
		$mc_mod_cg[] = HTML_QuickForm::createElement('radio', 'mc_mod_cg', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_cg, 'mc_mod_cg', _("Update mode"), '&nbsp;');
		$form->setDefaults(array('mc_mod_cg'=>'0'));
	}
	$ams3 = $form->addElement('advmultiselect', 'contact_cgNotif', array(_("Linked to Contact Groups"), _("Available"), _("Selected")), $notifCgs, $attrsAdvSelect, SORT_ASC);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Remove")));
	$ams3->setElementTemplate($eTemplate);
	echo $ams3->getElementJs(false);

	/**
	 * Contact Centreon information
	 */
	$form->addElement('header', 'oreon', _("Centreon"));
	$tab = array();
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_oreon', null, _("Yes"), '1');
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_oreon', null, _("No"), '0');
	$form->addGroup($tab, 'contact_oreon', _("Reach Centreon Front-end"), '&nbsp;');

	$form->addElement('password', 'contact_passwd', _("Password"), array("size"=>"30", "autocomplete"=>"off", "id"=>"passwd1", "onFocus" => "resetPwdType(this);"));
	$form->addElement('password', 'contact_passwd2', _("Confirm Password"), array("size"=>"30", "autocomplete"=>"off", "id"=>"passwd2", "onFocus" => "resetPwdType(this);"));
	$form->addElement('button','contact_gen_passwd',_("Generate"), array('onclick'=>'generatePassword("passwd");'));
    $form->addElement('select', 'contact_lang', _("Default Language"), $langs);
    $form->addElement('select', 'contact_type_msg', _("Mail Type"), array(NULL=>NULL, "txt"=>"txt", "html"=>"html", "pdf"=>"pdf"));
	$tab = array();
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_admin', null, _("Yes"), '1');
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_admin', null, _("No"), '0');
	$form->addGroup($tab, 'contact_admin', _("Admin"), '&nbsp;');

	/**
	 * ACL configurations
	 */
	if ($o == "mc")	{
		$mc_mod_cg = array();
		$mc_mod_cg[] = HTML_QuickForm::createElement('radio', 'mc_mod_acl', null, _("Incremental"), '0');
		$mc_mod_cg[] = HTML_QuickForm::createElement('radio', 'mc_mod_acl', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_cg, 'mc_mod_acl', _("Update mode"), '&nbsp;');
		$form->setDefaults(array('mc_mod_acl'=>'0'));
	}
	$ams3 = $form->addElement('advmultiselect', 'contact_acl_groups', array(_("Access list groups"), _("Available"), _("Selected")), $aclGroups, $attrsAdvSelect, SORT_ASC);
	$ams3->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams3->setButtonAttributes('remove', array('value' => _("Remove")));
	$ams3->setElementTemplate($eTemplate);
	echo $ams3->getElementJs(false);

	/**
	 * Include GMT Class
	 */
	require_once $centreon_path."www/class/centreonGMT.class.php";

	$CentreonGMT = new CentreonGMT($pearDB);

	$GMTList = $CentreonGMT->getGMTList();
	$form->addElement('select', 'contact_location', _("Timezone / Location"), $GMTList);
	$form->setDefaults(array('contact_location' => '0'));
	if (!isset($cct["contact_location"]))
		$cct["contact_location"] = 0;
	unset($GMTList);

	if ($o != "mc") {
        $auth_type = array();
	} else {
	    $auth_type = array(null => null);
	}

   	$auth_type["local"] = "Centreon";
	if ($oreon->optGen['ldap_auth_enable'] == 1) {
		$auth_type["ldap"] = "LDAP";
		$form->addElement('text', 'contact_ldap_dn', _("LDAP DN (Distinguished Name)"), $attrsText2);
	}
	if ($o != "mc") {
	    $form->setDefaults(array('contact_oreon' => '1', "contact_admin" => '0'));
	}
   	$form->addElement('select', 'contact_auth_type', _("Authentication Source"), $auth_type);

	/**
	 * Notification informations
	 */
   	$form->addElement('header', 'notification', _("Notification"));

	$tab = array();
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_enable_notifications', null, _("Yes"), '1');
	$tab[] = HTML_QuickForm::createElement('radio', 'contact_enable_notifications', null, _("No"), '0');
	$form->addGroup($tab, 'contact_enable_notifications', _("Enable Notifications"), '&nbsp;');
	$form->setDefaults(array('contact_enable_notifications' => '0'));

	/** ******************************
	 * Host notifications
	 */
	$form->addElement('header', 'hostNotification', _("Host"));
 	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'd', '&nbsp;', _("Down"), array('id' => 'hDown', 'onClick' => 'uncheckAllH(this);'));
	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', _("Unreachable"), array('id' => 'hUnreachable', 'onClick' => 'uncheckAllH(this);'));
	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', _("Recovery"), array('id' => 'hRecovery', 'onClick' => 'uncheckAllH(this);'));
	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'f', '&nbsp;', _("Flapping"), array('id' => 'hFlapping', 'onClick' => 'uncheckAllH(this);'));
	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 's', '&nbsp;', _("Downtime Scheduled"), array('id' => 'hScheduled', 'onClick' => 'uncheckAllH(this);'));
	$hostNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'n', '&nbsp;', _("None"), array('id' => 'hNone', 'onClick' => 'javascript:uncheckAllH(this);'));
	$form->addGroup($hostNotifOpt, 'contact_hostNotifOpts', _("Host Notification Options"), '&nbsp;&nbsp;');
    $form->addElement('select', 'timeperiod_tp_id', _("Host Notification Period"), $notifTps);
	unset($hostNotifOpt);

	if ($o == "mc")	{
		$mc_mod_hcmds = array();
		$mc_mod_hcmds[] = HTML_QuickForm::createElement('radio', 'mc_mod_hcmds', null, _("Incremental"), '0');
		$mc_mod_hcmds[] = HTML_QuickForm::createElement('radio', 'mc_mod_hcmds', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_hcmds, 'mc_mod_hcmds', _("Update mode"), '&nbsp;');
		$form->setDefaults(array('mc_mod_hcmds'=>'0'));
	}

	$ams1 = $form->addElement('advmultiselect', 'contact_hostNotifCmds', array(_("Host Notification Commands"), _("Available"), _("Selected")), $notifCmds, $attrsAdvSelect, SORT_ASC);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Remove")));
	$ams1->setElementTemplate($eTemplate);
	echo $ams1->getElementJs(false);

	/** ******************************
	 * Service notifications
	 */
	$form->addElement('header', 'serviceNotification', _("Service"));
 	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'w', '&nbsp;', _("Warning"), array('id' => 'sWarning', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'u', '&nbsp;', _("Unknown"), array('id' => 'sUnknown', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'c', '&nbsp;', _("Critical"), array('id' => 'sCritical', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'r', '&nbsp;', _("Recovery"), array('id' => 'sRecovery', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'f', '&nbsp;', _("Flapping"), array('id' => 'sFlapping', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 's', '&nbsp;', _("Downtime Scheduled"), array('id' => 'sScheduled', 'onClick' => 'uncheckAllS(this);'));
	$svNotifOpt[] = HTML_QuickForm::createElement('checkbox', 'n', '&nbsp;', _("None"), array('id' => 'sNone', 'onClick' => 'uncheckAllS(this);'));
	$form->addGroup($svNotifOpt, 'contact_svNotifOpts', _("Service Notification Options"), '&nbsp;&nbsp;');
	$form->addElement('select', 'timeperiod_tp_id2', _("Service Notification Period"), $notifTps);
 	if ($o == "mc")	{
		$mc_mod_svcmds = array();
		$mc_mod_svcmds[] = HTML_QuickForm::createElement('radio', 'mc_mod_svcmds', null, _("Incremental"), '0');
		$mc_mod_svcmds[] = HTML_QuickForm::createElement('radio', 'mc_mod_svcmds', null, _("Replacement"), '1');
		$form->addGroup($mc_mod_svcmds, 'mc_mod_svcmds', _("Update mode"), '&nbsp;');
		$form->setDefaults(array('mc_mod_svcmds'=>'0'));
	}
	$ams2 = $form->addElement('advmultiselect', 'contact_svNotifCmds', array(_("Service Notification Commands"), _("Available"), _("Selected")), $notifCmds, $attrsAdvSelect, SORT_ASC);
	$ams2->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams2->setButtonAttributes('remove', array('value' => _("Remove")));
	$ams2->setElementTemplate($eTemplate);
	echo $ams2->getElementJs(false);

	/**
	 * Further informations
	 */
	$form->addElement('header', 'furtherInfos', _("Additional Information"));
	$cctActivation[] = HTML_QuickForm::createElement('radio', 'contact_activate', null, _("Enabled"), '1');
	$cctActivation[] = HTML_QuickForm::createElement('radio', 'contact_activate', null, _("Disabled"), '0');
	$form->addGroup($cctActivation, 'contact_activate', _("Status"), '&nbsp;');
	$form->setDefaults(array('contact_activate' => '1'));

	$cctRegister[] = HTML_QuickForm::createElement('radio', 'contact_register', null, _("Enabled"), '0');
	$cctRegister[] = HTML_QuickForm::createElement('radio', 'contact_register', null, _("Disabled"), '1');
	$form->addGroup($cctRegister, 'contact_register', _("Is this contact a template ?"), '&nbsp;');
	$form->setDefaults(array('contact_register' => '0'));

	$form->addElement('textarea', 'contact_comment', _("Comments"), $attrsTextarea);

	$tab = array();
	$tab[] = HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
	$tab[] = HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
	$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
	$form->setDefaults(array('action'=>'1'));

	$form->addElement('hidden', 'contact_id');
	$redirect = $form->addElement('hidden', 'o');
	$redirect->setValue($o);
	if (is_array($select))	{
		$select_str = NULL;
		foreach ($select as $key => $value)
			$select_str .= $key.",";
		$select_pear = $form->addElement('hidden', 'select');
		$select_pear->setValue($select_str);
	}

	/**
	 * Form Rules
	 */
	function myReplace()	{
		global $form;
		$ret = $form->getSubmitValues();
		return (str_replace(" ", "_", $ret["contact_name"]));
	}
	$form->applyFilter('__ALL__', 'myTrim');
	$form->applyFilter('contact_name', 'myReplace');
	$from_list_menu = false;
	if ($o != "mc")	{
		$ret = $form->getSubmitValues();
		$form->addRule('contact_name', _("Compulsory Name"), 'required');
		$form->addRule('contact_alias', _("Compulsory Alias"), 'required');
		$form->addRule('contact_email', _("Valid Email"), 'required');
		$form->addRule('contact_oreon', _("Required Field"), 'required');
		$form->addRule('contact_lang', _("Required Field"), 'required');
		$form->addRule('contact_admin', _("Required Field"), 'required');
		$form->addRule('contact_auth_type', _("Required Field"), 'required');

		if (isset($ret["contact_enable_notifications"]["contact_enable_notifications"]) && $ret["contact_enable_notifications"]["contact_enable_notifications"] == 1) {
			$form->addRule('timeperiod_tp_id', _("Compulsory Period"), 'required');
			$form->addRule('timeperiod_tp_id2', _("Compulsory Period"), 'required');
			$form->addRule('contact_hostNotifOpts', _("Compulsory Option"), 'required');
			$form->addRule('contact_svNotifOpts', _("Compulsory Option"), 'required');
			$form->addRule('contact_hostNotifCmds', _("Compulsory Command"), 'required');
			$form->addRule('contact_svNotifCmds', _("Compulsory Command"), 'required');
		}

		$form->addRule(array('contact_passwd', 'contact_passwd2'), _("Passwords do not match"), 'compare');
		$form->registerRule('exist', 'callback', 'testContactExistence');
		$form->addRule('contact_name', "<font style='color: red;'>*</font>&nbsp;" . _("Contact already exists"), 'exist');
		$form->registerRule('existAlias', 'callback', 'testAliasExistence');
		$form->addRule('contact_alias', "<font style='color: red;'>*</font>&nbsp;" . _("Alias already exists"), 'existAlias');
		$form->registerRule('keepOneContactAtLeast', 'callback', 'keepOneContactAtLeast');
		$form->addRule('contact_alias', _("You have to keep at least one contact to access to Centreon"), 'keepOneContactAtLeast');
	} else if ($o == "mc")	{
		if ($form->getSubmitValue("submitMC"))
			$from_list_menu = false;
		else
			$from_list_menu = true;
	}
	$form->setRequiredNote("<font style='color: red;'>*</font>&nbsp;" . _("Required fields"));


	/**
	 * Smarty template Init
	 */
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	$tpl->assign("helpattr", 'TITLE, "'._("Help").'", CLOSEBTN, true, FIX, [this, 0, 5], BGCOLOR, "#ffff99", BORDERCOLOR, "orange", TITLEFONTCOLOR, "black", TITLEBGCOLOR, "orange", CLOSEBTNCOLORS, ["","black", "white", "red"], WIDTH, -300, SHADOW, true, TEXTALIGN, "justify"' );

	# prepare help texts
	$helptext = "";
	include_once("help.php");
	foreach ($help as $key => $text) {
		$helptext .= '<span style="display:none" id="help:'.$key.'">'.$text.'</span>'."\n";
	}
	$tpl->assign("helptext", $helptext);

	if ($o == "w")	{
		# Just watch a contact information
		if ($centreon->user->access->page($p) != 2)
			$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&contact_id=".$contact_id."'"));
	    $form->setDefaults($cct);
		$form->freeze();
	} else if ($o == "c")	{
		# Modify a contact information
		$subC = $form->addElement('submit', 'submitC', _("Save"));
		$res = $form->addElement('reset', 'reset', _("Reset"));
	    $form->setDefaults($cct);
	} else if ($o == "a")	{
		# Add a contact information
		$subA = $form->addElement('submit', 'submitA', _("Save"));
		$res = $form->addElement('reset', 'reset', _("Reset"));
	} else if ($o == "mc")	{
		# Massive Change
		$subMC = $form->addElement('submit', 'submitMC', _("Save"));
		$res = $form->addElement('reset', 'reset', _("Reset"));
	}

	if ($oreon->optGen['ldap_auth_enable'] == 1 && $cct['contact_auth_type'] == 'ldap') {
	    $tpl->assign("ldap_group", _("Group Ldap"));
	    if (isset($cgLdap)) {
	        $tpl->assign("ldapGroups", $cgLdap);
	    }
	}

	$valid = false;
	if ($form->validate() && $from_list_menu == false)	{
		$cctObj = $form->getElement('contact_id');
		if ($form->getSubmitValue("submitA"))
			$cctObj->setValue(insertContactInDB());
		else if ($form->getSubmitValue("submitC"))
			updateContactInDB($cctObj->getValue());
		else if ($form->getSubmitValue("submitMC"))	{
			$select = explode(",", $select);
			foreach ($select as $key=>$value)
				if ($value)
					updateContactInDB($value, true);
		}
		$o = NULL;
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&contact_id=".$cctObj->getValue()."'"));
		$form->freeze();
		$valid = true;
	}
	$action = $form->getSubmitValue("action");
	if ($valid && $action["action"]["action"]) {
		require_once($path."listContact.php");
	} else	{
		# Apply a template definition
		$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl, true);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
		$form->accept($renderer);
		$tpl->assign('form', $renderer->toArray());
		$tpl->assign('o', $o);
		$tpl->assign("tzUsed", $CentreonGMT->used());
		if ($oreon->optGen['ldap_auth_enable'])
			$tpl->assign('ldap', $oreon->optGen['ldap_auth_enable'] );
		$tpl->display("formContact.ihtml");
	}
?>

<script type="text/javascript">

function uncheckAllH(object)
{
	if (object.id == "hNone" && object.checked) {
		document.getElementById('hDown').checked = false;
		document.getElementById('hUnreachable').checked = false;
		document.getElementById('hRecovery').checked = false;
		if (document.getElementById('hFlapping')) {
			document.getElementById('hFlapping').checked = false;
		}
		if (document.getElementById('hScheduled')) {
			document.getElementById('hScheduled').checked = false;
		}
	} else {
		document.getElementById('hNone').checked = false;
	}
}

function uncheckAllS(object)
{
	if (object.id == "sNone" && object.checked) {
		document.getElementById('sWarning').checked = false;
		document.getElementById('sUnknown').checked = false;
		document.getElementById('sCritical').checked = false;
		document.getElementById('sRecovery').checked = false;
		if (document.getElementById('sFlapping')) {
			document.getElementById('sFlapping').checked = false;
		}
		if(document.getElementById('sScheduled')) {
			document.getElementById('sScheduled').checked = false;
		}
	} else {
		document.getElementById('sNone').checked = false;
	}
}

/*
 * Script found on http://www.blazonry.com/javascript/password.php
 * simplified to generate a random password for autologin_key
 */
function generatePassword(what)
{
    if (parseInt(navigator.appVersion) <= 3) {
        alert("Sorry this only works in 4.0+ browsers");
        return true;
    }

    var length=8;
    var sPassword = "";

    /*
    * Stick on 8 chars for user password, use random lenght for autologin key
    * at least 8, at max 64, more changes to keep something small wich is not bad as it will be used in url
    */
    if (what == "aKey") {
        length = Math.random();
       length = parseInt(length * 156);
       length = (length % 3) + 8;
    }

    for (i=0; i < length; i++) {
        numI = getRandomNum();
       while (checkPunc(numI)) { numI = getRandomNum(); }
        sPassword = sPassword + String.fromCharCode(numI);
    }

    /*
     * If for aKey => just enter the value
     * If for password => change the input type to text to allow to write down the pass. (Done on both for visual consistence)
     * TODO (maybe): Rechange the type if typing into the box...
     */
    if (what == "aKey") {
		document.getElementById('aKey').value = sPassword;
    } else {
		document.getElementById('passwd1').value = sPassword;
		document.getElementById('passwd1').setAttribute('type','text');
		document.getElementById('passwd2').value = sPassword;
		document.getElementById('passwd2').setAttribute('type','text');
    }
    sPassword = str_md5(sPassword);
    return true;
}

function getRandomNum()
{
	// between 0 - 1
	var rndNum = Math.random()

	// rndNum from 0 - 1000
	rndNum = parseInt(rndNum * 1000);

	// rndNum from 33 - 127
	rndNum = (rndNum % 94) + 33;

	return rndNum;
}

function checkPunc(num)
{
	if ((num >=33) && (num <=47)) { return true; }
	if ((num >=58) && (num <=64)) { return true; }
	if ((num >=91) && (num <=96)) { return true; }
	if ((num >=123) && (num <=126)) { return true; }

	return false;
}

function resetPwdType(elem)
{
	elem.setAttribute('type', 'password');
}
</script>
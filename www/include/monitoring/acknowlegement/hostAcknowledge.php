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

	if (!isset ($oreon))
		exit ();

	require_once "HTML/QuickForm.php";
	require_once 'HTML/QuickForm/Renderer/ArraySmarty.php';
	require_once "./DBNDOConnect.php";
	
	isset($_GET["host_name"]) ? $host_name = $_GET["host_name"] : $host_name = NULL;
	isset($_GET["cmd"]) ? $cmd = $_GET["cmd"] : $cmd = NULL;
	isset($_GET["en"]) ? $en = $_GET["en"] : $en = 1;
	
	$path = "./include/monitoring/acknowlegement/";

	/*
	 * Smarty template Init
	 */
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl, './templates/');

	if (!$is_admin)
		$lcaHostByName = $oreon->user->access->getHostServicesName($pearDBndo);
		
	if ($is_admin || (isset($lcaHostByName["LcaHost"][$host_name]))){

		#Pear library
		
		$form = new HTML_QuickForm('select_form', 'GET', "?p=".$p);
	
		$form->addElement('header', 'title', 'Command Options');
	
		$tpl->assign('hostlabel', _("Host Name"));
		$tpl->assign('hostname', $host_name);
		$tpl->assign('en', $en);
		$tpl->assign('authorlabel', _("Alias"));
		$tpl->assign('authoralias', $oreon->user->get_alias());
	
		$ckbx[] =& $form->addElement('checkbox', 'notify', 'notify');
		$ckbx[0]->setChecked(true);
			
		$ckbx1[] =& $form->addElement('checkbox', 'persistent', 'persistent');
		$ckbx1[0]->setChecked(true);
	
		$form->addElement('hidden', 'host_name', $host_name);
		$form->addElement('hidden', 'author', $oreon->user->get_alias());
		$form->addElement('hidden', 'cmd', $cmd);
		$form->addElement('hidden', 'p', $p);
	
		$form->addElement('hidden', 'en', $en);
		
		$attr =  array("rows"=>"4", "cols"=>"80");
		$form->addElement('textarea', 'comment', 'comment', $attr);
		
		$form->addRule('comment', _("Comment is required"), 'required', '', 'client');
		$form->setJsWarnings(_("Invalid information entered"),_("Please correct these fields"));
		
		$form->addElement('submit', 'submit', ($en == 1) ? _("Add") : _("Delete"));
		$form->addElement('reset', 'reset', _("Reset"));
	
		$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
		$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
		$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
	
		$form->accept($renderer);
		$tpl->assign('form', $renderer->toArray());
	
		$tpl->assign('o', 'hd');
		$tpl->display("hostAcknowledge.ihtml");
	}
?>
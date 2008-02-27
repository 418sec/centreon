<?php
/**
Centreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Julien Mathis - Romain Le Merlus

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/

	if (isset($_GET["command_id"]))
		$command_id = $_GET["command_id"];
	else if (isset($_POST["command_id"]))
		$command_id = $_POST["command_id"];
	else
		$command_id = NULL;

	if (isset($_GET["command_name"]))
		$command_name = $_GET["command_name"];
	else if (isset($_POST["command_name"]))
		$command_name = $_POST["command_name"];
	else
		$command_name = NULL;

	if($command_id != NULL){
		$DBRESULT =& $pearDB->query("SELECT * FROM command WHERE command_id = '".$command_id."' LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
		$cmd = $DBRESULT->fetchRow();

		$cmd_array = explode(" ", $cmd["command_line"]);
		$full_line = $cmd_array[0];
		$cmd_array = explode("#S#", $full_line);
		$resource_info = $cmd_array[0];
		$resource_def = str_replace('$', '@DOLLAR@', $resource_info);

		# Match if the first part of the path is a MACRO
		if (preg_match("/@DOLLAR@USER([0-9]+)@DOLLAR@/", $resource_def, $matches))	{
			$DBRESULT =& $pearDB->query("SELECT resource_line FROM cfg_resource WHERE resource_name = '\$USER".$matches[1]."\$' LIMIT 1");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
			$resource = $DBRESULT->fetchRow();
			$resource_path = explode("=", $resource["resource_line"]);
			$resource_path = $resource_path[1];
			unset($cmd_array[0]);
			$command = rtrim($resource_path, "/")."#S#".implode("#S#", $cmd_array);
		}
		else
			$command = $full_line;
	}
	else{
		$command = $oreon->optGen["nagios_path_plugins"] . $command_name;
	}

	$command = str_replace("#S#", "/", $command);
	$stdout = shell_exec($command." --help");
	$msg = str_replace ("\n", "<br />", $stdout);

	$attrsText 	= array("size"=>"25");
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	$form->addElement('header', 'title',_("Plugin Help"));
	#
	## Command information
	#
	$form->addElement('header', 'information', _("Help"));
	$form->addElement('text', 'command_line', _("Command Line"), $attrsText);
	$form->addElement('text', 'command_help', _("Output"), $attrsText);

	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);
	$tpl->assign('command_line', $command." --help");
	if (isset($msg) && $msg)
		$tpl->assign('msg', $msg);

	#
	##Apply a template definition
	#
	$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$form->accept($renderer);
	$tpl->assign('form', $renderer->toArray());
	$tpl->assign('o', $o);
	$tpl->display("minHelpCommand.ihtml");
?>
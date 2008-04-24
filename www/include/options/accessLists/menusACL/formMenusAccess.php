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
	 * Database retrieve information for LCA
	 */
	if ($o == "c" || $o == "w")	{
		$DBRESULT =& $pearDB->query("SELECT * FROM acl_topology WHERE acl_topo_id = '".$acl_id."' LIMIT 1");
		
		# Set base value
		$acl = array_map("myDecode", $DBRESULT->fetchRow());
		
		# Set Topology relations
		$DBRESULT =& $pearDB->query("SELECT topology_topology_id FROM acl_topology_relations WHERE acl_topo_id = '".$acl_id."'");
		for ($i = 0; $DBRESULT->fetchInto($topo); $i++)
			$acl["acl_topos"][$topo["topology_topology_id"]] = 1;
		$DBRESULT->free();
		
		# Set Contact Groups relations
		$DBRESULT =& $pearDB->query("SELECT DISTINCT acl_group_id FROM acl_group_topology_relations WHERE acl_topology_id = '".$acl_id."'");
		for($i = 0; $groups = $DBRESULT->fetchRow(); $i++)
			$acl["acl_groups"][$i] = $groups["acl_group_id"];
		$DBRESULT->free();
	}

	$groups = array();
	$DBRESULT =& $pearDB->query("SELECT acl_group_id, acl_group_name FROM acl_groups ORDER BY acl_group_name");
	if (PEAR::isError($DBRESULT)) print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	while($group = $DBRESULT->fetchRow())
		$groups[$group["acl_group_id"]] = $group["acl_group_name"];
	$DBRESULT->free();

	if	(!isset($acl["acl_topos"]))
		$acl["acl_topos"] = array();

	/*
	 * Var information to format the element
	 */
	
	$attrsText 		= array("size"=>"30");
	$attrsAdvSelect = array("style" => "width: 200px; height: 100px;");
	$attrsTextarea 	= array("rows"=>"3", "cols"=>"30");
	$template 		= "<table><tr><td>{unselected}</td><td align='center'>{add}<br /><br /><br />{remove}</td><td>{selected}</td></tr></table>";

	#
	## Form begin
	#
	$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
	if ($o == "a")
		$form->addElement('header', 'title', _("Add an ACL"));
	else if ($o == "c")
		$form->addElement('header', 'title', _("Modify an ACL"));
	else if ($o == "w")
		$form->addElement('header', 'title', _("View an ACL"));

	/*
	 * LCA basic information
	 */
	$form->addElement('header', 'information', _("General Information"));
	$form->addElement('text',	'acl_topo_name', _("ACL Definition"), $attrsText);
	$form->addElement('text', 	'acl_topo_alias', _("Alias"), $attrsText);

    $ams1 =& $form->addElement('advmultiselect', 'acl_groups', _("Linked Groups"), $groups, $attrsAdvSelect);
	$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
	$ams1->setButtonAttributes('remove', array('value' => _("Delete")));
	$ams1->setElementTemplate($template);
	echo $ams1->getElementJs(false);

	$tab = array();
	$tab[] = &HTML_QuickForm::createElement('radio', 'acl_topo_activate', null, _("Enabled"), '1');
	$tab[] = &HTML_QuickForm::createElement('radio', 'acl_topo_activate', null, _("Disabled"), '0');
	$form->addGroup($tab, 'acl_topo_activate', _("Status"), '&nbsp;');
	$form->setDefaults(array('acl_topo_activate' => '1'));

	/*
	 * Further informations
	 */
	$form->addElement('header', 'furtherInfos', _("Additional Information"));
	$form->addElement('textarea', 'lca_comment', _("Comments"), $attrsTextarea);
	
	/*
	 * Topology concerned
	 */
	$form->addElement('header', 'pages', _("Accessible Pages"));
	$DBRESULT1 =& $pearDB->query("SELECT topology_id, topology_page, topology_name, topology_parent FROM topology WHERE topology_parent IS NULL AND topology_page IN (".$oreon->user->lcaTStr.") ORDER BY topology_order, topology_group");
	
	$acl_topos 	= array();
	$acl_topos2 = array();
	$a = 0;
	while ($topo1 = $DBRESULT1->fetchRow())	{
		$acl_topos2[$a] = array();
		$acl_topos2[$a]["name"] = _($topo1["topology_name"]);
		$acl_topos2[$a]["id"] = $topo1["topology_id"];
		$acl_topos2[$a]["checked"] = isset($acl["acl_topos"][$topo1["topology_id"]]) ? "true" : "false";
		$acl_topos2[$a]["c_id"] = $a;
		$acl_topos2[$a]["childs"] = array();

		$acl_topos[] =  &HTML_QuickForm::createElement('checkbox', $topo1["topology_id"], null, _($topo1["topology_name"]), array("style"=>"margin-top: 5px;", "id"=>$topo1["topology_id"]));

		$b = 0;
	 	$DBRESULT2 =& $pearDB->query("SELECT topology_id, topology_page, topology_name, topology_parent FROM topology WHERE topology_parent = '".$topo1["topology_page"]."' AND topology_page IN (".$oreon->user->lcaTStr.") ORDER BY topology_order");
		while ($topo2 = $DBRESULT2->fetchRow())	{
			$acl_topos2[$a]["childs"][$b] = array();
			$acl_topos2[$a]["childs"][$b]["name"] = _($topo2["topology_name"]);
			$acl_topos2[$a]["childs"][$b]["id"] = $topo2["topology_id"];
			$acl_topos2[$a]["childs"][$b]["checked"] = isset($acl["acl_topos"][$topo2["topology_id"]]) ? "true" : "false";
			$acl_topos2[$a]["childs"][$b]["c_id"] = $a."_".$b;
			$acl_topos2[$a]["childs"][$b]["childs"] = array();
			
		 	$acl_topos[] =  &HTML_QuickForm::createElement('checkbox', $topo2["topology_id"], NULL, _($topo2["topology_name"])."<br />", array("style"=>"margin-top: 5px; margin-left: 20px;"));
			$c = 0;
		 	$DBRESULT3 =& $pearDB->query("SELECT topology_id, topology_name, topology_parent, topology_page FROM topology WHERE topology_parent = '".$topo2["topology_page"]."' AND topology_page IN (".$oreon->user->lcaTStr.") ORDER BY topology_order");
			while ($topo3 = $DBRESULT3->fetchRow()){
				$acl_topos2[$a]["childs"][$b]["childs"][$c] = array();
				$acl_topos2[$a]["childs"][$b]["childs"][$c]["name"] = _($topo3["topology_name"]);
				$acl_topos2[$a]["childs"][$b]["childs"][$c]["id"] = $topo3["topology_id"];
				$acl_topos2[$a]["childs"][$b]["childs"][$c]["checked"] = isset($acl["acl_topos"][$topo3["topology_id"]]) ? "true" : "false";
				$acl_topos2[$a]["childs"][$b]["childs"][$c]["c_id"] = $a."_".$b."_".$c;
				$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"] = array();

			 	$acl_topos[] =  &HTML_QuickForm::createElement('checkbox', $topo3["topology_id"], null, _($topo3["topology_name"])."<br />", array("style"=>"margin-top: 5px; margin-left: 40px;"));
				$d = 0;
			 	$DBRESULT4 =& $pearDB->query("SELECT topology_id, topology_name, topology_parent FROM topology WHERE topology_parent = '".$topo3["topology_page"]."' AND topology_page IN (".$oreon->user->lcaTStr.") ORDER BY topology_order");
				while ($topo4 = $DBRESULT4->fetchRow()){
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d] = array();
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d]["name"] = _($topo4["topology_name"]);
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d]["id"] = $topo4["topology_id"];
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d]["checked"] = isset($acl["acl_topos"][$topo4["topology_id"]]) ? "true" : "false";
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d]["c_id"] = $a."_".$b."_".$c."_".$d;
					$acl_topos2[$a]["childs"][$b]["childs"][$c]["childs"][$d]["childs"] = array();

					/*old*/
				 	$acl_topos[] =  &HTML_QuickForm::createElement('checkbox', $topo4["topology_id"], null, _("Name"), array("style"=>"margin-top: 5px; margin-left: 55px;"));
					/*old*/					
					$d++;
				}
				$c++;		
			}
			$b++;
		}
		$a++;
	}
	/*
	if ($o == "a")	{
		function one($v)	{
			$v->setValue(1);
			return $v;
		}
		$acl_topos = array_map("one", $acl);
	}
	*/
	$form->addGroup($acl_topos, 'acl_topos', _("Visible page"), '&nbsp;&nbsp;');
	$form->addElement('hidden', 'acl_topo_id');
	
	$redirect =& $form->addElement('hidden', 'o');
	$redirect->setValue($o);

	/*
	 * Form Rules
	 */
	 
	$form->applyFilter('__ALL__', 'myTrim');
	$form->addRule('lca_name', _("Required"), 'required');
	$form->registerRule('exist', 'callback', 'testExistence');
	$form->addRule('lca_name', _("Already exists"), 'exist');
	$form->setRequiredNote(_("Required field"));

	/*
	 * Smarty template Init
	 */ 
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	/*
	 * Just watch a LCA information
	 */

	if ($o == "w")	{
		$form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&acl_id=".$acl_id."'"));
	    $form->setDefaults($acl);
		$form->freeze();
	} else if ($o == "c"){ # Modify a LCA information
		$subC =& $form->addElement('submit', 'submitC', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Delete"));
	    $form->setDefaults($acl);
	} else if ($o == "a"){	# Add a LCA information
		$subA =& $form->addElement('submit', 'submitA', _("Save"));
		$res =& $form->addElement('reset', 'reset', _("Delete"));
	}
	$tpl->assign('msg', array ("changeL"=>"?p=".$p."&o=c&lca_id=".$acl_id, "changeT"=>_("Modify")));

	$tpl->assign("lca_topos2", $acl_topos2);
	$tpl->assign("sort1", _("General Information"));
	$tpl->assign("sort2", _("Resources"));
	$tpl->assign("sort3", _("Topology"));

	$valid = false;
	if ($form->validate())	{
		$aclObj =& $form->getElement('acl_topo_id');
		if ($form->getSubmitValue("submitA"))
			$aclObj->setValue(insertLCAInDB());
		else if ($form->getSubmitValue("submitC"))
			updateLCAInDB($aclObj->getValue());
		require_once("listsMenusAccess.php");
	} else {
		$action = $form->getSubmitValue("action");
		if ($valid && $action["action"]["action"])
			require_once("listsMenusAccess.php");
		else	{
			#Apply a template definition
			$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
			$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
			$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
			$form->accept($renderer);
			$tpl->assign('form', $renderer->toArray());
			$tpl->assign('o', $o);
			$tpl->assign('acl_topos2', $acl_topos2);
			$tpl->display("formMenusAccess.ihtml");
		}
	}
?>
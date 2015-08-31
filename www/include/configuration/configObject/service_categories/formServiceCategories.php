<?php
/*
 * Copyright 2015 Centreon (http://www.centreon.com/)
 * 
 * Centreon is a full-fledged industry-strength solution that meets 
 * the needs in IT infrastructure and application monitoring for 
 * service performance.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0  
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * For more information : contact@centreon.com
 * 
 */

if (!isset($oreon)) {
    exit();
}

if (!$oreon->user->admin) {
    if ($sc_id && $scString != "''" && false === strpos($scString, "'".$sc_id."'")) {
        $msg = new CentreonMsg();
        $msg->setImage("./img/icones/16x16/warning.gif");
        $msg->setTextStyle("bold");
        $msg->setText(_('You are not allowed to access this service category'));
        return null;
    }
}

/*
 * Database retrieve information for Contact
 */
$cct = array();
if (($o == "c" || $o == "w") && $sc_id)	{
    $DBRESULT = $pearDB->query("SELECT * FROM `service_categories` WHERE `sc_id` = '".$sc_id."' LIMIT 1");
    /*
     * Set base value
     */
    $sc = array_map("myDecode", $DBRESULT->fetchRow());
    $DBRESULT->free();
    $sc['sc_severity_level'] = $sc['level'];
    $sc['sc_severity_icon'] = $sc['icon_id'];

    $sc["sc_svc"] = array();
    $sc["sc_svcTpl"] = array();
    $DBRESULT = $pearDB->query("SELECT scr.service_service_id, s.service_register FROM service_categories_relation scr, service s WHERE s.service_id = scr.service_service_id AND scr.sc_id = '$sc_id'");
    while ($res = $DBRESULT->fetchRow()) {
        if ($res["service_register"] == 1)
            $sc["sc_svc"][] = $res["service_service_id"];
        if ($res["service_register"] == 0)
            $sc["sc_svcTpl"][] = $res["service_service_id"];
    }
    $DBRESULT->free();
}

/*
 * Get Service Available
 */
/*
  $hServices = array();
  $DBRESULT = $pearDB->query("SELECT DISTINCT host_id, host_name FROM host WHERE host_register = '1' ORDER BY host_name");
  while ($elem = $DBRESULT->fetchRow())	{
  $services = getMyHostServices($elem["host_id"]);
  foreach ($services as $key => $index)	{
  $index = str_replace('#S#', "/", $index);
  $index = str_replace('#BS#', "\\", $index);
  $hServices[$key] = $elem["host_name"]." / ".$index;
  }
  }
*/

/*
 * Get Service Template Available
 */
$hServices = array();
$DBRESULT = $pearDB->query("SELECT service_alias, service_description, service_id FROM service WHERE service_register = '0' ORDER BY service_alias, service_description");
while ($elem = $DBRESULT->fetchRow())	{
    $elem["service_description"] = str_replace('#S#', "/", $elem["service_description"]);
    $elem["service_description"] = str_replace('#BS#', "\\", $elem["service_description"]);
    $elem["service_alias"] = str_replace('#S#', "/", $elem["service_alias"]);
    $elem["service_alias"] = str_replace('#BS#', "\\", $elem["service_alias"]);
    $hServicesTpl[$elem["service_id"]] = $elem["service_alias"] . " (".$elem["service_description"].")";
}
$DBRESULT->free();

/*
 * Define Template
 */
$attrsText 		= array("size"=>"30");
$attrsText2 	= array("size"=>"60");
$attrsAdvSelect = array("style" => "width: 300px; height: 150px;");
$attrsTextarea 	= array("rows"=>"5", "cols"=>"40");
$eTemplate	= '<table><tr><td><div class="ams">{label_2}</div>{unselected}</td><td align="center">{add}<br /><br /><br />{remove}</td><td><div class="ams">{label_3}</div>{selected}</td></tr></table>';

/*
 * Form begin
 */
$form = new HTML_QuickForm('Form', 'post', "?p=".$p);
if ($o == "a")
    $form->addElement('header', 'title', _("Add a Service Category"));
else if ($o == "c")
    $form->addElement('header', 'title', _("Modify a Service Category"));
else if ($o == "w")
    $form->addElement('header', 'title', _("View a Service Category"));

/*
 * Contact basic information
 */
$form->addElement('header', 'information', _("Information"));
$form->addElement('header', 'links', _("Relations"));

/*
 * No possibility to change name and alias, because there's no interest
 */
$form->addElement('text', 'sc_name', _("Name"), $attrsText);
$form->addElement('text', 'sc_description', _("Description"), $attrsText);

/*
 * Severity
 */
$sctype = $form->addElement('checkbox', 'sc_type', _('Severity type'), null, array('id' => 'sc_type'));
if (isset($sc_id) && isset($sc['level']) && $sc['level'] != "") {
    $sctype->setValue('1');
}
$form->addElement('text', 'sc_severity_level', _("Level"), array("size" => "10"));
$iconImgs = return_image_list(1);
$form->addElement('select', 'sc_severity_icon', _("Icon"), $iconImgs, array(
                                                                            "id" => "icon_id",
                                                                            "onChange" => "showLogo('icon_id_ctn', this.value)",
                                                                            "onkeyup" => "this.blur(); this.focus();"));

$ams1 = $form->addElement('advmultiselect', 'sc_svc', array(_("Host Service Descriptions"), _("Available"), _("Selected")), $hServices, $attrsAdvSelect, SORT_ASC);
$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
$ams1->setButtonAttributes('remove', array('value' => _("Remove")));
$ams1->setElementTemplate($eTemplate);
echo $ams1->getElementJs(false);

$ams1 = $form->addElement('advmultiselect', 'sc_svcTpl', array(_("Service Template Descriptions"), _("Available"), _("Selected")), $hServicesTpl, $attrsAdvSelect, SORT_ASC);
$ams1->setButtonAttributes('add', array('value' =>  _("Add")));
$ams1->setButtonAttributes('remove', array('value' => _("Remove")));
$ams1->setElementTemplate($eTemplate);
if (!$oreon->user->admin) {
    $ams1->setPersistantFreeze(true);
    $ams1->freeze();
}
echo $ams1->getElementJs(false);

$sc_activate[] = HTML_QuickForm::createElement('radio', 'sc_activate', null, _("Enabled"), '1');
$sc_activate[] = HTML_QuickForm::createElement('radio', 'sc_activate', null, _("Disabled"), '0');
$form->addGroup($sc_activate, 'sc_activate', _("Status"), '&nbsp;');
$form->setDefaults(array('sc_activate' => '1'));

$tab = array();
$tab[] = HTML_QuickForm::createElement('radio', 'action', null, _("List"), '1');
$tab[] = HTML_QuickForm::createElement('radio', 'action', null, _("Form"), '0');
$form->addGroup($tab, 'action', _("Post Validation"), '&nbsp;');
$form->setDefaults(array('action'=>'1'));

$form->addElement('hidden', 'sc_id');
$redirect = $form->addElement('hidden', 'o');
$redirect->setValue($o);

if (is_array($select))	{
    $select_str = NULL;
    foreach ($select as $key => $value) {
        $select_str .= $key.",";
    }
    $select_pear = $form->addElement('hidden', 'select');
    $select_pear->setValue($select_str);
}

/*
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

$form->addRule('sc_name', _("Compulsory Name"), 'required');
$form->addRule('sc_description', _("Compulsory Alias"), 'required');

$form->registerRule('existName', 'callback', 'testServiceCategorieExistence');
$form->addRule('sc_name', _("Name is already in use"), 'existName');

$form->addRule('sc_severity_level', _("Must be a number"), 'numeric');

$form->registerRule('shouldNotBeEqTo0', 'callback', 'shouldNotBeEqTo0');
$form->addRule('sc_severity_level', _("Can't be equal to 0"), 'shouldNotBeEqTo0');

$form->addFormRule('checkSeverity');

$form->setRequiredNote("<font style='color: red;'>*</font>&nbsp;". _("Required fields"));

/*
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
    /*
     * Just watch a service_categories information
     */
    if ($centreon->user->access->page($p) != 2)
        $form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&sc_id=".$sc_id."'"));
    $form->setDefaults($sc);
    $form->freeze();
} else if ($o == "c")	{
    /*
     * Modify a service_categories information
     */
    $subC = $form->addElement('submit', 'submitC', _("Save"));
    $res = $form->addElement('reset', 'reset', _("Reset"));
    $form->setDefaults($sc);
} else if ($o == "a")	{
    /*
     * Add a service_categories information
     */
    $subA = $form->addElement('submit', 'submitA', _("Save"));
    $res = $form->addElement('reset', 'reset', _("Reset"));
}

$valid = false;
if ($form->validate() && $from_list_menu == false)	{
    $cctObj = $form->getElement('sc_id');
    if ($form->getSubmitValue("submitA"))
        $cctObj->setValue(insertServiceCategorieInDB());
    else if ($form->getSubmitValue("submitC"))
        updateServiceCategorieInDB($cctObj->getValue());
    $o = NULL;
    $form->addElement("button", "change", _("Modify"), array("onClick"=>"javascript:window.location.href='?p=".$p."&o=c&sc_id=".$cctObj->getValue()."'"));
    $form->freeze();
    $valid = true;
}

$action = $form->getSubmitValue("action");
if ($valid && $action["action"])
    require_once($path."listServiceCategories.php");
else	{
    /*
     * Apply a template definition
     */
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl, true);
    $renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
    $renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
    
    $form->accept($renderer);
    $tpl->assign('form', $renderer->toArray());
    $tpl->assign('o', $o);
    $tpl->assign('p', $p);
    $tpl->display("formServiceCategories.ihtml");
}

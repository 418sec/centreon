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

	if (!isset($oreon))
		exit();

	include_once $centreon_path."www/class/centreonGMT.class.php";
	include_once $centreon_path."www/class/centreonDB.class.php";

	if ($oreon->broker->getBroker() == "ndo") {
		$pearDBndo = new CentreonDB("ndo");
	}

	/*
	 * Init GMT class
	 */
	$centreonGMT = new CentreonGMT($pearDB);
	$centreonGMT->getMyGMTFromSession(session_id(), $pearDB);

	$hostStr = $oreon->user->access->getHostsString("ID", ($oreon->broker->getBroker() == "ndo" ? $pearDBndo : $pearDBO));

	if ($oreon->user->access->checkAction("service_schedule_downtime")) {
		isset($_GET["host_id"]) ? $cG = $_GET["host_id"] : $cG = NULL;
		isset($_POST["host_id"]) ? $cP = $_POST["host_id"] : $cP = NULL;
		$cG ? $host_id = $cG : $host_id = $cP;

	    $svc_description = NULL;

		if (isset($_GET["host_name"]) && isset($_GET["service_description"])){
			$host_id = getMyHostID($_GET["host_name"]);
			$service_id = getMyServiceID($_GET["service_description"], $host_id);
			$host_name = $_GET["host_name"];
			$svc_description = $_GET["service_description"];
		} else
			$host_name = NULL;

			$data = array();
			$data = array(
                            "start" => $centreonGMT->getDate("m/d/Y" , time() + 120), 
                            "end" => $centreonGMT->getDate("m/d/Y", time() + 7320),
                            "start_time" => $centreonGMT->getDate("G:i" , time() + 120),
                            "end_time" => $centreonGMT->getDate("G:i" , time() + 7320)
                        );
			if (isset($host_id))
				$data["host_id"] = $host_id;
			if (isset($service_id))
				$data["service_id"] = $service_id;

			/*
			 * Database retrieve information for differents elements list we need on the page
			 */
			$hosts = array(NULL => NULL);
			$query = "SELECT host_id, host_name " .
					"FROM `host` " .
					"WHERE host_register = '1' " .
					$oreon->user->access->queryBuilder("AND", "host_id", $hostStr) .
					"ORDER BY host_name";
			$DBRESULT = $pearDB->query($query);
			while ($host = $DBRESULT->fetchRow()){
				$hosts[$host["host_id"]]= $host["host_name"];
			}
			$DBRESULT->free();

			$services = array(NULL => NULL);
			if (isset($host_id))
				$services = $oreon->user->access->getHostServices(($oreon->broker->getBroker() == "ndo" ? $pearDBndo : $pearDBO), $host_id);

			$debug = 0;
			$attrsTextI		= array("size"=>"3");
			$attrsText 		= array("size"=>"30");
			$attrsTextarea 	= array("rows"=>"7", "cols"=>"100");

			/*
			 * Form begin
			 */
			$form = new HTML_QuickForm('Form', 'POST', "?p=".$p);
			$form->addElement('header', 'title', _("Add a Service downtime"));

			/*
			 * Indicator basic information
			 */
			$redirect = $form->addElement('hidden', 'o');
			$redirect->setValue($o);

		    $selHost = $form->addElement('select', 'host_id', _("Host Name"), $hosts, array("onChange" =>"this.form.submit();"));
			$selSv = $form->addElement('select', 'service_id', _("Service"), $services);
		    $chbx = $form->addElement('checkbox', 'persistant', _("Fixed"), null, array('id' => 'fixed', 'onClick' => 'javascript:setDurationField()'));
	        if (isset($oreon->optGen['monitoring_dwt_fixed']) && $oreon->optGen['monitoring_dwt_fixed']) {
	            $chbx->setChecked(true);
		    }
			$form->addElement('textarea', 'comment', _("Comments"), $attrsTextarea);

			$form->addElement('text', 'start', _("Start Time"), array('size' => 10, 'class' => 'datepicker'));
			$form->addElement('text', 'end', _("End Time"), array('size' => 10, 'class' => 'datepicker'));
                        
                        $form->addElement('text', 'start_time', '', array('size' => 5, 'class' => 'timepicker'));
			$form->addElement('text', 'end_time', '', array('size' => 5, 'class' => 'timepicker'));
                        
			$form->addElement('text', 'duration', _("Duration"), array('size' => '15', 'id' => 'duration'));
			$defaultDuration = 3600;
	        if (isset($oreon->optGen['monitoring_dwt_duration']) && $oreon->optGen['monitoring_dwt_duration']) {
	            $defaultDuration = $oreon->optGen['monitoring_dwt_duration'];
	        }
	        $form->setDefaults(array('duration' => $defaultDuration));
            
            $scaleChoices = array("s" => _("Seconds"),
                                  "m" => _("Mminutes"),
                                  "h" => _("Hours"),
                                  "d" => _("Days")
                        );
            $form->addElement('select', 'duration_scale', _("Scale of time"), $scaleChoices);
            $defaultScale = 's';
            if (isset($oreon->optGen['monitoring_dwt_duration_scale']) && $oreon->optGen['monitoring_dwt_duration_scale']) {
	            $defaultScale = $oreon->optGen['monitoring_dwt_duration_scale'];
	        }
            $form->setDefaults(array('duration_scale' => $defaultScale));
            
			$form->addElement('textarea', 'comment', _("Comments"), $attrsTextarea);

			$form->addRule('host_id', _("Required Field"), 'required');
			$form->addRule('service_id', _("Required Field"), 'required');
			$form->addRule('end', _("Required Field"), 'required');
			$form->addRule('start', _("Required Field"), 'required');
                        $form->addRule('end_time', _("Required Field"), 'required');
			$form->addRule('start_time', _("Required Field"), 'required');
			$form->addRule('comment', _("Required Field"), 'required');

			$form->setDefaults($data);

			$subA = $form->addElement('submit', 'submitA', _("Save"));
			$res = $form->addElement('reset', 'reset', _("Reset"));

		  	if ((isset($_POST["submitA"]) && $_POST["submitA"]) && $form->validate())	{
                            if (!isset($_POST["persistant"]))
                                $_POST["persistant"] = 0;
                            if (!isset($_POST["comment"]))
                                $_POST["comment"] = 0;
			    $_POST["comment"] = str_replace("'", " ", $_POST['comment']);
			    $duration = null;
				if (isset($_POST['duration'])) {
                    
                    if (isset($_POST['duration_scale'])) {
                        $duration_scale = $_POST['duration_scale'];
                    } else {
                        $duration_scale = 's';
                    }
                    
                    switch ($duration_scale)
                    {
                        default:
                        case 's':
                            $duration = $_POST['duration'];
                            break;
                        
                        case 'm':
                            $duration = $_POST['duration'] * 60;
                            break;
                        
                        case 'h':
                            $duration = $_POST['duration'] * 60 * 60;
                            break;
                        
                        case 'd':
                            $duration = $_POST['duration'] * 60 * 60 * 24;
                            break;
                    }
			    }
                $ecObj->AddSvcDowntime(
                    $_POST["host_id"], 
                    $_POST["service_id"],  
                    $_POST["comment"], 
                    $_POST["start"] . ' ' . $_POST['start_time'], 
                    $_POST["end"] . ' ' . $_POST['end_time'], 
                    $_POST["persistant"], 
                    $duration
                );
		    	require_once("viewServiceDowntime.php");
			} else {
				/*
				 * Smarty template Init
				 */
				$tpl = new Smarty();
				$tpl = initSmartyTpl($path, $tpl, "template/");

				/*
				 * Apply a template definition
				 */
				$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
				$renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
				$renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
				$form->accept($renderer);
				$tpl->assign('form', $renderer->toArray());
				$tpl->assign('seconds', _("seconds"));
				$tpl->assign('o', $o);
				$tpl->display("AddSvcDowntime.ihtml");
		    }
		}
	else {
		require_once("../errors/alt_error.php");
	}
?>
<script type='text/javascript'>
jQuery(function() {
    setDurationField();
});

function setDurationField()
{
	var durationField = document.getElementById('duration');
	var fixedCb = document.getElementById('fixed');

	if (fixedCb.checked == true) {
		durationField.disabled = true;
	} else {
		durationField.disabled = false;
	}
}
</script>
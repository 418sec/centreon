<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus - Cedrick Facon 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@oreon-project.org
 */
 
	if (!isset($oreon))
		exit();

	#Path to the configuration dir
	$path = "./include/views/graphs/";

	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl);

	#Pear library
	require_once "HTML/QuickForm.php";
	require_once 'HTML/QuickForm/Renderer/ArraySmarty.php';


	$openid = '0';
	$open_id_sub = '0';
	if (isset($_GET["openid"])){
		$openid = $_GET["openid"];
		$open_id_type = substr($openid, 0, 2);
		$open_id_sub = substr($openid, 3, strlen($openid));
	}

	(isset($_GET["host_id"]) && $open_id_type == "HH") ? $_GET["host_id"] = $open_id_sub : $_GET["host_id"] = null;

	$id = 1;
	if (isset($_GET["id"])){
		$id = $_GET["id"];
	} 
	if (isset($_POST["id"])){
		$id = $_POST["id"];
	}

	if (isset($_POST["svc_id"]) && $_POST["svc_id"]){
		$id = "";
		$id_svc = $_POST["svc_id"];
		$tab_svcs = explode(",", $id_svc);
		foreach($tab_svcs as $svc){
			$tmp = explode(";", $svc);
			$id .= "HS_" . getMyServiceID($tmp[1], getMyHostID($tmp[0])).",";
		}
	}
	if (isset($_GET["svc_id"]) && $_GET["svc_id"]){
		$id = "";
		$id_svc = $_GET["svc_id"];
		$tab_svcs = explode(",", $id_svc);
		foreach($tab_svcs as $svc){
			$tmp = explode(";", $svc);
			$id .= "HS_" . getMyServiceID($tmp[1], getMyHostID($tmp[0])).",";
		}
	}
	
	$id_log = "'RR_0'";
	$multi = 0;
	if (isset($_GET["mode"]) && $_GET["mode"] == "0"){
		$mode = 0;
		$id_log = "'".$id."'";
		$multi = 1;
	} else {
		$mode = 1;
		$id = 1;
	}

	## Form begin
	$form = new HTML_QuickForm('Form', 'get', "?p=".$p);
	$form->addElement('header', 'title', _("Choose the source to graph"));

	$periods = array(	""=>"",
						"10800"=>_("Last 3 Hours"),
						"21600"=>_("Last 6 Hours"),
						"43200"=>_("Last 12 Hours"),
						"86400"=>_("Last 24 Hours"),
						"172800"=>_("Last 2 Days"),
						"302400"=>_("Last 4 Days"),
						"604800"=>_("Last 7 Days"),
						"1209600"=>_("Last 14 Days"),
						"2419200"=>_("Last 28 Days"),
						"2592000"=>_("Last 30 Days"),
						"2678400"=>_("Last 31 Days"),
						"5184000"=>_("Last 2 Months"),
						"10368000"=>_("Last 4 Months"),
						"15552000"=>_("Last 6 Months"),
						"31104000"=>_("Last Year"));

	$sel =& $form->addElement('select', 'period', _("Graph Period"), $periods);

	$renderer =& new HTML_QuickForm_Renderer_ArraySmarty($tpl);
	$form->accept($renderer);
	$tpl->assign('form', $renderer->toArray());

	$tpl->assign('lang', $lang);
	$tpl->display("graphODS.ihtml");


?>
<script type="text/javascript" src="./include/common/javascript/LinkBar.js"></script>
<link href="./include/common/javascript/datePicker.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src='./include/common/javascript/tool.js'></script>
<script type="text/javascript">

		var css_file = './include/common/javascript/codebase/dhtmlxtree.css';
	    var headID = document.getElementsByTagName("head")[0];  
	    var cssNode = document.createElement('link');
	    cssNode.type = 'text/css';
	    cssNode.rel = 'stylesheet';
	    cssNode.href = css_file;
	    cssNode.media = 'screen';
	    headID.appendChild(cssNode);

		var multi = <?php echo $multi; ?>;
	  	var _menu_div = document.getElementById("menu_40211");

  		tree=new dhtmlXTreeObject("menu_40211","100%","100%","1");
        tree.setImagePath("./img/icones/csh_vista/");
        //link tree to xml
        tree.setXMLAutoLoading("./include/views/graphs/GetODSXmlTree.php");
            
        //load first level of tree
        tree.loadXML("./include/views/graphs/GetODSXmlTree.php?&id=<?php echo $id; ?>&mode=<?php echo $mode; ?>&sid=<?php echo session_id(); ?>");

		// system to reload page after link with new url
		//set function object to call on node select 
		tree.attachEvent("onClick",onNodeSelect)
		//set function object to call on node select 
		tree.attachEvent("onDblClick",onDblClick)
		//set function object to call on node select 		
		tree.attachEvent("onCheck",onCheck)
		//see other available event handlers in API documentation 
		tree.enableDragAndDrop(0);
		tree.enableTreeLines(false);	
		tree.enableCheckBoxes(true);
		tree.enableThreeStateCheckboxes(true);


		// linkBar to log/reporting/graph/ID_card		
		function getCheckedList(tree){
			return tree.getAllChecked();
		}
		
		if (document.getElementById('linkBar')){
			var _menu_2 = document.getElementById('linkBar')
			var _divBar = document.createElement("div");
			
			_divBar.appendChild(create_log_link(tree,'id'));
			_divBar.appendChild(create_monitoring_link(tree,'id'));
		
			_divBar.setAttribute('style','float:right; margin-right:110px;' );
			_menu_2.appendChild(_divBar);
		}

		function onDblClick(nodeId){
			tree.openAllItems(nodeId);
			return(false);
		}
		
		function onCheck(nodeId){
			multi = 1;
			if(document.getElementById('openid'))
				document.getElementById('openid').innerHTML = tree.getAllChecked();
			graph_4_host(tree.getAllChecked(),1);
		}
		
		function onNodeSelect(nodeId)
		{
			multi = 0;

			tree.openItem(nodeId);
			if(nodeId.substring(0,2) == 'HS' || nodeId.substring(0,2) == 'MS')
			{
				var graphView4xml = document.getElementById('graphView4xml');
				graphView4xml.innerHTML="..graph.." + nodeId;
				graph_4_host(nodeId);
			}
		}
		
		// it's fake methode for using ajax system by default
		function mk_pagination(){;}
		function set_header_title(){;}

		function apply_period()	{
			var openid = document.getElementById('openid').innerHTML;
			graph_4_host(openid, multi);
		}

		// Period
		var currentTime = new Date();
		var period ='';

		var _zero_hour = '';
		var _zero_min = '';
		var StartDate='';
		var EndDate='';
		var StartTime='';
		var EndTime='';

		if(document.formu && !document.formu.period_choice[1].checked)	{
			period = document.formu.period.value;
		} else {
			if(currentTime.getMinutes() <= 9){
				_zero_min = '0';
			}

			if (currentTime.getHours() >= 12){
				StartDate= currentTime.getMonth()+1+"/"+currentTime.getDate()+"/"+currentTime.getFullYear();
				EndDate= currentTime.getMonth()+1+"/"+ currentTime.getDate()+"/"+currentTime.getFullYear();						

				if((currentTime.getHours()- 12) <= 9){
					_zero_hour = '0';					
				} else{
					_zero_hour = '';											
				}
				StartTime = _zero_hour + (currentTime.getHours() - 12) +":" + _zero_min + currentTime.getMinutes();
				if(currentTime.getHours() <= 9){
					_zero_hour = '0';					
				} else{
					_zero_hour = '';											
				}	
				EndTime   = _zero_hour + currentTime.getHours() + ":" + _zero_min + currentTime.getMinutes();
			} else {
				StartDate= currentTime.getMonth()+1+"/"+(currentTime.getDate()-1)+"/"+currentTime.getFullYear();
				EndDate=   currentTime.getMonth()+1+"/"+ currentTime.getDate()+"/"+currentTime.getFullYear();

				StartTime=  (24 -(12 - currentTime.getHours()))+ ":00";

				if(currentTime.getHours() <= 9){
					_zero_hour = '0';					
				} else {
					_zero_hour = '';											
				}
				
				EndTime = _zero_hour + currentTime.getHours() + ":" + _zero_min + currentTime.getMinutes();
			}
		}

		if (document.formu){
			document.formu.StartDate.value = StartDate;
			document.formu.EndDate.value = EndDate;
			document.formu.StartTime.value = StartTime;
			document.formu.EndTime.value = EndTime;
		}


		function graph_4_host(id, multi)	{
			if (!multi)
				multi = 0;
			
			if (document.formu && !document.formu.period_choice[1].checked){
				period = document.formu.period.value;
			} else if(document.formu) {
				period = '';
				StartDate = document.formu.StartDate.value;
				EndDate = document.formu.EndDate.value;
				StartTime = document.formu.StartTime.value;
				EndTime = document.formu.EndTime.value;
			}

			// Metrics
			var _metrics ="";
			var _checked = "0";
			if (document.formu2 && document.formu2.elements["metric"]){
				for (i=0; i < document.formu2.elements["metric"].length; i++) {
					_checked = "0";
					if(document.formu2.elements["metric"][i].checked)	{
						_checked = "1";
					}
					_metrics += '&metric['+document.formu2.elements["metric"][i].value+']='+_checked ;
				}
			}

			// Templates
			var _tpl_id = 1;
			if (document.formu2 && document.formu2.template_select && document.formu2.template_select.value != ""){
				_tpl_id = document.formu2.template_select.value;
			}
			// Split metric
			var _split = 0;
			if (document.formu2 && document.formu2.split && document.formu2.split.checked)	{
				_split = 1
			}

			var _status = 0;
			if (document.formu2 && document.formu2.status && document.formu2.status.checked)	{
				_status = 1
			}
			
			tree.selectItem(id);
			var proc = new Transformation();
			var _addrXSL = "./include/views/graphs/GraphService.xsl";
			var _addrXML = './include/views/graphs/GetODSXmlGraph.php?multi='+multi+'&split='+_split+'&status='+_status+_metrics+'&template_id='+_tpl_id +'&period='+period+'&StartDate='+StartDate+'&EndDate='+EndDate+'&StartTime='+StartTime+'&EndTime='+EndTime+'&id='+id+'&sid=<?php echo $sid;?>';

//				var header = document.getElementById('header');
//				header.innerHTML += _addrXML;

				proc.setXml(_addrXML)
				proc.setXslt(_addrXSL)
				proc.transform("graphView4xml");
		}
	
		// Let's save the existing assignment, if any
    	var nowOnload = window.onload;
    	window.onload = function () {
        // Here is your precious function
        // You can call as many functions as you want here;
        myOnloadFunction1();
		//graph_4_host('HS_1506', '0');
		graph_4_host(<?php echo $id_log;?>, <?php echo $multi;?>);

        // Now we call old function which was assigned to onLoad, thus playing nice
        if (nowOnload != null && typeof(nowOnload) == 'function') {
            nowOnload();
        }
    }

    // Your precious function
    function myOnloadFunction1() {}
</script>
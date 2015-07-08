<?php
/*
 * Copyright 2005-2015 CENTREON
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
 * As a special exception, the copyright holders of this program give CENTREON
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of CENTREON choice, provided that
 * CENTREON also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

namespace CentreonConfiguration\Controllers;

use Centreon\Internal\Di;
use CentreonConfiguration\Models\Host;
use CentreonConfiguration\Models\Relation\Host\Hostchildren;
use CentreonConfiguration\Models\Relation\Host\Hostparents;
use CentreonConfiguration\Models\Relation\Host\Poller;
use CentreonConfiguration\Models\Timeperiod;
use CentreonConfiguration\Models\Command;
use CentreonConfiguration\Internal\HostDatatable;
use CentreonConfiguration\Repository\HostRepository;
use CentreonConfiguration\Repository\HostTemplateRepository;
use CentreonConfiguration\Repository\CustomMacroRepository;
use CentreonAdministration\Repository\TagsRepository;
use Centreon\Controllers\FormController;
use CentreonConfiguration\Repository\ServiceRepository;
use CentreonRealtime\Repository\ServiceRepository as ServiceRealTimeRepository;
use CentreonRealtime\Repository\HostRepository as HostRealTimeRepository;
use Centreon\Internal\Utils\String;

class HostController extends FormController
{
    protected $objectDisplayName = 'Host';
    public static $objectName = 'host';
    public static $enableDisableFieldName = 'host_activate';
    protected $datatableObject = '\CentreonConfiguration\Internal\HostDatatable';
    protected $objectBaseUrl = '/centreon-configuration/host';
    protected $objectClass = '\CentreonConfiguration\Models\Host';
    protected $repository = '\CentreonConfiguration\Repository\HostRepository';

    protected $inheritanceUrl = '/centreon-configuration/host/[i:id]/inheritance';
    protected $inheritanceTmplUrl = '/centreon-configuration/hosttemplate/inheritance';
    protected $tmplField = '#host_hosttemplates';
    protected $inheritanceTagsUrl = '/centreon-administration/tag/[i:id]/host/herited';
    
    public static $relationMap = array(
        'host_parents' => '\CentreonConfiguration\Models\Relation\Host\Hostparents',
        'host_childs' => '\CentreonConfiguration\Models\Relation\Host\Hostchildren',
        'host_hosttemplates' => '\CentreonConfiguration\Models\Relation\Host\Hosttemplate',
        'host_services' => '\CentreonConfiguration\Models\Relation\Host\Service',
        'host_icon' => '\CentreonConfiguration\Models\Relation\Host\Icon',
        'aclresource_hosts' => '\CentreonConfiguration\Models\Relation\Aclresource\Host',
        'aclresource_hosttags' => '\CentreonConfiguration\Models\Relation\Aclresource\Hosttag'
    );
    
    public static $isDisableable = true;

    /**
     * List hosts
     *
     * @method get
     * @route /host
     */
    public function listAction()
    {
        $router = Di::getDefault()->get('router');
        $this->tpl->addJs('centreon.overlay.js')
            ->addJs('jquery.qtip.min.js')
            ->addJs('hogan-3.0.0.min.js')
            ->addJs('centreon.tag.js', 'bottom', 'centreon-administration')
            ->addJs('centreon-clone.js')
            ->addJs('component/custommacro.js')
            ->addCss('centreon.qtip.css')
            ->addCss('centreon.tag.css', 'centreon-administration');
        
        $urls = array(
            'tag' => array(
                'add' => $router->getPathFor('/centreon-administration/tag/add'),
                'del' => $router->getPathFor('/centreon-administration/tag/delete'),
                'getallGlobal' => $router->getPathFor('/centreon-administration/tag/all'),
                'getallPerso' => $router->getPathFor('/centreon-administration/tag/allPerso'),
                'addMassive' => $router->getPathFor('/centreon-administration/tag/addMassive')
            )
        );

        $this->tpl->addCustomJs('$(function () {
                $("#modal").on("loaded.bs.modal", function() {
                    initCustomMacro();
                });
            });');

        $this->tpl->append('jsUrl', $urls, true);
        $this->tpl->assign('configuration', true);
        parent::listAction();
    }
    
    /**
     * 
     * @method get
     * @route /host/list
     */
    public function datatableAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');
                
        $myDatatable = new HostDatatable($this->getParams('get'), $this->objectClass);
        $myDataForDatatable = $myDatatable->getDatas();

        /* Secure strings */
        for ($i = 0; $i < count($myDataForDatatable['data']); $i++) {
            foreach ($myDataForDatatable['data'][$i] as $key => $value) {
                if (is_string($value)) {
                    $myDataForDatatable['data'][$i][$key] = String::escapeSecure($value);
                }
            }
        }
          
        $router->response()->json($myDataForDatatable);
    }
    
    /**
     * Create a new host
     *
     * @method post
     * @route /host/add
     */
    public function createAction()
    {
        $macroList = array();
        $aTagList = array();
        $aTags = array();
        
        $givenParameters = $this->getParams('post');
                
        $givenParameters['host_register'] = 1;
        
        if (isset($givenParameters['macro_name']) && isset($givenParameters['macro_value'])) {
            
            $macroName = $givenParameters['macro_name'];
            $macroValue = $givenParameters['macro_value'];
            
            $macroHidden = $givenParameters['macro_hidden'];

            foreach ($macroName as $key => $name) {
                if (!empty($name)) {
                    if (isset($macroHidden[$key])) {
                        $isPassword = '1';
                    } else {
                        $isPassword = '0';
                    }

                    $macroList[$name] = array(
                        'value' => $macroValue[$key],
                        'ispassword' => $isPassword
                    );
                }
            }
        }

        $id = parent::createAction(false);

        if (count($macroList) > 0) {
            try{
                CustomMacroRepository::saveHostCustomMacro(self::$objectName, $id, $macroList);
            } catch (\Exception $ex) {
                $errorMessage = $ex->getMessage();
                $this->router->response()->json(array('success' => false,'error' => $errorMessage));
            }
            
        }
        
        if (isset($givenParameters['host_tags'])) {
            $aTagList = explode(",", $givenParameters['host_tags']);
            foreach ($aTagList as $var) {                
                $var = trim($var);
                if (!empty($var)) {
                    array_push($aTags, $var);
                }
            }
            if (count($aTags) > 0) {
                TagsRepository::saveTagsForResource(self::$objectName, $id, $aTags, '', false, 1);
            }
        }
                
        $this->router->response()->json(array('success' => true));
    }

    /**
     * Show all tags of a Host
     *
     *
     * @method get
     * @route /host/[i:id]/tags
     */
    public function getHostTagsAction()
    {
        $requestParam = $this->getParams('named');
        
        
        
        
        $globalTags = TagsRepository::getList('host', $requestParam['id'],1,1);
        $globalTagsValues = array();
        foreach($globalTags as $globalTag){
            $globalTagsValues[] = $globalTag['text'];
        }
        $heritedTags = TagsRepository::getHeritedTags('host', $requestParam['id']);
        $heritedTagsValues = $heritedTags['values'];
        
        
        $tags['tags'] = array('globals' => $globalTagsValues,'herited' => $heritedTagsValues);
        $tags['success'] = true;
        /*
        echo '<pre>';
        print_r($tags);
        echo '</pre>';
        die;*/
        $this->router->response()->json($tags);
        /*$this->tpl->assign('tags', $tags);
        $this->tpl->display('file:[CentreonConfigurationModule]tags_menu_slide.tpl');*/
    }
    
    
    /**
     * Update a host
     *
     *
     * @method post
     * @route /host/update
     */
    public function updateAction()
    {
        $givenParameters = $this->getParams('post');
        $macroList = array();
        $aTagList = array();
        $aTags = array();
        $aTagsInTpl = array();
        $aTagsIdTpl = array();
        $bSuccess = true;
        $sMessage = '';
        $bReturn = true;
               
        if (isset($givenParameters['macro_name']) && isset($givenParameters['macro_value'])) {
            
            $macroName = $givenParameters['macro_name'];
            $macroValue = $givenParameters['macro_value'];
            $macroHidden = $givenParameters['macro_hidden'];

            foreach ($macroName as $key => $name) {
                if (!empty($name)) {
                    if (isset($macroHidden[$key])) {
                        $isPassword = '1';
                    } else {
                        $isPassword = '0';
                    }

                    $macroList[$name] = array(
                        'value' => $macroValue[$key],
                        'ispassword' => $isPassword
                    );
                }
            }
        }
        
        try{
            CustomMacroRepository::saveHostCustomMacro(self::$objectName, $givenParameters['object_id'], $macroList);
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            $this->router->response()->json(array('success' => false,'error' => $errorMessage));
        }
        
        
        
        //CustomMacroRepository::saveHostCustomMacro(self::$objectName, $givenParameters['object_id'], $macroList);
        
        //Delete all tags
        TagsRepository::deleteTagsForResource(self::$objectName, $givenParameters['object_id'], 0);
        
        //Insert tags affected to the HOST
        if (isset($givenParameters['host_tags'])) {
            $aTagList = explode(",", $givenParameters['host_tags']);
            foreach ($aTagList as $var) {     
                $var = trim($var);
                if (!empty($var)) {
                    array_push($aTags, $var);
                }
            }
            
            if (count($aTags) > 0) {
                TagsRepository::saveTagsForResource(self::$objectName, $givenParameters['object_id'], $aTags, '', false, 1);
            }
        }
        
        parent::updateAction();
    }
    
    /**
     * Get list of hostcategories for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/icon
     */
    public function iconForHostAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');
        
        $requestParam = $this->getParams('named');
        
        $objCall = static::$relationMap['host_icon'];
        $icon = $objCall::getIconForHost($requestParam['id']);
        $finalIconList = array();
        if (count($icon) > 0) {
            $filenameExploded = explode('.', $icon['filename']);
            $nbOfOccurence = count($filenameExploded);
            $fileFormat = $filenameExploded[$nbOfOccurence-1];
            $filenameLength = strlen($icon['filename']);
            $routeAttr = array(
                'image' => substr($icon['filename'], 0, ($filenameLength - (strlen($fileFormat) + 1))),
                'format' => '.'.$fileFormat
            );
            $imgSrc = $router->getPathFor('/uploads/[*:image][png|jpg|gif|jpeg:format]', $routeAttr);
            $finalIconList = array(
                "id" => $icon['binary_id'],
                "text" => $icon['filename'],
                "theming" => '<img src="'.$imgSrc.'" style="width:20px;height:20px;"> '.$icon['filename']
            );
        }
        
        $router->response()->json($finalIconList);
        
    }

    /**
     * Get host template for a specific host
     *
     * @method get
     * @route /host/[i:id]/hosttemplate
     */
    public function hostTemplateForHostAction()
    {
        parent::getRelations(static::$relationMap['host_hosttemplates']);
    }

    /**
     * 
     * @method get
     * @route /host/[i:id]/parent
     */
    public function parentForHostAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');
        
        $requestParam = $this->getParams('named');
        
        $HostparentsList = Hostparents::getMergedParameters(
            array('host_id', 'host_name'),
            array(),
            -1,
            0,
            null,
            "ASC",
            array('cfg_hosts_hostparents_relations.host_host_id' => $requestParam['id']),
            "AND"
        );

        $finalHostList = array();
        foreach ($HostparentsList as $Hostparents) {
            $finalHostList[] = array(
                "id" => $Hostparents['host_id'],
                "text" => $Hostparents['host_name'],
                "theming" => HostRepository::getIconImage(
                    $Hostparents['host_name']
                ).' '.$Hostparents['host_name']
            );
        }
        
        $router->response()->json($finalHostList);
    }

    /**
     * 
     * @method get
     * @route /host/[i:id]/child
     */
    public function childForHostAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');
        
        $requestParam = $this->getParams('named');
        
        $HostchildrenList = Hostchildren::getMergedParameters(
            array('host_id', 'host_name'),
            array(),
            -1,
            0,
            null,
            "ASC",
            array('cfg_hosts_hostparents_relations.host_parent_hp_id' => $requestParam['id']),
            "AND"
        );

        $finalHostList = array();
        foreach ($HostchildrenList as $Hostchildren) {
            $finalHostList[] = array(
                "id" => $Hostchildren['host_id'],
                "text" => $Hostchildren['host_name'],
                "theming" => HostRepository::getIconImage(
                    $Hostchildren['host_name']
                ).' '.$Hostchildren['host_name']
            );
        }
        
        $router->response()->json($finalHostList);
    }
    
    /**
     * Get list of Environment for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/environment
     */
    public function checkEnvironmentHostAction()
    {
        parent::getSimpleRelation('environment_id', '\CentreonAdministration\Models\Environment');
    }
    
    /**
     * Get list of Timeperiods for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/checkperiod
     */
    public function checkPeriodForHostAction()
    {
        parent::getSimpleRelation('timeperiod_tp_id', '\CentreonConfiguration\Models\Timeperiod');
    }
    
    /**
     * Get check command for a specific host
     *
     * @method get
     * @route /host/[i:id]/checkcommand
     */
    public function checkcommandForHostAction()
    {
        parent::getSimpleRelation('command_command_id', '\CentreonConfiguration\Models\Command');
    }

    /**
     * Get list of Commands for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/eventhandler
     */
    public function eventHandlerForHostAction()
    {
        parent::getSimpleRelation('command_command_id2', '\CentreonConfiguration\Models\Command');
    }
    
    /**
     * Get list of Commands for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/timezone
     */
    public function timezoneForHostAction()
    {
        parent::getSimpleRelation('timezone_id', '\CentreonAdministration\Models\Timezone');
    }

    /**
     * Get list of pollers for a specific host
     *
     *
     * @method get
     * @route /host/[i:id]/poller
     */
    public function pollerForHostAction()
    {
        parent::getSimpleRelation('poller_id', '\CentreonConfiguration\Models\Poller');
    }

    
    /**
     * Display the configuration snapshot of a host
     * with template inheritance
     *
     * @method get
     * @route /host/snapshot/[i:id]
     */
    public function snapshotAction()
    {
        $params = $this->getParams();
        $data = HostRepository::getConfigurationData($params['id']);
        $checkdata = HostRepository::formatDataForTooltip($data);
        $servicesStatus = ServiceRealTimeRepository::countAllStatusForHost($params['id']);
        $final = "";
        $this->tpl->assign('checkdata', $checkdata);
        $final .= $this->tpl->fetch('file:[CentreonConfigurationModule]host_conf_tooltip.tpl');
        $this->router->response()->body($final);
        
    }

    /**
     * Get list of services for a specific host
     * 
     * @method get
     * @route /host/[i:id]/service
     */
    public function hostForServiceAction()
    {
        $requestParam = $this->getParams('named');
        $services = HostRepository::getServicesForHost(static::$relationMap['host_services'],$requestParam['id']);

        foreach($services as &$service){
            $service = ServiceRepository::formatDataForSlider($service);
        }
        $this->router->response()->json(array('service' => $services,'success' => true));
    }

    
    
    /**
     * Display the configuration snapshot of a host
     * with template inheritance
     *
     * @method get
     * @route /host/snapshotslide/[i:id]
     */
    public function snapshotslideAction()
    {

        $params = $this->getParams();
        $data = HostRepository::getConfigurationData($params['id']);
        $data['realTimeData'] = HostRealTimeRepository::getRealTimeData($params['id']);
        $hostConfiguration = HostRepository::formatDataForSlider($data);
        $servicesStatus = ServiceRealTimeRepository::countAllStatusForHost($params['id']);
        $edit_url = $this->router->getPathFor("/centreon-configuration/host/".$params['id']);
        $this->router->response()->json(array('hostConfig'=>$hostConfiguration,'servicesStatus'=>$servicesStatus,'edit_url' => $edit_url,'success' => true));
    }

    /**
     * Get inheritance value
     *
     * @method get
     * @route /host/[i:id]/inheritance
     */
    public function getInheritanceAction()
    {
        $router = Di::getDefault()->get('router');
        $requestParam = $this->getParams('named');

        $inheritanceValues = HostRepository::getInheritanceValues($requestParam['id']);
        array_walk($inheritanceValues, function(&$item, $key) {
            if (false === is_null($item)) {
                $item = HostTemplateRepository::getTextValue($key, $item);
            }
        });
        $router->response()->json(array(
            'success' => true,
            'values' => $inheritanceValues));
    }

    /**
     * Get hosts for a specific acl resource
     *
     * @method get
     * @route /aclresource/[i:id]/host
     */
    public function hostsForAclResourceAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');

        $requestParam = $this->getParams('named');
        $finalHostList = HostRepository::getHostsByAclResourceId($requestParam['id']);

        $router->response()->json($finalHostList);
    }

     /**
     * Get host tag list for acl resource
     *
     * @method get
     * @route /host/tag/formlist
     */
    public function hostTagsAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');

        $list = TagsRepository::getGlobalList('host');

        $router->response()->json($list);
    }

    /**
     * Get host snmp version list
     *
     * @method get
     * @route /host/snmp-version/formlist
     */
    public function hostSnmpVersionsAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');

        $list = array(
            array("id" => "1", "text" => "1"),
            array("id" => "2c", "text" => "2c"),
            array("id" => "3", "text" => "3")
        );

        $router->response()->json($list);
    }

    /**
     * Get snmp version for a specific host
     *
     * @method get
     * @route /host/[i:id]/snmp-version
     */
    public function snmpVersionForHostAction()
    {
        $di = Di::getDefault();
        $router = $di->get('router');
        $requestParam = $this->getParams('named');


        $snmpVersionParam = Host::getParameters($requestParam['id'], 'host_snmp_version');

        $snmpVersion = array();
        if (isset($snmpVersionParam['host_snmp_version'])) {
            $snmpVersion = array(
                "id" => $snmpVersionParam['host_snmp_version'],
                "text" => $snmpVersionParam['host_snmp_version']
            );
        }

        $router->response()->json($snmpVersion);
    }
}

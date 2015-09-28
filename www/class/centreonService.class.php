<?php

/*
 * Copyright 2005-2015 Centreon
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
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
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

/**
 *  Class that contains various methods for managing services
 */
class CentreonService {

    protected $db;
    protected $instanceObj;

    /**
     *  Constructor
     *
     *  @param CentreonDB $db
     */
    public function __construct($db) {
        $this->db = $db;
        $this->instanceObj = new CentreonInstance($db);
    }

    /**
     *  Method that returns service description from service_id
     *
     *  @param int $svc_id
     *  @return string
     */
    public function getServiceDesc($svc_id) {
        static $svcTab = null;

        if (is_null($svcTab)) {
            $svcTab = array();

            $rq = "SELECT service_id, service_description
     			   FROM service";
            $res = $this->db->query($rq);
            while ($row = $res->fetchRow()) {
                $svcTab[$row['service_id']] = $row['service_description'];
            }
        }
        if (isset($svcTab[$svc_id])) {
            return $svcTab[$svc_id];
        }
        return null;
    }

    /**
     * Get Service Template ID
     * 
     * @param string $templateName
     * @return int
     */
    public function getServiceTemplateId($templateName = null) {
        if (is_null($templateName)) {
            return null;
        }
        $res = $this->db->query(
                "SELECT service_id 
                        FROM service
                        WHERE service_description = '" . $this->db->escape($templateName) . "' 
                            AND service_register = '0'"
        );
        if (!$res->numRows()) {
            return null;
        }
        $row = $res->fetchRow();
        return $row['service_id'];
    }

    /**
     *  Method that returns the id of a service
     *
     *  @param string $svc_desc
     *  @param string $host_name
     *  @return int
     */
    public function getServiceId($svc_desc = null, $host_name) {
        static $hostSvcTab = array();

        if (!isset($hostSvcTab[$host_name])) {
            $rq = "SELECT s.service_id, s.service_description " .
                    " FROM service s" .
                    " JOIN (SELECT hsr.service_service_id FROM host_service_relation hsr" .
                    " JOIN host h" .
                    "     ON hsr.host_host_id = h.host_id" .
                    "     	WHERE h.host_name = '" . $this->db->escape($host_name) . "'" .
                    "     UNION" .
                    "    	 SELECT hsr.service_service_id FROM hostgroup_relation hgr" .
                    " JOIN host h" .
                    "     ON hgr.host_host_id = h.host_id" .
                    " JOIN host_service_relation hsr" .
                    "     ON hgr.hostgroup_hg_id = hsr.hostgroup_hg_id" .
                    "     	WHERE h.host_name = '" . $this->db->escape($host_name) . "' ) ghsrv" .
                    " ON s.service_id = ghsrv.service_service_id";
            $DBRES = $this->db->query($rq);
            $hostSvcTab[$host_name] = array();
            while ($row = $DBRES->fetchRow()) {
                $hostSvcTab[$host_name][$row['service_description']] = $row['service_id'];
            }
        }
        if (!isset($svc_desc) && isset($hostSvcTab[$host_name])) {
            return $hostSvcTab[$host_name];
        }
        if (isset($hostSvcTab[$host_name]) && isset($hostSvcTab[$host_name][$svc_desc])) {
            return $hostSvcTab[$host_name][$svc_desc];
        }
        return null;
    }

    /**
     * Get Service Id From Hostgroup Name
     *
     * @param string $service_desc
     * @param string $hgName
     * @return int
     */
    public function getServiceIdFromHgName($service_desc, $hgName) {
        static $hgSvcTab = array();

        if (!isset($hgSvcTab[$hgName])) {
            $rq = "SELECT hsr.service_service_id, s.service_description
            		FROM host_service_relation hsr, hostgroup hg, service s
            		WHERE hsr.hostgroup_hg_id = hg.hg_id
        			AND hsr.service_service_id = s.service_id
            		AND hg.hg_name LIKE '" . $this->db->escape($hgName) . "' ";
            $res = $this->db->query($rq);
            while ($row = $res->fetchRow()) {
                $hgSvcTab[$hgName][$row['service_description']] = $row['service_service_id'];
            }
        }
        if (isset($hgSvcTab[$hgName]) && isset($hgSvcTab[$hgName][$service_desc])) {
            return $hgSvcTab[$hgName][$service_desc];
        }
        return null;
    }

    /**
     * Get Service alias
     *
     * @param int $sid
     * @return string
     */
    public function getServiceName($sid) {
        static $svcTab = array();

        if (!isset($svcTab[$sid])) {
            $query = "SELECT service_alias
     				  FROM service
     				  WHERE service_id = " . $this->db->escape($sid);
            $res = $this->db->query($query);
            if ($res->numRows()) {
                $row = $res->fetchRow();
                $svcTab[$sid] = $row['service_alias'];
            }
        }
        if (isset($svcTab[$sid])) {
            return $svcTab[$sid];
        }
        return null;
    }

    /**
     * Check illegal char defined into nagios.cfg file
     *
     * @param string $name
     * @return string
     */
    public function checkIllegalChar($name) {
        $DBRESULT = $this->db->query("SELECT illegal_object_name_chars FROM cfg_nagios");
        while ($data = $DBRESULT->fetchRow()) {
            $tab = str_split(html_entity_decode($data['illegal_object_name_chars'], ENT_QUOTES, "UTF-8"));
            foreach ($tab as $char) {
                $name = str_replace($char, "", $name);
            }
        }
        $DBRESULT->free();
        return $name;
    }

    /**
     *  Returns a string that replaces on demand macros by their values
     *
     *  @param int $svc_id
     *  @param string $string
     *  @param int $antiLoop
     *  @param int $instanceId
     *  @return string
     */
    public function replaceMacroInString($svc_id, $string, $antiLoop = null, $instanceId = null) {
        $rq = "SELECT service_register FROM service WHERE service_id = '" . $svc_id . "' LIMIT 1";
        $DBRES = $this->db->query($rq);
        if (!$DBRES->numRows())
            return $string;
        $row = $DBRES->fetchRow();

        /*
         * replace if not template
         */
        if ($row['service_register'] == 1) {
            if (preg_match('/\$SERVICEDESC\$/', $string)) {
                $string = str_replace("\$SERVICEDESC\$", $this->getServiceDesc($svc_id), $string);
            }
            if (!is_null($instanceId) && preg_match("\$INSTANCENAME\$", $string)) {
                $string = str_replace("\$INSTANCENAME\$", $this->instanceObj->getParam($instanceId, 'name'), $string);
            }
            if (!is_null($instanceId) && preg_match("\$INSTANCEADDRESS\$", $string)) {
                $string = str_replace("\$INSTANCEADDRESS\$", $this->instanceObj->getParam($instanceId, 'ns_ip_address'), $string);
            }
        }
        $matches = array();
        $pattern = '|(\$_SERVICE[0-9a-zA-Z\_\-]+\$)|';
        preg_match_all($pattern, $string, $matches);
        $i = 0;
        while (isset($matches[1][$i])) {
            $rq = "SELECT svc_macro_value FROM on_demand_macro_service WHERE svc_svc_id = '" . $svc_id . "' AND svc_macro_name LIKE '" . $matches[1][$i] . "'";
            $DBRES = $this->db->query($rq);
            while ($row = $DBRES->fetchRow()) {
                $string = str_replace($matches[1][$i], $row['svc_macro_value'], $string);
            }
            $i++;
        }
        if ($i) {
            $rq2 = "SELECT service_template_model_stm_id FROM service WHERE service_id = '" . $svc_id . "'";
            $DBRES2 = $this->db->query($rq2);
            while ($row2 = $DBRES2->fetchRow()) {
                if (!isset($antiLoop) || !$antiLoop) {
                    $string = $this->replaceMacroInString($row2['service_template_model_stm_id'], $string, $row2['service_template_model_stm_id']);
                } elseif ($row2['service_template_model_stm_id'] != $antiLoop) {
                    $string = $this->replaceMacroInString($row2['service_template_model_stm_id'], $string, $antiLoop);
                }
            }
        }
        return $string;
    }

    /**
     * Get list of service templates
     * 
     * @return array 
     */
    public function getServiceTemplateList() {
        $res = $this->db->query("SELECT service_id, service_description 
                            FROM service
                            WHERE service_register = '0'
                            ORDER BY service_description");
        $list = array();
        while ($row = $res->fetchRow()) {
            $list[$row['service_id']] = $row['service_description'];
        }
        return $list;
    }

    /* public function getServiceTemplateTree($serviceId,$macros = null){
      $res = $this->db->query("SELECT s.service_id, s.service_template_model_stm_id
      FROM service s
      WHERE s.service_id = ".$this->db->escape($serviceId)."
      ");
      $service = array();
      while ($row = $res->fetchRow()) {
      $service['service_id'] = $row['service_id'];
      $service['macros'] = $this->getCustomMacro($row['service_id']);
      if(!is_null($row['service_template_model_stm_id'])){
      $service['parentTpl'] = $this->getServiceTemplateTree($row['service_template_model_stm_id']);
      }
      }
      return $service;
      }

      public function getMacrosFromService($serviceId){
      $res = $this->db->query("SELECT svc_macro_name, svc_macro_value, is_password
      FROM on_demand_macro_service
      WHERE svc_svc_id = " .
      $this->db->escape($serviceId));
      $macroArray = array();
      while ($row = $res->fetchRow()) {
      $arr = array();
      if (preg_match('/\$_SERVICE(.*)\$$/', $row['svc_macro_name'], $matches)) {
      $arr['name'] = $matches[1];
      $arr['value'] = $row['svc_macro_value'];
      $arr['password'] = $row['is_password'] ? 1 : NULL;
      $macroArray[] = $arr;
      }
      }
      } */

    /**
     * Insert macro
     * 
     * @param int $serviceId
     * @param array $macroInput
     * @param array $macroValue
     * @param array $macroPassword
     * @param array $macroDescription
     * @param bool $isMassiveChange
     * @return void
     */
    public function insertMacro($serviceId, $macroInput = array(), $macroValue = array(), $macroPassword = array(), $macroDescription = array(), $isMassiveChange = false, $cmdId = false) {
        if (false === $isMassiveChange) {
            $this->db->query("DELETE FROM on_demand_macro_service
                    WHERE svc_svc_id = " . $this->db->escape($serviceId)
            );
        } else {
            $macroList = "";
            foreach ($macroInput as $v) {
                $macroList .= "'\$_SERVICE" . strtoupper($this->db->escape($v)) . "\$',";
            }
            if ($macroList) {
                $macroList = rtrim($macroList, ",");
                $this->db->query("DELETE FROM on_demand_macro_service
                         WHERE svc_svc_id = " . $this->db->escape($serviceId) . "
                         AND svc_macro_name IN ({$macroList})"
                );
            }
        }

        $macros = $macroInput;
        $macrovalues = $macroValue;
        
        $this->hasMacroFromServiceChanged($this->db,$serviceId,$macros,$macrovalues,$cmdId);
        
        $stored = array();
        $cnt = 0;
        foreach ($macros as $key => $value) {
            if ($value != "" &&
                    !isset($stored[strtolower($value)])) {
                $this->db->query("INSERT INTO on_demand_macro_service (`svc_macro_name`, `svc_macro_value`, `is_password`, `description`, `svc_svc_id`, `macro_order`) 
                                VALUES ('\$_SERVICE" . strtoupper($this->db->escape($value)) . "\$', '" . $this->db->escape($macrovalues[$key]) . "', " . (isset($macroPassword[$key]) ? 1 : 'NULL') . ", '" . $this->db->escape($macroDescription[$key]) . "', " . $this->db->escape($serviceId) . ", " . $cnt . " )");
                $stored[strtolower($value)] = true;
                $cnt ++;
            }
        }
    }

    public function getCustomMacroInDb($serviceId = null, $template = null) {
        $arr = array();
        $i = 0;
        if ($serviceId) {
            $res = $this->db->query("SELECT svc_macro_name, svc_macro_value, is_password, description
                                FROM on_demand_macro_service
                                WHERE svc_svc_id = " .
                    $this->db->escape($serviceId) . "
                                ORDER BY macro_order ASC");
            while ($row = $res->fetchRow()) {
                if (preg_match('/\$_SERVICE(.*)\$$/', $row['svc_macro_name'], $matches)) {
                    $arr[$i]['macroInput_#index#'] = $matches[1];
                    $arr[$i]['macroValue_#index#'] = $row['svc_macro_value'];
                    $arr[$i]['macroPassword_#index#'] = $row['is_password'] ? 1 : NULL;
                    $arr[$i]['macroDescription_#index#'] = $row['description'];
                    $arr[$i]['macroDescription'] = $row['description'];
                    if(!is_null($template)){
                        $arr[$i]['macroTpl_#index#'] = $template['service_description'];
                    }
                    $i++;
                }
            }
        }
        return $arr;
    }
    
    
    /**
     * Get service custom macro
     * 
     * @param int $serviceId
     * @return array
     */
    public function getCustomMacro($serviceId = null) {
        $arr = array();
        $i = 0;
        if (!isset($_REQUEST['macroInput']) && $serviceId) {
            $res = $this->db->query("SELECT svc_macro_name, svc_macro_value, is_password, description
                                FROM on_demand_macro_service
                                WHERE svc_svc_id = " .
                    $this->db->escape($serviceId) . "
                                ORDER BY macro_order ASC");
            while ($row = $res->fetchRow()) {
                if (preg_match('/\$_SERVICE(.*)\$$/', $row['svc_macro_name'], $matches)) {
                    $arr[$i]['macroInput_#index#'] = $matches[1];
                    $arr[$i]['macroValue_#index#'] = $row['svc_macro_value'];
                    $arr[$i]['macroPassword_#index#'] = $row['is_password'] ? 1 : NULL;
                    $arr[$i]['macroDescription_#index#'] = $row['description'];
                    $arr[$i]['macroDescription'] = $row['description'];
                    $i++;
                }
            }
        } elseif (isset($_REQUEST['macroInput'])) {
            foreach ($_REQUEST['macroInput'] as $key => $val) {
                $arr[$i]['macroInput_#index#'] = $val;
                $arr[$i]['macroValue_#index#'] = $_REQUEST['macroValue'][$key];
                $arr[$i]['macroPassword_#index#'] = isset($_REQUEST['is_password'][$key]) ? 1 : NULL;
                $arr[$i]['macroDescription_#index#'] = $row['description'];
                $arr[$i]['macroDescription'] = $row['description'];
                $i++;
            }
        }
        return $arr;
    }

    /**
     * Returns array of locked templates
     * 
     * @return array
     */
    public function getLockedServiceTemplates() {
        static $arr = null;

        if (is_null($arr)) {
            $arr = array();
            $res = $this->db->query("SELECT service_id 
                    FROM service 
                    WHERE service_locked = 1");
            while ($row = $res->fetchRow()) {
                $arr[$row['service_id']] = true;
            }
        }
        return $arr;
    }

    /**
     * Clean up service relations (services by hostgroup)
     * 
     * @param string $table
     * @param string $host_id_field
     * @param string $service_id_field
     * @return void
     */
    public function cleanServiceRelations($table = "", $host_id_field = "", $service_id_field = "") {
        $sql = "DELETE FROM {$table}
                    WHERE NOT EXISTS ( 
                        SELECT hsr1.host_host_id 
                        FROM host_service_relation hsr1
                        WHERE hsr1.host_host_id = {$table}.{$host_id_field}
                        AND hsr1.service_service_id = {$table}.{$service_id_field}
                    )
                    AND NOT EXISTS (
                        SELECT hsr2.host_host_id 
                        FROM host_service_relation hsr2, hostgroup_relation hgr
                        WHERE hsr2.host_host_id = hgr.host_host_id
                        AND hgr.host_host_id = {$table}.{$host_id_field}
                        AND hsr2.service_service_id = {$table}.{$service_id_field}
                    )";
        $this->db->query($sql);
    }

    /**
     * @param array $service
     * @param int $type | 0 = contact, 1 = contactgroup
     * @param array $cgSCache
     * @param array $cctSCache
     * @return bool
     */
    public function serviceHasContact($service, $type = 0, $cgSCache, $cctSCache) {
        static $serviceTemplateHasContactGroup = array();
        static $serviceTemplateHasContact = array();

        if ($type == 0) {
            $staticArr = & $serviceTemplateHasContact;
            $cache = $cctSCache;
        } else {
            $staticArr = & $serviceTemplateHasContactGroup;
            $cache = $cgSCache;
        }

        if (isset($cache[$service['service_id']])) {
            return true;
        }
        while (isset($service['service_template_model_stm_id']) && $service['service_template_model_stm_id']) {
            $serviceId = $service['service_template_model_stm_id'];
            if (isset($cache[$serviceId]) || isset($staticArr[$serviceId])) {
                $staticArr[$serviceId] = true;
                return true;
            }
            $res = $this->db->query("SELECT service_template_model_stm_id 
			   	    FROM service 
				    WHERE service_id = {$serviceId}"
            );
            $service = $res->fetchRow();
        }
        return false;
    }
    
    
    public function hasMacroFromServiceChanged($pearDB, $service_id,&$macroInput,&$macroValue, $cmdId = false){
        
        $aListTemplate = getListTemplates($pearDB, $service_id);
        
        if (!isset($cmdId)) {
            $cmdId = "";
        }
        $aMacros = $this->getMacros($service_id, $aListTemplate, $cmdId);
        foreach($aMacros as $macro){
            foreach($macroInput as $ind=>$input){
                if($input == $macro['macroInput_#index#'] && $macroValue[$ind] == $macro["macroValue_#index#"]){
                    unset($macroInput[$ind]);
                    unset($macroValue[$ind]);
                }
            }
        }
    }
    
    
    /**
     * This method get the macro attached to the service
     * 
     * @param int $iServiceId
     * @param array $aListTemplate
     * @param int $iIdCommande
     * 
     * @return array
     */
    public function getMacros($iServiceId, $aListTemplate, $iIdCommande)
    {
        
        $aMacro = array();
        $macroArray = array();
        $aMacroInService = array();
        
        //Get macro attached to the service
        $macroArray = $this->getCustomMacroInDb($iServiceId);
        $iNb = count($macroArray);

        //Get macro attached to the template
        $aMacroTemplate = array();
        
        foreach ($aListTemplate as $template) {
            if (!empty($template)) {
                $aMacroTemplate[] = $this->getCustomMacroInDb($template['service_template_model_stm_id'],$template);
            }
        }
        //Get macro attached to the command        
        if (!empty($iIdCommande)) {
            $oCommand = new CentreonCommand($this->db);
            $aMacroInService[] = $oCommand->getMacroByIdAndType($iIdCommande, 'service');
        }

        //filter a macro
        $aTempMacro = array();
        if (count($macroArray) > 0) {
            foreach($macroArray as $directMacro){
                $directMacro['source'] = 'direct';
                $aTempMacro[] = $directMacro;
            }
        }
        
        $iNb = count($aTempMacro);
        if (count($aMacroTemplate) > 0) {  
            foreach ($aMacroTemplate as $key => $macr) {
                foreach ($macr as $mm) {
                    $mm['source'] = 'fromTpl';
                    $aTempMacro[$iNb++] = $mm;
                }
            }
        }
        $serv = current($aMacroInService);
        if (count($aMacroInService) > 0) {
            for ($i = 0; $i < count($serv); $i++) {
                $serv[$i]['source'] = 'fromService';
                $aTempMacro[$iNb++] = $serv[$i];
            }
        }
        $aFinalMacro = macro_unique($aTempMacro);
        
        return $aFinalMacro;
    }

    /**
     * 
     * @param integer $field
     * @return array
     */
    public static function getDefaultValuesParameters($field)
    {
        $parameters = array();
        $parameters['currentObject']['table'] = 'service';
        $parameters['currentObject']['id'] = 'service_id';
        $parameters['currentObject']['name'] = 'service_description';
        $parameters['currentObject']['comparator'] = 'service_id';

        switch ($field) {
            case 'timeperiod_tp_id':
            case 'timeperiod_tp_id2':
                $parameters['type'] = 'simple';
                $parameters['externalObject']['table'] = 'timeperiod';
                $parameters['externalObject']['id'] = 'tp_id';
                $parameters['externalObject']['name'] = 'tp_name';
                $parameters['externalObject']['comparator'] = 'tp_id';
                break;
        }
        
        return $parameters;
    }
}

?>

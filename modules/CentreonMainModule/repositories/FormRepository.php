<?php
/*
 * Copyright 2005-2014 CENTREON
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

namespace CentreonMain\Repository;


use Centreon\Internal\Di;
use Centreon\Internal\Exception;
use Centreon\Internal\Form\Validators\Validator;
use CentreonMain\Events\PreSave as PreSaveEvent;
use CentreonMain\Events\PostSave as PostSaveEvent;
use Centreon\Internal\CentreonSlugify;

/**
 * Abstact class for configuration repository
 *
 * @version 3.0.0
 * @author Sylvestre Ho <sho@centreon.com>
 */
abstract class FormRepository extends ListRepository
{
    
    /**
     *
     * @var array
     */
    public static $exposedParams = array();
    
    /**
     * Get list of objects
     *
     * @param string $searchStr
     * @return array
     */
    public static function getFormList($searchStr = "", $objectId = null, $additionalGetParams = null)
    {
        if (!empty(static::$secondaryObjectClass)) {
            $class = static::$secondaryObjectClass;
        } else {
            $class = static::$objectClass;
        }
        
        $idField = $class::getPrimaryKey();
        $uniqueField = $class::getUniqueLabelField();
        $filters = array(
            $uniqueField => '%'.$searchStr.'%'
        );

        $columns = $class::getColumns();
        if (in_array(static::ORGANIZATION_FIELD, $columns)) {
           $filters[static::ORGANIZATION_FIELD] = Di::getDefault()->get('organization');
        }

        if(!empty($additionalGetParams)){
            foreach($additionalGetParams as $key=>$additionalGetParam){
                if(isset(static::$exposedParams[$key])){
                    if(in_array(static::$exposedParams[$key], $columns)){
                        $filters[static::$exposedParams[$key]] = $additionalGetParam;
                    }
                }
            }
        }
        

        $list = $class::getList(array($idField, $uniqueField), -1, 0, null, "ASC", $filters, "AND");
        $finalList = array();
        foreach ($list as $obj) {
            $finalList[] = array(
                "id" => $obj[$idField],
                "text" => $obj[$uniqueField]
            );
        }
        return $finalList;
    }
    
    /**
     * 
     * @param type $givenParameters
     * @param type $origin
     * @param type $route
     */
    protected static function validateForm($givenParameters, $origin = "", $route = "", $validateMandatory = true)
    {
        $formValidator = new Validator($origin, array('route' => $route, 'params' => array(), 'version' => '3.0.0'));
        
        if (is_a($givenParameters, '\Klein\DataCollection\DataCollection')) {
            $givenParameters = $givenParameters->all();
        }
        
        $formValidator->validate($givenParameters, $validateMandatory);
    }

    /**
     * Generic create action
     *
     * @param array $givenParameters
     * @return int id of created object
     */
    public static function create($givenParameters, $origin = "", $route = "", $validate = true, $validateMandatory = true)
    {
        $id = null;
        $db = Di::getDefault()->get('db_centreon');

        $extraParameters = array();
        foreach ($givenParameters as $name => $value) {
            $explodedName = explode("__", $name);
            if (count($explodedName) == 2) {
                $extraParameters[$explodedName[0]][$explodedName[1]] = $value;
                unset($givenParameters[$name]);
            }
        }

        $events = Di::getDefault()->get('events');
        $preSaveEvent = new PreSaveEvent('create', $givenParameters, $extraParameters);
        $events->emit('centreon-main.pre.save', array($preSaveEvent));

        try {
            if ($validate) {
                self::validateForm($givenParameters, $origin, $route, $validateMandatory);
            }
            
        
            $class = static::$objectClass;
            $pk = $class::getPrimaryKey();
            $columns = $class::getColumns();
            $insertParams = array();
            $givenParameters[static::ORGANIZATION_FIELD] = Di::getDefault()->get('organization');
            
            $oSlugify = new CentreonSlugify($class, get_called_class());             
            $givenParameters[$class::getSlugField()]  = $oSlugify->slug($givenParameters[$class::getUniqueLabelField()]);
            
            //var_dump($givenParameters);die;
            foreach ($givenParameters as $key => $value) {
                if (in_array($key, $columns)) {
                    if (!is_array($value)) {
                        $value = trim($value);
                        if (!empty($value)) {
                            $insertParams[$key] = trim($value);
                        }
                    }
                }
            }

            
            $db->beginTransaction();
            $id = $class::insert($insertParams);
            if (is_null($id)) {
                $db->rollback();
                throw new Exception('Could not create object');
            }
            foreach (static::$relationMap as $k => $rel) {
                if (!isset($givenParameters[$k])) {
                    continue;
                }
                $arr = explode(',', ltrim($givenParameters[$k], ','));

                foreach ($arr as $relId) {
                    $relId = trim($relId);
                    if (is_numeric($relId)) {
                        if ($rel::$firstObject == static::$objectClass) {
                            $rel::insert($id, $relId);
                        } else {
                            $rel::insert($relId, $id);
                        }
                    } elseif (!empty($relId)) {
                        $complexeRelId = explode('_', $relId);
                        if ($rel::$firstObject == static::$objectClass) {
                            $rel::insert($id, $complexeRelId[1], $complexeRelId[0]);
                        }
                    }
                }
                unset($givenParameters[$k]);
            }
            $db->commit();
        
            if (method_exists(get_called_class(), 'postSave')) {
                static::postSave($id, 'add', $givenParameters);
            }
        } catch (\PDOException $e) {
            $db->rollback();
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        $givenParameters['object_id'] = $id;
        $postSaveEvent = new PostSaveEvent('create', $givenParameters, $extraParameters);
        $events->emit('centreon-main.post.save', array($postSaveEvent));

        return $id;
    }
    
    public static function disable($givenParameters)
    {
        static::update($givenParameters, '', '', false);
    }

    /**
     * Generic update function
     *
     * @param array $givenParameters
     * @throws \Centreon\Internal\Exception
     */
    public static function update($givenParameters, $origin = "", $route = "", $validate = true, $validateMandatory = true)
    {
        if ($validate) {
            self::validateForm($givenParameters, $origin, $route, $validateMandatory);
        }

        $extraParameters = array();
        foreach ($givenParameters as $name => $value) {
            $explodedName = explode("__", $name);
            if (count($explodedName) == 2) {
                $extraParameters[$explodedName[0]][$explodedName[1]] = $value;
                unset($givenParameters[$name]);
            }
        }

        $events = Di::getDefault()->get('events');
        $preSaveEvent = new PreSaveEvent('update', $givenParameters, $extraParameters);
        $events->emit('centreon-main.pre.save', array($preSaveEvent));
        
        $class = static::$objectClass;
        $pk = $class::getPrimaryKey();
        $givenParameters[$pk] = $givenParameters['object_id'];
        
        $oSlugify = new CentreonSlugify($class, get_called_class());             
        $givenParameters[$class::getSlugField()]  = $oSlugify->slug($givenParameters[$class::getUniqueLabelField()]);
            
        if (!isset($givenParameters[$pk])) {
            throw new \Exception('Primary key of object is not defined');
        }
        $db = Di::getDefault()->get('db_centreon');
        $id = $givenParameters[$pk];
        unset($givenParameters[$pk]);
        foreach (static::$relationMap as $k => $rel) {
            try {
                if (!isset($givenParameters[$k])) {
                    continue;
                }
                try {
                    if ($rel::$firstObject == static::$objectClass) {
                        $rel::delete($id);
                    } else {
                        $rel::delete(null, $id);
                    }
                } catch (Exception $e) {
                    ; // it's okay if nothing got deleted
                }
                $arr = explode(',', ltrim($givenParameters[$k], ','));
                $db->beginTransaction();

                foreach ($arr as $relId) {
                    $relId = trim($relId);
                    if (is_numeric($relId)) {
                        if ($rel::$firstObject == static::$objectClass) {
                            $rel::insert($id, $relId);
                        } else {
                            $rel::insert($relId, $id);
                        }
                    } elseif (!empty($relId)) {
                        $complexeRelId = explode('_', $relId);
                        if ($rel::$firstObject == static::$objectClass) {
                            $rel::insert($id, $complexeRelId[1], $complexeRelId[0]);
                        }
                    }
                }
                $db->commit();
                unset($givenParameters[$k]);
            } catch (Exception $e) {
                throw new Exception('Error while updating', 0, $e);
            }
        }
        $columns = $class::getColumns();
        $updateValues = array();
        foreach ($givenParameters as $key => $value) {
            if (in_array($key, $columns)) {
                if (is_string($value)) {
                    $updateValues[$key] = trim($value);
                } else {
                    $updateValues[$key] = $value;
                }
            }
        }
        
        $class::update($id, $updateValues);
       
        $postSaveEvent = new PostSaveEvent('update', $givenParameters, $extraParameters);
        $events->emit('centreon-main.post.save', array($postSaveEvent));
 
        if (method_exists(get_called_class(), 'postSave')) {
            static::postSave($id, 'update', $givenParameters);
        }
    }

    /**
     * Delete an object
     *
     * @param array $ids | array of ids to delete
     */
    public static function delete($ids)
    {
        $objClass = static::$objectClass;
        foreach ($ids as $id) {
            if (method_exists(get_called_class(), 'preSave')) {
                static::preSave($id, 'delete', array());
            }
            
            $objClass::delete($id);
            
            if (method_exists(get_called_class(), 'postSave')) {
                static::postSave($id, 'delete', array());
            }
        }
    }

    /**
     * Duplicate a object
     *
     * @param array $listDuplicate
     */
    public static function duplicate($listDuplicate)
    {
        $objClass = static::$objectClass;
        foreach ($listDuplicate as $id => $nb) {
            $objClass::duplicate($id, $nb);
        }
    }
}

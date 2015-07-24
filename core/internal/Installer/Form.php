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
namespace Centreon\Internal\Installer;

use Centreon\Internal\Di;
use Centreon\Models\Validators;
use Centreon\Models\Module;
use Cocur\Slugify\Slugify;
/**
 * Description of Form
 *
 * @author lionel
 */
class Form
{
    /**
     *
     * @var type 
     */
    protected static $forms;
    
    /**
     *
     * @var type 
     */
    protected static $sections;
    
    /**
     *
     * @var type 
     */
    protected static $blocks;
    
    /**
     *
     * @var type 
     */
    protected static $fields;
    
    /**
     *
     * @var type 
     */
    protected static $blockFields;
    
    /**
     *
     * @var type 
     */
    protected static $steps;
    
    /**
     *
     * @var type 
     */
    protected static $wizards;
    
    /**
     *
     * @var type 
     */
    protected static $stepFields;
    
    /**
     *
     * @var type 
     */
    protected static $validators;
    
    /**
     * 
     * @param type $moduleSlug
     */
    public function __construct($moduleSlug)
    {
        ;
    }
    
    /**
     * 
     */
    public function updateFormsInDb()
    {
        
    }

    /**
     * 
     * @param type $formName
     */
    public static function initForm($formName)
    {
        $sql = "SELECT f.form_id, f.name as form_name, 
            s.section_id, s.name as section_name, 
            b.block_id, b.name as block_name,
            d.field_id, d.name as field_name
            FROM cfg_forms f, cfg_forms_sections s, cfg_forms_blocks b, cfg_forms_fields d, cfg_forms_blocks_fields_relations r
            WHERE f.form_id = s.form_id
            AND s.section_id = b.section_id
            AND b.block_id = r.block_id
            AND r.field_id = d.field_id
            AND f.name = ?";
        $db = \Centreon\Internal\Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare($sql);
        $stmt->execute(array($formName));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        self::$forms = array();
        self::$sections = array();
        self::$blocks = array();
        self::$fields = array();
        self::$blockFields = array();
        foreach ($rows as $row) {
            $form_key = $row['form_name'];
            $section_key = $form_key . ';' . $row['section_name'];
            $block_key = $section_key . ';' . $row['block_name'];
            $field_key = $row['field_name'];
            $block_field_key = $block_key . ';' . $row['field_name'];
            if (!isset(self::$forms[$form_key])) {
                self::$forms[$form_key] = $row['form_id'];
            }
            if (!isset(self::$sections[$section_key])) {
                self::$sections[$section_key] = $row['section_id'];
            }
            if (!isset(self::$blocks[$block_key])) {
                self::$blocks[$block_key] = $row['block_id'];
            }
            if (!isset(self::$fields[$field_key])) {
                self::$fields[$field_key] = $row['field_id'];
            }
            if (!isset(self::$blockFields[$block_field_key])) {
                self::$blockFields[$block_field_key] = $row['block_id'] . ';' . $row['field_id'];
            }
        }
    }
    
    /**
     * 
     */
    public static function initValidators()
    {
        self::$validators = array();
        $db = \Centreon\Internal\Di::getDefault()->get('db_centreon');
        $stmt = $db->query("SELECT validator_id, name FROM cfg_forms_validators");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            self::$validators[$row['name']] = $row['validator_id'];
        }
    }
    
    /**
     * 
     * @param array $validatorsList
     */
    public static function insertValidators($validatorsList)
    {
        foreach($validatorsList as $validator) {
            $validatorId = Validators::getIdByParameter('name', array($validator['name']));
            if (count($validatorId) == 0) {
                Validators::insert($validator);
            } else {
                $aDatas = array('name' => $validator['name'], 'route' => $validator['route']);
                $id = current($validatorId);
                if (is_int($id)) {
                    Validators::update($id, $aDatas);
                }
            }
        }
    }
    /**
     * 
     * @param type $aValidators
     */

    public static function removeValidators($aValidators)
    {
        foreach ($aValidators as $validator) {
            Validators::delete($validator['name']);
        }
    }

    /**
     * 
     * @param type $wizardName
     */
    public static function initWizard($wizardName)
    {
        $sql = "SELECT w.wizard_id, w.name as wizard_name, 
            s.step_id, s.name as step_name,
            d.field_id, d.name as field_name
            FROM cfg_forms_wizards w, cfg_forms_steps s, cfg_forms_steps_fields_relations r, cfg_forms_fields d
            WHERE w.wizard_id = s.wizard_id
            AND s.step_id = r.step_id
            AND r.field_id = d.field_id
            AND w.name = ?";
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare($sql);
        $stmt->execute(array($wizardName));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        self::$wizards = array();
        self::$steps = array();
        self::$stepFields = array();
        foreach ($rows as $row) {
            $wizard_key = $row['wizard_name'];
            $step_key = $wizard_key . ';' . $row['step_name'];
            $field_key = $row['field_name'];
            $step_field_key = $step_key . ';' . $row['field_name'];
            if (!isset(self::$wizards[$wizard_key])) {
                self::$wizards[$wizard_key] = $row['wizard_id'];
            }
            if (!isset(self::$steps[$step_key])) {
                self::$steps[$step_key] = $row['step_id'];
            }
            if (!isset(self::$stepFields[$step_field_key])) {
                self::$stepFields[$step_field_key] = $row['step_id'] . ';' . $row['field_id'];
            }
        }
    }
    

    /**
     * 
     * @param type $data
     * @param type $moduleId
     */
    public static function insertForm($data, $moduleId)
    {
        $key = $data['name'];
        $db = Di::getDefault()->get('db_centreon');
        if (!isset(self::$forms[$key])) {
            $stmt = $db->prepare(
                'SELECT count(route)as count,form_id,module_id '
                . 'FROM cfg_forms '
                . 'WHERE route = :route'
            );
            $stmt->bindParam(':route', $data['route'], \PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (isset($row['count']) && $row['count'] > 0 && $row['module_id'] !== $moduleId) {
                self::$forms[$key] = $row['form_id'];
                return array('success' => false, 'moduleId' => $row['module_id']);
            } else if (isset($row['count']) && $row['count'] > 0) {
                self::$forms[$key] = $row['form_id'];
                return array('success' => false, 'moduleId' => $row['module_id']);
            } else {
                $sql = 'INSERT INTO cfg_forms (name, route, redirect, redirect_route, module_id)'
                    . ' VALUES (:name, :route, :redirect, :redirect_route, :module)';
            }
        } else {
            $sql = 'UPDATE cfg_forms SET route = :route,'
                . ' redirect = :redirect,'
                . ' redirect_route = :redirect_route'
                . ' WHERE name = :name'
                . ' AND module_id = :module';
        }
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':route', $data['route']);
        $stmt->bindParam(':redirect', $data['redirect']);
        $stmt->bindParam(':redirect_route', $data['redirect_route']);
        $stmt->bindParam(':module', $moduleId);
        $stmt->execute();
        if (!isset(self::$forms[$key])) {
            self::$forms[$key] = $db->lastInsertId('cfg_forms', 'form_id');
        }
        return array('success' => true, 'moduleId' => $moduleId);
    }

    /**
     * 
     * @param type $data
     */
    public static function insertSection($data)
    {
        $key = $data['form_name'] . ';' . $data['name'];
        $db = \Centreon\Internal\Di::getDefault()->get('db_centreon');
        if (!isset(self::$sections[$key])) {
            $sql = 'INSERT INTO cfg_forms_sections (name, rank, form_id) 
                VALUES (:name, :rank, :form_id)';
        } else {
            $sql = 'UPDATE cfg_forms_sections SET rank = :rank,
                form_id = :form_id
                WHERE name = :name
                AND section_id = :section_id';
        }
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':rank', $data['rank'], \PDO::PARAM_INT);
        if (isset(self::$sections[$key])) {
            $stmt->bindParam(':section_id', $sections[$key]);
        }
        $stmt->bindParam(':form_id', self::$forms[$data['form_name']], \PDO::PARAM_INT);
        $stmt->execute();
        if (!isset(self::$sections[$key])) {
            self::$sections[$key] = $db->lastInsertId('cfg_forms_sections', 'section_id');
        }
    }

    /**
     * Insert block into db
     *
     * @param array $data
     */
    public static function insertBlock($data)
    {
        $sectionKey = $data['form_name'] . ';' . $data['section_name'];
        $key = implode(';', array($data['form_name'], $data['section_name'], $data['name']));
        $db = \Centreon\Internal\Di::getDefault()->get('db_centreon');
        if (!isset(self::$blocks[$key])) {
            $sql = 'INSERT INTO cfg_forms_blocks (name, rank, section_id) 
                VALUES (:name, :rank, :section_id)';
        } else {
            $sql = 'UPDATE cfg_forms_blocks SET rank = :rank,
                section_id = :section_id
                WHERE name = :name
                AND block_id = :block_id';
        }
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':rank', $data['rank'], \PDO::PARAM_INT);
        if (isset(self::$blocks[$key])) {
            $stmt->bindParam(':block_id', self::$blocks[$key]);
        }
        $stmt->bindParam(':section_id', self::$sections[$sectionKey], \PDO::PARAM_INT);
        $stmt->execute();
        if (!isset(self::$blocks[$key])) {
            self::$blocks[$key] = $db->lastInsertId('cfg_forms_blocks', 'block_id');
        }
    }

    /**
     * Insert field into db
     *
     * @param array $data
     */
    public static function insertField($data)
    {
        $key = $data['name'];
        $db = Di::getDefault()->get('db_centreon');
        if (!isset(self::$fields[$key])) {
            $sql = 'INSERT INTO cfg_forms_fields 
                (name, label, default_value, attributes, advanced, type, 
                help, module_id, parent_field, parent_value, child_actions, mandatory, child_mandatory, show_label, normalized_name, width) VALUES 
                (:name, :label, :default_value, :attributes, :advanced, 
                :type, :help, :module_id, :parent_field, :parent_value, :child_actions, :mandatory, :child_mandatory, :show_label, :normalized_name, :width)';
        } else {
            $sql = 'UPDATE cfg_forms_fields SET label = :label,
                default_value = :default_value,
                attributes = :attributes,
                advanced = :advanced,
                type = :type,
                help = :help,
                module_id = :module_id,
                parent_field = :parent_field,
                parent_value = :parent_value,
                child_actions = :child_actions,
                mandatory = :mandatory,
                child_mandatory = :child_mandatory,
                show_label = :show_label,
                normalized_name = :normalized_name,
                width = :width
                WHERE name = :name
                AND field_id = :field_id';
        }
        $stmt = $db->prepare($sql);
        if (isset(self::$fields[$key])) {
            $stmt->bindParam(':field_id', self::$fields[$key]);
        }
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':label', $data['label']);
        $stmt->bindParam(':default_value', $data['default_value']);
        $stmt->bindParam(':attributes', $data['attributes']);
        $stmt->bindParam(':advanced', $data['advanced']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':help', $data['help']);
        $stmt->bindParam(':module_id', $data['module_id']);
        $stmt->bindParam(':parent_field', $data['parent_field']);
        $stmt->bindParam(':child_actions', $data['child_actions']);
        if(isset($data['show_label'])){
            $stmt->bindParam(':show_label', $data['show_label']);
        } else {
            $data['show_label'] = 1;
            $stmt->bindParam(':show_label', $data['show_label']);
        }
        
        
        if(isset($data['normalized_name']) && !empty($data['normalized_name'])){
            $stmt->bindParam(':normalized_name', $data['normalized_name']);
        } else {
            $slugifier = new Slugify('/([^a-z0-9]|-)+/');
            $sSlug = $slugifier->slugify($data['name']);
            $stmt->bindParam(':normalized_name', $sSlug);
        }
        
        if (!isset($data['mandatory'])) {
            $data['mandatory'] = '0';
        }
        $stmt->bindParam(':mandatory', $data['mandatory']);

        if (!isset($data['child_mandatory'])) {
            $data['child_mandatory'] = '0';
        }
        $stmt->bindParam(':child_mandatory', $data['child_mandatory']);

        if (!isset($data['parent_value'])) {
            $data['parent_value'] = null;
        }
        $stmt->bindParam(':parent_value', $data['parent_value']);

        if (!isset($data['width']) || ($data['width'] === "")) {
            $stmt->bindValue(':width', null);
        } else {
            $stmt->bindParam(':width', $data['width']);
        }
        
        $stmt->execute();
        if (!isset(self::$fields[$key])) {
            self::$fields[$key] = $db->lastInsertId('cfg_forms_fields', 'field_id');
        }
    }

    /**
     * Add field to a block
     *
     * @param array $data
     */
    public static function addFieldToBlock($data)
    {
        $fname = $data['field_name'];
        $key = implode(';', array($data['form_name'], $data['section_name'], $data['block_name']));
        if (isset(self::$blocks[$key]) && isset(self::$fields[$fname])) {
            $db = Di::getDefault()->get('db_centreon');
            $stmt = $db->prepare(
                'DELETE FROM cfg_forms_blocks_fields_relations WHERE block_id = :block_id AND field_id = :field_id'
            );
            $stmt->bindParam(':block_id', self::$blocks[$key]);
            $stmt->bindParam(':field_id', self::$fields[$fname]);
            $stmt->execute();

            foreach ($data['versions'] as $version) {
                $stmt = $db->prepare(
                    'REPLACE INTO cfg_forms_blocks_fields_relations (block_id, field_id, rank, product_version) '
                    . 'VALUES (:block_id, :field_id, :rank, :product_version)'
                );
                $stmt->bindParam(':block_id', self::$blocks[$key]);
                $stmt->bindParam(':field_id', self::$fields[$fname]);
                $stmt->bindParam(':rank', $data['rank']);
                $stmt->bindParam(':product_version', $version);
                $stmt->execute();
            }
        }
        $tmp = $key . ';' . $fname;
        self::$blockFields[$tmp] = self::$blocks[$key] . ';' . self::$fields[$fname];
    }
    
    /**
     * 
     * @param type $data
     */
    public static function addValidatorsToField($data)
    {
        $db = Di::getDefault()->get('db_centreon');
        $fname = (string)$data['field_name'];
        $validators = $data['validators'];

        if (isset(self::$fields[$fname])) {
            $formName = (string)$data['form_name'];
            if (isset(static::$forms[$formName])) {
                $stmt = $db->prepare('DELETE fv FROM cfg_forms_fields_validators_relations fv 
                   JOIN cfg_forms_blocks_fields_relations bf ON fv.field_id = bf.field_id
                   JOIN cfg_forms_blocks fbf on fbf.block_id = bf.block_id
                   JOIN cfg_forms_sections fs ON fs.section_id = fbf.section_id
                   WHERE form_id = :form_id AND fv.field_id = :field_id' 
               );
               $stmt->bindParam(':field_id', self::$fields[$fname]);
               $stmt->bindParam(':form_id', self::$forms[$formName]);
               $stmt->execute();
            }
            
            if (!is_null($validators->validator)) {
                foreach ($validators->validator as $validator) {
                    if (isset($validator['serverside'])) {
                        $serverside = (string)$validator['serverside'];
                    } else {
                        $serverside = null;
                    }

                    if (isset(self::$validators[$serverside])) {

                        $stmt = $db->prepare(
                            'REPLACE INTO cfg_forms_fields_validators_relations (validator_id, field_id, client_side_event, params, server_side) '
                            . 'VALUES (:validator_id, :field_id, :client_side_event, :params, :server_side)'
                        );
                        $stmt->bindParam(':validator_id', self::$validators[$serverside]);
                        $stmt->bindParam(':field_id', self::$fields[$fname]);
                        $stmt->bindValue(':client_side_event', (string)$validator['rules']);

                        if (is_null($serverside)) {
                            $stmt->bindValue(':server_side', '0');
                        } else {
                            $stmt->bindValue(':server_side', '1');
                        }

                        //unset($validator['rules']);
                        $validatorParams = array();
                        foreach ($validator->argument as $argument) {
                            //$validatorParams = (string)$argument['name'] . '=' . $argument;
                            $validatorName = (string)$argument['name'];
                            $validatorValue = (string)$argument;
                            $validatorParams[$validatorName] = $validatorValue;
                        }
                        $encodedParams = json_encode($validatorParams);
                        $stmt->bindParam(':params', $encodedParams);
                        $stmt->execute();
                    }
                }
            }
        }
    }

    /**
     * 
     * @param type $moduleId
     * @param type $xmlFile
     */
    public static function installFromXml($moduleId, $xmlFile = "")
    {
        $xml = simplexml_load_file($xmlFile);
        if ($xml) {
            foreach ($xml as $form) {
                if ($form->getName() == 'form') {
                    self::processForm($form, $moduleId);
                } elseif ($form->getName() == 'wizard') {
                    self::processWizard($form, $moduleId);
                }
            }
        } else {
            throw new \Exception("Error when loading file ".$xmlFile);
        }
    }

    /**
     * 
     * @param type $data
     * @param type $moduleId
     */
    protected static function insertWizard($data, $moduleId)
    {
        $key = $data['name'];
        $db = Di::getDefault()->get('db_centreon');
        if (!isset(self::$wizards[$key])) {
            $stmt = $db->prepare(
                'SELECT count(route) as count,wizard_id, module_id '
                . 'FROM cfg_forms_wizards '
                . 'WHERE route = :route'
            );
            $stmt->bindParam(':route', $data['route'], \PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
       
            if (isset($row['count']) && $row['count'] > 0 && $row['module_id'] !== $moduleId) {
                self::$wizards[$key] = $row['wizard_id'];
                return array('success' => false, 'moduleId' => $row['module_id']);
            } else if (isset($row['count']) && $row['count'] > 0) {
                self::$wizards[$key] = $row['wizard_id'];
                return array('success' => false, 'moduleId' => $row['module_id']);
            } else {
                $sql = 'INSERT INTO cfg_forms_wizards (name, route, module_id)'
                    . ' VALUES (:name, :route, :module)';
            }
        } else {
            $sql = 'UPDATE cfg_forms_wizards SET route = :route
                WHERE name = :name 
                AND wizard_id = :wizard_id
                AND module_id = :module';
        }
        $stmt = $db->prepare($sql);
        if (isset(self::$wizards[$key])) {
            $stmt->bindParam(':wizard_id', self::$wizards[$key]);
        }
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':route', $data['route']);
        $stmt->bindParam(':module', $moduleId);
        $stmt->execute();
        if (!isset(self::$wizards[$key])) {
            self::$wizards[$key] = $db->lastInsertId('cfg_forms_wizards', 'wizard_id');
        }
        return array('success' => true, 'moduleId' => $moduleId);
    }

    /**
     * Insert step
     *
     * @param array $data
     */
    protected static function insertStep($data)
    {
        $key = implode(';', array($data['wizard_name'], $data['name']));
        $db = Di::getDefault()->get('db_centreon');
        if (!isset(self::$steps[$key])) {
            $sql = 'INSERT INTO cfg_forms_steps (name, rank, wizard_id) 
                VALUES (:name, :rank, :wizard_id)';
        } else {
            $sql = 'UPDATE cfg_forms_steps SET rank = :rank,
                wizard_id = :wizard_id
                WHERE name = :name
                AND step_id = :step_id';
        }
        $stmt = $db->prepare($sql);
        if (isset(self::$steps[$key])) {
            $stmt->bindParam(':step_id', self::$steps[$key]);
        }
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':rank', $data['rank'], \PDO::PARAM_INT);
        $stmt->bindParam(':wizard_id', self::$wizards[$data['wizard_name']], \PDO::PARAM_INT);
        $stmt->execute();
        if (!isset(self::$steps[$key])) {
            self::$steps[$key] = $db->lastInsertId('cfg_forms_steps', 'step_id');
        }
    }

    /**
     * Add field to step
     *
     * @param array $data
     */
    protected static function addFieldToStep($data)
    {
        $fname = $data['field_name'];
        $key = implode(';', array($data['wizard_name'], $data['step_name']));
        if (isset(self::$steps[$key]) && isset(self::$fields[$fname])) {
            $db = Di::getDefault()->get('db_centreon');
            $stmt = $db->prepare(
                'REPLACE INTO cfg_forms_steps_fields_relations (step_id, field_id, rank) '
                . 'VALUES (:step_id, :field_id, :rank)'
            );
            $stmt->bindParam(':step_id', self::$steps[$key]);
            $stmt->bindParam(':field_id', self::$fields[$fname]);
            $stmt->bindParam(':rank', $data['rank']);
            $stmt->execute();
        }
        $tmp = $key . ';' . $fname;
        self::$stepFields[$tmp] = self::$steps[$key] . ';' . self::$fields[$fname];
    }

    /**
     * 
     * @param type $wizard
     * @param type $moduleId
     */
    protected static function processWizard($wizard, $moduleId)
    {
        $insertedSteps = array();
        $insertedFields = array();
        self::initWizard($wizard['name']);
        self::initValidators();
        $wizardData = array(
            'name' => $wizard['name'],
            'route' => $wizard->route
        );
        $wizardAdded = self::insertWizard(array_map('strval', $wizardData), $moduleId);
        $stepRank = 1;
        foreach ($wizard->step as $step) {
            $stepData = array(
                'name' => $step['name'],
                'wizard_name' => $wizard['name'],
                'rank' => $stepRank
            );
            self::insertStep(array_map('strval', $stepData));
            $stepRank++;
            $fieldRank = 1;
            foreach ($step->field as $field) {
                if ($wizardAdded['success'] === false && $wizardAdded['moduleId'] != $moduleId) {
                    $moduleName = Module::getParameters($moduleId, 'name');
                    if (isset($moduleName['name'])) {
                        $field['name'] = $moduleName['name'] . '__' . $field['name'];
                    }
                }
                $stepFieldData = array(
                    'wizard_name' => $wizard['name'],
                    'step_name' => $step['name'],
                    'field_name' => $field['name'],
                    'rank' => $fieldRank
                );
                self::addFieldToStep(array_map('strval', $stepFieldData));
                $fieldValidators = array(
                    'form_name' => $wizard['name'],
                    'field_name' => $field['name'],
                    'validators' => $field->validators
                );
                self::addValidatorsToField($fieldValidators);
                $fieldRank++;
                $insertedFields[] = implode(';', array($wizard['name'], $step['name'], $field['name']));
            }
            $insertedSteps[] = implode(';', array($wizard['name'], $step['name']));
        }
        self::purgeSteps($insertedSteps);
        self::purgeStepFields($insertedFields);
    }

    /**
     * 
     * @param type $form
     * @param type $moduleId
     */
    protected static function processForm($form, $moduleId)
    {
        $insertedSections = array();
        $insertedBlocks = array();
        $insertedFields = array();
        self::initForm($form['name']);
        self::initValidators();
        $formData = array(
            'name' => $form['name'],
            'route' => $form->route,
            'redirect' => $form->redirect,
            'redirect_route' => $form->redirect_route
        );
        $formAdded = self::insertForm(array_map('strval', $formData), $moduleId);
        $sectionRank = 1;
        foreach ($form->section as $section) {
            $sectionData = array(
                'name' => $section['name'],
                'form_name' => $form['name'],
                'rank' => $sectionRank
            );
            self::insertSection(array_map('strval', $sectionData));
            $sectionRank++;
            $blockRank = 1;
            foreach ($section->block as $block) {
                $blockData = array(
                    'name' => $block['name'],
                    'form_name' => $form['name'],
                    'section_name' => $section['name'],
                    'rank' => $blockRank
                );
                self::insertBlock(array_map('strval', $blockData));
                $blockRank++;
                $fieldRank = 1;
                foreach ($block->field as $field) {
                    $attributes = array();
                    $versions = array('');
                    if (isset($field->attributes)) {
                        $attributes = self::parseAttributes($field->attributes);
                    }
                    if (isset($field->versions)) {
                        $versions = self::parseVersions($field->versions);
                    }
                    $attributes = json_encode($attributes);
                    if ($formAdded['success'] === false && $formAdded['moduleId'] != $moduleId) {
                        $moduleName = Module::getParameters($moduleId, 'name');
                        if (isset($moduleName['name'])) {
                            $field['name'] = $moduleName['name'] . '__' . $field['name'];
                        }
                    }
                    
                    if(!isset($field['show_label'])){
                        $field['show_label'] = 1;
                    }

                    if(!isset($field['width'])){
                        $field['width'] = null;
                    }
                    
                    $fieldData = array(
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'default_value' => $field['default_value'],
                        'advanced' => $field['advanced'],
                        'type' => $field['type'],
                        'parent_field' => $field['parent_field'],
                        'parent_value' => $field['parent_value'],
                        'module_id' => $moduleId,
                        'child_actions' => $field->child_actions,
                        'attributes' => $attributes,
                        'mandatory' => $field['mandatory'],
                        'help' => $field->help,
                        'show_label' => $field['show_label'],
                        'normalized_name' => $field['normalized_name'],
                        'width' => $field['width']
                    );
                    self::insertField(array_map('strval', $fieldData));
                    $blockFieldData = array(
                        'form_name' => strval($form['name']),
                        'section_name' => strval($section['name']),
                        'block_name' => strval($block['name']),
                        'field_name' => strval($field['name']),
                        'rank' => $fieldRank,
                        'versions' => $versions
                    );
                    self::addFieldToBlock($blockFieldData);
                    $fieldValidators = array(
                        'form_name' => $form['name'],
                        'field_name' => $field['name'],
                        'validators' => $field->validators
                    );
                    self::addValidatorsToField($fieldValidators);
                    $fieldRank++;
                    $insertedFields[] = implode(
                        ';',
                        array($form['name'], $section['name'], $block['name'], $field['name'])
                    );
                    
                    //delete link with massive change
                    $nameField = $field['name'];
                    if (isset(self::$fields[(string)$nameField])) {
                        $idField = self::$fields[(string)$nameField];

                        self::deleteFieldToMassiveChange($idField);
                    }
                    
                    //Add field for massive change
                    if (isset($field['massive_change']) && $field['massive_change'] == '1') {
                        
                        $iPos = strrpos($form->route, '/');
                        if ($iPos > 0) {
                            $sRoute = substr($form->route, 0, $iPos). "/mc_fields";
                        
                            if (isset(self::$fields[(string)$nameField])) {
                                $idField = self::$fields[(string)$nameField];
                                $aDatas = array(
                                    'field_id'   => $idField,
                                    'form_name'  => $form['name'],
                                    'route_name' => $sRoute
                                );

                                self::addFieldToMassiveChange($aDatas);
                            }
                        }
                    }
                }
                $insertedBlocks[] = implode(';', array($form['name'], $section['name'], $block['name']));
            }
            $insertedSections[] = implode(';', array($form['name'], $section['name']));
        }
        self::purgeFields($insertedFields);
        self::purgeBlocks($insertedBlocks);
        self::purgeSections($insertedSections);
    }
    
    /**
     * 
     * @param type $attributes
     * @return boolean
     */
    protected static function parseAttributes($attributes)
    {
        $finalAttributes = array();
        foreach ($attributes->children() as $attr) {
            
            $attrName = $attr->getName();
            if (isset($attr['name']) && $attr['name']) {
                $attrName = $attr['name'];
            }
            
            if (count($attr->children()) > 0) {
                if ($attrName == 'selectData') {
                    $values = self::parseAttributes($attr);
                    $finalAttributes[$attrName] = array();
                    foreach ($values as $key => $value) {
                        $finalAttributes[$attrName][] = array(
                            'id' => $key,
                            'text' => $value
                        );
                    }
                } else {
                    $finalAttributes[$attrName] = self::parseAttributes($attr);
                }
            } else {
                $finalAttributes[$attrName] = $attr->__toString();
                if ($finalAttributes[$attrName] == "true") {
                    $finalAttributes[$attrName] = true;
                } elseif ($finalAttributes[$attrName] == "false") {
                    $finalAttributes[$attrName] = false;
                }
            }
        }
        return $finalAttributes;
    }

    /**
     * Parse the list of versions for relation between fields and a block, if the form can use product version
     *
     * @param \SimpleXMLElement
     * @return array
     */
    protected static function parseVersions($versions)
    {
        $finalVersions = array();
        foreach ($versions->children() as $version) {
            $finalVersions[] = strval($version);
        }
        if (0 === count($finalVersions)) {
            return array('');
        }
        return $finalVersions;
    }

    /**
     * Purge fields
     *
     * @param array $insertedFields
     */
    protected static function purgeFields($insertedFields)
    {
        $db = Di::getDefault()->get('db_centreon');
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM cfg_forms_blocks_fields_relations WHERE CONCAT_WS(';', block_id, field_id) = ?");
        foreach (self::$blockFields as $key => $value) {
            if (!in_array($key, $insertedFields)) {
                $stmt->execute(array($value));
            }
        }
        $db->commit();
        $stmt = $db->prepare(
            "DELETE FROM cfg_forms_fields "
            . "WHERE NOT EXISTS "
            . "(SELECT field_id FROM cfg_forms_blocks_fields_relations r WHERE r.field_id = cfg_forms_fields.field_id)"
        );
        $stmt->execute();
    }

    /**
     * Purge blocks
     *
     * @param array $insertedBlocks
     */
    protected static function purgeBlocks($insertedBlocks)
    {
        $db = Di::getDefault()->get('db_centreon');
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM cfg_forms_blocks WHERE block_id = ?");
        foreach (self::$blocks as $key => $value) {
            if (!in_array($key, $insertedBlocks)) {
                $stmt->execute(array($value));
            }
        }
        $db->commit();
    }

    /**
     * Purge sections
     *
     * @param array $insertedSections
     */
    protected static function purgeSections($insertedSections)
    {
        $db = Di::getDefault()->get('db_centreon');
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM cfg_forms_sections WHERE section_id = ?");
        foreach (self::$sections as $key => $value) {
            if (!in_array($key, $insertedSections)) {
                $stmt->execute(array($value));
            }
        }
        $db->commit();
    }

    /**
     * Purge steps
     *
     * @param array $insertedSteps
     */
    protected static function purgeSteps($insertedSteps)
    {
        $db = Di::getDefault()->get('db_centreon');
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM cfg_forms_steps WHERE step_id = ?");
        foreach (self::$steps as $key => $value) {
            if (!in_array($key, $insertedSteps)) {
                $stmt->execute(array($value));
            }
        }
        $db->commit();
    }

    /**
     * Purge step fields
     *
     * @param array $insertedFields
     */
    protected static function purgeStepFields($insertedFields)
    {
        $db = Di::getDefault()->get('db_centreon');
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM cfg_forms_steps_fields_relations WHERE CONCAT_WS(';', step_id, field_id) = ?");
        foreach (self::$stepFields as $key => $value) {
            if (!in_array($key, $insertedFields)) {
                $stmt->execute(array($value));
            }
        }
        $db->commit();
    }
    
    /**
     * Add field to massive change
     *
     * @param array $data
     */
    protected static function addFieldToMassiveChange($data)
    {
        $massiveId = self::addMassiveChange($data);

        if (isset($data['field_id'])) {
            $db = Di::getDefault()->get('db_centreon');
            $stmt = $db->prepare(
                'INSERT INTO cfg_forms_massive_change_fields_relations (massive_change_id, field_id) '
                . 'VALUES (:massive_change_id, :field_id)'
            );
            $stmt->bindParam(':massive_change_id', $massiveId);
            $stmt->bindParam(':field_id', $data['field_id']);

            $stmt->execute();
        }
    }
    
    /**
     * Add field to massive change
     *
     * @param array $data
     */
    protected static function addMassiveChange($data)
    {
        $massiveId = static::getMassiveChangeId($data);
        
        if (empty($massiveId)) {
            $db = Di::getDefault()->get('db_centreon');
            $stmt = $db->prepare('INSERT INTO cfg_forms_massive_change (name, route) VALUES (:name, :route)');
            $stmt->bindParam(':name', $data['form_name']);
            $stmt->bindParam(':route', $data['route_name']);
            $stmt->execute();
            
            $massiveId = static::getMassiveChangeId($data);
        }
        return $massiveId;
    }
    
    /**
     * 
     * @param type $data
     * @return string
     */
    public static function getMassiveChangeId($data)
    {        
        $dbconn = Di::getDefault()->get('db_centreon'); 
        $stmt = $dbconn->prepare('SELECT massive_change_id FROM cfg_forms_massive_change WHERE name = :name AND route = :route LIMIT 1');
        $stmt->bindParam(':name', $data['form_name']);
        $stmt->bindParam(':route', $data['route_name']);
            
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (isset($data[0]['massive_change_id'])) {
            $sRep = $data[0]['massive_change_id'];
        } else {
            $sRep = "";
        }
        
        return $sRep;
    }
    
    /**
     * delete field to massive change
     *
     * @param int $iIdField
     */
    protected static function deleteFieldToMassiveChange($iIdField)
    {
        if (empty($iIdField)) {
            return;
        }
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare('DELETE FROM cfg_forms_massive_change_fields_relations WHERE field_id = :field_id');
        $stmt->bindParam(':field_id', $iIdField);
        $stmt->execute();

    }
    /**
     * delete massive change
     */
    public static function deleteMassiveChange()
    {
        $db = Di::getDefault()->get('db_centreon');
        $stmt = $db->prepare('DELETE FROM cfg_forms_massive_change');
        $stmt->execute();

    }
}

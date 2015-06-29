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
namespace Centreon\Internal\Form\Validators;

use Centreon\Internal\Form\Generator\Web\Full;
use Centreon\Internal\Form\Generator\Web\Wizard;
use Centreon\Internal\Form\Generator\Api;
use Centreon\Internal\Form\Generator\Cli;
use Centreon\Internal\Form\Exception\InvalidTokenException;
use Centreon\Internal\Exception;
use Centreon\Internal\Exception\Validator\MissingParameterException;
use Centreon\Internal\Utils\String\CamelCaseTransformation;
use \Centreon\Internal\Exception\Http\BadRequestException;

/**
 * Description of Validator
 *
 * @author lionel
 */
class Validator
{
    /**
     *
     * @var type 
     */
    private $formType;
    
    /**
     *
     * @var type 
     */
    private $formGenerator;
    
    /**
     * 
     * @param type $formType
     * @param type $formInfo
     */
    public function __construct($formType, $formInfo)
    {
        $this->getFormGenerator($formType, $formInfo);
        $this->formType = $formType;
    }
    
    /**
     * 
     * @param type $formType
     * @param type $formInfo
     */
    private function getFormGenerator($formType, $formInfo = array())
    {
        switch(strtolower($formType)) {
            case 'form':
                $this->formGenerator = new Full($formInfo['route'], $formInfo['params'], $formInfo['version']);
                break;
            case 'wizard':
                $this->formGenerator = new Wizard($formInfo['route'], $formInfo['params']);
                break;
            case 'api':
                $this->formGenerator = new Api($formInfo['route'], $formInfo['params'], $formInfo['version']);
                break;
            case 'cli':
                $this->formGenerator = new Cli($formInfo['route'], $formInfo['params'], $formInfo['version']);
                break;
        }
    }
    
    /**
     * 
     * @param type $submittedDatas
     */
    public function validate($submittedDatas, $validateMandatory = true)
    {
        $validationScheme = $this->formGenerator->getValidationScheme(array_keys($submittedDatas));
        $this->validateDatas($validationScheme, $submittedDatas, $validateMandatory);
    }
    
    /**
     * 
     * @param type $validationScheme
     * @param type $submittedDatas
     */
    public function customValidate($validationScheme, $submittedDatas, $validateMandatory = true)
    {
        $this->validateDatas($validationScheme, $submittedDatas, $validateMandatory);
    }
    
    /**
     * 
     * @param type $validationScheme
     * @param type $submittedDatas
     * @throws \Exception
     */
    private function validateDatas($validationScheme, $submittedDatas, $validateMandatory = true)
    {
        $errors = array();
        if ($validateMandatory) {
            // If not all mandatory parameters are in the dataset, throw an exception
            $missingKeys = array();
            foreach ($validationScheme['mandatory'] as $mandatoryField) {
                if (!isset($submittedDatas[$mandatoryField]) || trim($submittedDatas[$mandatoryField]) == "") {
                    $missingKeys[] = $mandatoryField;
                }
            }
            if (count($missingKeys) > 0) {
                $errorMessage = _("The following mandatory parameters are missing") . " :\n    - ";
                $errorMessage .= implode("\n    - ", $missingKeys);
                throw new MissingParameterException($errorMessage);
            }
        }

        $objectParams = array();
        if (isset($submittedDatas['object'])) {
            $objectParams['object'] = $submittedDatas['object'];
        }
        if (isset($submittedDatas['object_id'])) {
            $objectParams['object_id'] = $submittedDatas['object_id'];
        }

        // Validate each field according to its validators
        foreach ($submittedDatas as $key => $value) {
            
            if (isset($validationScheme['fieldScheme'][$key])) {
                
                foreach ($validationScheme['fieldScheme'][$key] as $validatorElement) {
                    
                    // Getting Validator Class to be called
                    $call = $this->parseValidatorName($validatorElement['call']);
                    $validator = new $call($validatorElement['params']);
                    $validatorParams = array_merge($objectParams, json_decode($validatorElement['params'], true));
                    $validatorParams['extraParams'] = $submittedDatas;
                    // Launch validation
                    $result = $validator->validate($value, $validatorParams,$validatorElement['label']);

                    //If field is not mandatory and the value is empty ==> when can validate
                    if (!in_array($key, $validationScheme['mandatory'])
                            && $validatorElement['call'] == 'core.String'
                            && strpos($validatorElement['params'], 'minlength')
                            && empty($value)
                            ) {
                            $result['success'] = true;
                    }
                    
                    if ($result['success'] === false) {
                        $errors[] = $result['error'];
                    }
                    
                }
            }
        }
        // If we got error, we throw Exception
        if (count($errors) > 0) {
            $this->raiseValidationException($errors);
        }
    }

    /**
     * 
     * @param type $validatorName
     * @return type
     */
    protected function parseValidatorName($validatorName)
    {
        $call = "";
        $parsedValidator = explode('.', $validatorName);

        if ($parsedValidator[0] === 'core') {
            $call .= '\\Centreon\\Internal\\Form\\Validators\\';
        } else {
            $call .= '\\' . CamelCaseTransformation::customToCamelCase($parsedValidator[0], '-')
                . '\\Forms\\Validators\\';
        }
        
        for ($i = 1; $i < count($parsedValidator); $i++) {
            $call .= ucfirst($parsedValidator[$i]);
        }

        return $call;
    }
    
    /**
     * 
     * @param type $errors
     * @throws \Exception
     */
    private function raiseValidationException($errors)
    {
        throw new BadRequestException('Validation error', $errors);
    }
}

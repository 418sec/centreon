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
namespace Centreon\Internal\Installer\Module;

use Centreon\Internal\Module\Informations;
use Centreon\Internal\Installer\StaticFiles;
use Centreon\Internal\Utils\CommandLine\Colorize;
use Centreon\Internal\Utils\CommandLine\InputOutput;
use Centreon\Internal\Utils\Dependency\PhpDependencies;
use Centreon\Internal\Exception\Module\MissingDependenciesException;
use Centreon\Internal\Exception\Module\DependenciesConstraintException;
use Centreon\Internal\Exception\Module\CoreModuleRemovalConstraintException;
use Centreon\Internal\Installer\Versioning;
use Centreon\Internal\Installer\Form;
use Centreon\Internal\Exception\FilesystemException;
use Centreon\Internal\Hook;
use Centreon\Models\Module;
use Centreon\Internal\Di;
use Centreon\Internal\Install\Db;
use Centreon\Internal\Database\Migrate;

/**
 * 
 */
abstract class AbstractModuleInstaller
{
    /**
     *
     * @var type 
     */
    protected $moduleSlug;
    
    /**
     *
     * @var type 
     */
    protected $moduleId;

    /**
     *
     * @var type 
     */
    protected $moduleFullName;
    
    /**
     *
     * @var type 
     */
    protected $moduleDescription;
    
    /**
     *
     * @var type 
     */
    protected $moduleInfo;
    
    /**
     *
     * @var type 
     */
    protected $moduleDirectory;
    
    /**
     *
     * @var type 
     */
    protected $launcher;
    
    /**
     *
     * @var type 
     */
    protected $versionManager;


    /**
     * 
     * @param type $moduleDirectory
     * @param type $moduleInfo
     * @param type $launcher
     */
    public function __construct($moduleDirectory, $moduleInfo, $launcher)
    {
        $this->moduleInfo = $moduleInfo;
        $this->moduleDirectory = $moduleDirectory;
        $this->launcher = $launcher;
        $this->moduleFullName = $this->moduleInfo['name'];
        $this->moduleSlug = $this->moduleInfo['shortname'];
        $this->versionManager = new Versioning($this->moduleSlug);
        $this->versionManager->setVersion($this->moduleInfo['version']);
        $this->versionManager->setModuleInfo($this->moduleInfo);
    }
    
    /**
     * Perform Install operation for module
     * 
     */
    public function install()
    {
        // Starting Message
        $message = _("Starting installation of %s module");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'info'
            )
        );
        
        // Performing pre operation check
        $this->checkOperationValidity('install');
        
        // Performing pre-install operation
        $this->preInstall();
        
        // Set TemporaryVersion
        $this->versionManager->setTemporaryVersion('install', true);
        
        // Install DB
        $this->installDb();
        
        // Install menu
        $this->installMenu();
        
        // Install Hooks
        $this->installHooks();
        
        // Install Forms
        $this->deployForms();
        
        // Deploy module Static files
        $this->deployStaticFiles();
        
        // Set Final Version
        $this->versionManager->setVersion($this->moduleInfo['version']);
        $this->versionManager->updateVersionInDb($this->moduleInfo['version'], true);
        
        // Performing custom install task
        $this->customInstall();
        
        // Performing post-install operation
        $this->postInstall();
        
        // Ending Message
        $message = _("Installation of %s module complete");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'success'
            )
        );
    }
    
    /**
     * Perform upgrade operation for module
     * 
     * @param type $verbose
     */
    public function upgrade()
    {
        // Starting Message
        $message = _("Starting upgrade of %s module");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'info'
            )
        );
        
        // Performing pre operation check
        $this->checkOperationValidity('upgrade');
        
        // Set TemporaryVersion
        $this->versionManager->setTemporaryVersion('upgrade', true);
        $this->moduleId = Informations::getModuleIdByName($this->moduleSlug);
        
        // Install DB
        $migrationManager = new Migrate($this->moduleDirectory . '/install/db/propel/');
        $migrationManager->up();
        
        // Install menu
        $this->installMenu();
        
        // Install Forms
        $this->deployForms();
        
        // Install Hooks
        //$this->installHooks();
        
        // Remove old static files and deploy new ones
        $this->removeStaticFiles();
        $this->deployStaticFiles();
        
        // Set Final Version
        $this->versionManager->setVersion($this->moduleInfo['version']);
        $this->versionManager->updateVersionInDb($this->moduleInfo['version']);
        
        // Ending Message
        $message = _("Upgrade of %s module complete");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'success'
            )
        );
    }
    
    /**
     * Perform uninstall operation for module
     * 
     * @param type $verbose
     */
    public function uninstall()
    {
        $coreModule = Informations::getCoreModuleList();
        if (in_array($this->moduleSlug, $coreModule)) {
            $exceptionMessage = _("This module is a core module and therefore can't be uninstalled");
            throw new CoreModuleRemovalConstraintException($this->colorizeMessage($exceptionMessage, 'danger'), 1103);
        }
        
        
        // Starting Message
        $message = _("Starting removal of %s module");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'info'
            )
        );
        
        // Performing pre operation check
        $this->checkOperationValidity('uninstall');
        
        // Set TemporaryVersion
        $this->versionManager->setTemporaryVersion('uninstall', true);
        $this->moduleId = Informations::getModuleIdByName($this->moduleSlug);
        
        // 
        $this->preRemove();
        $this->removeHook();
        
        // Remove old static files
        $this->removeStaticFiles();
        
        //
        $this->removeValidators();
        
        // Custom removal of the module
        $this->customRemove();
        $this->postRemove();
        
        // Ending Message
        $message = _("Removal of %s module complete");
        $this->displayOperationMessage(
            $this->colorizeMessage(
                sprintf($message, $this->moduleFullName),
                'success'
            )
        );
    }
    
    /**
     * 
     */
    abstract public function customPreInstall();
    
    /**
     * 
     */
    abstract public function customInstall();
    
    /**
     * 
     */
    abstract public function customRemove();
    
    /**
     * 
     * @throws \Exception
     */
    protected function preInstall()
    {
        $newModuleId = Module::getIdByParameter('name', $this->moduleInfo['shortname']);
        if (count($newModuleId) == 0) {
            $params = array(
                'name' => $this->moduleInfo['shortname'],
                'alias' => $this->moduleInfo['name'],
                'description' => $this->moduleInfo['description'],
                'author' => implode(", ", $this->moduleInfo['author']),
                'name' => $this->moduleInfo['shortname'],
                'version' => $this->moduleInfo['version'],
                'isactivated' => '0',
                'isinstalled' => '0',
            );
            Module::insert($params);
            $newModuleId = Module::getIdByParameter('name', $this->moduleInfo['shortname']);
            $this->moduleId = $newModuleId[0];
        } else {
            throw new \Exception("Module already installed");
        }
    }
    
    /**
     * 
     */
    protected function postInstall()
    {
        $isinstalled = 1;
        $isactivated = 1;
        
        if (isset($this->moduleInfo['isuninstallable']) && ($this->moduleInfo['isuninstallable'] === false)) {
            $isinstalled = 2;
        }
        
        if (isset($this->moduleInfo['isdisableable']) && ($this->moduleInfo['isdisableable'] === false)) {
            $isactivated = 2;
        }
        
        Module::update(
            $this->moduleId,
            array('isactivated' => $isactivated,'isinstalled' => $isinstalled)
        );
    }
    
    /**
     * 
     * Deploy module's static files
     */
    protected function deployStaticFiles()
    {
        StaticFiles::deploy($this->moduleSlug);
    }
    
    /**
     * 
     * Remove module's static files
     */
    protected function removeStaticFiles()
    {
        StaticFiles::remove($this->moduleSlug);
    }
    
    /**
     * 
     * @param type $message
     */
    protected function displayOperationMessage($message, $withEndOfLine = true)
    {
        if ($this->launcher == 'console') {
            InputOutput::display($message, $withEndOfLine);
        } elseif ($this->launcher == 'web') {
            
        }
    }
    
    /**
     * 
     * @param type $text
     * @param type $color
     * @param type $background
     * @param type $bold
     * @return type
     */
    protected function colorizeText($text, $color = 'white', $background = 'black', $bold = false)
    {
        $finalMessage = '';
        
        if ($this->launcher == 'console') {
            $finalMessage .= Colorize::colorizeText($text, $color, $background, $bold);
        } elseif ($this->launcher == 'web') {
            
        }
        
        return $finalMessage;
    }
    
    /**
     * 
     * @param type $message
     * @param type $status
     * @param type $background
     * @return type
     */
    protected function colorizeMessage($message, $status = 'success', $background = 'black')
    {
        $finalMessage = '';
        
        if ($this->launcher == 'console') {
            $finalMessage .= Colorize::colorizeMessage($message, $status, $background);
        } elseif ($this->launcher == 'web') {
            
        }
        
        return $finalMessage;
    }


    /**
     * 
     * @param type $operation
     * @throws MissingDependenciesException
     */
    protected function checkModulesDependencies()
    {
        $dependenciesSatisfied = true;
        $missingDependencies = array();
        $exceptionMessage = '';
        
        foreach ($this->moduleInfo['dependencies'] as $module) {
            if (!Informations::checkDependency($module)) {
                $dependenciesSatisfied = false;
                $missingDependencies[] = $module['name'];
            }

            if ($dependenciesSatisfied === false) {
                $exceptionMessage .= _("The following dependencies are not satisfied") . " :\n   - ";
                $exceptionMessage .= implode("\n    - ", $missingDependencies);
                throw new MissingDependenciesException($this->colorizeMessage($exceptionMessage, 'danger'), 1104);
            }
        }
    }
    
    /**
     * 
     * @throws DependenciesConstraintException
     */
    protected function checkReverseModulesDependencies()
    {
        $missingDependencies = Informations::getChildren($this->moduleSlug);
        if (count($missingDependencies) > 0) {
            $exceptionMessage = _("This module can't be uninstalled because the following modules depends on it : ") . "\n    - ";
            $exceptionMessage .= implode("\n    - ", $missingDependencies);
            throw new DependenciesConstraintException("\n" . $this->colorizeMessage($exceptionMessage, 'danger'), 1109);
        }
    }
    
    /**
     * 
     * @throws MissingDependenciesException
     */
    protected function checkSystemDependencies()
    {
        $status = PhpDependencies::checkDependencies($this->moduleInfo['php module dependencies'], false);
        if ($status['success'] === false) {
            $exceptionMessage = _("\nThe following dependencies are not satisfied") . " :\n";
            $exceptionMessage .= implode("\n    - ", $status['errors']);
            throw new MissingDependenciesException($this->colorizeMessage($exceptionMessage, 'danger'), 1004);
        }
    }
    
    /**
     * 
     * @param type $operation
     */
    protected function checkOperationValidity($operation)
    {
        $message = $this->colorizeText(_("Checking operation validity..."));
        $this->displayOperationMessage($message, false);
        
        if ($operation === 'uninstall') {
            $this->checkReverseModulesDependencies();
        } else {
            // Check modules dependencies
            $this->checkModulesDependencies();
        
            // Check system dependencies
            $this->checkSystemDependencies();
        }
        
        $message = $this->colorizeMessage(_("     Done"), 'green');
        $this->displayOperationMessage($message);
    }
    
    /**
     * 
     * @throws FilesystemException
     */
    protected function installValidators()
    {
        $validatorFile = $this->moduleDirectory . '/install/validators.json';
        if (file_exists($validatorFile)) {                       
            $message = $this->colorizeText(_("Installation of validators..."));
            $this->displayOperationMessage($message, false);
            Form::insertValidators(json_decode(file_get_contents($validatorFile), true));
            $message = $this->colorizeMessage(_("     Done"), 'green');
            $this->displayOperationMessage($message);
        }
    }
    
    /**
     * 
     */
    protected function deployForms()
    {
        try {
            $message = $this->colorizeText(_("Deployment of Forms..."));
            $this->displayOperationMessage($message, false);
                        
            $this->installValidators();
            
            $currentModuleId = Informations::getModuleIdByName($this->moduleSlug);
            $myFormFiles = glob($this->moduleDirectory. '/install/forms/*.xml');
            foreach ($myFormFiles as $formFile) {
                Form::installFromXml($this->moduleId, $formFile);
            }
            $message = $this->colorizeMessage(_("     Done"), 'green');
            $this->displayOperationMessage($message);
        } catch (FilesystemException $ex) {
            
        }
        
    }
     /**
     * 
     */
    protected function removeValidators()
    {        
        $validatorFile = $this->moduleDirectory . '/install/validators.json';
        if (file_exists($validatorFile)) {
            $message = $this->colorizeText(_("Remove validators..."));
            $this->displayOperationMessage($message, false);
            $moduleValidators = json_decode(file_get_contents($validatorFile), true);
            Form::removeValidators($moduleValidators);
            $message = $this->colorizeMessage(_("     Done"), 'green');
            $this->displayOperationMessage($message);
        }
    }
    
    /**
     * 
     */
    protected function installHooks()
    {
        $hooksFile = $this->moduleDirectory . '/install/hooks.json';
        $moduleHooksFile = $this->moduleDirectory . '/install/registeredHooks.json';
        if (file_exists($hooksFile)) {
            $hooks = json_decode(file_get_contents($hooksFile), true);
            foreach ($hooks as $hook) {
                Hook::insertHook($hook['name'], $hook['description']);
            }
        }
        
        if (file_exists($moduleHooksFile)) {
            $moduleHooks = json_decode(file_get_contents($moduleHooksFile), true);
            foreach ($moduleHooks as $moduleHook) {
                Hook::register(
                    $this->moduleId,
                    $moduleHook['name'],
                    $moduleHook['moduleHook'],
                    $moduleHook['moduleHookDescription']
                );
            }
        }
    }
    
    /**
     * 
     */
    protected function installMenu()
    {
        $filejson = $this->moduleDirectory . 'install/menu.json';
        if (file_exists($filejson)) {
            $menus = json_decode(file_get_contents($filejson), true);
            if (!is_null($menus)) {
                self::parseMenuArray($this->moduleId, $menus);
            } else {
                throw new \Exception('Error while parsing the menu JSON file of the module');
            }
        }
    }
    
    /**
     * 
     */
    protected function remove()
    {
        $this->preRemove();
        $this->removeHook();
        $this->removeDb();
        $this->postRemove();
    }
    
    /**
     * 
     */
    protected function preRemove()
    {
        if (is_null($this->moduleId)) {
            $this->moduleId = $this->moduleInfo['id'];
        }
    }
    
    /**
     * 
     */
    protected function postRemove()
    {
        Module::delete($this->moduleId);
    }
    
    /**
     * 
     */
    protected function removeHook()
    {
        $moduleHooksFile = $this->moduleDirectory . '/install/registeredHooks.json';
        if (file_exists($moduleHooksFile)) {
            $moduleHooks = json_decode(file_get_contents($moduleHooksFile), true);
            foreach ($moduleHooks as $moduleHook) {
                Hook::unregister(
                    $this->moduleId,
                    $moduleHook['name'],
                    $moduleHook['moduleHook']
                );
            }
        }
    }
    
    /**
     * @todo After seeing Propel
     */
    protected function installDb($installDefault = true)
    {
        // Initialize configuration
        $di = Di::getDefault();
        $config = $di->get('config');
        $dbName = $config->get('db_centreon', 'dbname');
        echo "Updating " . Colorize::colorizeText('centreon', 'blue', 'black', true) . " database... ";
        Db::update($dbName);
        echo Colorize::colorizeText('Done', 'green', 'black', true) . "\n";
        if ($installDefault) {
            Db::loadDefaultDatas($this->moduleDirectory . 'install/datas');
        }
    }
    
    /**
     * 
     * @param int $moduleId
     * @param array $menus
     * @param string $parent
     */
    public static function parseMenuArray($moduleId, $menus, $parent = null)
    {
        foreach ($menus as $menu) {
            if (!is_null($parent)) {
                $menu['parent'] = $parent;
            }
            $menu['module'] = $moduleId;
            Informations::setMenu($menu);
            if (isset($menu['menus']) && count($menu['menus'])) {
                self::parseMenuArray($moduleId, $menu['menus'], $menu['short_name']);
            }
        }
    }
}

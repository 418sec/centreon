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

namespace CentreonAdministration\Controllers;

use Centreon\Internal\Form;
use Centreon\Controllers\FormController;

class AclresourceController extends FormController
{
    protected $objectDisplayName = 'Acl Resource';
    public static $objectName = 'aclresource';
    protected $objectBaseUrl = '/centreon-administration/aclresource';
    protected $objectClass = '\CentreonAdministration\Models\Aclresource';
    public static $relationMap = array(
        'aclresource_usergroups' => '\CentreonAdministration\Models\Relation\Aclresource\Usergroup',
        'aclresource_environments' => '\CentreonAdministration\Models\Relation\Aclresource\Environment',
        'aclresource_domains' => '\CentreonAdministration\Models\Relation\Aclresource\Domain',
    );
    protected $datatableObject = '\CentreonAdministration\Internal\AclresourceDatatable';
    protected $repository = '\CentreonAdministration\Repository\AclresourceRepository';

    public static $isDisableable = true;
    public static $enableDisableFieldName = 'status';

    /**
     * Usergroups for a specific acl resource
     *
     * @method get
     * @route /aclresource/[i:id]/usergroup
     */
    public function usergroupForAclresourceAction()
    {
        parent::getRelations(static::$relationMap['aclresource_usergroups']);
    }

    /**
     * Environments for a specific acl resource
     *
     * @method get
     * @route /aclresource/[i:id]/environment
     */
    public function environmentForAclresourceAction()
    {
        parent::getRelations(static::$relationMap['aclresource_environments']);
    }

    /**
     * Domains for a specific acl resource
     *
     * @method get
     * @route /aclresource/[i:id]/domain
     */
    public function domainForAclresourceAction()
    {
        parent::getRelations(static::$relationMap['aclresource_domains']);
    }
}

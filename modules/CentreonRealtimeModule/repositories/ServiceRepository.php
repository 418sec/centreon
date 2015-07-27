<?php
/*
 * Copyright 2005-2014 MERETHIS
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
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

namespace CentreonRealtime\Repository;

use CentreonConfiguration\Repository\HostRepository as HostConfigurationRepository;
use CentreonConfiguration\Repository\ServiceRepository as ServiceConfigurationRepository;
use CentreonRealtime\Models\Service as ServiceRealtime;
use CentreonRealtime\Models\Acknowledgements;
use Centreon\Internal\Utils\Datetime;
use Centreon\Internal\Di;

/**
 * @author Sylvestre Ho <sho@centreon.com>
 * @package CentreonRealtime
 * @subpackage Repository
 */
class ServiceRepository extends \CentreonRealtime\Repository\Repository
{
    /**
     *
     * @var string
     */
    public static $tableName = 'rt_services';
    
    /**
     *
     * @var string
     */
    public static $objectName = 'Service';

    /**
     *
     * @var string
     */
    public static $objectId = 'service_id';

    /**
     *
     * @var string
     */
    public static $hook = 'displayServiceRtColumn';
    
    /**
     * Get service status
     *
     * @param int $host_id
     * @param int $service_id
     * @return mixed
     */
    public static function getStatus($host_id, $service_id)
    {
        // Initializing connection
        $di = Di::getDefault();
        $dbconn = $di->get('db_centreon');
        
        $stmt = $dbconn->prepare(
            'SELECT last_hard_state as state 
            FROM rt_services 
            WHERE service_id = ? 
            AND host_id = ? 
            AND enabled = 1 
            LIMIT 1'
        );
        $stmt->execute(array($service_id, $host_id));
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['state'];
        }
        return -1;
    }

    /**
     * Get service acknowledgement information
     *
     * @param int $service_id
     * @return array
     */
    public static function getAcknowledgementInfos($service_id)
    {
        $acknowledgement = array();
        $di = Di::getDefault();
        $dbconn = $di->get('db_centreon');

        $stmt = $dbconn->prepare('SELECT acknowledgement_id, entry_time, author, comment_data
            FROM rt_acknowledgements
            WHERE service_id = ?
            AND deletion_time IS NULL');

        $stmt->execute(array($service_id));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $acknowledgement = $row;
        }

        return $acknowledgement;
    }
    
    
    public static function formatDataForHeader($data)
    {
        /* Check data */
        $checkdata = array();
        $checkdata[_('id')] = $data['service_id'];
        $checkdata[_('name')] = $data['service_desc'];
        $checkdata[_('state')] = $data['state'];
      
        $checkdata[_('icon')] = "";
        if(!empty($data['icon'])){
            $checkdata[_('icon')] = $data['icon'];
        }
        
        $checkdata[_('url')] = "";
        if(!empty($data['url'])){
            $checkdata[_('url')] = $data['url'];
        }
        
        $checkdata[_('issue_duration')] = "";
        if(!empty($data['issue_duration'])){
            $checkdata[_('issue_duration')] = $data['issue_duration'];
        }
        
        $checkdata[_('states')] = "";
        if(!empty($data['states'])){
            $checkdata[_('states')] = $data['states'];
        }
        
        
        return $checkdata;
    }
    
    /**
     * Count service status for a host grouped by status id
     *
     * @param int $host_id
     * @return array
     */
    public static function countAllStatusForHost($host_id){
        
        
        $arrayStatus = array('success','warning','danger','default','info');
        $di = Di::getDefault();
        $dbconn = $di->get('db_centreon');
        
        $stmt = $dbconn->prepare('SELECT last_hard_state as state, count(service_id) as nbr
            FROM rt_services 
            WHERE rt_services.host_id = ? 
            AND rt_services.enabled = 1 
            GROUP BY rt_services.last_hard_state');
        $stmt->execute(array($host_id));
        $arrayReturn = array('success' => "0",
                            'warning' => "0",
                            'danger' => "0",
                            'default' => "0",
                            'info' => "0"
                            );
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $arrayReturn[$arrayStatus[$row['state']]] = $row['nbr'];
        }
        
        return $arrayReturn;
    }
    

    /**
     * Format small badge status
     *
     * @param int $status
     * @return string
     */
    public static function getStatusBadge($status)
    {
        switch ($status) {
            case 0:
                $status = "label-success";
                break;
            case 1:
                $status = "label-warning";
                break;
            case 2:
                $status = "label-danger";
                break;
            case 3:
                $status = "label-default";
                break;
            case 4:
                $status = "label-info";
                break;
            default:
                $status = "";
                break;
        }
        return "<span class='label $status pull-right overlay'>&nbsp;</span>";
    }
    
    /**
     * 
     * @param int $hostId
     * @param string $domain
     * @return array
     */
    public static function getServicesByDomainForHost($hostId)
    {
        static $serviceList = array();

        if (!isset($serviceList[$hostId])) {
            $serviceList[$hostId] = array();

            $db = Di::getDefault()->get('db_centreon');
            $query = "SELECT service_id, value as domain 
                FROM rt_customvariables 
                WHERE name = 'CENTREON_DOMAIN'
                AND host_id = :host";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':host', $hostId, \PDO::PARAM_INT);
            $stmt->execute();
            $servicesIdList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($servicesIdList as $service) {
                $domain = $service['domain'];
                if (!isset($serviceList[$hostId][$domain])) {
                    $serviceList[$hostId][$domain] = array();
                }
                $serviceList[$hostId][$domain][] = ServiceRealtime::get($service['service_id']);
            }
        }
        
        return $serviceList;
    }
}

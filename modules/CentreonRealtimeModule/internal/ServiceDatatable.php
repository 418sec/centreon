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

namespace CentreonRealtime\Internal;

use CentreonConfiguration\Repository\HostRepository as HostConfigurationRepository;
use CentreonConfiguration\Repository\ServiceRepository as ServiceConfigurationRepository;
use CentreonRealtime\Repository\ServiceRepository as ServiceRealtimeRepository;
use CentreonRealtime\Models\Host;
use Centreon\Internal\Utils\Datetime;
use Centreon\Internal\Datatable;
use CentreonAdministration\Repository\TagsRepository;
use Centreon\Internal\Di;
/**
 * Description of ServiceDatatable
 *
 * @author lionel
 */
class ServiceDatatable extends Datatable
{
   /*
    protected static $hook = 'displayTagList';
    protected static $hookParams = array(
        'resourceType' => 'service'
    );
*/
    protected static $objectId = 'service_id';
    protected static $objectName = 'Service';
    
    /**
     *
     * @var type 
     */
    protected static $rowIdColumn = array('id' => 'service_id', 'name' => 'description');

    /**
     *
     * @var array 
     */
    protected static $configuration = array(
        'autowidth' => false,
        'order' => array(
            array('s.state', 'desc'),
            array('s.description', 'asc')
        ),
        'searchCols' => array(),
        'stateSave' => false,
        'paging' => true,
    );
    
    protected static $dataprovider = '\Centreon\Internal\Datatable\Dataprovider\CentreonDb';
    
    /**
     *
     * @var type 
     */
    protected static $datasource = '\CentreonRealtime\Models\Service';
    
    /**
     *
     * @var array 
     */
    protected static  $aFieldNotAuthorized = array('tagname');
    
    /**
     *
     * @var array 
     */
    public static $columns = array(

        array (
            'title' => "Id",
            'name' => 'service_id',
            'data' => 'service_id',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => false,
            'className' => 'cell_center',
            'width' => '15px',
            'className' => 'cell_center'
        ),
         array (
            'title' => 'Host',
            'name' => 'host_id',
            'data' => 'host_id',
            'orderable' => true,
            'searchable' => true,
            'searchLabel' => 'host',
            'type' => 'string',
            'visible' => true,
            'source' => 'relation',
            'cast' => array(
                'type' => 'url',
                'parameters' => array(
                    'route' => '/centreon-realtime/host/[i:id]',
                    'routeParams' => array(
                        'id' => '::host_id::'
                    ),
                    'linkName' => '::name::'
                )
            )
        ),
        array (
            'title' => 'Service',
            'name' => 's.description',
            'data' => 'description',
            'orderable' => true,
            'searchable' => true,
            'searchLabel' => 'service',
            'type' => 'string',
            'visible' => true,
            'cast' => array(
                'type' => 'url',
                'parameters' => array(
                    'route' => '/centreon-realtime/service/[i:hid]/[i:sid]',
                    'routeParams' => array(
                        'hid' => '::host_id::',
                        'sid' => '::service_id::'
                    ),
                    'linkName' => '::description::'
                )
            ),
        ),
        array (
            'title' => 'Status',
            'name' => 's.state',
            'data' => 'state',
            'orderable' => true,
            'searchable' => true,
            'type' => 'integer',
            'visible' => true,
            'cast' => array(
                'type' => 'select',
                'parameters' => array(
                    '0' => '<span class="label label-success label-fade-success ">OK</span>',
                    '1' => '<span class="label label-warning">Warning</span>',
                    '2' => '<span class="label label-danger">Critical</span>',
                    '3' => '<span class="label label-default">Unknown</span>',
                    '4' => '<span class="label label-info">Pending</span>',
                )
            ),
            'searchParam' => array(
                'type' => 'select',
                'additionnalParams' => array(
                    'OK' => '0',
                    'Warning' => '1',
                    'Critical' => '2',
                    'Unknown' => '3',
                    'Pending' => '4'
                )
            ),
            'className' => 'cell_center'
        ),
        array (
            'title' => "Graph",
            'name' => 's.host_id',
            'data' => 'ico',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => true,
            "width" => '15px',
            'className' => 'cell_center'
        ),

        array (
            'title' => 'Last Check',
            'name' => 's.last_check',
            'data' => 'last_check',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => true
        ),
        array (
            'title' => 'Duration',
            'name' => 's.last_hard_state_change AS duration',
            'data' => 'duration',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => true,
            'width' => '10%',
            'className' => 'cell_center'
        ),
        array (
            'title' => 'Retry',
            'name' => 'CONCAT(s.check_attempt, " / ", s.max_check_attempts) as retry',
            'data' => 'retry',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => true,
            'width' => '25px',
            'className' => 'cell_center'
        ),
        array (
            'title' => 'Output',
            'name' => 's.output',
            'data' => 'output',
            'orderable' => true,
            'searchable' => true,
            'type' => 'string',
            'visible' => true,
        ),
        array (
            'title' => 'Perfdata',
            'name' => 's.perfdata',
            'data' => 'perfdata',
            'orderable' => false,
            'searchable' => false,
            'type' => 'string',
            'visible' => false,
        ),
        array (
            'title' => 'Tags',
            'name' => 'tagname',
            'data' => 'tagname',
            'orderable' => false,
            'searchable' => true,
            'type' => 'string',
            'visible' => false,
            'width' => '40px',
            'tablename' => 'cfg_tags'
        ),
    );

     protected static $extraParams = array(
        'addToHook' => array(
            'objectType' => 'service'
        )
    );

    //protected static $hook = 'displayTagList';
    protected static $hookParams = array(
        'resourceType' => 'service'
    );
    /**
     * 
     * @param array $params
     */
    public function __construct($params, $objectModelClass = '')
    {
        parent::__construct($params, $objectModelClass);
    }
    
    /**
     * 
     * @param array $resultSet
     * @todo fix getIconImage() (perf issue)
     */
    protected function formatDatas(&$resultSet)
    {
        $router = Di::getDefault()->get('router');
        $previousHost = '';
        HostConfigurationRepository::setObjectClass('\CentreonConfiguration\Models\Host');
        foreach ($resultSet as $key => &$myServiceSet) {
            $aTagUsed = array();
            // Set host_name
            $myHostName = Host::get($myServiceSet['host_id'], array('name'));
            $myServiceSet['name'] = $myHostName['name'];

            // @todo remove virtual hosts and virtual services
            if ($myServiceSet['name'] === '_Module_BAM') {
                unset($resultSet[$key]);
                continue;
            }
            if ($myServiceSet['name'] === $previousHost) {
                $myServiceSet['name'] = '';
            } else {
                $previousHost = $myServiceSet['name'];
                $icon = '<span class="icoListing">'.HostConfigurationRepository::getIconImage($myServiceSet['name']).'</span>';
                $myServiceSet['name'] = $icon.$myServiceSet['name'];
            }
            
            $icon = '<span class="icoListing">'.ServiceConfigurationRepository::getIconImage($myServiceSet['service_id']).'</span>';
            $myServiceSet['DT_RowData']['right_side_details'] = $router->getPathFor('/centreon-realtime/service/')
                . $myServiceSet['host_id']
                . '/'.$myServiceSet['service_id']
                . '/tooltip';
            
            
            $myServiceSet['description'] = '<span>'
                . $icon
                . ''.$myServiceSet['description'].'</span>';

            if ($myServiceSet['state'] != '0' && $myServiceSet['state'] != '4') {
                $acknowledgement = ServiceRealtimeRepository::getAcknowledgementInfos($myServiceSet['service_id']);
                if (count($acknowledgement) > 0) {
                    $myServiceSet['description'] .= ' <i class="fa fa-thumb-tack"></i>';
                }
            }

            if ($myServiceSet['perfdata'] != '') {
                $myServiceSet['ico'] = '<span data-overlay-url="/centreon-realtime/service/'
                    . $myServiceSet['host_id']
                    . '/' . $myServiceSet['service_id']
                    .     '/graph"><span class="overlay"><i class="fa fa-bar-chart-o"></i></span></span>';
            } else {
                $myServiceSet['ico'] = ''; 
            }

            $myServiceSet['duration'] = Datetime::humanReadable(
                time() - $myServiceSet['duration'],
                Datetime::PRECISION_FORMAT,
                2
            );

            $myServiceSet['last_check'] = Datetime::humanReadable(
                time() - $myServiceSet['last_check'],
                Datetime::PRECISION_FORMAT,
                2
            );
            
            /* Tags */
            $myServiceSet['tagname']  = "";
            $aTags = TagsRepository::getList('service', $myServiceSet['service_id'], 2, 0);
            foreach ($aTags as $oTags) {
                if (!in_array($oTags['id'], $aTagUsed)) {
                    $aTagUsed[] = $oTags['id'];
                    $myServiceSet['tagname'] .= TagsRepository::getTag('service', $myServiceSet['service_id'], $oTags['id'], $oTags['text'], $oTags['user_id'], $oTags['template_id']);
                }
            }
            /*
            $templates = ServiceConfigurationRepository::getListTemplates($myServiceSet['service_id'], array(), -1);
            foreach ($templates as $template) {
                $aTags = TagsRepository::getList('service', $template, 2, 0);
                foreach ($aTags as $oTags) {
                    if (!in_array($oTags['id'], $aTagUsed)) {
                        $aTagUsed[] = $oTags['id'];
                        $myServiceSet['tagname'] .= TagsRepository::getTag('service', $template, $oTags['id'], $oTags['text'], $oTags['user_id'], 1);
                    }
                }
            }
            */
            
            $myServiceSet['tagname'] .= TagsRepository::getAddTag('service', $myServiceSet['service_id']);
        }
        $resultSet = array_values($resultSet);
    }
}

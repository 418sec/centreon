<?php
/*
 * Copyright 2015 Centreon (http://www.centreon.com/)
 * 
 * Centreon is a full-fledged industry-strength solution that meets 
 * the needs in IT infrastructure and application monitoring for 
 * service performance.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0  
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * For more information : contact@centreon.com
 * 
 */

namespace Test\CentreonConfiguration\Models;

require_once CENTREON_PATH . "/tests/DbTestCase.php";

use \Test\Centreon\DbTestCase,
    \CentreonConfiguration\Models\Hosttemplate;

class HosttemplateTest extends DbTestCase
{
    protected $errMsg = 'Object not in database.';
    protected $dataPath = '/modules/CentreonConfigurationModule/tests/data/json/';

    public function testInsert()
    {
        $newHost = array(
            'host_name' => 'Test hosttemplate',
            'host_alias' => 'Test hosttemplate',
            'display_name' => 'Test hosttemplate',
            'host_comment' => 'Testing hosttemplate',
            'organization_id' => 1
        );
        Hosttemplate::insert($newHost);
        /* Assert for test insert in DB */
        $dataset = $this->createXmlDataSet(
            dirname(__DIR__) . '/data/hosttemplate.insert.xml'
        )->getTable('cfg_hosts');
        $tableResult = $this->getConnection()->createQueryTable(
            'cfg_hosts',
            'SELECT * FROM cfg_hosts'
        );
        $this->assertTablesEqual($dataset, $tableResult);

        /* Test exception unique */
        $this->setExpectedException(
            'PDOException',
            '',
            23000
        );
        Hosttemplate::insert($newHost);
    }

    public function testInsertBadCommand()
    {
        /* Test exception unique */
        $this->setExpectedException(
            'PDOException',
            '',
            23000
        );
        $newHost = array(
            'command_command_id' => 42,
            'host_name' => 'Test hosttemplate',
            'host_alias' => 'Test hosttemplate',
            'display_name' => 'Test hosttemplate',
            'host_comment' => 'Testing hosttemplate',
            'organization_id' => 1
        );
        Hosttemplate::insert($newHost);
    }

    public function testInsertBadTimeperiod()
    {
        /* Test exception unique */
        $this->setExpectedException(
            'PDOException',
            '',
            23000
        );
        $newHost = array(
            'timeperiod_tp_id' => 42,
            'host_name' => 'Test hosttemplate',
            'host_alias' => 'Test hosttemplate',
            'display_name' => 'Test hosttemplate',
            'host_comment' => 'Testing hosttemplate',
            'organization_id' => 1
        );
        Hosttemplate::insert($newHost);
    }

    public function testDelete()
    {
        Hosttemplate::delete(1);
        /* Assert for test delete in DB */
        $dataset = $this->createXmlDataSet(
            dirname(__DIR__) . '/data/hosttemplate.delete.xml'
        )->getTable('cfg_hosts');
        $tableResult = $this->getConnection()->createQueryTable(
            'cfg_hosts',
            'SELECT * FROM cfg_hosts'
        );
        $this->assertTablesEqual($dataset, $tableResult);

        /* Test exception object doesn't exist */
        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            $this->errMsg,
            0
        );
        Hosttemplate::delete(42);
    }

    public function testUpdate()
    {
        $newInformation = array(
            'host_comment' => 'Modified hosttemplate',
            'host_activate' => '0'
        );
        Hosttemplate::update(1, $newInformation);
        /* Assert for test update in DB */
        $dataset = $this->createXmlDataSet(
            dirname(__DIR__) . '/data/hosttemplate.update.xml'
        )->getTable('cfg_hosts');
        $tableResult = $this->getConnection()->createQueryTable(
            'cfg_hosts',
            'SELECT * FROM cfg_hosts'
        );
        $this->assertTablesEqual($dataset, $tableResult);
    }

    public function testUpdateNotUnique()
    {
        /* Test exception unique */
        $newInformation = array(
            'host_name' => 'Host 1'
        );
        $this->setExpectedException(
            'PDOException',
            '',
            23000
        );
        Hosttemplate::update(3, $newInformation);
    }

    public function testUpdateNotFound() {
        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            $this->errMsg,
            0
        );
        $newInformation = array(
            'host_name' => 'Host'
        );
        Hosttemplate::update(42, $newInformation);
    }

    public function testDuplicate()
    {
        Hosttemplate::duplicate(1);
        /* Assert for test duplicate 1 in DB */
        $dataset = $this->createXmlDataSet(
            dirname(__DIR__) . '/data/hosttemplate.duplicate-1.xml'
        )->getTable('cfg_hosts');
        $tableResult = $this->getConnection()->createQueryTable(
            'cfg_hosts',
            'SELECT * FROM cfg_hosts'
        );
        $this->assertTablesEqual($dataset, $tableResult);

        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            $this->errMsg,
            0
        );
        Hosttemplate::duplicate(42);
    }

    public function testGetParameters()
    {
        $testInformation = array(
            'host_id' => '1',
            'host_template_model_htm_id' => null,
            'command_command_id' => '2',
            'command_command_id_arg1' => '1!2',
            'timeperiod_tp_id' => '1',
            'timeperiod_tp_id2' => '1',
            'command_command_id2' => '3',
            'command_command_id_arg2' => '80!90',
            'host_name' => 'Template host',
            'host_alias' => 'Template host',
            'host_address' => null,
            'display_name' => 'Template host',
            'host_max_check_attempts' => '5',
            'host_check_interval' => '5',
            'host_retry_check_interval' => '5',
            'host_active_checks_enabled' => '2',
            'host_passive_checks_enabled' => '2',
            'host_checks_enabled' => '2',
            'initial_state' => 'u',
            'host_obsess_over_host' => '2',
            'host_check_freshness' => '2',
            'host_freshness_threshold' => '5',
            'host_event_handler_enabled' => '2',
            'host_low_flap_threshold' => '5',
            'host_high_flap_threshold' => '5',
            'host_flap_detection_enabled' => '2',
            'flap_detection_options' => 'f',
            'host_process_perf_data' => '2',
            'host_retain_status_information' => '2',
            'host_retain_nonstatus_information' => '2',
            'host_notification_interval' => '300',
            'host_notification_options' => '$HOSTNAME$',
            'host_notifications_enabled' => '2',
            'contact_additive_inheritance' => '1',
            'cg_additive_inheritance' => '1',
            'host_first_notification_delay' => '0',
            'host_snmp_community' => 'public',
            'host_snmp_version' => '2c',
            'host_location' => '0',
            'host_comment' => 'Host template',
            'host_register' => '0',
            'host_activate' => '1',
            'organization_id' => 1,
            'environment_id' => null,
            'poller_id' => null
        );
        $connector = Hosttemplate::getParameters(1, '*');

        $this->assertEquals($connector, $testInformation);

        $connector = Hosttemplate::getParameters(1, 'host_name');
        $this->assertEquals($connector, array('host_name' => 'Template host'));

        $connector = Hosttemplate::getParameters(1, array('host_name', 'host_register'));
        $this->assertEquals($connector, array('host_name' => 'Template host', 'host_register' => 0));
    }

    public function testGetParametersNotFound()
    {
        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            $this->errMsg,
            0
        );
        $connector = Hosttemplate::getParameters(42, '*');
    }

    public function testGetParametersBadColumns()
    {
        $this->setExpectedException(
            'PDOException',
            '',
            '42S22'
        );
        Hosttemplate::getParameters(1, 'test_error');

        Connector::getParameters(1, array('name', 'test_error'));
    }

    public function testGetList()
    {
        $testResult = array(
            array(
            	'host_id' => '1',
            	'host_template_model_htm_id' => null,
            	'command_command_id' => '2',
            	'command_command_id_arg1' => '1!2',
            	'timeperiod_tp_id' => '1',
            	'timeperiod_tp_id2' => '1',
            	'command_command_id2' => '3',
            	'command_command_id_arg2' => '80!90',
            	'host_name' => 'Template host',
            	'host_alias' => 'Template host',
            	'host_address' => null,
            	'display_name' => 'Template host',
            	'host_max_check_attempts' => '5',
            	'host_check_interval' => '5',
            	'host_retry_check_interval' => '5',
            	'host_active_checks_enabled' => '2',
            	'host_passive_checks_enabled' => '2',
            	'host_checks_enabled' => '2',
            	'initial_state' => 'u',
            	'host_obsess_over_host' => '2',
            	'host_check_freshness' => '2',
            	'host_freshness_threshold' => '5',
            	'host_event_handler_enabled' => '2',
            	'host_low_flap_threshold' => '5',
            	'host_high_flap_threshold' => '5',
            	'host_flap_detection_enabled' => '2',
            	'flap_detection_options' => 'f',
            	'host_process_perf_data' => '2',
            	'host_retain_status_information' => '2',
            	'host_retain_nonstatus_information' => '2',
            	'host_notification_interval' => '300',
            	'host_notification_options' => '$HOSTNAME$',
            	'host_notifications_enabled' => '2',
            	'contact_additive_inheritance' => '1',
            	'cg_additive_inheritance' => '1',
            	'host_first_notification_delay' => '0',
            	'host_snmp_community' => 'public',
            	'host_snmp_version' => '2c',
            	'host_location' => '0',
            	'host_comment' => 'Host template',
            	'host_register' => '0',
            	'host_activate' => '1',
                'organization_id' => 1,
                'environment_id' => null,
                'poller_id' => null
            )
        );
        $result = Hosttemplate::getList();
        $this->assertEquals($testResult, $result);   

        $testResult = array(
            array(
            	'host_id' => '1',
            	'host_template_model_htm_id' => null,
            	'command_command_id' => '2',
            	'command_command_id_arg1' => '1!2',
            	'timeperiod_tp_id' => '1',
            	'timeperiod_tp_id2' => '1',
            	'command_command_id2' => '3',
            	'command_command_id_arg2' => '80!90',
            	'host_name' => 'Template host',
            	'host_alias' => 'Template host',
            	'host_address' => null,
            	'display_name' => 'Template host',
            	'host_max_check_attempts' => '5',
            	'host_check_interval' => '5',
            	'host_retry_check_interval' => '5',
            	'host_active_checks_enabled' => '2',
            	'host_passive_checks_enabled' => '2',
            	'host_checks_enabled' => '2',
            	'initial_state' => 'u',
            	'host_obsess_over_host' => '2',
            	'host_check_freshness' => '2',
            	'host_freshness_threshold' => '5',
            	'host_event_handler_enabled' => '2',
            	'host_low_flap_threshold' => '5',
            	'host_high_flap_threshold' => '5',
            	'host_flap_detection_enabled' => '2',
            	'flap_detection_options' => 'f',
            	'host_process_perf_data' => '2',
            	'host_retain_status_information' => '2',
            	'host_retain_nonstatus_information' => '2',
            	'host_notification_interval' => '300',
            	'host_notification_options' => '$HOSTNAME$',
            	'host_notifications_enabled' => '2',
            	'contact_additive_inheritance' => '1',
            	'cg_additive_inheritance' => '1',
            	'host_first_notification_delay' => '0',
            	'host_snmp_community' => 'public',
            	'host_snmp_version' => '2c',
            	'host_location' => '0',
            	'host_comment' => 'Host template',
            	'host_register' => '0',
            	'host_activate' => '1',
                'organization_id' => 1,
                'environment_id' => null,
                'poller_id' => null
            )
        );
        $result = Hosttemplate::getList('*', 1);
        $this->assertEquals($testResult, $result);   

        $testResult = array();
        $result = Hosttemplate::getList('*', 1, 1);
        $this->assertEquals($testResult, $result);   

        $testResult = array(
            array('host_name' => 'Template host')
        );
        $result = Hosttemplate::getList('host_name');
        $this->assertEquals($testResult, $result);

        $testResult = array(
            array('host_name' => 'Template host', 'host_id' => 1),
        );
        $result = Hosttemplate::getList(array('host_name', 'host_id'));
        $this->assertEquals($testResult, $result);

        $testResult = array(
            array('host_name' => 'Template host')
        );
        $result = Hosttemplate::getList('host_name', -1, 0, 'host_name', 'DESC');
        $this->assertEquals($testResult, $result);

        $testResult = array(
            array('host_name' => 'Template host')
        );
        $result = Hosttemplate::getList('host_name', -1, 0, null, 'ASC', array('host_name' => 'Template host'));
        $this->assertEquals($testResult, $result);

        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            "Unknown filter type",
            0
        );
        Hosttemplate::getList('host_name', -1, 0, null, 'ASC', array('host_name' => array('Host 1', 'Host 2')), 'ERR');
    }

    public function testGetListBySearch()
    {
        $testResult = array(
            array('host_name' => 'Template host')
        );
        $result = Hosttemplate::getListBySearch('host_name');
        $this->assertEquals($testResult, $result);

        $testResult = array(
            array('host_name' => 'Template host')
        );
        $result = Hosttemplate::getListBySearch('host_name', -1, 0, null, 'ASC', array('host_name' => 'Template'));
        $this->assertEquals($testResult, $result);
    }

    public function testGet()
    {
        $testResult = array(
            'host_id' => '1',
            'host_template_model_htm_id' => null,
            'command_command_id' => '2',
            'command_command_id_arg1' => '1!2',
            'timeperiod_tp_id' => '1',
            'timeperiod_tp_id2' => '1',
            'command_command_id2' => '3',
            'command_command_id_arg2' => '80!90',
            'host_name' => 'Template host',
            'host_alias' => 'Template host',
            'host_address' => null,
            'display_name' => 'Template host',
            'host_max_check_attempts' => '5',
            'host_check_interval' => '5',
            'host_retry_check_interval' => '5',
            'host_active_checks_enabled' => '2',
            'host_passive_checks_enabled' => '2',
            'host_checks_enabled' => '2',
            'initial_state' => 'u',
            'host_obsess_over_host' => '2',
            'host_check_freshness' => '2',
            'host_freshness_threshold' => '5',
            'host_event_handler_enabled' => '2',
            'host_low_flap_threshold' => '5',
            'host_high_flap_threshold' => '5',
            'host_flap_detection_enabled' => '2',
            'flap_detection_options' => 'f',
            'host_process_perf_data' => '2',
            'host_retain_status_information' => '2',
            'host_retain_nonstatus_information' => '2',
            'host_notification_interval' => '300',
            'host_notification_options' => '$HOSTNAME$',
            'host_notifications_enabled' => '2',
            'contact_additive_inheritance' => '1',
            'cg_additive_inheritance' => '1',
            'host_first_notification_delay' => '0',
            'host_snmp_community' => 'public',
            'host_snmp_version' => '2c',
            'host_location' => '0',
            'host_comment' => 'Host template',
            'host_register' => '0',
            'host_activate' => '1',
            'organization_id' => '1',
            'environment_id' => null,
            'poller_id' => null
        );
        $result = Hosttemplate::get(1);
        $this->assertEquals($testResult, $result);

        $testResult = array(
            'host_name' => 'Template host'
        );
        $result = Hosttemplate::get(1, 'host_name');
        $this->assertEquals($testResult, $result);

        $testResult = array(
            'host_name' => 'Template host',
            'host_comment' => 'Host template'
        );
        $result = Hosttemplate::get(1, array('host_name', 'host_comment'));
        $this->assertEquals($testResult, $result);

        $this->setExpectedException(
            '\Centreon\Internal\Exception',
            $this->errMsg,
            0
        );
        Hosttemplate::get(42);
    }

    public function testGetIdByParameter()
    {
        $testResult = array(1);
        $result = Hosttemplate::getIdByParameter('host_name', 'Template host');
        $this->assertEquals($testResult, $result);

        $this->setExpectedException(
            'PDOException',
            '',
            '42S22'
        );
        Hosttemplate::getIdByParameter('errColumn', 'Host');
    }

    public function testGetPrimaryKey()
    {
        $this->assertEquals('host_id', Hosttemplate::getPrimaryKey());
    }

    public function testGetUniqueLabelField()
    {
        $this->assertEquals('host_name', Hosttemplate::getUniqueLabelField());
    }

    public function getRelations()
    {
        $this->assertEquals(array(), Host::getRelations());
    }

    public function testIsUnique()
    {
        $this->assertTrue(Hosttemplate::isUnique('Template host', 1));
        $this->assertFalse(Hosttemplate::isUnique('Template host', 2));
        $this->assertFalse(Hosttemplate::isUnique('Template host'));
    }

    public function testGetTableName()
    {
        $this->assertEquals('cfg_hosts', Hosttemplate::getTableName());
    }

    public function testGetColumns()
    {
        $this->assertEquals(
            array(
                'host_id',
                'host_template_model_htm_id',
                'command_command_id',
                'command_command_id_arg1',
                'timeperiod_tp_id',
                'timeperiod_tp_id2',
                'command_command_id2',
                'command_command_id_arg2',
                'host_name',
                'host_alias',
                'host_address',
                'display_name',
                'host_max_check_attempts',
                'host_check_interval',
                'host_retry_check_interval',
                'host_active_checks_enabled',
                'host_passive_checks_enabled',
                'host_checks_enabled',
                'initial_state',
                'host_obsess_over_host',
                'host_check_freshness',
                'host_freshness_threshold',
                'host_event_handler_enabled',
                'host_low_flap_threshold',
                'host_high_flap_threshold',
                'host_flap_detection_enabled',
                'flap_detection_options',
                'host_process_perf_data',
                'host_retain_status_information',
                'host_retain_nonstatus_information',
                'host_notification_interval',
                'host_notification_options',
                'host_notifications_enabled',
                'contact_additive_inheritance',
                'cg_additive_inheritance',
                'host_first_notification_delay',
                'host_snmp_community',
                'host_snmp_version',
                'host_location',
                'host_comment',
                'host_register',
                'host_activate',
                'organization_id',
                'environment_id',
                'poller_id'
            ),
            Hosttemplate::getColumns()
        );
    }
}

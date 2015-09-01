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

namespace CentreonConfiguration\Events;

/**
 * Parameters for events centreon-configuration.broker.poller.conf
 *
 * @author Maximilien Bersoult <mbersoult@centreon.com>
 * @version 3.0.0
 * @package Centreon
 * @subpackage CentreonConfiguration
 */
class BrokerPollerConf
{
    /**
     * Refers to the poller id
     * @var int
     */
    private $pollerId;

    /**
     * The list of values saved in databases
     * @var array
     */
    private $values;

    /**
     * Constructor
     *
     * @param int $pollerId The poller id
     * @param array $values The list of values
     */
    public function __construct($pollerId, &$values)
    {
        $this->pollerId = $pollerId;
        $this->values = &$values;
    }

    /**
     * Return the poller id
     *
     * @return int
     */
    public function getPollerId()
    {
        return $this->pollerId;
    }

    /**
     * Return the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Append values to configuration
     *
     * @param array $values The values to append
     */
    public function addValues($values)
    {
        foreach ($values as $key => $value) {
            $this->values[$key] = $value;
        }
    }
}

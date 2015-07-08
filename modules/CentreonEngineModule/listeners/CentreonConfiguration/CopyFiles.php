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

namespace CentreonEngine\Listeners\CentreonConfiguration;

use Centreon\Internal\Di;
use Centreon\Internal\Exception;
use CentreonEngine\Repository\ConfigGenerateMainRepository;
use CentreonConfiguration\Events\CopyFiles as CopyFilesEvent;
use CentreonEngine\Repository\EngineRepository;

class CopyFiles
{
    /**
     * Execute action 
     *
     * @param \CentreonConfiguration\Events\CopyFiles $event
     * @throws Exception
     */
    public static function execute(CopyFilesEvent $event)
    {
        $config = Di::getDefault()->get('config');
        $tmpdir = $config->get('global', 'centreon_generate_tmp_dir');

        $output = array();
        exec("rm -rf {$tmpdir}/engine/apply/{$event->getPollerId()}/* 2>&1", $output, $statusDelete);
        if ($statusDelete) {
            $event->setOutput(_('Error while deleting Engine configuration files') . "\n" . implode("\n", $output));
        }

        if (false === is_dir($tmpdir . '/engine/apply/' . $event->getPollerId())) {
            if (false === mkdir($tmpdir . '/engine/apply/' . $event->getPollerId())) {
                throw new Exception("Error while prepare copy of Engine configuration files\n");
            }
        }
        $output = array();
        exec("cp -Rpf $tmpdir/engine/generate/{$event->getPollerId()}/* {$tmpdir}/engine/apply/{$event->getPollerId()}/ 2>&1", $output, $statusCopy);
        if ($statusCopy) {
            $event->setOutput(_('Error while copying Engine configuration files') . "\n" . implode("\n", $output));
        }

        if (!$statusDelete && !$statusCopy) {
            $event->setOutput(_('Successfully copied files for Engine.'));
        }
    }
}

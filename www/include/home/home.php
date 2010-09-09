<?php
/**
 * Copyright 2005-2010 MERETHIS
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
 * SVN : $URL$
 * SVN : $Id$
 *
 */

	if (!isset($oreon)) {
		exit();
	}

	// Including files and dependences
	include_once "./include/monitoring/common-Func.php";
	include_once "./class/centreonDB.class.php";

	include_once $centreon_path . "www/include/common/common-Func.php";

	$ndo_base_prefix = getNDOPrefix();

	$pearDBndo = new CentreonDbPdo("ndo");

	$tabSatusHost = array(0 => "UP", 1 => "DOWN", 2 => "UNREACHABLE");
	$tabSatusService = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN", 4 => "PENDING");

	if (preg_match("/error/", $pearDBndo->toString(), $str) || preg_match("/failed/", $pearDBndo->toString(), $str)) {
		print "<div class='msg'>"._("Connection Error to NDO DataBase ! \n")."</div>";
	} else {

		// The user must install the ndo table with the 'centreon_acl'
		if ($err_msg = table_not_exists("centreon_acl")) {
			print "<div class='msg'>"._("Warning: ").$err_msg."</div>";
		}

		// Directory of Home pages
		$path = "./include/home/";

		// Displaying a Smarty Template
		$template = new Smarty();
		$template = initSmartyTpl($path, $template, "./");
		$template->assign("session", session_id());
		$template->assign("host_label", _("Hosts"));
		$template->assign("svc_label", _("Services"));

		/*
		 * Status informations
		 */

		// HOSTS
		$rq1 = 	" SELECT count(DISTINCT ".$ndo_base_prefix."objects.name1) cnt, ".$ndo_base_prefix."hoststatus.current_state" .
			" FROM ".$ndo_base_prefix."hoststatus, ".$ndo_base_prefix."objects " .
			" WHERE ".$ndo_base_prefix."objects.object_id = ".$ndo_base_prefix."hoststatus.host_object_id " .
			" AND ".$ndo_base_prefix."objects.is_active = 1 " .
			$oreon->user->access->queryBuilder("AND", $ndo_base_prefix."objects.name1", $oreon->user->access->getHostsString("NAME", $pearDBndo)) .
			" GROUP BY ".$ndo_base_prefix."hoststatus.current_state " .
			" ORDER by ".$ndo_base_prefix."hoststatus.current_state";
		$DBRESULT_NDO1 =& $pearDBndo->query($rq1);
		$data = array();
		$statHosts = _("Hosts");
		while ($ndo =& $DBRESULT_NDO1->fetchRow()){
			$data[] = $ndo["cnt"];
			if ($statHosts !=  _("Hosts")) {
				$statHosts .= " - ";
			}
			$statHosts .=  " " . _($tabSatusHost[$ndo["current_state"]]).": ".$ndo["cnt"];
		}
		$DBRESULT_NDO1->free();

		$template->assign("statHosts", $statHosts);

		// SERVICES
		if (!$centreon->user->admin)
			$rq2 = 	" SELECT count(nss.current_state), nss.current_state" .
					" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no, centreon_acl " .
					" WHERE no.object_id = nss.service_object_id".
					" AND no.name1 NOT LIKE '_Module_%' ".
					" AND no.name1 = centreon_acl.host_name ".
					" AND no.name2 = centreon_acl.service_description " .
					" AND centreon_acl.group_id IN (".$centreon->user->access->getAccessGroupsString().") ".
					" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";
		else
			$rq2 = 	" SELECT count(nss.current_state), nss.current_state" .
					" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no" .
					" WHERE no.object_id = nss.service_object_id".
					" AND no.name1 NOT LIKE '_Module_%' ".
					" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";
		$DBRESULT_NDO2 =& $pearDBndo->query($rq2);

		$svc_stat = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0);
		$data = array();
		$statServices = _("Services");
		while ($ndo =& $DBRESULT_NDO2->fetchRow()){
			$data[] = $ndo["count(nss.current_state)"];
			if ($statServices !=  _("Services")) {
				$statServices .= " - ";
			}
			$statServices .= " " . _($tabSatusService[$ndo["current_state"]]).": ".$ndo["count(nss.current_state)"];
		}
		$DBRESULT_NDO2->free();
		$template->assign("statServices", $statServices);

		$template->display("home.ihtml");
	}
?>
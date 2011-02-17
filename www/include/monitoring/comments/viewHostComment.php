<?php
/*
 * Copyright 2005-2011 MERETHIS
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
	if (!isset($centreon)) {
		exit();
	}

	include_once $centreon_path."www/class/centreonGMT.class.php";
	include_once "./include/common/autoNumLimit.php";

	if (isset($_POST["hostgroup"]))
	  $hostgroup = $_POST["hostgroup"];
	else if (isset($_GET["hostgroup"]))
	   $hostgroup = $_GET["hostgroup"];
	else if (isset($centreon->hostgroup) && $centreon->hostgroup)
	   $hostgroup = $centreon->hostgroup;
	else
	   $hostgroup = 0;

	if (isset($_POST["search_host"]))
		$host_name = $_POST["search_host"];
	else if (isset($_GET["search_host"]))
		$host_name = $_GET["search_host"];
	else
		$host_name = NULL;

	if (isset($_POST["search_output"]))
		$search_output = $_POST["search_output"];
	else if (isset($_GET["search_output"]))
		$search_output = $_GET["search_output"];
	else
		$search_output = NULL;

	/*
	 * Init GMT class
	 */
	$centreonGMT = new CentreonGMT($pearDB);
	$centreonGMT->getMyGMTFromSession(session_id(), $pearDB);

	/*
	 * Smarty template Init
	 */
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path, $tpl, "template/");

	$ndo_base_prefix = getNDOPrefix();
	include_once("./class/centreonDB.class.php");

	$pearDBndo = new CentreonDB("ndo");

	$form = new HTML_QuickForm('select_form', 'GET', "?p=".$p);

	$tab_comments_host = array();
	$tab_comments_svc = array();

	$en = array("0" => _("No"), "1" => _("Yes"));

	$acl_host_list = $centreon->user->access->getHostsString("NAME", $pearDBndo);

	$search_request = "";
	if (isset($host_name)) {
		$search_request = " AND obj.name1 LIKE '%$host_name%'";
	}

	$search_HG_request = "";
	if (isset($hostgroup)) {
		$search_HG_request = " AND obj.name1 LIKE '%$host_name%'";
	}

	/** *******************************************
	 * Hosts Comments
	 */
	if ($is_admin) {
		$rq2 =	"SELECT SQL_CALC_FOUND_ROWS cmt.internal_comment_id, unix_timestamp(cmt.comment_time) AS entry_time, cmt.author_name, cmt.comment_data, cmt.is_persistent, obj.name1 host_name, obj.name2 service_description " .
				"FROM ".$ndo_base_prefix."comments cmt, ".$ndo_base_prefix."objects obj " . ($hostgroup ? ", ".$ndo_base_prefix."hostgroup_members hgm " : "") .
				"WHERE obj.name1 IS NOT NULL " .
				"AND obj.name2 IS NULL " .
				(isset($search_output) && $search_output != "" ? " AND cmt.comment_data LIKE '%$search_output%'" : "") .
				($hostgroup ? " AND hgm.hostgroup_id = $hostgroup AND hgm.host_object_id = cmt.object_id " : "")  .
				"AND obj.object_id = cmt.object_id $search_request " .
				"AND cmt.expires = 0 ORDER BY cmt.comment_time DESC LIMIT ".$num * $limit.", ".$limit;
	} else {
		$rq2 =	"SELECT SQL_CALC_FOUND_ROWS cmt.internal_comment_id, unix_timestamp(cmt.comment_time) AS entry_time, cmt.author_name, cmt.comment_data, cmt.is_persistent, obj.name1 host_name, obj.name2 service_description " .
				"FROM ".$ndo_base_prefix."comments cmt, ".$ndo_base_prefix."objects obj " . ($hostgroup ? ", ".$ndo_base_prefix."hostgroup_members hgm " : "") .
				"WHERE obj.name1 IS NOT NULL " .
				"AND obj.name2 IS NULL " .
				(isset($search_output) && $search_output != "" ? " AND cmt.comment_data LIKE '%$search_output%'" : "") .
				($hostgroup ? " AND hgm.hostgroup_id = $hostgroup AND hgm.host_object_id = cmt.object_id " : "")  .
				"AND obj.object_id = cmt.object_id $search_request " .
				$centreon->user->access->queryBuilder("AND", "obj.name1", $acl_host_list) .
				" AND cmt.expires = 0 ORDER BY cmt.comment_time DESC LIMIT ".$num * $limit.", ".$limit;
	}
	$DBRESULT_NDO = $pearDBndo->query($rq2);

	for ($i = 0; $data = $DBRESULT_NDO->fetchRow(); $i++){
		$tab_comments_host[$i] = $data;
		$tab_comments_host[$i]["is_persistent"] = $en[$tab_comments_host[$i]["is_persistent"]];
		$tab_comments_host[$i]["entry_time"] = $centreonGMT->getDate("m/d/Y H:i" , $tab_comments_host[$i]["entry_time"]);
	}
	unset($data);

	$rows = $pearDBndo->numberRows();
	include("./include/common/checkPagination.php");

	/*
	 * Element we need when we reload the page
	 */
	$form->addElement('hidden', 'p');
	$tab = array ("p" => $p);
	$form->setDefaults($tab);


	if ($centreon->user->access->checkAction("host_comment")) {
		$tpl->assign('msgh', array ("addL"=>"?p=".$p."&o=ah", "addT"=>_("Add a comment"), "delConfirm"=>_("Do you confirm the deletion ?")));
	}

	$tpl->assign("p", $p);
	$tpl->assign("tab_comments_host", $tab_comments_host);
	$tpl->assign("nb_comments_host", count($tab_comments_host));
	$tpl->assign("no_host_comments", _("No Comment for hosts."));
	$tpl->assign("cmt_host_name", _("Host Name"));
	$tpl->assign("cmt_entry_time", _("Entry Time"));
	$tpl->assign("cmt_author", _("Author"));
	$tpl->assign("cmt_comment", _("Comments"));
	$tpl->assign("cmt_persistent", _("Persistent"));
	$tpl->assign("cmt_host_comment", _("Hosts Comments"));
	$tpl->assign("svc_comment_link", "./main.php?p=".$p."&o=vs");
	$tpl->assign("view_svc_comments", _("View comments of services"));
	$tpl->assign("delete", _("Delete"));


	$tpl->assign("Host", _("Host Name"));
	$tpl->assign("Output", _("Output"));
	$tpl->assign("user", _("Useres"));
	$tpl->assign('Hostgroup', _("Hostgroup"));
	$tpl->assign('Search', _("Search"));
	$tpl->assign("search_output", $search_output);
	$tpl->assign('search_host', $host_name);

	$DBRESULT = $pearDBndo->query("SELECT hostgroup_id, alias FROM ".$ndo_base_prefix."hostgroups ORDER BY alias");
	$options = "<option value='0'></options>";
	while ($data = $DBRESULT->fetchRow()) {
        $options .= "<option value='".$data["hostgroup_id"]."' ".(($hostgroup == $data["hostgroup_id"]) ? 'selected' : "").">".$data["alias"]."</option>";
    }
    $DBRESULT->free();

	$tpl->assign('hostgroup', $options);
	unset($options);

	$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);

	$form->accept($renderer);
	$tpl->assign('limit', $limit);
	$tpl->assign('form', $renderer->toArray());
	$tpl->display("hostComments.ihtml");
?>
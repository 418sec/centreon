<?php
/*
 * Copyright 2005-2009 MERETHIS
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
 * SVN : $URL
 * SVN : $Id$
 * 
 */
 
	/* 
	 * Define Local Functions
	 */

	function getParameters($str){
		$var = NULL;
		if (isset($_GET[$str]))
			$var = $_GET[$str];
		if (isset($_POST[$str]))
			$var = $_POST[$str];
		if ($var == "")
			$var = NULL;
		return $var;
	}

	
 	/*
 	 * Purge Values 
 	 */
 	 
	if (function_exists('filter_var')){	
		foreach ($_GET as $key => $value){
			if (!is_array($value)){
				$value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
				$_GET[$key] = $value;
			}
		}
	}
	
	$p = getParameters("p");
	$o = getParameters("o");
	$min = getParameters("min");
	
	/*
	 * Include all func
	 */
	
	include_once ("./basic-functions.php");
	include_once ("./include/common/common-Func.php");
	include_once ("./header.php");

	/*
	 * LCA Init Common Var
	 */
	  
	global $is_admin;
	$is_admin = isUserAdmin(session_id());
	
	$DBRESULT =& $pearDB->query("SELECT topology_parent,topology_name,topology_id,topology_url,topology_page FROM topology WHERE topology_page = '".$p."'");
	if (PEAR::isError($DBRESULT)) 
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	$redirect =& $DBRESULT->fetchRow();

	$nb_page = NULL;
	if (!$is_admin){
		if (!count(!$oreon->user->lcaTopo) || !isset($oreon->user->lcaTopo[$p])){
			$nb_page = 0;
			include_once "./alt_error.php";
		} else
			$nb_page = 1;
	} else
		$nb_page = 1;

	/*
	 * Init URL
	 */
	 
	$url = "";
	if (!isset($_GET["doc"])){
		if ((isset($nb_page) && $nb_page) || $is_admin){
			if ($redirect["topology_page"] < 100){
				$ret = get_child($redirect["topology_page"], $oreon->user->lcaTStr);
				if (!$ret['topology_page']){
					if (file_exists($redirect["topology_url"])){
						$url = $redirect["topology_url"];
						reset_search_page($url);
					} else
						$url = "./alt_error.php";
				} else {
					$ret2 = get_child($ret['topology_page'], $oreon->user->lcaTStr);
					if ($ret2["topology_url_opt"])	{
						if (!$o) {
							$tab = split("\=", $ret2["topology_url_opt"]);
							$o = $tab[1];
						}
						$p = $ret2["topology_page"];
					}
					if (file_exists($ret2["topology_url"])){
						$url = $ret2["topology_url"];
						reset_search_page($url);
						if ($ret2["topology_url_opt"]){
							$tab = split("\=", $ret2["topology_url_opt"]);
							$o = $tab[1];
						}
					} else {
						$url = "./alt_error.php";
					}
				}
			} else if ($redirect["topology_page"] >= 100 && $redirect["topology_page"] < 1000) {
				$ret = get_child($redirect["topology_page"], $oreon->user->lcaTStr);
				if (!$ret['topology_page']){
					if (file_exists($redirect["topology_url"])){
						$url = $redirect["topology_url"];
						reset_search_page($url);
					} else
						$url = "./alt_error.php";
				} else {
					if ($ret["topology_url_opt"]){
						if (!$o) {
							$tab = split("\=", $ret["topology_url_opt"]);
							$o = $tab[1];
						}
						$p = $ret["topology_page"];
					}
					if (file_exists($ret["topology_url"])){
						$url = $ret["topology_url"];
						reset_search_page($url);
					} else
						$url = "./alt_error.php";
				}
			} else if ($redirect["topology_page"] >= 1000) {
				$ret = get_child($redirect["topology_page"], $oreon->user->lcaTStr);
				if (!$ret['topology_page']){
					if (file_exists($redirect["topology_url"])){
						$url = $redirect["topology_url"];
						reset_search_page($url);
					} else
						$url = "./alt_error.php";
				} else {
					if (file_exists($redirect["topology_url"]) && $ret['topology_page']){
						$url = $redirect["topology_url"];
						reset_search_page($url);
					} else
						$url = "./alt_error.php";
				}
			}
		}
	} else
		$url = "./include/doc/index.php";

	/*
	 *  Header HTML
	 */
	include_once "./htmlHeader.php";

	/*
	 * Display Menu
	 */
	if (!$min)
		include_once "menu/Menu.php";

	/*
	 * Display PathWay
	 */
	if ($min != 1)
		include_once "pathWay.php";

	/*
	 * Go on our page
	 */
	if (isset($url) && $url)
    	include_once $url;

	if (!isset($oreon->historyPage))
		$oreon->createHistory();
	
	/*
	 * Keep in memory all informations about pagination, keyword for search... 
	 */
	if (isset($url) && $url){
		if (isset($_GET["num"]))
			$oreon->historyPage[$url] = $_GET["num"];
		if (isset($_POST["num"]))
			$oreon->historyPage[$url] = $_POST["num"];
		if (isset($_GET["search"]))
			$oreon->historySearch[$url] = $_GET["search"];
		if (isset($_POST["search"]))
			$oreon->historySearch[$url] = $_POST["search"];
		if (isset($_GET["limit"]))
			$oreon->historyLimit[$url] = $_GET["limit"];
		if (isset($_POST["limit"]))
			$oreon->historyLimit[$url] = $_POST["limit"];
	}

	print "\t\t\t</td>\t\t</tr>\t</table>\n</div>";
	print "<!-- Footer -->";
	
	/*
	 * Display Footer
	 */
	if (!$min)
		include_once "footer.php";
?>
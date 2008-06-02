<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@centreon.com
 */
 
/*
 * SVN: $URL$
 * SVN: $Id$
 */
 
	/*
	 * Bench
	 */
	function microtime_float() 	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

	set_time_limit(60);
	$time_start = microtime_float();

	$advanced_search = 0;

	/*
	 * Define
	 */
	define('SMARTY_DIR', '../GPL_LIB/Smarty/libs/');

	/*
	 * Include
	 */
	require_once ("@CENTREON_ETC@/centreon.conf.php");
	require_once ("./DBconnect.php");
	require_once ("./DBOdsConnect.php");
	require_once ("$classdir/Session.class.php");
	require_once ("$classdir/Oreon.class.php");
	require_once (SMARTY_DIR."Smarty.class.php");

	ini_set("session.gc_maxlifetime", "31536000");

	Session::start();
	if (version_compare(phpversion(), '5.0') < 0) {
	    eval('
	    function clone($object) {
	      return $object;
	    }
	    ');
	}

	/*
	 * Delete Session Expired
	 */
	$DBRESULT =& $pearDB->query("SELECT `session_expire` FROM `general_opt` LIMIT 1");
	if (PEAR::isError($DBRESULT)) 
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	$DBRESULT->fetchInto($session_expire);
	$time_limit = time() - ($session_expire["session_expire"] * 60);

	$DBRESULT =& $pearDB->query("DELETE FROM `session` WHERE `last_reload` < '".$time_limit."'");
	if (PEAR::isError($DBRESULT)) 
		print "DB error Where deleting Sessions : ".$DBRESULT->getDebugInfo()."<br />";

	/*
	 * Get session and Check if session is not expired
	 */
	$DBRESULT =& $pearDB->query("SELECT `user_id` FROM `session` WHERE `session_id` = '".session_id()."'");
	if (PEAR::isError($DBRESULT)) 
		print "DB Error : ".$DBRESULT->getDebugInfo()."<br />";
	
	if (!$DBRESULT->numRows())
		header("Location: index.php?disconnect=2");

	if (!isset($_SESSION["oreon"]))
		header("Location: index.php?disconnect=1");

	/*
	 * Define Oreon var alias
	 */
	$oreon =& $_SESSION["oreon"];
	if (!is_object($oreon))
		exit();

	/*
	 * Init differents elements we need in a lot of pages
	 */
	unset($oreon->user->lcaTopo);
	unset($oreon->user->lcaTStr);
	$oreon->user->createLCA($pearDB);
	unset($oreon->Nagioscfg);
	$oreon->initNagiosCFG($pearDB);
	unset($oreon->optGen);
	$oreon->initOptGen($pearDB);

	if (!$p){
		$root_menu = get_my_first_allowed_root_menu($oreon->user->lcaTStr);
		if (isset($root_menu["topology_page"])) 
			$p = $root_menu["topology_page"]; 
		else 
			$p = NULL;
		if (isset($root_menu["topology_url_opt"])){
			$tab = split("\=", $root_menu["topology_url_opt"]);
			if (isset($tab[1]))
				$o = $tab[1];
		}
	}

    /*
     * Cut Page ID
     */
	$level1 = NULL;
	$level2 = NULL;
	$level3 = NULL;
	$level4 = NULL;
	switch (strlen($p))	{
		case 1 :  $level1= $p; break;
		case 3 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
		case 5 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
		case 6 :  $level1 = substr($p, 0, 2); $level2 = substr($p, 2, 2); $level3 = substr($p, 3, 2); break;
		case 7 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); $level4 = substr($p, 5, 2); break;
		default : $level1= $p; break;
	}

	/*
	 * Skin path
	 */
	$DBRESULT =& $pearDB->query("SELECT `template` FROM `general_opt` LIMIT 1");
	if (PEAR::isError($DBRESULT))
		print "DB error : ".$DBRESULT->getDebugInfo()."<br />";
	$data = $DBRESULT->fetchRow();
	$skin = "./Themes/".$data["template"]."/";

	$tab_file_css = array();
	$i = 0;
	if ($handle  = @opendir($skin."Color"))	{
		while ($file = @readdir($handle)) {
			if (is_file($skin."Color"."/$file"))
				$tab_file_css[$i++] = $file;
		}
		@closedir($handle);
	}

	$colorfile = "Color/". $tab_file_css[0];

	/*
	 * Get CSS Order and color
	 */
	$DBRESULT =& $pearDB->query("SELECT `css_name` FROM `css_color_menu` WHERE `menu_nb` = '".$level1."'");
	if (PEAR::isError($DBRESULT))
		print ($DBRESULT->getMessage());
	if ($DBRESULT->numRows() && ($elem =& $DBRESULT->fetchRow()))
		$colorfile = "Color/".$elem["css_name"];

	/*
	 * Update Session Table For last_reload and current_page row
	 */
	$DBRESULT =& $pearDB->query("UPDATE `session` SET `current_page` = '".$level1.$level2.$level3.$level4."', `last_reload` = '".time()."', `ip_address` = '".$_SERVER["REMOTE_ADDR"]."' WHERE CONVERT(`session_id` USING utf8) = '".session_id()."' AND `user_id` = '".$oreon->user->user_id."' LIMIT 1");
	if (PEAR::isError($DBRESULT))
		print "DB Error WHERE Updating Session : ".$DBRESULT->getDebugInfo()."<br />";

	$locale = $oreon->user->get_lang();
	putenv("LANG=$locale");
	setlocale(LC_ALL, $locale);
	bindtextdomain("messages", "./locale/");
	textdomain("messages");

    $mlang = $oreon->user->get_lang();
?>
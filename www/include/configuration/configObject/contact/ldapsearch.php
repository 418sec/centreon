<?php
/**
Centreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Julien Mathis - Romain Le Merlus - Christophe Coraboeuf

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/

 include("../../../../centreon.conf.php");
 include("../../../../include/common/common-Func.php");
 require_once ("../../../../$classdir/Session.class.php");
 require_once ("../../../../$classdir/Oreon.class.php");

 Session::start();

 if (!isset($_SESSION["oreon"])) {
 	// Quick dirty protection
 	header("Location: ../../../../index.php");
 	//exit();
 }else {
 	$oreon =& $_SESSION["oreon"];
 }

#
## pearDB init
#
require_once 'DB.php';
	/* Connect to oreon DB */

	$dsn = array(
		     'phptype'  => 'mysql',
		     'username' => $conf_oreon['user'],
		     'password' => $conf_oreon['password'],
		     'hostspec' => $conf_oreon['host'],
		     'database' => $conf_oreon['db'],
		     );

	$options = array(
			 'debug'       => 2,
			 'portability' => DB_PORTABILITY_ALL ^ DB_PORTABILITY_LOWERCASE,
			 );

	$pearDB =& DB::connect($dsn, $options);
	if (PEAR::isError($pearDB))
		print "DB Error : ".$pearDB->getDebugInfo()."<br>";

	$pearDB->setFetchMode(DB_FETCHMODE_ASSOC);

	$res =& $pearDB->query("SELECT ldap_host, ldap_port, ldap_base_dn, ldap_login_attrib, ldap_ssl, ldap_auth_enable, ldap_search_user, ldap_search_user_pwd, ldap_search_filter, ldap_search_timeout, ldap_search_limit FROM general_opt LIMIT 1");
	$ldap_search = array_map("myDecode", $res->fetchRow());

	$ldap_search_filter = $ldap_search['ldap_search_filter'];
	$ldap_base_dn = $ldap_search['ldap_base_dn'];
	$ldap_search_timeout = $ldap_search['ldap_search_timeout'];
	$ldap_search_limit = $ldap_search['ldap_search_limit'];

	if (isset($_GET["ldap_search_filter"]) && ($_GET["ldap_search_filter"]!= "undefined") )
		$ldap_search_filter = $_GET["ldap_search_filter"];
	else if (isset($_POST["ldap_search_filter"])  && ($_POST["ldap_search_filter"]!= "undefined"))
		$ldap_search_filter = $_POST["ldap_search_filter"];

	if (isset($_GET["ldap_base_dn"]) && ($_GET["ldap_base_dn"]!= "undefined") )
		$ldap_base_dn = $_GET["ldap_base_dn"];
	else if (isset($_POST["ldap_base_dn"])  && ($_POST["ldap_base_dn"]!= "undefined"))
		$ldap_base_dn = $_POST["ldap_base_dn"];


	if (isset($_GET["ldap_search_timeout"]) && ($_GET["ldap_search_timeout"]!= "undefined") )
		$ldap_search_timeout = $_GET["ldap_search_timeout"];
	else if (isset($_POST["ldap_search_timeout"])  && ($_POST["ldap_search_timeout"]!= "undefined"))
		$ldap_search_timeout = $_POST["ldap_search_timeout"];

	if (isset($_GET["ldap_search_limit"]) && ($_GET["ldap_search_limit"]!= "undefined") )
		$ldap_search_limit = $_GET["ldap_search_limit"];
	else if (isset($_POST["ldap_search_limit"])  && ($_POST["ldap_search_limit"]!= "undefined"))
		$ldap_search_limit = $_POST["ldap_search_limit"];

	$connect = true;

	$DBRESULT =& $pearDB->query("SELECT debug_path, debug_ldap_import FROM general_opt LIMIT 1");
	if (PEAR::isError($DBRESULT))
		die($DBRESULT->getMessage());

	$debug = $DBRESULT->fetchRow();

	$debug_ldap_import = $debug['debug_ldap_import'];
	$debug_path = $debug['debug_path'];

	if (!isset($debug_ldap_import))
		$debug_ldap_import = 0;

	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : $ldap_search_filter\n", 3, $debug_path."ldapsearch.log");

	if ($ldap_search['ldap_ssl'])
		$ldapuri = "ldaps://" ;
	else
		$ldapuri = "ldap://" ;

//	print $ldapuri . $ldap_search['ldap_host']." :: ".$ldap_search['ldap_port'] . " :: " .$ldap_search['ldap_search_user']. " :: " . $ldap_search['ldap_search_user_pwd'] . "<br />";
	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : URI : " . $ldapuri . $ldap_search['ldap_host'].":".$ldap_search['ldap_port'] ."\n", 3, $debug_path."ldapsearch.log");
 	$ds = @ldap_connect($ldapuri . $ldap_search['ldap_host'].":".$ldap_search['ldap_port']);


	@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Credentials : " . $ldap_search['ldap_search_user'] . " :: " . $ldap_search['ldap_search_user_pwd'] ."\n", 3, $debug_path."ldapsearch.log");
	if ($ldap_search['ldap_search_user'] && $ldap_search['ldap_search_user_pwd'])
		@ldap_bind($ds,$ldap_search['ldap_search_user'],$ldap_search['ldap_search_user_pwd']);
	else
		@ldap_bind($ds);

	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Bind : " . ldap_errno($ds) ."\n", 3, $debug_path."ldapsearch.log");
	/* In some case, we fallback to local Auth
    0 : Bind succesfull => Default case
    -1 : Can't contact LDAP server (php4) => Fallback
    51 : Server is busy => Fallback
    52 : Server is unavailable => Fallback
    81 : Can't contact LDAP server (php5) => Fallback
    Else : Go away !!
	*/
	if ($ds) {
		switch (ldap_errno($ds)) {
			case 0:
			   $connect = true;
			   if ($debug_ldap_import == 1)
			   	error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Bind OK\n", 3, $debug_path."ldapsearch.log");
			   break;
			case -1:
			case 51:
			case 52:
			case 81:
				$connect = false;
			   break;
			default:
			   $connect = false;
			   break;
		}
	} else {
		$connect = false;
	}

if ($connect ) {

	$attrib = array("givenname", "mail", "uid","cn","sn","samaccountname"); //
	if ($debug_ldap_import == 1) {
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Base DN : ". $ldap_base_dn ."\n", 3, $debug_path."ldapsearch.log");
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Filter : ". $ldap_search_filter . "\n", 3, $debug_path."ldapsearch.log");
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Size Limit : ". $ldap_search_limit . "\n", 3, $debug_path."ldapsearch.log");
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Timeout : ". $ldap_search_timeout . "\n", 3, $debug_path."ldapsearch.log");
	}
	$sr=@ldap_search($ds, $ldap_base_dn, $ldap_search_filter,$attrib,0,$ldap_search_limit,$ldap_search_timeout);

	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : Error : ". ldap_err2str($ds)."\n", 3, $debug_path."ldapsearch.log");

	@ldap_sort($ds, $sr, "dn");
	$number_returned = @ldap_count_entries($ds,$sr);
	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : ". (isset($number_returned) ? $number_returned : "0") . " entries found\n", 3, $debug_path."ldapsearch.log");

	$info = @ldap_get_entries($ds, $sr);
	if ($debug_ldap_import == 1)
		error_log("[" . date("d/m/Y H:s") ."] LDAP Search : ". $info["count"] . " \n", 3, $debug_path."ldapsearch.log");
	@ldap_free_result($sr);

	if ($number_returned) {
		$buffer = '<reponse>';
		$buffer .= '<entries>'.$number_returned.'</entries>' ;
		for ($i=0; $i < $number_returned; $i++) {
			$isvalid = "0";
			if ( isset($info[$i]["uid"][0]) ) {
				$isvalid = "1";
				$uid = $info[$i]["uid"][0];
			} else if (isset($info[$i]["samaccountname"][0])) {
				$isvalid = "1";
				$uid = $info[$i]["samaccountname"][0];
			} else {
				$isvalid = "0";
				$uid = '';
			}
			if ( !isset($info[$i]["mail"][0]) )
				$isvalid = "0";
	
			$info[$i]["givenname"][0] = str_replace("'", "", $info[$i]["givenname"][0]);
			$info[$i]["givenname"][0] = str_replace("\"", "", $info[$i]["givenname"][0]);
			$info[$i]["givenname"][0] = htmlentities($info[$i]["givenname"][0]);
			
			$info[$i]["cn"][0] = str_replace("'", "", $info[$i]["cn"][0]);
			$info[$i]["cn"][0] = str_replace("\"", "", $info[$i]["cn"][0]);
			$info[$i]["cn"][0] = htmlentities($info[$i]["cn"][0]);
			
			$buffer .= "<user isvalid='".$isvalid."'>";
			$buffer .= "<dn isvalid='". (isset($info[$i]["dn"]) ? "1" : "0" ) ."'><![CDATA[". (isset($info[$i]["dn"]) ? $info[$i]["dn"] : "" )."]]></dn>";
			$buffer .= "<sn isvalid='". (isset($info[$i]["sn"]) ? "1" : "0" ) ."'><![CDATA[". (isset($info[$i]["sn"][0]) ? $info[$i]["sn"][0] : "")."]]></sn>";
			$buffer .= "<givenname isvalid='". (isset($info[$i]["givenname"]) ? "1" : "0" ) ."'><![CDATA[".(isset($info[$i]["givenname"][0]) ? str_replace("\'", "\\\'", $info[$i]["givenname"][0]) : "" ). "]]></givenname>";
			$buffer .= "<mail isvalid='". (isset($info[$i]["mail"]) ? "1" : "0" ) ."'><![CDATA[".(isset($info[$i]["mail"][0]) ? $info[$i]["mail"][0] : "" )."]]></mail>";
			$buffer .= "<cn isvalid='". (isset($info[$i]["cn"]) ? "1" : "0" ) ."'><![CDATA[".(isset($info[$i]["cn"][0]) ? $info[$i]["cn"][0] : "" ). "]]></cn>";
			$buffer .= "<uid isvalid='". (empty($uid) ? "0" : "1" ) ."'><![CDATA[".$uid. "]]></uid>";
			$buffer .= "</user>";
	   	}
	   	$buffer .= "</reponse>";
	} else {
		$buffer = '<reponse>';
		$buffer .= '<entries>0</entries>' ;
		$buffer .= '<error>'.ldap_err2str($ds).'</error>' ;
		$buffer .= '</reponse>';
	}
}
@ldap_close($ds);

if(isset($error)){
	$buffer  = '<reponse>';
	$buffer .= '<error><![CDATA[' . $error . ']]></error>';
	$buffer .= '</reponse>';
}

header('Content-Type: text/xml');
$buffer =  '<?xml version="1.0"?>' . $buffer ;
print $buffer;
if ($debug_ldap_import == 1)
	error_log("[" . date("d/m/Y H:s") ."] LDAP Search : XML Output : $buffer\n", 3, $debug_path."ldapsearch.log");
?>
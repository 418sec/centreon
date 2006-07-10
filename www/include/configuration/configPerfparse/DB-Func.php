<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/gpl.txt
Developped by : Julien Mathis - Romain Le Merlus

Adapted to Pear library by Merethis company, under direction of Cedrick Facon, Romain Le Merlus, Julien Mathis

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
	function testExistence ($name = NULL)	{
		global $pearDB;
		global $form;
		$id = NULL;
		if (isset($form))
			$id = $form->getSubmitValue('perfparse_id');
		$res =& $pearDB->query("SELECT perfparse_name, perfparse_id FROM cfg_perfparse WHERE perfparse_name = '".htmlentities($name, ENT_QUOTES)."'");
		$perfparse =& $res->fetchRow();
		#Modif case
		if ($res->numRows() >= 1 && $perfparse["perfparse_id"] == $id)	
			return true;
		#Duplicate entry
		else if ($res->numRows() >= 1 && $perfparse["perfparse_id"] != $id)
			return false;
		else
			return true;
	}	
	
	function enablePerfparseInDB ($perfparse_id = null)	{
		if (!$perfparse_id) return;
		global $pearDB;
		$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '0'");
		$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '1' WHERE perfparse_id = '".$perfparse_id."'");
	}
	
	function disablePerfparseInDB ($perfparse_id = null)	{
		if (!$perfparse_id) return;
		global $pearDB;
		$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '0' WHERE perfparse_id = '".$perfparse_id."'");
		$res =& $pearDB->query("SELECT MAX(perfparse_id) FROM cfg_perfparse WHERE perfparse_id != '".$perfparse_id."'");
		$maxId =& $res->fetchRow();
		if (isset($maxId["MAX(perfparse_id)"]))
			$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '1' WHERE perfparse_id = '".$maxId["MAX(perfparse_id)"]."'");
	}
	
	function deletePerfparseInDB ($perfparse = array())	{
		global $pearDB;
		foreach($perfparse as $key=>$value)
			$pearDB->query("DELETE FROM cfg_perfparse WHERE perfparse_id = '".$key."'");
		$res =& $pearDB->query("SELECT perfparse_id FROM cfg_perfparse WHERE perfparse_activate = '1'");		  
		if (!$res->numRows())	{
			$res =& $pearDB->query("SELECT MAX(perfparse_id) FROM cfg_perfparse");
			$perfparse_id = $res->fetchRow();
			$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '1' WHERE perfparse_id = '".$perfparse_id["MAX(perfparse_id)"]."'");
		}
	}
	
	function multiplePerfparseInDB ($perfparse = array(), $nbrDup = array())	{
		foreach($perfparse as $key=>$value)	{
			global $pearDB;
			$res =& $pearDB->query("SELECT * FROM cfg_perfparse WHERE perfparse_id = '".$key."' LIMIT 1");
			$row = $res->fetchRow();
			$row["perfparse_id"] = '';
			$row["perfparse_activate"] = '0';
			for ($i = 1; $i <= $nbrDup[$key]; $i++)	{
				$val = null;
				foreach ($row as $key2=>$value2)	{
					$key2 == "perfparse_name" ? ($perfparse_name = clone($value2 = $value2."_".$i)) : null;
					$val ? $val .= ($value2!=NULL?(", '".$value2."'"):", NULL") : $val .= ($value2!=NULL?("'".$value2."'"):"NULL");
				}
				if (testExistence($perfparse_name))	{
					$val ? $rq = "INSERT INTO cfg_perfparse VALUES (".$val.")" : $rq = null;
					$pearDB->query($rq);
				}
			}
		}
	}
	
	function updatePerfparseInDB ($perfparse_id = NULL)	{
		if (!$perfparse_id) return;
		updatePerfparse($perfparse_id);
	}	
	
	function insertPerfparseInDB ()	{
		$perfparse_id = insertPerfparse();
		return ($perfparse_id);
	}
	
	function insertPerfparse()	{
		global $form;
		global $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "INSERT INTO `cfg_perfparse` ( `perfparse_id` , `perfparse_name` , `Server_Port` , `Service_Log` , " .
				"`Service_Log_Position_Mark_Path` , `Error_Log` , `Error_Log_Rotate` , `Error_Log_Keep_N_Days` , " .
				"`Drop_File` , `Drop_File_Rotate` , `Drop_File_Keep_N_Days` , `Lock_File` , `Show_Status_Bar` , " .
				"`Do_Report` , `Default_user_permissions_Policy` , `Default_user_permissions_Host_groups` , " .
				"`Default_user_permissions_summary` , `Output_Log_File` , `Output_Log_Filename` , `Output_Log_Rotate` , " .
				"`Output_Log_Keep_N_Days` , `Use_Storage_Socket_Output` , `Storage_Socket_Output_Host_Name` , " .
				"`Storage_Socket_Output_Port` , `Use_Storage_Mysql` , `No_Raw_Data` , `No_Bin_Data` , `DB_User` , " .
				"`DB_Pass` , `DB_Name` , `DB_Host` , `Dummy_Hostname` , `Storage_Modules_Load` , `perfparse_comment` , " .
				"`perfparse_activate` ) VALUES (";
		$rq .= "NULL, ";
        isset($ret["perfparse_name"]) && $ret["perfparse_name"] != NULL ? $rq .= "'".htmlentities($ret["perfparse_name"], ENT_QUOTES)."', " : $rq .= "NULL, ";
        isset($ret["server_port"]) && $ret["server_port"] != NULL ? $rq .= "'".htmlentities($ret["server_port"], ENT_QUOTES)."', " : $rq .= "NULL, ";
		isset($ret["service_log"]) && $ret["service_log"] != NULL ? $rq .= "'".htmlentities($ret["service_log"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["service_log_position_mark_path"]) && $ret["service_log_position_mark_path"] != NULL ? $rq .= "'".htmlentities($ret["service_log_position_mark_path"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
       	isset($ret["error_log"]) && $ret["error_log"] != NULL ? $rq .= "'".htmlentities($ret["error_log"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["error_log_rotate"]["error_log_rotate"]) && $ret["error_log_rotate"]["error_log_rotate"] != NULL ? $rq .= "'".$ret["error_log_rotate"]["error_log_rotate"]."',  " : $rq .= "NULL, ";
        isset($ret["error_log_keep_n_days"]) && $ret["error_log_keep_n_days"] != NULL ? $rq .= "'".htmlentities($ret["error_log_keep_n_days"], ENT_QUOTES)."',  "  : $rq .= "NULL, ";
        isset($ret["drop_file"]) && $ret["drop_file"] != NULL ? $rq .= "'".htmlentities($ret["drop_file"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["drop_file_rotate"]["drop_file_rotate"]) && $ret["drop_file_rotate"]["drop_file_rotate"] != NULL ? $rq .= "'".$ret["drop_file_rotate"]["drop_file_rotate"]."',  " : $rq .= "NULL, ";
 	    isset($ret["drop_file_keep_n_days"]) && $ret["drop_file_keep_n_days"] != NULL ? $rq .= "'".htmlentities($ret["drop_file_keep_n_days"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["lock_file"]) && $ret["lock_file"] != NULL ? $rq .= "'".htmlentities($ret["lock_file"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["show_status_bar"]["show_status_bar"]) && $ret["show_status_bar"]["show_status_bar"] != NULL ? $rq .= "'".$ret["show_status_bar"]["show_status_bar"]."',  " : $rq .= "NULL, ";
        isset($ret["do_report"]["do_report"]) && $ret["do_report"]["do_report"] != NULL ? $rq .= "'".$ret["do_report"]["do_report"]."',  " : $rq .= "NULL, ";
        isset($ret["default_user_permissions_policy"]["default_user_permissions_policy"]) && $ret["default_user_permissions_policy"]["default_user_permissions_policy"] != NULL ? $rq .= "'".$ret["default_user_permissions_policy"]["default_user_permissions_policy"]."',  " : $rq .= "NULL, ";
        isset($ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"]) && $ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"] != NULL ? $rq .= "'".$ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"]."',  " : $rq .= "NULL, ";
        isset($ret["default_user_permissions_summary"]["default_user_permissions_summary"]) && $ret["default_user_permissions_summary"]["default_user_permissions_summary"] != NULL ? $rq .= "'".$ret["default_user_permissions_summary"]["default_user_permissions_summary"]."',  " : $rq .= "NULL, ";
        isset($ret["output_log_file"]["output_log_file"]) && $ret["output_log_file"]["output_log_file"] != NULL ? $rq .= "'".$ret["output_log_file"]["output_log_file"]."',  " : $rq .= "NULL, ";
        isset($ret["output_log_filename"]) && $ret["output_log_filename"] != NULL ? $rq .= "'".htmlentities($ret["output_log_filename"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["output_log_rotate"]["output_log_rotate"]) && $ret["output_log_rotate"]["output_log_rotate"] != NULL ? $rq .= "'".$ret["output_log_rotate"]["output_log_rotate"]."',  " : $rq .= "NULL, ";
        isset($ret["output_log_keep_n_days"]) && $ret["output_log_keep_n_days"] != NULL ? $rq .= "'".htmlentities($ret["output_log_keep_n_days"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["use_storage_socket_output"]["use_storage_socket_output"]) && $ret["use_storage_socket_output"]["use_storage_socket_output"] != NULL ? $rq .= "'".$ret["use_storage_socket_output"]["use_storage_socket_output"]."',  " : $rq .= "NULL, ";
        isset($ret["storage_socket_output_host_name"]) && $ret["storage_socket_output_host_name"] != NULL ? $rq .= "'".htmlentities($ret["storage_socket_output_host_name"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["storage_socket_output_port"]) && $ret["storage_socket_output_port"] != NULL ? $rq .= "'".htmlentities($ret["storage_socket_output_port"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["use_storage_mysql"]["use_storage_mysql"]) && $ret["use_storage_mysql"]["use_storage_mysql"] != NULL ? $rq .= "'".$ret["use_storage_mysql"]["use_storage_mysql"]."',  " : $rq .= "NULL, ";
        isset($ret["no_raw_data"]["no_raw_data"]) && $ret["no_raw_data"]["no_raw_data"] != NULL ? $rq .= "'".$ret["no_raw_data"]["no_raw_data"]."',  " : $rq .= "NULL, ";
        isset($ret["no_bin_data"]["no_bin_data"]) && $ret["no_bin_data"]["no_bin_data"] != NULL ? $rq .= "'".$ret["no_bin_data"]["no_bin_data"]."',  " : $rq .= "NULL, ";
        isset($ret["db_user"]) && $ret["db_user"] != NULL ? $rq .= "'".htmlentities($ret["db_user"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["db_pass"]) && $ret["db_pass"] != NULL ? $rq .= "'".htmlentities($ret["db_pass"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["db_name"]) && $ret["db_name"] != NULL ? $rq .= "'".htmlentities($ret["db_name"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["db_host"]) && $ret["db_host"] != NULL ? $rq .= "'".htmlentities($ret["db_host"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["dummy_hostname"]) && $ret["dummy_hostname"] != NULL ? $rq .= "'".htmlentities($ret["dummy_hostname"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["storage_modules_load"]) && $ret["storage_modules_load"] != NULL ? $rq .= "'".htmlentities($ret["storage_modules_load"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
        isset($ret["perfparse_comment"]) && $ret["perfparse_comment"] != NULL ? $rq .= "'".htmlentities($ret["perfparse_comment"], ENT_QUOTES)."',  " : $rq .= "NULL, ";
		$rq .= "'".$ret["perfparse_activate"]["perfparse_activate"]."')";
		$pearDB->query($rq);
		$res =& $pearDB->query("SELECT MAX(perfparse_id) FROM cfg_perfparse");
		$perfparse_id = $res->fetchRow();
		if ($ret["perfparse_activate"]["perfparse_activate"])
			$pearDB->query("UPDATE cfg_perfparse SET perfparse_activate = '0' WHERE perfparse_id != '".$perfparse_id["MAX(perfparse_id)"]."'");
		return ($perfparse_id["MAX(perfparse_id)"]);
	}
	
	function updatePerfparse($perfparse_id = null)	{
		if (!$perfparse_id) return;
		global $form;
		global $pearDB;
		$ret = array();
		$ret = $form->getSubmitValues();
		$rq = "UPDATE cfg_perfparse SET ";
	    isset($ret["perfparse_name"]) && $ret["perfparse_name"] != NULL ? $rq .= "perfparse_name = '".htmlentities($ret["perfparse_name"], ENT_QUOTES)."', " : $rq .= "perfparse_name = NULL, ";
        isset($ret["server_port"]) && $ret["server_port"] != NULL ? $rq .= "Server_Port = '".htmlentities($ret["server_port"], ENT_QUOTES)."', " : $rq .= "Server_Port = NULL, ";
		isset($ret["service_log"]) && $ret["service_log"] != NULL ? $rq .= "Service_Log = '".htmlentities($ret["service_log"], ENT_QUOTES)."',  " : $rq .= "Service_Log = NULL, ";
        isset($ret["service_log_position_mark_path"]) && $ret["service_log_position_mark_path"] != NULL ? $rq .= "Service_Log_Position_Mark_Path = '".htmlentities($ret["service_log_position_mark_path"], ENT_QUOTES)."',  " : $rq .= "Service_Log_Position_Mark_Path = NULL, ";
       	isset($ret["error_log"]) && $ret["error_log"] != NULL ? $rq .= "Error_Log = '".htmlentities($ret["error_log"], ENT_QUOTES)."',  " : $rq .= "Error_Log = NULL, ";
        isset($ret["error_log_rotate"]["error_log_rotate"]) && $ret["error_log_rotate"]["error_log_rotate"] != NULL ? $rq .= "Error_Log_Rotate = '".$ret["error_log_rotate"]["error_log_rotate"]."',  " : $rq .= "Error_Log_Rotate = NULL, ";
        isset($ret["error_log_keep_n_days"]) && $ret["error_log_keep_n_days"] != NULL ? $rq .= "Error_Log_Keep_N_Days = '".htmlentities($ret["error_log_keep_n_days"], ENT_QUOTES)."',  "  : $rq .= "Error_Log_Keep_N_Days = NULL, ";
        isset($ret["drop_file"]) && $ret["drop_file"] != NULL ? $rq .= "Drop_File = '".htmlentities($ret["drop_file"], ENT_QUOTES)."',  " : $rq .= "Drop_File = NULL, ";
        isset($ret["drop_file_rotate"]["drop_file_rotate"]) && $ret["drop_file_rotate"]["drop_file_rotate"] != NULL ? $rq .= "Drop_File_Rotate = '".$ret["drop_file_rotate"]["drop_file_rotate"]."',  " : $rq .= "Drop_File_Rotate = NULL, ";
 	    isset($ret["drop_file_keep_n_days"]) && $ret["drop_file_keep_n_days"] != NULL ? $rq .= "Drop_File_Keep_N_Days = '".htmlentities($ret["drop_file_keep_n_days"], ENT_QUOTES)."',  " : $rq .= "Drop_File_Keep_N_Days = NULL, ";
        isset($ret["lock_file"]) && $ret["lock_file"] != NULL ? $rq .= "Lock_File = '".htmlentities($ret["lock_file"], ENT_QUOTES)."',  " : $rq .= "Lock_File = NULL, ";
        isset($ret["show_status_bar"]["show_status_bar"]) && $ret["show_status_bar"]["show_status_bar"] != NULL ? $rq .= "Show_Status_Bar = '".$ret["show_status_bar"]["show_status_bar"]."',  " : $rq .= "Show_Status_Bar = NULL, ";
        isset($ret["do_report"]["do_report"]) && $ret["do_report"]["do_report"] != NULL ? $rq .= "Do_Report = '".$ret["do_report"]["do_report"]."',  " : $rq .= "Do_Report = NULL, ";
        isset($ret["default_user_permissions_policy"]["default_user_permissions_policy"]) && $ret["default_user_permissions_policy"]["default_user_permissions_policy"] != NULL ? $rq .= "Default_user_permissions_Policy = '".$ret["default_user_permissions_policy"]["default_user_permissions_policy"]."',  " : $rq .= "Default_user_permissions_Policy = NULL, ";
        isset($ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"]) && $ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"] != NULL ? $rq .= "Default_user_permissions_Host_groups = '".$ret["default_user_permissions_host_groups"]["default_user_permissions_host_groups"]."',  " : $rq .= "Default_user_permissions_Host_groups = NULL, ";
        isset($ret["default_user_permissions_summary"]["default_user_permissions_summary"]) && $ret["default_user_permissions_summary"]["default_user_permissions_summary"] != NULL ? $rq .= "Default_user_permissions_Summary = '".$ret["default_user_permissions_summary"]["default_user_permissions_summary"]."',  " : $rq .= "Default_user_permissions_Summary = NULL, ";
        isset($ret["output_log_file"]["output_log_file"]) && $ret["output_log_file"]["output_log_file"] != NULL ? $rq .= "Output_Log_File = '".$ret["output_log_file"]["output_log_file"]."',  " : $rq .= "Output_Log_File = NULL, ";
        isset($ret["output_log_filename"]) && $ret["output_log_filename"] != NULL ? $rq .= "Output_Log_Filename = '".htmlentities($ret["output_log_filename"], ENT_QUOTES)."',  " : $rq .= "Output_Log_Filename = NULL, ";
        isset($ret["output_log_rotate"]["output_log_rotate"]) && $ret["output_log_rotate"]["output_log_rotate"] != NULL ? $rq .= "Output_Log_Rotate = '".$ret["output_log_rotate"]["output_log_rotate"]."',  " : $rq .= "Output_Log_Rotate = NULL, ";
        isset($ret["output_log_keep_n_days"]) && $ret["output_log_keep_n_days"] != NULL ? $rq .= "Output_Log_Keep_N_Days = '".htmlentities($ret["output_log_keep_n_days"], ENT_QUOTES)."',  " : $rq .= "Output_Log_Keep_N_Days = NULL, ";
        isset($ret["use_storage_socket_output"]["use_storage_socket_output"]) && $ret["use_storage_socket_output"]["use_storage_socket_output"] != NULL ? $rq .= "Use_Storage_Socket_Output = '".$ret["use_storage_socket_output"]["use_storage_socket_output"]."',  " : $rq .= "Use_Storage_Socket_Output = NULL, ";
        isset($ret["storage_socket_output_host_name"]) && $ret["storage_socket_output_host_name"] != NULL ? $rq .= "Storage_Socket_Output_Host_Name = '".htmlentities($ret["storage_socket_output_host_name"], ENT_QUOTES)."',  " : $rq .= "Storage_Socket_Output_Host_Name = NULL, ";
        isset($ret["storage_socket_output_port"]) && $ret["storage_socket_output_port"] != NULL ? $rq .= "Storage_Socket_Output_Port = '".htmlentities($ret["storage_socket_output_port"], ENT_QUOTES)."',  " : $rq .= "Storage_Socket_Output_Port = NULL, ";
        isset($ret["use_storage_mysql"]["use_storage_mysql"]) && $ret["use_storage_mysql"]["use_storage_mysql"] != NULL ? $rq .= "Use_Storage_Mysql = '".$ret["use_storage_mysql"]["use_storage_mysql"]."',  " : $rq .= "Use_Storage_Mysql = NULL, ";
        isset($ret["no_raw_data"]["no_raw_data"]) && $ret["no_raw_data"]["no_raw_data"] != NULL ? $rq .= "No_Raw_Data = '".$ret["no_raw_data"]["no_raw_data"]."',  " : $rq .= "No_Raw_Data = NULL, ";
        isset($ret["no_bin_data"]["no_bin_data"]) && $ret["no_bin_data"]["no_bin_data"] != NULL ? $rq .= "No_Bin_Data = '".$ret["no_bin_data"]["no_bin_data"]."',  " : $rq .= "No_Bin_Data = NULL, ";
        isset($ret["db_user"]) && $ret["db_user"] != NULL ? $rq .= "DB_User = '".htmlentities($ret["db_user"], ENT_QUOTES)."',  " : $rq .= "DB_user = NULL, ";
        isset($ret["db_pass"]) && $ret["db_pass"] != NULL ? $rq .= "DB_Pass = '".htmlentities($ret["db_pass"], ENT_QUOTES)."',  " : $rq .= "DB_Pass = NULL, ";
        isset($ret["db_name"]) && $ret["db_name"] != NULL ? $rq .= "DB_Name = '".htmlentities($ret["db_name"], ENT_QUOTES)."',  " : $rq .= "DB_Name = NULL, ";
        isset($ret["db_host"]) && $ret["db_host"] != NULL ? $rq .= "DB_Host = '".htmlentities($ret["db_host"], ENT_QUOTES)."',  " : $rq .= "DB_Host = NULL, ";
        isset($ret["dummy_hostname"]) && $ret["dummy_hostname"] != NULL ? $rq .= "Dummy_Hostname = '".htmlentities($ret["dummy_hostname"], ENT_QUOTES)."',  " : $rq .= "Dummy_Hostname = NULL, ";
        isset($ret["storage_modules_load"]) && $ret["storage_modules_load"] != NULL ? $rq .= "Storage_Modules_Load = '".htmlentities($ret["storage_modules_load"], ENT_QUOTES)."',  " : $rq .= "Storage_Modules_Load = NULL, ";
        isset($ret["perfparse_comment"]) && $ret["perfparse_comment"] != NULL ? $rq .= "perfparse_comment = '".htmlentities($ret["perfparse_comment"], ENT_QUOTES)."',  " : $rq .= "perfparse_comment = NULL, ";
		$rq .= "perfparse_activate = '".$ret["perfparse_activate"]["perfparse_activate"]."' ";
		$rq .= "WHERE perfparse_id = '".$perfparse_id."'";
		$pearDB->query($rq);
		if ($ret["perfparse_activate"]["perfparse_activate"])
			enablePerfparseInDB($perfparse_id);
	}
?>
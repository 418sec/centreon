<?
/** 
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
Developped by : Julien Mathis - Romain Le Merlus

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
	
	if (!isset($oreon))
		exit();
	
	unlink($XMLConfigPath."osm_list.xml");
	$handle = create_file($XMLConfigPath."osm_list.xml", $oreon->user->get_name(), false);
	$str = NULL;
	$str = "<osm_list>\n";
	$str .= "<elements>\n";
	
	#
	##	Listing
	#
	
	# Host List
	foreach($gbArr[2] as $key => $value)	{
		$DBRESULT =& $pearDB->query("SELECT host_name, host_template_model_htm_id, host_address, host_register, ehi.city_id FROM host, extended_host_information ehi WHERE host_id = '".$key."' AND ehi.host_host_id = host_id LIMIT 1");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$host = $DBRESULT->fetchRow();
		if ($host["host_register"])	{
			if (!$host["host_name"])
				$host["host_name"] = getMyHostName($host['host_template_model_htm_id']);
			if (!$host["host_address"])
				$host["host_address"] = getMyHostAddress($host['host_template_model_htm_id']);
			$str .= "<h id='".$key."' name='".html_entity_decode($host["host_name"], ENT_QUOTES)."' address='".$host["host_address"]."'";
			if ($host["city_id"])	{
				$DBRESULT2 =& $pearDB->query("SELECT city_lat, city_long FROM view_city WHERE city_id = '".$host["city_id"]."' LIMIT 1");
				if (PEAR::isError($DBRESULT2))
					print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
				$gps =& $DBRESULT2->fetchRow();
				if ($gps["city_lat"] && $gps["city_long"])
					$str .= " gps='true' lat='".$gps["city_lat"]."' long='".$gps["city_long"]."'";
				else
					$str .= " gps='false'";
				$DBRESULT2->free();
			}
			else
				$str .= " gps='false'";
			$str .= "/>\n";
		}
		else
			unset($gbArr[2][$key]);
	}
	# Host Group List
	foreach($gbArr[3] as $key => $value)	{		
		$DBRESULT =& $pearDB->query("SELECT * FROM hostgroup WHERE hg_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$hostGroup = $DBRESULT->fetchRow();
		$str .= "<hg id='".$key."' name='".html_entity_decode($hostGroup["hg_name"], ENT_QUOTES)."'";
		if ($hostGroup["city_id"])	{
			$DBRESULT2 =& $pearDB->query("SELECT city_lat, city_long FROM view_city WHERE city_id = '".$hostGroup["city_id"]."' LIMIT 1");
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
			$gps =& $DBRESULT2->fetchRow();
			if ($gps["city_lat"] && $gps["city_long"])
				$str .= " gps='true' lat='".$gps["city_lat"]."' long='".$gps["city_long"]."'";
			else
				$str .= " gps='false'";
			$DBRESULT2->free();
		}
		else
			$str .= " gps='false'";		
		$str .= "/>\n";
	}
	# Services List
	foreach($gbArr[4] as $key => $value)	{		
		$DBRESULT =& $pearDB->query("SELECT DISTINCT sv.service_description, sv.service_template_model_stm_id, service_register, hsr.host_host_id, hsr.hostgroup_hg_id FROM service sv, host_service_relation hsr WHERE sv.service_id = '".$key."' AND hsr.service_service_id = sv.service_id");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while ($DBRESULT->fetchInto($sv))	{
			if ($sv["service_register"])	{
				if (!$sv["service_description"])
					$sv["service_description"] = getMyServiceName($sv['service_template_model_stm_id']);
				if ($sv["host_host_id"]){
					$sv["service_description"] = str_replace("#S#", "/", $sv["service_description"]);
					$sv["service_description"] = str_replace("#BS#", "\\", $sv["service_description"]);
					$str .= "<sv id='".$sv["host_host_id"]."_".$key."' name='".$sv["service_description"]."'/>\n";
				} else if ($sv["hostgroup_hg_id"])	{
					$DBRESULT2 =& $pearDB->query("SELECT DISTINCT host_host_id FROM hostgroup_relation WHERE hostgroup_hg_id = '".$sv["hostgroup_hg_id"]."'");
					if (PEAR::isError($DBRESULT2))
						print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
					while ($DBRESULT2->fetchInto($host))
						if (array_key_exists($host["host_host_id"], $gbArr[2])){
							$sv["service_description"] = str_replace("#S#", "/", $sv["service_description"]);
							$sv["service_description"] = str_replace("#BS#", "\\", $sv["service_description"]);
							$str .= "<sv id='".$host["host_host_id"]."_".$key."' name='".$sv["service_description"]."'/>\n";
						}
					$DBRESULT2->free();
				}
			}
			else
				unset($gbArr[4][$key]);
		}
	}
	# Service Group List
	foreach($gbArr[5] as $key => $value)	{		
		$DBRESULT =& $pearDB->query("SELECT * FROM servicegroup WHERE sg_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$serviceGroup = $DBRESULT->fetchRow();
		$str .= "<sg id='".$key."' name='".html_entity_decode($serviceGroup["sg_name"], ENT_QUOTES)."'";
		if ($serviceGroup["city_id"])	{
			$DBRESULT2 =& $pearDB->query("SELECT city_lat, city_long FROM view_city WHERE city_id = '".$serviceGroup["city_id"]."' LIMIT 1");
			if (PEAR::isError($DBRESULT2))
				print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
			$gps =& $DBRESULT2->fetchRow();
			if ($gps["city_lat"] && $gps["city_long"])
				$str .= " gps='true' lat='".$gps["city_lat"]."' long='".$gps["city_long"]."'";
			else
				$str .= " gps='false'";
			$DBRESULT2->free();
		}
		else
			$str .= " gps='false'";	
		$str .= "/>\n";
	}
	# OSL
	foreach($gbArr[6] as $key => $value)	{		
		$DBRESULT =& $pearDB->query("SELECT name FROM osl WHERE osl_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$osl = $DBRESULT->fetchRow();
		$str .= "<osl id='".$key."' name='".html_entity_decode($osl["name"], ENT_QUOTES)."'/>\n";
		$DBRESULT->free();
	}
	
	# Meta Service
	foreach($gbArr[7] as $key => $value)	{		
		$DBRESULT =& $pearDB->query("SELECT meta_name FROM meta_service WHERE meta_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$osm = $DBRESULT->fetchRow();
		$str .= "<ms id='".$key."' name='".html_entity_decode($osm["meta_name"], ENT_QUOTES)."'/>\n";
		$DBRESULT->free();
	}
	$str .= "</elements>\n";
	
	#
	##	Dependencies
	#
	$str .= "<dependencies>\n";
	
	#	Host
	foreach($gbArr[2] as $key => $value)	{
		$DBRESULT =& $pearDB->query("SELECT host_template_model_htm_id AS tpl, host_register FROM host WHERE host_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$host = $DBRESULT->fetchRow();
		$str .= "<h id='".$key."'>\n";
		## Parents
		$str .= "<prts>\n";
		# Host Groups
		$DBRESULT =& $pearDB->query("SELECT hgr.hostgroup_hg_id FROM hostgroup_relation hgr WHERE hgr.host_host_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($hostGroup))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($hostGroup["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($hostGroup["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)
				$str .="<hg id='".$hostGroup["hostgroup_hg_id"]."'/>\n";
		}
		$DBRESULT->free();
		# Hosts
		$DBRESULT =& $pearDB->query("SELECT hpr.host_parent_hp_id FROM host_hostparent_relation hpr WHERE hpr.host_host_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		//if (!$DBRESULT->numRows() && $host["tpl"])
		//	$DBRESULT =& getMyHostParents($host["tpl"]);
		while($DBRESULT->fetchInto($host))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($host["host_parent_hp_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($host["host_parent_hp_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)
				$str .= "<h id='".$host["host_parent_hp_id"]."'/>\n";
		}
		$str .= "</prts>\n";
		$DBRESULT->free();
		## Childs
		$str .= "<chds>\n";
		# Hosts
		$DBRESULT =& $pearDB->query("SELECT host_host_id FROM host_hostparent_relation WHERE host_parent_hp_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($host))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($host["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($host["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)
				$str .= "<h id='".$host["host_host_id"]."'/>\n";
		}
		$DBRESULT->free();
		# Services from Host
		$DBRESULT =& $pearDB->query("SELECT hsr.service_service_id FROM host_service_relation hsr WHERE hsr.host_host_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($service))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)
				$str .= "<sv id='".$key."_".$service["service_service_id"]."'/>\n";
		}
		$DBRESULT->free();
		# Services from Host Group
		$DBRESULT =& $pearDB->query("SELECT hgr.hostgroup_hg_id FROM hostgroup_relation hgr WHERE hgr.host_host_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($hostGroup))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($hostGroup["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($hostGroup["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)	{
				$DBRESULT2 =& $pearDB->query("SELECT hsr.service_service_id FROM host_service_relation hsr WHERE hsr.hostgroup_hg_id = '".$hostGroup["hostgroup_hg_id"]."'");
				if (PEAR::isError($DBRESULT2))
					print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
				while($DBRESULT2->fetchInto($service))	{
					$BP = false;
					if ($ret["level"]["level"] == 1)
						array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
					else if ($ret["level"]["level"] == 2)
						array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
					else if ($ret["level"]["level"] == 3)
						$BP = true;
					if ($BP)
						$str .= "<sv id='".$key."_".$service["service_service_id"]."'/>\n";
				}	
				$DBRESULT2->free();
			}
		}		
		$str .= "</chds>\n";
		$str .= "</h>\n";
	}
	# HostGroup
	foreach($gbArr[3] as $key => $value)	{
		$str .= "<hg id='".$key."'>\n";
		## Parents
		$str .= "<prts>\n";
		$str .= "</prts>\n";
		
		## Childs
		$str .= "<chds>\n";		
		$DBRESULT =& $pearDB->query("SELECT hgr.host_host_id FROM hostgroup_relation hgr WHERE hgr.hostgroup_hg_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($host))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($host["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($host["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)
				$str .= "<h id='".$host["host_host_id"]."'/>\n";
		}
		$DBRESULT->free();
		$str .= "</chds>\n";
		$str .= "</hg>\n";
	}
	# Service
	foreach($gbArr[4] as $key => $value)	{
		$DBRESULT =& $pearDB->query("SELECT hsr.host_host_id, hsr.hostgroup_hg_id FROM host_service_relation hsr WHERE hsr.service_service_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while ($DBRESULT->fetchInto($sv))	{
			if ($sv["host_host_id"])	{
				$str .= "<sv id='".$sv["host_host_id"]."_".$key."'>\n";								
				## Parents
				$str .= "<prts>\n";
				$str .= "<h id='".$sv["host_host_id"]."'/>\n";
				$str .= "</prts>\n";						
				## Childs
				$str .= "<chds>\n";
				$str .= "</chds>\n";
				$str .= "</sv>\n";
			}
			else if ($sv["hostgroup_hg_id"])	{
				$DBRESULT2 =& $pearDB->query("SELECT DISTINCT host_host_id FROM hostgroup_relation WHERE hostgroup_hg_id = '".$sv["hostgroup_hg_id"]."'");
				if (PEAR::isError($DBRESULT2))
					print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
				while ($DBRESULT2->fetchInto($host))
					if (array_key_exists($host["host_host_id"], $gbArr[2]))	{
						$str .= "<sv id='".$host["host_host_id"]."_".$key."'>\n";				
						## Parents
						$str .= "<prts>\n";
						$str .= "<h id='".$host["host_host_id"]."'/>\n";
						$str .= "</prts>\n";						
						## Childs
						$str .= "<chds>\n";
						$str .= "</chds>\n";
						$str .= "</sv>\n";
					}
				$DBRESULT2->free();
			}			
		}
		$DBRESULT->free();
	}
	# ServiceGroup
	foreach($gbArr[5] as $key => $value)	{
		$str .= "<sg id='".$key."'>\n";
		## Parents
		$str .= "<prts>\n";
		$str .= "</prts>\n";
		
		## Childs
		$str .= "<chds>\n";
		$DBRESULT =& $pearDB->query("SELECT sgr.service_service_id FROM servicegroup_relation sgr WHERE sgr.servicegroup_sg_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($service))	{
			$BP = false;
			if ($ret["level"]["level"] == 1)
				array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 2)
				array_key_exists($service["service_service_id"], $gbArr[4]) ? $BP = true : NULL;
			else if ($ret["level"]["level"] == 3)
				$BP = true;
			if ($BP)	{
				$DBRESULT2 =& $pearDB->query("SELECT hsr.host_host_id, hsr.hostgroup_hg_id FROM host_service_relation hsr WHERE hsr.service_service_id = '".$service["service_service_id"]."'");
				if (PEAR::isError($DBRESULT2))
					print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
				while($DBRESULT2->fetchInto($service2))	{
					$BP = false;
					if ($ret["level"]["level"] == 1)	{
						array_key_exists($service2["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
						array_key_exists($service2["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
					}
					else if ($ret["level"]["level"] == 2)	{
						array_key_exists($service2["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
						array_key_exists($service2["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
					}
					else if ($ret["level"]["level"] == 3)
						$BP = true;
					if ($BP)	{
						if ($service2["hostgroup_hg_id"])	{
							$DBRESULT3 =& $pearDB->query("SELECT hgr.host_host_id FROM hostgroup_relation hgr WHERE hgr.hostgroup_hg_id = '".$service2["hostgroup_hg_id"]."'");
							if (PEAR::isError($DBRESULT3))
								print "DB Error : ".$DBRESULT3->getDebugInfo()."<br>";
							while($DBRESULT3->fetchInto($service3))	{
								$BP = false;
								if ($ret["level"]["level"] == 1)
									array_key_exists($service3["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
								else if ($ret["level"]["level"] == 2)
									array_key_exists($service3["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
								else if ($ret["level"]["level"] == 3)
									$BP = true;
								if ($BP)
									$str .= "<sv id='".$service3["host_host_id"]."_".$service["service_service_id"]."'/>\n";
							}	
							unset($service3);
							$DBRESULT3->free();						
						}
						else
							$str .= "<sv id='".$service2["host_host_id"]."_".$service["service_service_id"]."'/>\n";
					}
				}
				$DBRESULT2->free();
			}
		}
		$DBRESULT->free();
		$str .= "</chds>\n";
		$str .= "</sg>\n";
	}
	# OSL
	foreach($gbArr[6] as $key => $value)	{
		$str .= "<osl id='".$key."'>\n";
		## Parents
		$str .= "<prts>\n";
		$DBRESULT =& $pearDB->query("SELECT id_osl FROM osl_indicator WHERE id_indicator_osl = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($osl))
			$str .= "<osl id='".$osl["id_osl"]."'/>";
		$DBRESULT->free();
		$str .= "</prts>\n";
		
		## Childs
		$str .= "<chds>\n";
		$DBRESULT =& $pearDB->query("SELECT host_id, service_id, id_indicator_osl, meta_id FROM osl_indicator WHERE id_osl = '".$key."' AND activate = '1'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		while($DBRESULT->fetchInto($osl))	{
			if ($osl["host_id"] && $osl["service_id"])	{
				$BP = false;
				if ($ret["level"]["level"] == 1)
					array_key_exists($osl["host_id"], $gbArr[2]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 2)
					array_key_exists($osl["host_id"], $gbArr[2]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 3)
					$BP = true;
				if ($BP)
					$str .= "<sv id='".$osl["host_id"]."_".$osl["service_id"]."'/>";
			}
			else if ($osl["id_indicator_osl"])	{
				$BP = false;
				if ($ret["level"]["level"] == 1)
					array_key_exists($osl["id_indicator_osl"], $gbArr[6]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 2)
					array_key_exists($osl["id_indicator_osl"], $gbArr[6]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 3)
					$BP = true;
				if ($BP)
					$str .= "<osl id='".$osl["id_indicator_osl"]."'/>";
			}
			else if ($osl["meta_id"])
				$str .= "<oms id='".$osl["meta_id"]."'/>";
		}
		$DBRESULT->free();
		$str .= "</chds>\n";		
		$str .= "</osl>\n";
	}
	# Meta Service
	foreach($gbArr[7] as $key => $value)	{
		$str .= "<ms id='".$key."'>\n";
		## Parents
		$str .= "<prts>\n";
		$str .= "</prts>\n";
		
		## Childs
		$str .= "<chds>\n";
		$DBRESULT =& $pearDB->query("SELECT meta_select_mode, regexp_str FROM meta_service WHERE meta_id = '".$key."'");
		if (PEAR::isError($DBRESULT))
			print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
		$meta =& $DBRESULT->fetchrow();
		$DBRESULT->free();
		# Regexp mode
		if ($meta["meta_select_mode"] == 2)	{
			$DBRESULT =& $pearDB->query("SELECT service_id FROM service WHERE service_description LIKE '".$meta["regexp_str"]."'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
			while($DBRESULT->fetchInto($service))	{
				$BP = false;
				if ($ret["level"]["level"] == 1)
					array_key_exists($service["service_id"], $gbArr[4]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 2)
					array_key_exists($service["service_id"], $gbArr[4]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 3)
					$BP = true;
				if ($BP)	{
					$DBRESULT2 =& $pearDB->query("SELECT hsr.host_host_id, hsr.hostgroup_hg_id FROM host_service_relation hsr WHERE hsr.service_service_id = '".$service["service_id"]."'");
					if (PEAR::isError($DBRESULT2))
						print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
					while($DBRESULT2->fetchInto($service2))	{
						$BP = false;
						if ($ret["level"]["level"] == 1)	{
							array_key_exists($service2["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
							array_key_exists($service2["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
						}
						else if ($ret["level"]["level"] == 2)	{
							array_key_exists($service2["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
							array_key_exists($service2["hostgroup_hg_id"], $gbArr[3]) ? $BP = true : NULL;
						}
						else if ($ret["level"]["level"] == 3)
							$BP = true;
						if ($BP)	{
							if ($service2["hostgroup_hg_id"])	{
								$DBRESULT3 =& $pearDB->query("SELECT hgr.host_host_id FROM hostgroup_relation hgr WHERE hgr.hostgroup_hg_id = '".$service2["hostgroup_hg_id"]."'");
								if (PEAR::isError($DBRESULT3))
									print "DB Error : ".$DBRESULT3->getDebugInfo()."<br>";
								while($DBRESULT3->fetchInto($service3))	{
									$BP = false;
									if ($ret["level"]["level"] == 1)
										array_key_exists($service3["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
									else if ($ret["level"]["level"] == 2)
										array_key_exists($service3["host_host_id"], $gbArr[2]) ? $BP = true : NULL;
									else if ($ret["level"]["level"] == 3)
										$BP = true;
									if ($BP)
										$str .= "<sv id='".$service3["host_host_id"]."_".$service["service_id"]."'/>\n";
								}	
								unset($service3);
								$DBRESULT3->free();						
							}
							else
								$str .= "<sv id='".$service2["host_host_id"]."_".$service["service_id"]."'/>\n";
						}
					}
					$DBRESULT2->free();
				}
			}
			$DBRESULT->free();
		}
		else if ($meta["meta_select_mode"] == 1)	{
			require_once("./DBOdsConnect.php");
			$DBRESULT =& $pearDB->query("SELECT meta_id, host_id, metric_id FROM meta_service_relation msr WHERE meta_id = '".$key."' AND activate = '1'");
			if (PEAR::isError($DBRESULT))
				print "DB Error : ".$DBRESULT->getDebugInfo()."<br>";
			while($DBRESULT->fetchInto($metric))	{
				$BP = false;
				if ($ret["level"]["level"] == 1)
					array_key_exists($metric["host_id"], $gbArr[2]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 2)
					array_key_exists($metric["host_id"], $gbArr[2]) ? $BP = true : NULL;
				else if ($ret["level"]["level"] == 3)
					$BP = true;
				if ($BP)	{
					$DBRESULT2 =& $pearDBO->query("SELECT service_description FROM metrics m, index_data i WHERE m.metric_id = '".$metric["metric_id"]."' and m.index_id=i.id");
					if (PEAR::isError($DBRESULT2))
						print "DB Error : ".$DBRESULT2->getDebugInfo()."<br>";
					$OService =& $DBRESULT2->fetchRow();
					$sv_id =& getMyServiceID($OService["service_description"], $metric["host_id"]);
					$BP = false;
					if ($ret["level"]["level"] == 1)
						array_key_exists($sv_id, $gbArr[4]) ? $BP = true : NULL;
					else if ($ret["level"]["level"] == 2)
						array_key_exists($sv_id, $gbArr[4]) ? $BP = true : NULL;
					else if ($ret["level"]["level"] == 3)
						$BP = true;
					if ($BP)
						$str .= "<sv id='".$metric["host_id"]."_".$sv_id."'/>\n";
					$DBRESULT2->free();
				}
			}
			$DBRESULT->free();
		}
		$str .= "</chds>\n";		
		$str .= "</ms>\n";
	}
	
	$str .= "</dependencies>\n";
	$str .= "</osm_list>";
	write_in_file($handle, $str, $XMLConfigPath."osm_list.xml");
	fclose($handle);
	$DBRESULT->free();
	unset($str);
?>
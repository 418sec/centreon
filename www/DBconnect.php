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
 * SVN : $URL$
 * SVN : $Id$
 * 
 */ 
 
	if (isset($oreon))
		exit();
	
	// This file have to be included whenever we want to connect to the DB
		
	require_once("DB.php");
	
	// Pear connection
	
	$debug = 0;
	$dsn = array(
	    'phptype'  => 'mysql',
	    'username' => $conf_centreon["user"],
	    'password' => $conf_centreon["password"],
	    'hostspec' => $conf_centreon["hostCentreon"],
	    'database' => $conf_centreon["db"],
	);
	
	$options = array('debug' => 2,'portability' => DB_PORTABILITY_ALL ^ DB_PORTABILITY_LOWERCASE);
	
	global $pearDB;
	
	$flag = 1;
	for ($i = 0; $i <= 3 && $flag ; $i++) {
		$pearDB =& DB::connect($dsn, $options);
		if (PEAR::isError($pearDB))
		    die("[DB Centreon connexion] " . $pearDB->getMessage());
		else
			$flag = 0;
	}    	
	$pearDB->setFetchMode(DB_FETCHMODE_ASSOC);
	// End of Pear connection
?>

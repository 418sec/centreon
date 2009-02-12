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
 * SVN : $Id
 * 
 */
 

class CentreonAuthLDAP {
	
	var $ldapInfos;
	var $ldapuri;
	var $ds;
	var $CentreonLog;
	var $contactInfos;
	var $typePassword;
	
	
	function CentreonAuthLDAP($pearDB, $CentreonLog, $login, $password, $contactInfos) {
		
		$this->CentreonLog = $CentreonLog;
		
		$DBRESULT =& $pearDB->query("SELECT `ldap_host`, `ldap_port`, `ldap_base_dn`, `ldap_login_attrib`, `ldap_ssl`, `ldap_auth_enable` FROM `general_opt` LIMIT 1");
		
		$this->ldapInfos = $DBRESULT->fetchRow();
		
		/*
		 * Set contact Informations
		 */
		$this->contactInfos = $contactInfos;
		
		/*
		 * Keep password
		 */
		$this->typePassword = $password;
				
		/*
		 * Create URI
		 */
		($this->ldapInfos['ldap_ssl']) ? $this->ldapuri = "ldaps://" : $this->ldapuri = "ldap://" ;
	}
	
	function connect() {
		if  (!isset($this->contactInfos['contact_ldap_dn']) || $this->contactInfos['contact_ldap_dn'] == '')
			$this->contactInfos['contact_ldap_dn'] = "anonymous" ;
			print $this->ldapuri . $this->ldapInfos['ldap_host'].":".$this->ldapInfos['ldap_port'];
		$this->ds = ldap_connect($this->ldapuri . $this->ldapInfos['ldap_host'].":".$this->ldapInfos['ldap_port']);
		$this->CentreonLog->insertLog(3, "LDAP Auth Cnx : ". $this->ldapuri . $this->ldapInfos['ldap_host'].":".$this->ldapInfos['ldap_port']." : ".ldap_error($this->ds)." (".ldap_errno($this->ds).")");
		$this->CentreonLog->insertLog(3, "LDAP Auth Cnx : ". $this->ldapuri . $this->ldapInfos['ldap_host'].":".$this->ldapInfos['ldap_port']." : ".ldap_error($this->ds)." (".ldap_errno($this->ds).")");
	}
	
	function checkPassword() {
		
		/*
		 * LDAP BIND
		 */
		@ldap_bind($this->ds, $this->contactInfos['contact_ldap_dn'], $this->typePassword);
		$this->CentreonLog->insertLog(3, "Connexion = ".$this->contactInfos['contact_ldap_dn']." :: ".ldap_error($this->ds));
		/* In some case, we fallback to local Auth
		  0 : Bind succesfull => Default case
		  2 : Protocol error
		 -1 : Can't contact LDAP server (php4) => Fallback
		 51 : Server is busy => Fallback
		 52 : Server is unavailable => Fallback
		 81 : Can't contact LDAP server (php5) => Fallback
		 Else : Go away !!
		*/
	
		if (isset($this->ds) && $this->ds) {
			print ldap_errno($this->ds);
			switch (ldap_errno($this->ds)) {
				case 0:
					$this->CentreonLog->insertLog(3, "LDAP AUTH : OK, let's go to Local AUTH");
				   	return 1;
				   	break;
				case -1:
				case 51:
					$this->CentreonLog->insertLog(3, "LDAP AUTH : Error, Server Busy. Try later");
					return 0;
					break;
				case 52:
					$this->CentreonLog->insertLog(3, "LDAP AUTH : Error, Server unavailable. Try later");
					return 0;
					break;
				case 81:
					$this->CentreonLog->insertLog(3, "LDAP AUTH : Error, Fallback to Local AUTH");
					return 0;
					break;
				default:
				   	$this->CentreonLog->insertLog(3, "LDAP AUTH : LDAP don't like you, sorry");
				   	return 0;
				   	break;
			}
		} else {
			$this->CentreonLog->insertLog(3, "DS empty");
			return 0;
		}
	}
	
	/*
	 * Close LDAP Connexion
	 */
	function close() {
		ldap_close($this->ds);
	}
	
}
 
 
?>
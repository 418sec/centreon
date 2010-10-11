#! /usr/bin/perl -w
################################################################################
# Copyright 2005-2010 MERETHIS
# Centreon is developped by : Julien Mathis and Romain Le Merlus under
# GPL Licence 2.0.
# 
# This program is free software; you can redistribute it and/or modify it under 
# the terms of the GNU General Public License as published by the Free Software 
# Foundation ; either version 2 of the License.
# 
# This program is distributed in the hope that it will be useful, but WITHOUT ANY
# WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
# PARTICULAR PURPOSE. See the GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License along with 
# this program; if not, see <http://www.gnu.org/licenses>.
# 
# Linking this program statically or dynamically with other modules is making a 
# combined work based on this program. Thus, the terms and conditions of the GNU 
# General Public License cover the whole combination.
# 
# As a special exception, the copyright holders of this program give MERETHIS 
# permission to link this program with independent modules to produce an executable, 
# regardless of the license terms of these independent modules, and to copy and 
# distribute the resulting executable under terms of MERETHIS choice, provided that 
# MERETHIS also meet, for each linked independent module, the terms  and conditions 
# of the license of that module. An independent module is a module which is not 
# derived from this program. If you modify this program, you may extend this 
# exception to your version of the program, but you are not obliged to do so. If you
# do not wish to do so, delete this exception statement from your version.
# 
# For more information : contact@centreon.com
# 
# SVN : $URL$
# SVN : $Id$
#
####################################################################################
#
# Script init
#

use strict;
use DBI;

use vars qw($mysql_database_oreon $mysql_database_ods $mysql_host $mysql_user $mysql_passwd);
use vars qw($cmdFile $etc);

###############################
# Init 

cmdFile = "@CENTREON_VARLIB@/centcore.cmd";
etc = "@CENTREON_ETC@";

###############################
# require config file

require $etc."/conf.pm";

###############################
## GET HOSTNAME FROM IP ADDRESS
#

sub get_hostinfos($$$){
    my $sth = $_[0]->prepare("SELECT host_name FROM host WHERE host_address='$_[1]' OR host_address='$_[2]'");
    $sth->execute();
    my @host;
    while (my $temp = $sth->fetchrow_array()) {
		$host[scalar(@host)] = $temp;
    }
    $sth->finish();
    return @host;
}

###############################
## GET host location
#

sub get_hostlocation($$){
    my $sth = $_[0]->prepare("SELECT localhost FROM host, `ns_host_relation`, nagios_server WHERE host.host_id = ns_host_relation.host_host_id AND ns_host_relation.nagios_server_id = nagios_server.id AND host.host_name = '".$_[1]."'");
    $sth->execute();
    if ($sth->rows()){
	my $temp = $sth->fetchrow_array();
	$sth->finish();
    	return $temp;
    } else {
    	return 0;
    }
}

##################################
## GET nagios server id for a host
#

sub get_hostNagiosServerID($$){
    my $sth = $_[0]->prepare("SELECT id FROM host, `ns_host_relation`, nagios_server WHERE host.host_id = ns_host_relation.host_host_id AND ns_host_relation.nagios_server_id = nagios_server.id AND (host.host_name = '".$_[1]."' OR host.host_address = '".$_[1]."')");
    $sth->execute();
    if ($sth->rows()){
	my $temp = $sth->fetchrow_array();
	$sth->finish();
    	return $temp;
    } else {
    	return 0;
    }
}

#####################################################################
## GET SERVICES FOR GIVEN HOST (GETTING SERVICES TEMPLATES IN ACCOUNT)
#

sub getServicesIncludeTemplate($$$$) {
    my ($dbh, $sth_st, $host_id, $trap_id) = @_;
    my @service;
    $sth_st->execute();
    while (my @temp = $sth_st->fetchrow_array()) {
	my $tr_query = "SELECT `traps_id` FROM `traps_service_relation` WHERE `service_id` = '".$temp[0]."' AND `traps_id` = '".$trap_id."'";
	my $sth_st3 = $dbh->prepare($tr_query);
	$sth_st3->execute();
	my @trap = $sth_st3->fetchrow_array();
	if (defined($trap[0])) {
	    $service[scalar(@service)] = $temp[1];
	} else {
	    if (defined($temp[2])) {
		my $found = 0;
		my $service_template = $temp[2];
		while (!$found) {
		    my $st1_query = "SELECT `service_id`, `service_template_model_stm_id`, `service_description` FROM service s WHERE `service_id` = '".$service_template."'";
		    my $sth_st1 = $dbh->prepare($st1_query);
		    $sth_st1 -> execute();
		    my @st1_result = $sth_st1 -> fetchrow_array();
		    if (defined($st1_result[0])) {
			my $sth_st2 = $dbh->prepare("SELECT `traps_id` FROM `traps_service_relation` WHERE `service_id` = '".$service_template."' AND `traps_id` = '".$trap_id."'");
			$sth_st2 -> execute();
			my @st2_result = $sth_st2 -> fetchrow_array();
			if (defined($st2_result[0])) {
			    $found = 1;
			    $service[scalar(@service)] = $temp[1];
			} else {
			    $found = 1;
			    if (defined($st1_result[1]) && $st1_result[1]) {
				$service_template = $st1_result[1];
				$found = 0;
			    }
			}
			$sth_st2->finish;		    
		    }
		    $sth_st1->finish;
		}
	    }
	}
	$sth_st3->finish;
    }
    return (@service);
}

##########################
# GET SERVICE DESCRIPTION
#
sub getServiceInformations($$$)	{

    my $sth = $_[0]->prepare("SELECT `host_id` FROM `host` WHERE `host_name` = '$_[2]'");
    $sth->execute();
    my $host_id = $sth->fetchrow_array();
    exit if (!defined $host_id);
    $sth->finish();

    $sth = $_[0]->prepare("SELECT `traps_id`, `traps_status`, `traps_submit_result_enable`, `traps_execution_command`, `traps_reschedule_svc_enable`, `traps_execution_command_enable` FROM `traps` WHERE `traps_oid` = '$_[1]'");
    $sth->execute();
    my ($trap_id, $trap_status, $traps_submit_result_enable, $traps_execution_command, $traps_reschedule_svc_enable, $traps_execution_command_enable) = $sth->fetchrow_array();
    exit if (!defined $trap_id);
    $sth->finish();

    ######################################################
    # getting all "services by host" for given host
    my $st_query = "SELECT s.service_id, service_description, service_template_model_stm_id FROM service s, host_service_relation h";
    $st_query .= " where  s.service_id=h.service_service_id and h.host_host_id='$host_id'";
    my $sth_st = $_[0]->prepare($st_query); 
    my @service = getServicesIncludeTemplate($_[0], $sth_st, $host_id, $trap_id);
    $sth_st->finish;

    ######################################################
    # getting all "services by hostgroup" for given host
    my $query_hostgroup_services = "SELECT s.service_id, service_description, service_template_model_stm_id FROM hostgroup_relation hgr,  service s, host_service_relation hsr";
    $query_hostgroup_services .= " WHERE hgr.host_host_id = '".$host_id."' AND hsr.hostgroup_hg_id = hgr.hostgroup_hg_id";
    $query_hostgroup_services .= " AND s.service_id = hsr.service_service_id";
    $sth_st = $_[0]->prepare($query_hostgroup_services);
    $sth_st->execute();
    @service = (@service,getServicesIncludeTemplate($_[0], $sth_st, $host_id, $trap_id));
    $sth_st->finish;
    return $trap_status, \@service, $traps_submit_result_enable, $traps_execution_command, $traps_reschedule_svc_enable, $traps_execution_command_enable;
}

#######################################
# GET HOSTNAME AND SERVICE DESCRIPTION
#
sub getTrapsInfos($$$$$){
    my $ip = shift;
    my $hostname = shift;
    my $oid = shift;
    my $arguments_line = shift;
    my $allargs = shift;

    my $dbh = DBI->connect("dbi:mysql:".$mysql_database_oreon.";host=".$mysql_host, $mysql_user, $mysql_passwd) or die "Echec de la connexion\n";

    # Get Nagios.cfg configuration
    my $sth = $dbh->prepare("SELECT `command_file` FROM `cfg_nagios` WHERE `nagios_activate` = '1' LIMIT 1");
    $sth->execute();
    my @conf = $sth->fetchrow_array();
    $sth->finish();

    my @host = get_hostinfos($dbh, $ip, $hostname);
    foreach (@host) {
		my $this_host = $_;
		my ($trap_id, $status, $ref_servicename, $traps_submit_result_enable, $traps_execution_command, $traps_reschedule_svc_enable, $traps_execution_command_enable, $traps_advanced_treatment) = getServiceInformations($dbh, $oid, $_);
		my @servicename = @{$ref_servicename};
	
		## Split $* informations
		my @args = split(/ /, $allargs);
	
		foreach (@servicename) {
		    my $this_service = $_;
	
		    my $datetime = `date +%s`;
		    chomp($datetime);
	
		    my $location = get_hostlocation($dbh, $this_host);
	
		    ######################################################################
		    # Submit value to passiv service
		    if (defined($traps_submit_result_enable) && $traps_submit_result_enable eq 1) { 
				# Advanced matching rules
				if (defined($traps_advanced_treatment) && $traps_advanced_treatment eq 1) {
			    	# Check matching options 
			    	my $sth = $dbh->prepare("SELECT tmo_regexp, tmo_status, tmo_string FROM traps_matching_properties WHERE trap_id = '".$trap_id."' ORDER BY tmo_order");
			    	$sth->execute();
			    	while (my ($regexp, $tmoStatus, $tmoString) = $sth->fetchrow_array()) {
						my @temp = split(//, $regexp);
						my $i = 0;
						my $len = length($regexp);
						$regexp = "";
						foreach (@temp) {
				    		if ($i eq 0 && $_ =~ "/") {
								print "";
				    		} elsif ($i eq ($len - 1) && $_ =~ "/") { 
								print "";
				    		} else {
								print $_;
				    		}
				    		$i++;
						}
						##########################
						# REPLACE ARGS
						my $x = 0;
						foreach (@args) {
							$tmoString =~ s/\$$x\/$_/g;				
							$x++;
						}
						
						##########################
						# REPLACE MACROS
						$tmoString =~ s/\&quot\;/\"/g;
						$tmoString =~ s/\@HOSTNAME\@/$this_host/g;
						$tmoString =~ s/\@HOSTADDRESS\@/$_[1]/g;
						$tmoString =~ s/\@HOSTADDRESS2\@/$_[2]/g;
						$tmoString =~ s/\@TRAPOUTPUT\@/$arguments_line/g;
						$tmoString =~ s/\@TIME\@/$datetime/g;
						
						if (defined(tmoString) && $tmoString =~ m/$regexp/g) {
				    		$status = $tmoStatus;
				    		last;
						}
			    	}
			    	$sth->finish();
	
			    	if ($location != 0){
	            		my $submit = `/bin/echo "[$datetime] PROCESS_SERVICE_CHECK_RESULT;$this_host;$this_service;$status;$arguments_line" >> $conf[0]`;
					} else {
	                	my $id = get_hostNagiosServerID($dbh, $this_host);
	                	if (defined($id) && $id != 0) {
	                    	my $submit = `/bin/echo "EXTERNALCMD:$id:[$datetime] PROCESS_SERVICE_CHECK_RESULT;$this_host;$this_service;$status;$arguments_line" >> $cmdFile`;
	                    	undef($id);
	                	}
	            	}
				} else {
			    	# No matching rules
			    	if ($location != 0){
						my $submit = `/bin/echo "[$datetime] PROCESS_SERVICE_CHECK_RESULT;$this_host;$this_service;$status;$arguments_line" >> $conf[0]`;
			    	} else {
						my $id = get_hostNagiosServerID($dbh, $this_host);
						if (defined($id) && $id != 0) {
				    		my $submit = `/bin/echo "EXTERNALCMD:$id:[$datetime] PROCESS_SERVICE_CHECK_RESULT;$this_host;$this_service;$status;$arguments_line" >> $cmdFile`;
				    		undef($id);
						}
			    	}
				}
		    }
		    
		    ######################################################################
		    # Force service execution with external command
		    if (defined($traps_reschedule_svc_enable) && $traps_reschedule_svc_enable eq 1) {
				if ($location != 0){
				    my $submit = `/bin/echo "[$datetime] SCHEDULE_FORCED_SVC_CHECK;$this_host;$this_service;$datetime" >> $conf[0]`;
				} else {
				    my $id = get_hostNagiosServerID($dbh, $this_host);
				    if (defined($id) && $id != 0) {
						my $submit = `/bin/echo "EXTERNALCMD:$id:[$datetime] SCHEDULE_FORCED_SVC_CHECK;$this_host;$this_service;$datetime" >> $cmdFile`;
						undef($id);
				    }
				}
				undef($location);
		    }
		    ######################################################################
		    # Execute special command
		    if (defined($traps_execution_command_enable) && $traps_execution_command_enable) {
				##########################
				# REPLACE ARGS
				my $x = 0;
				foreach (@args) {
					$traps_execution_command =~ s/\$$x\/$_/g;				
					$x++;
				}
				
				##########################
				# REPLACE MACROS
				$traps_execution_command =~ s/\&quot\;/\"/g;
				$traps_execution_command =~ s/\@HOSTNAME\@/$this_host/g;
				$traps_execution_command =~ s/\@HOSTADDRESS\@/$_[1]/g;
				$traps_execution_command =~ s/\@HOSTADDRESS2\@/$_[2]/g;
				$traps_execution_command =~ s/\@TRAPOUTPUT\@/$arguments_line/g;
				$traps_execution_command =~ s/\@STATUS\@/$status/g;
				$traps_execution_command =~ s/\@TIME\@/$datetime/g;
		
				##########################
				# SEND COMMAND
				system($traps_execution_command);				
		    }
		    undef($sth);
		    undef($location);
		}
    }
    $dbh->disconnect();
    exit;
}


##########################
# PARSE TRAP INFORMATIONS
#
if (scalar(@ARGV)) {
    my ($ip, $hostname, $oid, $arguments, $allargs) = @ARGV;
    getTrapsInfos($ip, $hostname, $oid, $arguments, $allArgs);
}

#!/bin/sh
###################################################################
# Oreon is developped with GPL Licence 2.0 
#
# GPL License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
#
# Developped by : Julien Mathis - Romain Le Merlus
#
#                 jmathis@merethis.com
#
###################################################################
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
#    For information : contact@merethis.com
####################################################################
#
# Script init
#
### BEGIN INIT INFO Suse
# Provides:       centstorage
# Required-Start: mysqld nagios
# Required-Stop:
# Default-Start:  3 5
# Default-Stop: 0 1 6
# Description:    Start the CentStorage collector
### END INIT INFO

### BEGIN INIT INFO Redhat
# chkconfig: - 71 31
# description: Centreon Core
# processname: centcore
# config:
# pidfile:
### END INIT INFO

status_centstorage()
{
    if test ! -f $centstorageRunFile; then
	echo "No lock file found in $centstorageRunFile"
	return 1
    fi
    centstoragePID=`head -n 1 $centstorageRunFile`
    if ps -p $centstoragePID; then
	return 0
    else
	return 1
    fi
    return 1
}

killproc_centstorage()
{
    if test ! -f $centstorageRunFile; then
	echo "No lock file found in $centstorageRunFile"
	return 1
    fi    
    centstoragePID=`head -n 1 $centstorageRunFile`
    kill -s INT $centstoragePID
}


# Source function library
# Solaris doesn't have an rc.d directory, so do a test first

if [ -f /etc/rc.d/init.d/functions ]; then
    . /etc/rc.d/init.d/functions
elif [ -f /etc/init.d/functions ]; then
    . /etc/init.d/functions
fi

prefix=@CENTREON_DIR@/
Bin=@CENTSTORAGE_BINDIR@/centstorage
centstorageCfgFile=@CENTREON_ETC@/conf.pm
centstorageLogDir=@CENTREON_LOG@
centstorageRunDir=@CENTREON_RUNDIR@
centstorageVarDir=${prefix}/var/
centstorageRunFile=${centstorageRunDir}/centstorage.pid
centstorageDemLog=@CENTREON_LOG@/centstorage.log
#centstorageDemLog=${censtorageLogDir}/centstorage.log
centstorageLockDir=/var/lock/subsys
centstorageLockFile=centstorage
NICE=5

# Check that centstorage exists.
if [ ! -f $centstorageBin ]; then
    echo "Executable file $centstorageBin not found.  Exiting."
    exit 1
fi

# Check that centstorage.cfg exists.
if [ ! -f $centstorageCfgFile ]; then
    echo "Configuration file $centstorageCfgFile not found.  Exiting."
    exit 1
fi
          
# See how we were called.
case "$1" in
    start)
	# Check lock file
    if test -f $centstorageRunFile; then
	echo "Error : $centstorageRunFile already Exists."
	NDcentstorageRUNNING=`ps -edf | grep $centstorageBin | grep -v grep | wc -l `
	if [ $NDcentstorageRUNNING = 0 ]
	    then
	    echo "But no centstorage process runnig"
	    rm -f $centstorageRunFile
	    echo "Removing centstorage pid file"
	else 
	    exit 1
	fi
    fi
    echo "Starting centstorage Collector : centstorage"
    su - @NAGIOS_USER@ -c "$Bin >> $centstorageDemLog 2>&1"
    if [ -d $centstorageLockDir ]; then 
    	touch $centstorageLockDir/$centstorageLockFile; 
    fi
    exit 0
    ;;
    
    stop)
    echo "Stopping centreon data collector Collector : centstorage"
    killproc_centstorage centstorage
    echo -n 'Waiting for centstorage to exit .'
    for i in `seq 20` ; do
	if status_centstorage > /dev/null; then
	    echo -n '.'
	    sleep 1
	else
	    break
	fi
    done
    if status_centstorage > /dev/null; then
	echo ''
	echo 'Warning - running centstorage did not exit in time'
    else
	echo ' done.'
    fi
    ;;
    
    status)
    status_centstorage centstorage
    ;;
    
    restart)
    $0 stop
    $0 start
    ;;
    
    reload|force-reload)
    if test ! -f $centstorageRunFile; then
	$0 start
    else
	centstoragePID=`head -n 1 $centstorageRunFile`
	if status_centstorage > /dev/null; then
	    killproc_centstorage centstorage -HUP
	    echo "done"
	else
	    $0 stop
	    $0 start
	fi
    fi
    ;;
    
    *)
    echo "Usage: centstorage {start|stop|restart|reload|status}"
    exit 1
    ;;
    
esac
# End of this script

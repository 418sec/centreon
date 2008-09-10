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
	 * return the interval of time which must be reported
	 */
	function getPeriodToReport() {
		$period = (isset($_POST["period"])) ? $_POST["period"] : ""; 
		$period = (isset($_GET["period"])) ? $_GET["period"] : $period;
		$end_date = 0;
		$start_date = 0;
		$start_date = (isset($_POST["start"])) ? $_POST["start"] : "";
		$start_date = (isset($_GET["start"])) ? $_GET["start"] : $start_date;
		$end_date = (isset($_POST["end"])) ? $_POST["end"] : "";
		$end_date = (isset($_GET["end"])) ? $_GET["end"] : $end_date;
		$interval = array(0, 0);
		if ($period == "" && $start_date != "") {
			$interval = getDateSelect_customized($start_date, $end_date);
		}else {
			$interval = getDateSelect_predefined($period);
		}
		$start_date = $interval[0];
		$end_date = $interval[1];
		return(array($start_date,$end_date));
	}
	
	/*
	 * Return a table containing all stats information that will be displayed on dashboard for host and hostgroup
	 */
	function getHostStatsValueName() {
		return (array("UP_T", "UP_A", "DOWN_T", "DOWN_A", "UNREACHABLE_T", "UNREACHABLE_A", "UNDETERMINED_T",
						"UP_TP", "DOWN_TP", "UNREACHABLE_TP", "UNDETERMINED_TP"));
	}
	
	/*
	 * Return a table containing all stats information that will be displayed on dashboard for services and servicegroup
	 */
	function getServicesStatsValueName() {
		return (array("OK_T", "OK_A", "WARNING_T", "WARNING_A", "CRITICAL_T", "CRITICAL_A", "UNKNOWN_T", "UNKNOWN_A", "UNDETERMINED_T",
					"OK_TP", "WARNING_TP", "CRITICAL_TP", "UNKNOWN_TP", "UNDETERMINED_TP"));
	}
	/*
	 * return start and end date to report in timestamp 
	 * ==>> function must be optimized
	 */
	function getDateSelect_predefined($period){
		$time = time();
		$day = date("d", $time);
		$year = date("Y", $time);
		$month = date("m", $time);
		if (!is_null($period)){
			if($period == "yesterday"){
				$start_date = mktime(0, 0, 0, $month, $day - 1, $year);
				$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
			} else if($period == "thisweek"){
				$dd = (date("D",mktime(24, 0, 0, $month, $day - 1, $year)));
				for($ct = 1; $dd != "Mon" ;$ct++)
					$dd = (date("D",mktime(0, 0, 0, $month, ($day - $ct), $year)));
				$start_date = mktime(0, 0, 0, $month, $day - $ct, $year);
				$end_date = mktime(24, 0, 0, $month, ($day - 1), $year);
			} else if($period == "last7days"){
				$start_date = mktime(0, 0, 0, $month, $day - 7, $year);
				$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
			} else if($period == "last30days"){
				$start_date = mktime(0, 0, 0, $month, $day - 30, $year);
				$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
			} else if($period == "lastyear"){
				$start_date = mktime(0, 0, 0, 1, 1, $year-2);
				$end_date = mktime(0, 0, 0, 1, 1, $year-1);
			} else if($period == "thismonth") {
				$start_date = mktime(0, 0, 0, $month, 1, $year);
				$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
			} else if($period == "thisyear"){				
				$start_date = mktime(0, 0, 0, 1, 1, $year);
				$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
			} else { /* last month */
				$start_date = mktime(0, 0, 0, $month - 1, 1, $year);
				$end_date = mktime(0, 0, 0, $month, 1, $year);
			}		
		} else {
			$start_date = mktime(0, 0, 0, $month, $day - 1, $year);
			$end_date = mktime(24, 0, 0, $month, $day - 1, $year);
		}
		if ($start_date > $end_date) {
			$start_date = $end_date;
		}
		return (array($start_date, $end_date));
	}
	
	function getDateSelect_customized($start, $end){
		$time = time();
		$day = date("d",$time);
		$year = date("Y",$time);
		$month = date("m",$time);
		$end_time = mktime(0, 0, 0, $month, $day, $year);
		if ($end != "") {
			list($m,$d,$y) = split('/',$end);
			$end = mktime(24, 0, 0, $m, $d, $y);
			if ($end < $end_time)
				$end_time = $end;
				print "here\n";
		}
		list($m,$d,$y) = split('/',$start);
		$start_time = mktime(0, 0, 0, $m, $d, $y);
		if ($start_time >= $end_time)
			$start_time = $end_time - (60 * 60 * 24);
		return (array($start_time, $end_time));
	}
	/*
	 * Return time between two timestamp
	 * excluding days and time which are not in the parameters defined in menu "Options>General Options>Reporting"
	 */
	function getTotalTimeFromInterval($start, $end, $reportTimePeriod) {
		$one_day_real_duration = 60 * 60 * 24;
		$totalTime = 0;
		$reportTime = 0;
		$day_duration =  mktime($reportTimePeriod["report_hour_end"], $reportTimePeriod["report_minute_end"], 0, 0, 0, 0)
						- mktime($reportTimePeriod["report_hour_start"], $reportTimePeriod["report_minute_start"], 0, 0, 0, 0);
		while ($start < $end) {
			
			if (isset($reportTimePeriod["report_".date("l", $start)])
				&& $reportTimePeriod["report_".date("l", $start)])
					$reportTime += $day_duration;# if the day is selected in the timeperiod
			$totalTime += $day_duration;
			//$start = $day_real_end;
			$start += $one_day_real_duration;
		}
		$tab = array("totalTime" => $totalTime, "reportTime" => $reportTime);
		return $tab;
	}

	function my_getTimeTamps($dateSTR)	{
		list($m,$d,$y) = split('/',$dateSTR);
		return (mktime(0,0,0,$m,$d,$y));
	}
	
	function getLogData($time_event, $host, $service, $status, $output, $type){
		$tab_img = array("UP" => './img/icones/12x12/recovery.gif',
						"DOWN" => './img/icones/12x12/alert.gif',
						"UNREACHABLE" => './img/icones/12x12/queue.gif',
						"NONE" => './img/icones/12x12/info.gif',
						);

		$tab["time"] = date(_("Y/m/d G:i"), $time_event);
		$tab["timeb"] = $time_event;
		$tab["host"] = $host;
		$tab["service"] = $service;
		$tab["status"] = $status;
		$tab["output"] = $output;
		$tab["type"] = $type;
		$tab["img"] = $tab_img[$status];
		return $tab ;
	}
	
	function getPeriodList(){
	
	$periodList = array();
	$periodList[""] = "";
	$periodList["yesterday"] = _("Yesterday");
	$periodList["thisweek"] = _("This Week");
	$periodList["last7days"] = _("Last 7 Days");
	$periodList["thismonth"] = _("This Month");
	$periodList["last30days"] = _("Last 30 Days");
	$periodList["lastmonth"] = _("Last Month");
	$periodList["thisyear"] = _("This Year");
	$periodList["lastyear"] = _("Last Year");
	
	return $periodList;
	}
	
	function create_date_timeline_format($time_unix){
		$tab_month = array(
		"01" => "Jan",
		"02" => "Feb",
		"03"=> "Mar",
		"04"=> "Apr",
		"05" => "May",
		"06"=> "Jun",
		"07"=> "Jul",
		"08"=> "Aug",
		"09"=> "Sep",
		"10"=> "Oct",
		"11"=> "Nov",
		"12"=> "Dec");
		$date = $tab_month[date('m', $time_unix)].date(" d Y G:i:s", $time_unix);
		return $date;
	}
	
	function getTimeString($time, $reportTimePeriod) {
		$min = 60;
		$hour = $min * 60;
		$day = mktime($reportTimePeriod["report_hour_end"], $reportTimePeriod["report_minute_end"], 0, 0, 0, 0)
				- mktime($reportTimePeriod["report_hour_start"], $reportTimePeriod["report_minute_start"], 0, 0, 0, 0);
		$week = 0;
		if (isset($reportTimePeriod["report_Monday"])  && $reportTimePeriod["report_Monday"])
			$week ++;
		if (isset($reportTimePeriod["report_Tuesday"])  && $reportTimePeriod["report_Tuesday"])
			$week ++;
		if (isset($reportTimePeriod["report_Wednesday"])  && $reportTimePeriod["report_Wednesday"])
			$week ++;
		if (isset($reportTimePeriod["report_Thursday"])  && $reportTimePeriod["report_Thursday"])
			$week ++;
		if (isset($reportTimePeriod["report_Friday"])  && $reportTimePeriod["report_Friday"])
			$week ++;
		if (isset($reportTimePeriod["report_Saturday"])  && $reportTimePeriod["report_Saturday"])
			$week ++;
		if (isset($reportTimePeriod["report_Sunday"])  && $reportTimePeriod["report_Sunday"])
			$week ++;
		$week *= $day;
		$year = $week * 52;
		$month = $year / 12;
		$str = "";
		if ($year && $time / $year >= 1) {
			$str .= floor($time / $year)."Y ";
			$time = $time % $year;
		}
		if ($month && $time / $month >= 1) {
			$str .= floor($time / $month)."M ";
			$time = $time % $month;
		}
		if ($week && $time / $week >= 1) {
			$str .= floor($time / $week)."w ";
			$time = $time % $week;
		}
		if ($day && $time / $day >= 1) {
			$str .= floor($time / $day)."d ";
			$time = $time % $day;
		}
		if ($hour && $time / $hour >= 1) {
			$str .= floor($time / $hour)."h ";
			$time = $time % $hour;
		}
		if ($min && $time / $min >= 1) {
			$str .= floor($time / $min)."m ";
			$time = $time % $min;
		}
		if ($time)
			$str .=  $time."s";
		return $str;
	}
	function formatData($state, $time, $timeTOTAL, $time_none, $nb_alert, $color){
		$tab = array();
		$tab["state"] = _($state);
		$tab["time"] = Duration::toString($time);
		$tab["timestamp"] = $time;
		$tab["pourcentTime"] = round($time/($timeTOTAL+1)*100,2) ;
		if ($state != "Undetermined")
			$tab["pourcentkTime"] = round($time/($timeTOTAL-$time_none+1)*100,2). "%";
		else
			$tab["pourcentkTime"] = NULL;
		$tab["nbAlert"] = $nb_alert;
		$tab["style"] = "class='ListColCenter' style='background:" . $color."'";	
		return $tab;
	}
?>
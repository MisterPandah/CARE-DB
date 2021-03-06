<?php
include "global.php";
include "globalhelper.php";

// Do not remove these few lines of code unless for good reasons
// These sessions keep users remain logged in as themselves
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session'));
session_start();

// If no one is logged in, redirect them to the login page
if(!(isset($_SESSION['login']) && $_SESSION['login'] != '')) {
	header("Location: login.php");
}

//=======================
//       READ ME
//=======================

// For new files, (eg. newpage.php) run this command in console:
// chmod 755 newpage.php

$currentDate = date("F j, Y");

//===================
// CONNECT TO ORACLE
//===================
if ($c = oci_connect ($ora_usr, $ora_pwd, "ug")) {

	$id = 20; // testing purposes

	// Schedule Query
	$query = "select d.ename, p.pname, p.phone, s.time
		  from doctor d, patient p
		  inner join schedule s
		  on p.pid = s.pid
		  where trunc(s.time) = '".date("y-m-d")."' and d.eid = s.deid";
	if(getUserType() == "doctor")	
		$query .= " and s.deid = ". $_SESSION['doctor'];
	$query .= " order by s.time";

	$s = oci_parse($c, $query);
	oci_execute($s);

	//Oracle Fetches
	$n_rows = oci_fetch_all($s, $schedule, null, null, OCI_FETCHSTATEMENT_BY_ROW);

	oci_close($c);

} else {
	$err = oci_error();
	echo "Oracle Connect Error " . $err['message'];
}

// Helper Functions
function buildSchedule($num, $arr) {
	echo '<table class = "pSearch">';
	echo '<tr>';
	if(!(getUserType() == "doctor"))
		echo '<th>Doctor Name</th>';
	echo '<th>Patient Name</th>';
	echo '<th>Phone</th>';
	echo '<th>Scheduled Time</th>';
	echo '</tr>';
	for($i = 0; $i < $num; $i++) {
		echo '<tr>';
		if(!(getUserType() == "doctor"))
			echo '<td>'. $arr[$i]['ENAME'] .'</td>';
		echo '<td>'. $arr[$i]['PNAME'] .'</td>';
		echo '<td>'. $arr[$i]['PHONE'] .'</td>';
		
		$timestamp = strtotime($arr[$i]['TIME']);
		echo '<td>'. date("g:i a", $timestamp);

		$endtimestamp = mktime(date("g", $timestamp)+1, date("i", $timestamp), 0);
		
		echo ' - '. date("g:i a", $endtimestamp);
		echo '</td>';	
		echo '</tr>';
	}
	echo '</table>';
}
?>

<!--Design the page below-->
<html>
<head>
	<title>CARE Clinic</title>
	<link rel = "stylesheet" type = "text/css" href= "./styles/styling.css">
</head>
<body style = "text-align: center;">
	<div id = "header">
		<?php attachHeader(); ?>
	</div>

	<div id = "menu-nav">
		<?php buildMenuTab(); ?>
	</div>
	<div id = "content">
		<?php
		
		echo '<h3 id = "pagetitle">'.$currentDate.'</h3>';

		if($n_rows > 0)	
			buildSchedule($n_rows, $schedule);
		else
			echo 'Currently No Schedule';
		?>
	</div>
	<div id = "footer"></div>
</body>
</html>

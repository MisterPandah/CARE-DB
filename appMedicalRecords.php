<?php
include "global.php";
include "globalhelper.php";

// Do not remove these few lines of code unless for good reasons
// These sessions keep users remain logged in as themselves
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session'));
session_start();

// If no one is logged in, redirect them to the login page
if(!(isset($_SESSION['login']) || $_SESSION['login'] == '')) {
	header("Location: login.php");
}

//=======================
//       READ ME
//=======================

// For new files, (eg. newpage.php) run this command in console:
// chmod 755 newpage.php

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Obtaining Info
	$condition = trim($_POST["condition"]);
	$medication = trim($_POST["medication"]);
	$pname = $_POST["pname"];
	$today = date('y-m-d');
	
	$delete =$_POST["delete"];
	$cond =$_POST["cond"];
	
	$allergies = trim($_POST["allergies"]);
	$emercontact = trim($_POST["emercontact"]);
	
	$addallergies = trim($_POST["addallergies"]);
	$addemercontact = trim($_POST["addemercontact"]);
	
	$aFname = trim($_POST["aFname"]);
	$aRelation = trim($_POST["aRelation"]);
	$aCond = trim($_POST["aCond"]);
	
	$dCond = $_POST["dCond"];
	$dFname = $_POST["dFname"];
	
	$patientID = $_POST["addpatient"];
	//===================
	// CONNECT TO ORACLE
	//===================
	if ($c = oci_connect ($ora_usr, $ora_pwd, "ug")) {

		//Handles Additions to contains_pHistory
		if($condition != null && $medication != null && $pname != null){
			$queryInsert = "insert into contains_pHistory values
				($patientID, '$pname','$today','$condition','$medication')";
			$sInsert = oci_parse($c, $queryInsert);
			oci_execute($sInsert);
		}
		
		//Handles Deletions to contains_pHistory
		if($delete != null && $cond!=null){
		$queryDelete = "delete from contains_pHistory 
						where pid = '$patientID' and condition ='$cond' and pdate ='$delete'";
		$sDelete = oci_parse($c, $queryDelete);
		oci_execute($sDelete);
		}
		
		//Handles Additions to Family History
		if($aCond != null && $aFname != null && $aRelation != null && $pname != null){
			$queryInsert = "insert into has_fHistory values
				($patientID, '$pname','$aFname','$aRelation','$aCond')";
			$sInsert = oci_parse($c, $queryInsert);
			oci_execute($sInsert);
		}
		
		//Handles Deletions to Family History
		if ($dCond != null && $dFname != null){
		$queryDelete = "delete from has_fHistory 
						where pid = '$patientID' and condition ='$dCond' and fname ='$dFname'";
		$sDelete = oci_parse($c, $queryDelete);
		oci_execute($sDelete);
		}
		
		//Handles Updates to Medical Record
		if($allergies != null && $emercontact != null){
			$queryUpdate = "update has_medicalrecords
							set allergies ='$allergies', emercontacts='$emercontact'
							where pid = $patientID";
			$sUpdate = oci_parse($c, $queryUpdate);
			oci_execute($sUpdate);
		}
		
		//Handles Addition to Medical Record
		if($addallergies!= null && $addemercontact !=null && pname!= null){
		$queryAdd = "insert into has_medicalrecords values
					($patientID, '$pname', '$addallergies', '$addemercontact')";
		$sAdd = oci_parse($c, $queryAdd);
		oci_execute($sAdd);
		}
		
		
		//Queries for has_medicalrecord, has_fHistory, contains_pHistory
		// based on pID
		$queryMedRec = "select *
						from has_medicalrecords
						where pid = $patientID";
		$sMedRec = oci_parse($c, $queryMedRec);
		oci_execute($sMedRec);
		
		$rowsMedRec = oci_fetch_all($sMedRec, $resultMedRec, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		$patient = $resultMedRec[0]['PNAME'];
		
		$queryFHistory = "select *
						from has_fhistory
						where pid = $patientID";
		$sFHistory = oci_parse($c, $queryFHistory);
		oci_execute($sFHistory);
		
		$rowsFHistory = oci_fetch_all($sFHistory, $resultFHistory, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		
		
		$queryPHistory = "select *
						from contains_phistory
						where pid = $patientID";
		$sPHistory = oci_parse($c, $queryPHistory);
		oci_execute($sPHistory);
		$rowsPHistory = oci_fetch_all($sPHistory, $resultPHistory, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		
		
		
		
		
		oci_close($c);
	} else {
		$err = oci_error();
		echo "Oracle Connect Error " . $err['message'];
	}
}

// Helper Functions
function buildMedRecList($num, $arr, $patient, $patientID) {
if($num == 0){
echo '<table class = "med">';
echo '<tr>';
echo '<td>Medical Record is not Avaliable</td>';
echo '</tr>';
echo '<tr>';
	  echo '<td>';
                 echo '<form method = "post" action = appAddMedicalRecords.php>';
                  echo '<button type = "submit" name = "addpatient" value ="'. $patientID .'">Add</button>';
             echo '</form>';
           echo '</td>';
	echo '</tr>';
echo '</table>';
}else{

	echo '<table class = "med">';
	echo '<tr>';
	echo '<td>Medical Record for ' . $patient . '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Allergies</th>';
	echo '<th>Emergency Contact</th>';
	echo '</tr>';
	for($i = 0; $i < $num; $i++) {
		echo '<tr>';
		echo '<td>'. $arr[$i]['ALLERGIES'] .'</td>';
		echo '<td>'. $arr[$i]['EMERCONTACTS'] .'</td>';
		echo '</tr>';
	}
	echo '<tr>';
	  echo '<td>';
                 echo '<form method = "post" action = appChangeMedicalRecords.php>';
                  echo '<button type = "submit" name = "addpatient" value ="'. $patientID .'">Change</button>';
             echo '</form>';
           echo '</td>';
	echo '</tr>';
	echo '</table>';
	}
}

function buildFHistoryList($num, $arr, $patient, $patientID){
if ($num == 0){
echo '<table class = "med">';
	echo '<tr>';
	echo '<td>Add Family History for ' . $patient . '</td>';
	echo '</tr>';
		echo '<tr>';
	echo '<th>Enter Family Member</th>';
	echo '<th>Enter Relation to Patient</th>';
	echo '<th>Enter Condition</th>';
	echo '</tr>';
}else{
	echo '<table class = "med">';
	echo '<tr>';
	echo '<td>Family History for ' . $patient . '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Family Member</th>';
	echo '<th>Relation to Patient</th>';
	echo '<th>Condition</th>';
	echo '</tr>';
	for($i = 0; $i < $num; $i++) {
		echo '<tr>';
		echo '<td>'. $arr[$i]['FNAME'] .'</td>';
		echo '<td>'. $arr[$i]['RELATION'] .'</td>';
		echo '<td>'. $arr[$i]['CONDITION'] .'</td>';
		echo '<td>';
		 echo '<form method = "post" action = appMedicalRecords.php>';
		 echo '<INPUT TYPE ="hidden" NAME ="pname" value ="'.$patient.'">';
		 echo '<INPUT TYPE = "hidden" NAME = "addpatient" VALUE ="'. $patientID .'">';
		 echo '<INPUT TYPE = "hidden" NAME = "dCond" VALUE = "' .$arr[$i]['CONDITION'] . '">';
		 echo '<button type = "submit" name = "dFname" value = "'.$arr[$i]['FNAME'].'">Delete</button>';
		 echo '</form>';
		 echo '</td>';
		echo '</tr>';
	}
	}
	echo '<tr>';
	
	echo '<form method = "post" action = appMedicalRecords.php>';
	echo '<INPUT TYPE ="hidden" NAME ="pname" value ="'.$patient.'">';
	echo '<td><INPUT TYPE="text" NAME="aFname" SIZE="55" ></td>';
	echo '<td><INPUT TYPE="text" NAME="aRelation" SIZE="20" ></td>';
	echo '<td><INPUT TYPE="text" NAME="aCond" SIZE="20" ></td>';
	echo '<td><button type = "submit" name = "addpatient" value ="'. $patientID .'">Add</button></td>';
				
	echo '</tr>';
	
	// For Additions to Family History
	echo '</table>';

}

function buildPHistoryList($num, $arr, $patient,$patientID, $today){
if ($num == 0){
echo '<table class = "med">';
	echo '<tr>';
	echo '<td>Add Patient History for ' . $patient . '</td>';
	echo '</tr>';
		echo '<tr>';
	echo '<th>Enter Condition</th>';
	echo '<th>Enter Medication</th>';
	echo '<th>Date</th>';
	echo '</tr>';
}else{
	echo '<table class = "med">';
	echo '<tr>';
	echo '<td>Patient History for ' . $patient . '</td>';
	echo '</tr>';
	echo '<tr>';
        echo '<th>Condition</th>';
        echo '<th>Medication</th>';
        echo '<th>Date</th>';
        echo '</tr>';
	for($i = 0; $i < $num; $i++) {
		echo '<tr>';
		echo '<td>'. $arr[$i]['CONDITION'] .'</td>';
		echo '<td>'. $arr[$i]['MEDICATION'] .'</td>';
		echo '<td>'. $arr[$i]['PDATE'] .'</td>';
		 echo '<form method = "post" action = appMedicalRecords.php>';
		 echo '<INPUT TYPE = "hidden" NAME = "addpatient" VALUE ="'. $patientID .'">';
		 echo '<INPUT TYPE = "hidden" NAME = "cond" VALUE = "' .$arr[$i]['CONDITION'] . '">';
		 echo '<td><button type = "submit" name = "delete" value = "'.$arr[$i]['PDATE'].'">Delete</button></td>';
		 echo '</form>';
		echo '</tr>';
	}
	}
	// For additions to Patient History
	echo '<tr>';
	
	echo '<form method = "post" action = appMedicalRecords.php>';
	echo '<INPUT TYPE ="hidden" NAME ="pname" value ="'.$patient.'">';
	echo '<td><INPUT TYPE="text" NAME="condition" SIZE="40" ></td>';
	echo '<td><INPUT TYPE="text" NAME="medication" SIZE="40" ></td>';
	echo "<td>$today</td>";
	echo '<td><button type = "submit" name = "addpatient" value ="'. $patientID .'">Add</button></td>';
				
	echo '</tr>';
	echo '</table>';

}

?>

<!--Design the page below-->
<html>
<head>
	<title>Medical Records</title>
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
	<div id = "Medical Record">
		<?php 
		buildMedRecList($rowsMedRec, $resultMedRec, $patient, $patientID);
		?>
		

	</div>
	<div id = "Family History">
	
	<?php 
		if ($rowsMedRec >0)
		buildFHistoryList($rowsFHistory, $resultFHistory, $patient, $patientID);
		//echo "<br> $aFname , $aCond, $aRelation";
		//echo $queryFHistory;
		?>	
		
	
	</div>
	<div id = "Patient History">
		<?php
		if ($rowsMedRec >0)
		buildPHistoryList($rowsPHistory, $resultPHistory, $patient, $patientID, $today);
	
	?>
	</div>
	
	
	
	
</div>
	<div id = "footer"></div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}

$student = $_SESSION['user'];
$sid = $student['SID'];
$name = $student['name'];
$cid = $student['CID'];

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'coursework_db';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drafts submitted by this student
$res = $conn->query("SELECT COUNT(*) AS total FROM Submit WHERE SID = $sid");
$submitted = $res->fetch_assoc()['total'] ?? 0;

// Drafts viewed by teacher
$res = $conn->query("SELECT COUNT(*) AS total FROM Submit WHERE SID = $sid AND status = 'Viewed'");
$viewed = $res->fetch_assoc()['total'] ?? 0;

// Drafts not yet viewed
$res = $conn->query("SELECT COUNT(*) AS total FROM Submit WHERE SID = $sid AND status = 'Pending'");
$unviewed = $res->fetch_assoc()['total'] ?? 0;

// Total tasks assigned to this class
$res = $conn->query("SELECT COUNT(*) AS total FROM Task WHERE TID IN (SELECT TID FROM Class WHERE CID = $cid)");
$total_tasks = $res->fetch_assoc()['total'] ?? 0;

// Student's checked tasks
$res = $conn->query("SELECT COUNT(*) AS total FROM Student_TK WHERE SID = $sid AND is_checked = 1");
$checked = $res->fetch_assoc()['total'] ?? 0;

$progress = $total_tasks > 0 ? round(($checked / $total_tasks) * 100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Dashboard</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}</style>
</head>
<body class="w3-light-grey">

<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar"><br>
  <div class="w3-container w3-row">
    <span>Welcome, <strong><?= htmlspecialchars($name) ?></strong></span><br>
  </div>
  <hr>
  <div class="w3-container"><h5>Menu</h5></div>
  <div class="w3-bar-block">
    <a href="#" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-eye fa-fw"></i> Overview</a>
    <a href="schecklist.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
    <a href="sdrafts.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-upload fa-fw"></i> Drafts</a>
    <a href="../php/logout.php" 
    class="w3-bar-item w3-button w3-padding">
   <i class="fa fa-sign-out fa-fw"></i> Logout
   </a>
  </div>
</nav>

<div class="w3-overlay w3-hide-large w3-animate-opacity"
     onclick="w3_close()" style="cursor:pointer" title="close menu" id="myOverlay"></div>

<div class="w3-main" style="margin-left:300px;margin-top:43px;">
  <header class="w3-container" style="padding-top:22px">
    <h5><b><i class="fa fa-dashboard"></i> My Dashboard</b></h5>
  </header>

  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-quarter">
     <div class="w3-container w3-blue-grey w3-padding-16">
        <div class="w3-left"><i class="fa fa-eye-slash w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $unviewed ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Unviewed Drafts</h4>
      </div>
    </div>
    <div class="w3-quarter">
      <div class="w3-container w3-blue w3-padding-16">
        <div class="w3-left"><i class="fa fa-upload w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $submitted ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Submitted Drafts</h4>
      </div>
    </div>
    <div class="w3-quarter">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-eye w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $viewed ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Viewed Drafts</h4>
      </div>
    </div>
    <div class="w3-quarter">
      <div class="w3-container w3-orange w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-check-square-o w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $checked ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Tasks Completed</h4>
      </div>
    </div>
  </div>

  <hr>
  <div class="w3-container">
    <h5>My Progress</h5>
    <div class="w3-grey">
      <div class="w3-container w3-center w3-padding w3-green" style="width:<?= $progress ?>%">
        <?= $progress ?>%
      </div>
    </div>
  </div>

</div>

<script>
function w3_open() {
  document.getElementById("mySidebar").style.display = "block";
  document.getElementById("myOverlay").style.display = "block";
}
function w3_close() {
  document.getElementById("mySidebar").style.display = "none";
  document.getElementById("myOverlay").style.display = "none";
}
</script>

</body>
</html>

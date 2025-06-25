<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$teacher = $_SESSION['user'];
$teacher_name = $teacher['name'];
$tid = $teacher['TID'];

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'coursework_db';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Unviewed drafts based on Submit.status = 'Pending'
$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM Submit
    JOIN Student ON Submit.SID = Student.SID
    JOIN Class   ON Student.CID = Class.CID
    WHERE Class.TID = $tid AND Submit.status = 'Pending'
");
$unviewed = $res->fetch_assoc()['total'] ?? 0;

// 2. Total tasks assigned by this teacher
$res = $conn->query("SELECT COUNT(*) AS total FROM Task WHERE TID = $tid");
$tasks = $res->fetch_assoc()['total'] ?? 0;

// 3. Viewed drafts based on Submit.status = 'Viewed'
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Submit
    JOIN Student ON Submit.SID = Student.SID
    JOIN Class   ON Student.CID = Class.CID
    WHERE Class.TID = $tid AND Submit.status = 'Viewed'
");
$viewed = $res->fetch_assoc()['total'] ?? 0;

// 4. Submitted drafts = total Submit entries for this teacher’s classes
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM Submit
    JOIN Student ON Submit.SID = Student.SID
    JOIN Class   ON Student.CID = Class.CID
    WHERE Class.TID = $tid
");
$submitted = $res->fetch_assoc()['total'] ?? 0;

// 5. Student progress = (checked tasks) / (total tasks) for this teacher
$res = $conn->query("
    SELECT COUNT(*) AS checked 
    FROM Student_TK 
    JOIN Task ON Student_TK.TKID = Task.TKID
    WHERE Task.TID = $tid AND Student_TK.is_checked = 1
");
$checked = $res->fetch_assoc()['checked'] ?? 0;

$res = $conn->query("
    SELECT COUNT(*) AS total 
    FROM Student_TK 
    JOIN Task ON Student_TK.TKID = Task.TKID
    WHERE Task.TID = $tid
");
$total_ck = $res->fetch_assoc()['total'] ?? 0;

$progress = $total_ck > 0 ? round(($checked / $total_ck) * 100) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Teacher Dashboard</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}</style>
</head>
<body class="w3-light-grey">

<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <button class="w3-bar-item w3-button w3-hide-large w3-hover-none w3-hover-text-light-grey"
          onclick="w3_open();"><i class="fa fa-bars"></i> Menu</button>
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar"><br>
  <div class="w3-container w3-row">
    <div class="w3-col s8 w3-bar">
      <span>Welcome, <strong><?= htmlspecialchars($teacher_name) ?></strong></span><br>
    </div>
  </div><hr>
  <div class="w3-container"><h5>Menu</h5></div>
  <div class="w3-bar-block">
    <a href="#" class="w3-bar-item w3-button w3-padding w3-blue">
      <i class="fa fa-eye fa-fw"></i> Overview
    </a>
    <a href="#" class="w3-bar-item w3-button w3-padding">
      <i class="fa fa-check-square-o fa-fw"></i> Checklist
    </a>
    <a href="#" class="w3-bar-item w3-button w3-padding">
      <i class="fa fa-upload fa-fw"></i> Drafts
    </a>
    <a href="#" class="w3-bar-item w3-button w3-padding">
      <i class="fa fa-users fa-fw"></i> Students
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
    <!-- Unviewed drafts -->
    <div class="w3-quarter">
      <div class="w3-container w3-red w3-padding-16">
        <div class="w3-left"><i class="fa fa-eye-slash w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $unviewed ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Unviewed Drafts</h4>
      </div>
    </div>
    <!-- Tasks -->
    <div class="w3-quarter">
      <div class="w3-container w3-blue w3-padding-16">
        <div class="w3-left"><i class="fa fa-tasks w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $tasks ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Tasks</h4>
      </div>
    </div>
    <!-- Viewed drafts -->
    <div class="w3-quarter">
      <div class="w3-container w3-teal w3-padding-16">
        <div class="w3-left"><i class="fa fa-eye w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $viewed ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Viewed Drafts</h4>
      </div>
    </div>
    <!-- Submitted drafts -->
    <div class="w3-quarter">
      <div class="w3-container w3-orange w3-text-white w3-padding-16">
        <div class="w3-left"><i class="fa fa-upload w3-xxxlarge"></i></div>
        <div class="w3-right"><h3><?= $submitted ?></h3></div>
        <div class="w3-clear"></div>
        <h4>Submitted Drafts</h4>
      </div>
    </div>
  </div>

  <hr>
  <div class="w3-container">
    <h5>Students Progress</h5>
    <div class="w3-grey">
      <div class="w3-container w3-center w3-padding w3-green"
           style="width:<?= $progress ?>%"><?= $progress ?>%</div>
    </div>
  </div>

  <footer class="w3-container w3-padding-16 w3-light-grey">
    <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a></p>
  </footer>
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

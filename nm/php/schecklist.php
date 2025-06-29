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
$db = 'coursework_db';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle checkbox submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_ids'])) {
    foreach ($_POST['task_ids'] as $tkid => $checked) {
        $val = $checked === '1' ? 1 : 0;

        // Check if row exists
        $res = $conn->query("SELECT * FROM Student_TK WHERE SID = $sid AND TKID = $tkid");
        if ($res->num_rows > 0) {
            $conn->query("UPDATE Student_TK SET is_checked = $val WHERE SID = $sid AND TKID = $tkid");
        } else {
            $conn->query("INSERT INTO Student_TK (SID, TKID, is_checked) VALUES ($sid, $tkid, $val)");
        }
    }
    echo "<script>location.href='schecklist.php';</script>";
    exit;
}

// Load tasks assigned to student's class
$res = $conn->query("
    SELECT Task.TKID, Task.name, Task.TK_desc, IFNULL(Student_TK.is_checked, 0) as is_checked
    FROM Task
    JOIN Class ON Class.TID = Task.TID
    JOIN Student ON Student.CID = Class.CID
    LEFT JOIN Student_TK ON Student_TK.TKID = Task.TKID AND Student_TK.SID = Student.SID
    WHERE Student.SID = $sid
    ORDER BY Task.TKID ASC
");
$tasks = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Checklist - Student</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
  </style>
</head>
<body class="w3-light-grey">

<!-- Top bar -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<!-- Sidebar -->
<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar">
  <br>
  <div class="w3-container w3-row">
    <span>Welcome, <strong><?= htmlspecialchars($name) ?></strong></span><br>
  </div>
  <hr>
  <div class="w3-container">
    <h5>Menu</h5>
  </div>
  <div class="w3-bar-block">
    <a href="shome.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
    <a href="schecklist.php" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
    <a href="sdrafts.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-upload fa-fw"></i> Drafts</a>
    <a href="../php/logout.php" 
    class="w3-bar-item w3-button w3-padding">
   <i class="fa fa-sign-out fa-fw"></i> Logout
   </a>
   
    <div class="w3-bar w3-card w3-left-align w3-large" style="background-color: #003366; color: white;">
   


 

</div>

  </div>
</nav>

<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity"
     onclick="w3_close()" style="cursor:pointer" title="close menu" id="myOverlay"></div>

<!-- Page content -->
<div class="w3-main" style="margin-left:300px;margin-top:43px;">
  <div class="w3-container" style="padding:22px">
    <h3><i class="fa fa-check-square-o"></i> My Checklist</h3>

    <form method="post">
      <table class="w3-table-all w3-hoverable w3-white">
        <tr class="w3-light-grey">
          <th>Task</th>
          <th>Description</th>
          <th>Done</th>
        </tr>
        <?php foreach ($tasks as $task): ?>
          <tr>
            <td><?= htmlspecialchars($task['name']) ?></td>
            <td><?= htmlspecialchars($task['TK_desc']) ?></td>
            <td>
              <input type="hidden" name="task_ids[<?= $task['TKID'] ?>]" value="0">
              <input type="checkbox" name="task_ids[<?= $task['TKID'] ?>]" value="1" <?= $task['is_checked'] ? 'checked' : '' ?>>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
      <br>
      <button type="submit" class="w3-button w3-blue">Save Checklist</button>
    </form>
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

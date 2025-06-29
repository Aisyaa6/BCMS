<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$teacher = $_SESSION['user'];
$tid = $teacher['TID'];
$name = htmlspecialchars($teacher['name']);

$conn = new mysqli("localhost", "root", "", "coursework_db", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch classes under this teacher
$class_q = $conn->query("SELECT * FROM Class WHERE TID = $tid");
$classes = [];
while ($c = $class_q->fetch_assoc()) {
    $cid = $c['CID'];
    $class_name = $c['name'];

    // Fetch students in this class
    $student_q = $conn->query("SELECT * FROM Student WHERE CID = $cid");
    $students = [];

    while ($s = $student_q->fetch_assoc()) {
        $sid = $s['SID'];

        // Count total drafts
        $draft_q = $conn->query("SELECT COUNT(*) AS total FROM Submit WHERE SID = $sid");
        $drafts = $draft_q->fetch_assoc()['total'] ?? 0;

        // Count ticked tasks
        $tick_q = $conn->query("SELECT COUNT(*) AS ticked FROM Student_TK WHERE SID = $sid AND is_checked = 1");
        $ticked = $tick_q->fetch_assoc()['ticked'] ?? 0;

        // Count total tasks
        $total_q = $conn->query("SELECT COUNT(*) AS total FROM Student_TK WHERE SID = $sid");
        $total = $total_q->fetch_assoc()['total'] ?? 0;

        $progress = $total > 0 ? round(($ticked / $total) * 100) : 0;

        $students[] = [
            'name' => $s['name'],
            'email' => $s['email'],
            'drafts' => $drafts,
            'progress' => $progress
        ];
    }

    $classes[] = ['name' => $class_name, 'students' => $students];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Students Overview</title>
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

<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar">
    <br>
    <div class="w3-container w3-row">
        <span>Welcome, <strong><?= $name ?></strong></span><br>
    </div>
    <hr>
    <div class="w3-container"><h5>Menu</h5></div>
    <div class="w3-bar-block">
        <a href="ahome.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
        <a href="achecklist.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
        <a href="adrafts.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-upload fa-fw"></i> Drafts</a>
        <a href="astudents.php" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-users fa-fw"></i> Students</a>
    </div>
</nav>

<div class="w3-overlay w3-hide-large w3-animate-opacity"
     onclick="w3_close()" style="cursor:pointer" title="close menu" id="myOverlay"></div>

<div class="w3-main" style="margin-left:300px;margin-top:43px;">
    <header class="w3-container" style="padding-top:22px">
        <h5><b><i class="fa fa-users"></i> Students by Class</b></h5>
    </header>

    <div class="w3-container">
        <?php foreach ($classes as $class): ?>
            <div class="w3-card w3-white w3-margin-bottom w3-padding">
                <h4><?= htmlspecialchars($class['name']) ?></h4>
                <table class="w3-table-all w3-hoverable">
                    <tr class="w3-light-grey">
                        <th>Name</th>
                        <th>Email</th>
                        <th>Drafts Submitted</th>
                        <th>Checklist Progress</th>
                    </tr>
                    <?php foreach ($class['students'] as $stu): ?>
                        <tr>
                            <td><?= htmlspecialchars($stu['name']) ?></td>
                            <td><?= htmlspecialchars($stu['email']) ?></td>
                            <td><?= $stu['drafts'] ?></td>
                            <td>
                                <div class="w3-light-grey w3-round-xlarge">
                                    <div class="w3-container w3-green w3-round-xlarge"
                                         style="width:<?= $stu['progress'] ?>%">
                                        <?= $stu['progress'] ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

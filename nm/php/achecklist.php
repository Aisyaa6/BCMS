<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$teacher = $_SESSION['user'];
$teacher_id = $teacher['TID'];

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'coursework_db';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    $tkid = intval($_POST['delete_task_id']);

    // Confirm task belongs to teacher
    $check = $conn->prepare("SELECT TID FROM Task WHERE TKID = ?");
    $check->bind_param("i", $tkid);
    $check->execute();
    $result = $check->get_result();
    $owner = $result->fetch_assoc();

    if ($owner && $owner['TID'] == $teacher_id) {
        $conn->query("DELETE FROM Student_TK WHERE TKID = $tkid");
        $conn->query("DELETE FROM Task WHERE TKID = $tkid");
        echo "<script>alert('🗑️ Task deleted.'); window.location.href='achecklist.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Unauthorized or invalid task.');</script>";
    }
}

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_name'])) {
    $task_name = $_POST['task_name'];
    $task_desc = $_POST['task_desc'];

    $stmt = $conn->prepare("INSERT INTO Task (name, TK_desc, TID) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $task_name, $task_desc, $teacher_id);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Task created successfully.'); window.location.href='achecklist.php';</script>";
        exit;
    } else {
        echo "❌ Error creating task: " . $stmt->error;
    }
    $stmt->close();
}

// Load tasks
$tasks = $conn->query("SELECT * FROM Task WHERE TID = $teacher_id ORDER BY TKID DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checklist - Teacher</title>
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

<!-- Top Bar -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<!-- Sidebar -->
<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar">
  <br>
  <div class="w3-container w3-row">
    <span>Welcome, <strong><?= htmlspecialchars($teacher['name']) ?></strong></span><br>
  </div>
  <hr>
  <div class="w3-container">
    <h5>Menu</h5>
  </div>
  <div class="w3-bar-block">
    <a href="ahome.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
    <a href="achecklist.php" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
    <a href="adrafts.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-upload fa-fw"></i> Drafts</a>
    <a href="astudents.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i> Students</a>
    <a href="../php/logout.php" 
    class="w3-bar-item w3-button w3-padding">
   <i class="fa fa-sign-out fa-fw"></i> Logout
   </a>
  </div>
</nav>

<!-- Page Content -->
<div class="w3-main" style="margin-left:300px;margin-top:43px;">

  <div class="w3-container" style="padding:22px">
    <h3><i class="fa fa-check-square-o"></i> Create New Task</h3>

    <form action="" method="post" class="w3-container w3-card w3-padding w3-white w3-margin-bottom">
        <label><b>Task Name</b></label>
        <input class="w3-input w3-border w3-margin-bottom" type="text" name="task_name" required>

        <label><b>Description</b></label>
        <textarea class="w3-input w3-border w3-margin-bottom" name="task_desc" required></textarea>

        <button type="submit" class="w3-button w3-blue">Create Task</button>
    </form>

    <hr>
    <h4>Checklist</h4>
    <table class="w3-table-all w3-hoverable w3-white">
        <tr class="w3-light-grey">
            <th>ID</th>
            <th>Task Name</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $tasks->fetch_assoc()): ?>
            <tr>
                <td><?= $row['TKID'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['TK_desc']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Delete this task?');">
                        <input type="hidden" name="delete_task_id" value="<?= $row['TKID'] ?>">
                        <button type="submit" class="w3-button w3-small w3-red">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
  </div>

</div>

</body>
</html>

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

        // Fetch drafts for this student
        $draft_q = $conn->query("SELECT * FROM Submit WHERE SID = $sid");
        $drafts = [];
        while ($d = $draft_q->fetch_assoc()) {
            $drafts[] = [
                'filename' => htmlspecialchars($d['draft_file']), // FIXED: use draft_file column
                'file_path' => "../uploads/" . htmlspecialchars($d['draft_file'])
            ];
        }

        // Count tasks progress
        $tick_q = $conn->query("SELECT COUNT(*) AS ticked FROM Student_TK WHERE SID = $sid AND is_checked = 1");
        $ticked = $tick_q->fetch_assoc()['ticked'] ?? 0;

        $total_q = $conn->query("SELECT COUNT(*) AS total FROM Student_TK WHERE SID = $sid");
        $total = $total_q->fetch_assoc()['total'] ?? 0;

        $progress = $total > 0 ? round(($ticked / $total) * 100) : 0;

        $students[] = [
            'sid' => $sid,
            'name' => htmlspecialchars($s['name']),
            'email' => htmlspecialchars($s['email']),
            'drafts' => $drafts,
            'progress' => $progress
        ];
    }

    $classes[] = ['cid' => $cid, 'name' => $class_name, 'students' => $students];
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
    <style>
        html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
        .w3-modal-content {max-width: 600px;}
        .draft-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .draft-filename {
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body class="w3-light-grey">

<!-- Student Modal -->
<div id="studentModal" class="w3-modal">
  <div class="w3-modal-content w3-card-4">
    <header class="w3-container w3-blue">
      <span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span>
      <h3 id="modalName"></h3>
    </header>
    <div class="w3-container">
      <p><b>Email:</b> <span id="modalEmail"></span></p>
      <p><b>Progress:</b></p>
      <div class="w3-light-grey w3-round">
        <div id="modalProgress" class="w3-container w3-green w3-round" style="width:0%">0%</div>
      </div>
      <hr>
      <p><b>All Drafts Uploaded:</b> <span id="modalDraftsCount"></span></p>
      <div id="modalDraftsList"></div>
    </div>
  </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="w3-modal">
  <div class="w3-modal-content w3-card-4">
    <header class="w3-container w3-red">
      <span onclick="closePasswordModal()" class="w3-button w3-display-topright">&times;</span>
      <h3>Confirm Action</h3>
    </header>
    <div class="w3-container">
      <p>Please enter your password to confirm:</p>
      <input class="w3-input w3-border" type="password" id="confirmPassword" placeholder="Password">
      <button class="w3-button w3-red w3-margin-top" onclick="confirmAction()">Confirm</button>
    </div>
  </div>
</div>

<!-- Top Bar -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<!-- Sidebar -->
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
    <a href="../php/logout.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
  </div>
</nav>

<!-- Main Content -->
<div class="w3-main" style="margin-left:300px;margin-top:43px;">
  <header class="w3-container" style="padding-top:22px">
    <h5><b><i class="fa fa-users"></i> Students by Class</b></h5>
  </header>

  <div class="w3-container">
    <?php foreach ($classes as $class): ?>
      <div class="w3-card w3-white w3-margin-bottom w3-padding">
        <div class="w3-row">
          <div class="w3-col s9">
            <h4><?= htmlspecialchars($class['name']) ?></h4>
          </div>
          <div class="w3-col s3 w3-right-align">
            <button class="w3-button w3-red" onclick="showPasswordModal('clear', <?= $class['cid'] ?>)">Clear All</button>
          </div>
        </div>

        <table class="w3-table-all w3-hoverable">
          <tr class="w3-light-grey">
            <th>Name</th>
            <th>Email</th>
            <th>Progress</th>
            <th>Action</th>
          </tr>
          <?php foreach ($class['students'] as $stu): ?>
            <tr>
              <td><?= $stu['name'] ?></td>
              <td><?= $stu['email'] ?></td>
              <td>
                <div class="w3-light-grey w3-round">
                  <div class="w3-green w3-round" style="width:<?= $stu['progress'] ?>%">
                    <?= $stu['progress'] ?>%
                  </div>
                </div>
              </td>
              <td>
                <button class="w3-button w3-blue w3-small" onclick='openModal(<?= json_encode($stu) ?>)'>View</button>
                <button class="w3-button w3-red w3-small" onclick="showPasswordModal('remove', <?= $stu['sid'] ?>)">Remove</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
let actionType = '';
let targetId = 0;

function openModal(student) {
  document.getElementById('modalName').innerText = student.name;
  document.getElementById('modalEmail').innerText = student.email;
  document.getElementById('modalProgress').style.width = student.progress + '%';
  document.getElementById('modalProgress').innerText = student.progress + '%';

  document.getElementById('modalDraftsCount').innerText = student.drafts.length;

  let draftHTML = '';
  if (student.drafts.length > 0) {
    student.drafts.forEach(d => {
      draftHTML += `
        <div class="draft-row">
          <span class="draft-filename">${d.filename}</span>
          <a href="${d.file_path}" download class="w3-button w3-white w3-border w3-round">
            <i class="fa fa-download"></i>
          </a>
        </div>`;
    });
  } else {
    draftHTML = '<p class="w3-text-grey">No drafts submitted.</p>';
  }
  document.getElementById('modalDraftsList').innerHTML = draftHTML;

  document.getElementById('studentModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('studentModal').style.display = 'none';
}

function showPasswordModal(type, id) {
  actionType = type;
  targetId = id;
  document.getElementById('confirmPassword').value = '';
  document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
  document.getElementById('passwordModal').style.display = 'none';
}

function confirmAction() {
  const password = document.getElementById('confirmPassword').value;

  if (actionType === 'remove') {
    fetch('../php/remove_student.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({sid: targetId, password: password})
    }).then(res => location.reload());
  } else if (actionType === 'clear') {
    fetch('../php/clear_class.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({cid: targetId, password: password})
    }).then(res => location.reload());
  }

  closePasswordModal();
}
</script>
</body>
</html>

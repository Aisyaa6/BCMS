<?php
// Handle deletion requests at the top
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_action'])) {
    session_start();
    if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
        die("Unauthorized");
    }

    $teacher = $_SESSION['user'];
    $tid = $teacher['TID'];

    $conn = new mysqli("localhost", "root", "", "coursework_db", 3306);
    if ($conn->connect_error) {
        die("Connection failed");
    }

    $password = $_POST['password'] ?? '';
    $action = $_POST['delete_action'];

    // Check MD5 password
    $check = $conn->prepare("SELECT password FROM Teacher WHERE TID = ?");
    $check->bind_param("i", $tid);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo "INVALID_PASSWORD";
        exit;
    }

    $row = $result->fetch_assoc();
    $stored_password = $row['password'];

    if (md5($password) !== $stored_password) {
        echo "INVALID_PASSWORD";
        exit;
    }

    // Delete logic
    if ($action === 'student') {
        $sid = intval($_POST['target_id']);
        $conn->query("DELETE FROM Submit WHERE SID = $sid");
        $conn->query("DELETE FROM Student_TK WHERE SID = $sid");
        $conn->query("DELETE FROM Student WHERE SID = $sid");
        echo "STUDENT_DELETED";
    } elseif ($action === 'class') {
        $cid = intval($_POST['target_id']);
        $conn->query("DELETE s FROM Submit s JOIN Student st ON s.SID = st.SID WHERE st.CID = $cid");
        $conn->query("DELETE sttk FROM Student_TK sttk JOIN Student st ON sttk.SID = st.SID WHERE st.CID = $cid");
        $conn->query("DELETE FROM Student WHERE CID = $cid");
        echo "CLASS_CLEARED";
    }

    exit;
}

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

// Handle sort option
$sort = $_GET['sort'] ?? 'name_asc';

function sort_students(&$students, $sort) {
    if ($sort === 'progress_asc') {
        usort($students, fn($a, $b) => $a['progress'] <=> $b['progress']);
    } elseif ($sort === 'progress_desc') {
        usort($students, fn($a, $b) => $b['progress'] <=> $a['progress']);
    } elseif ($sort === 'name_desc') {
        usort($students, fn($a, $b) => strcasecmp($b['name'], $a['name'])); // Case-insensitive
    } else { // Default: name_asc
        usort($students, fn($a, $b) => strcasecmp($a['name'], $b['name'])); // Case-insensitive
    }
}

// Fetch classes under this teacher
$class_q = $conn->query("SELECT * FROM Class WHERE TID = $tid");
$classes = [];
while ($c = $class_q->fetch_assoc()) {
    $cid = $c['CID'];
    $class_name = $c['name'];

    // Fetch students in this class
    $students = [];
    $student_q = $conn->query("SELECT * FROM Student WHERE CID = $cid");

    while ($s = $student_q->fetch_assoc()) {
        $sid = $s['SID'];

        // Fetch progress
        $tick_q = $conn->query("SELECT COUNT(*) AS ticked FROM Student_TK WHERE SID = $sid AND is_checked = 1");
        $ticked = $tick_q->fetch_assoc()['ticked'] ?? 0;

        $total_q = $conn->query("SELECT COUNT(*) AS total FROM Student_TK WHERE SID = $sid");
        $total = $total_q->fetch_assoc()['total'] ?? 0;

        $progress = $total > 0 ? round(($ticked / $total) * 100) : 0;

        // Fetch drafts
        $draft_q = $conn->query("SELECT * FROM Submit WHERE SID = $sid");
        $drafts = [];
        while ($d = $draft_q->fetch_assoc()) {
            $drafts[] = [
                'filename' => htmlspecialchars($d['draft_file']),
                'file_path' => "../uploads/" . htmlspecialchars($d['draft_file'])
            ];
        }

        $students[] = [
            'sid' => $sid,
            'name' => htmlspecialchars($s['name']),
            'email' => htmlspecialchars($s['email']),
            'progress' => $progress,
            'drafts' => $drafts
        ];
    }

    // Sort students in this class
    sort_students($students, $sort);

    $classes[] = [
        'cid' => $cid,
        'name' => $class_name,
        'students' => $students
    ];
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
        .progress-bar {height: 20px;}
    </style>
</head>
<body class="w3-light-grey">

<!-- Student Info Modal -->
<div id="studentModal" class="w3-modal">
  <div class="w3-modal-content w3-card-4">
    <header class="w3-container w3-blue">
      <span onclick="closeStudentModal()" class="w3-button w3-display-topright">&times;</span>
      <h3 id="modalStudentName"></h3>
    </header>
    <div class="w3-container">
      <p><b>Email:</b> <span id="modalStudentEmail"></span></p>
      <p><b>Progress:</b></p>
      <div class="w3-light-grey w3-round progress-bar">
        <div id="modalStudentProgress" class="w3-green w3-round" style="width:0%">0%</div>
      </div>
      <p><b>Drafts:</b></p>
      <div id="modalStudentDrafts"></div>
      <hr>
      <button class="w3-button w3-red" onclick="promptDelete('student', currentStudentId)">Delete Student</button>
    </div>
  </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="w3-modal">
  <div class="w3-modal-content w3-card-4">
    <header class="w3-container w3-red">
      <span onclick="closePasswordModal()" class="w3-button w3-display-topright">&times;</span>
      <h3>Confirm Deletion</h3>
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
    <div class="w3-container" style="padding-top:22px">
        <h5><b><i class="fa fa-users"></i> Students by Class</b></h5>

        <!-- Sort Dropdown -->
        <form method="get" class="w3-margin-bottom">
            <label><b>Sort students by:</b></label>
            <select name="sort" class="w3-select w3-border" style="width:auto; display:inline-block" onchange="this.form.submit()">
                <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name (A-Z)</option>
                <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Name (Z-A)</option>
                <option value="progress_asc" <?= $sort==='progress_asc'?'selected':'' ?>>Progress (Low → High)</option>
                <option value="progress_desc" <?= $sort==='progress_desc'?'selected':'' ?>>Progress (High → Low)</option>
            </select>
        </form>

        <?php foreach ($classes as $class): ?>
            <div class="w3-card w3-white w3-margin-bottom w3-padding">
                <div class="w3-row">
                    <div class="w3-col s9">
                        <h4><?= htmlspecialchars($class['name']) ?></h4>
                    </div>
                    <div class="w3-col s3 w3-right-align">
                        <button class="w3-button w3-red w3-small" onclick="promptDelete('class', <?= $class['cid'] ?>)">Delete All Students</button>
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
                                <div class="w3-light-grey w3-round progress-bar">
                                    <div class="w3-green w3-round" style="width:<?= $stu['progress'] ?>%">
                                        <?= $stu['progress'] ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button class="w3-button w3-blue w3-small" onclick='viewStudent(<?= json_encode($stu) ?>)'>View Info</button>
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
let currentStudentId = 0;

function viewStudent(student) {
  currentStudentId = student.sid;

  document.getElementById('modalStudentName').innerText = student.name;
  document.getElementById('modalStudentEmail').innerText = student.email;

  let progressBar = document.getElementById('modalStudentProgress');
  progressBar.style.width = student.progress + '%';
  progressBar.innerText = student.progress + '%';

  let draftsHTML = '';
  if (student.drafts.length > 0) {
    student.drafts.forEach(d => {
      draftsHTML += `<p><a href="${d.file_path}" download>${d.filename}</a></p>`;
    });
  } else {
    draftsHTML = '<p class="w3-text-grey">No drafts submitted.</p>';
  }
  document.getElementById('modalStudentDrafts').innerHTML = draftsHTML;

  document.getElementById('studentModal').style.display = 'block';
}

function closeStudentModal() {
  document.getElementById('studentModal').style.display = 'none';
}

function promptDelete(type, id) {
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

  const formData = new FormData();
  formData.append('delete_action', actionType);
  formData.append('target_id', targetId);
  formData.append('password', password);

  fetch('astudents.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(resp => {
    if (resp === "INVALID_PASSWORD") {
      alert("❌ Wrong password.");
    } else {
      alert("✅ Deleted successfully.");
      location.reload();
    }
  });

  closePasswordModal();
}
</script>
</body>
</html>

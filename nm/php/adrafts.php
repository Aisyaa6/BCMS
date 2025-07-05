<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}
$teacher      = $_SESSION['user'];
$teacher_id   = $teacher['TID'];
$teacher_name = htmlspecialchars($teacher['name']);

$conn = new mysqli('localhost','root','','coursework_db',3306);
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Handle updates
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['subid'])) {
    $subid   = intval($_POST['subid']);
    $status  = $conn->real_escape_string($_POST['status']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $stmt = $conn->prepare("UPDATE Submit SET status=?, comment=? WHERE SUBID=?");
    $stmt->bind_param("ssi",$status,$comment,$subid);
    $stmt->execute();
    $stmt->close();
    header("Location: adrafts.php"); exit;
}

// Handle sort option
$sort = $_GET['sort'] ?? 'newest'; // default: newest first
switch ($sort) {
    case 'name_asc':  $order_by = "st.name ASC"; break;
    case 'name_desc': $order_by = "st.name DESC"; break;
    case 'class_asc': $order_by = "c.name ASC"; break;
    case 'oldest':    $order_by = "s.SUBID ASC"; break;
    default:          $order_by = "s.SUBID DESC"; // newest
}

// Fetch submissions
$sql = "
  SELECT 
    s.SUBID, s.draft_file, s.status, s.comment,
    st.name AS student_name, c.name AS class_name
  FROM Submit s
  JOIN Student st ON s.SID=st.SID
  JOIN Class c   ON st.CID=c.CID
  WHERE c.TID=? 
  ORDER BY $order_by
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Draft Submissions</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    html,body,h1,h2,h3,h4,h5{font-family:"Raleway",sans-serif}
    .w3-modal-content { width: 50%; max-width: 700px; }
    .modal-textarea { width: 100%; min-height: 150px; resize: vertical; }
    .filter-header .w3-bar-item {
      border-bottom: 3px solid transparent;
    }
    .filter-header .w3-bar-item.active {
      border-bottom: 3px solid #2196F3;
      color: #2196F3;
    }
  </style>
</head>
<body class="w3-light-grey">

<!-- Top bar -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <button class="w3-bar-item w3-button w3-hide-large w3-hover-none w3-hover-text-light-grey"
          onclick="w3_open()">
    <i class="fa fa-bars"></i>
  </button>
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<!-- Sidebar -->
<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px" id="mySidebar">
  <br>
  <div class="w3-container w3-row">
    <span>Welcome, <strong><?= $teacher_name ?></strong></span>
  </div>
  <hr>
  <div class="w3-container"><h5>Menu</h5></div>
  <div class="w3-bar-block">
    <a href="ahome.php"      class="w3-bar-item w3-button w3-padding"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
    <a href="achecklist.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
    <a href="adrafts.php"    class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-upload fa-fw"></i> Drafts</a>
    <a href="astudents.php"  class="w3-bar-item w3-button w3-padding"><i class="fa fa-users fa-fw"></i> Students</a>
    <a href="../php/logout.php" 
       class="w3-bar-item w3-button w3-padding">
      <i class="fa fa-sign-out fa-fw"></i> Logout
    </a>
  </div>
</nav>

<!-- Overlay for small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="w3_close()"
     style="cursor:pointer" title="close menu" id="myOverlay"></div>

<!-- Main content -->
<div class="w3-main" style="margin-left:300px;margin-top:43px">
  <div class="w3-container" style="padding:22px">
    <h3><i class="fa fa-upload"></i> Draft Submissions</h3>

    <!-- Filter Header -->
    <div class="w3-bar w3-light-grey w3-border-bottom filter-header w3-margin-bottom">
      <button class="w3-bar-item w3-button active" onclick="filterTable('all')">All</button>
      <button class="w3-bar-item w3-button" onclick="filterTable('Pending')">Pending</button>
      <button class="w3-bar-item w3-button" onclick="filterTable('Viewed')">Viewed</button>
    </div>

    <!-- Sort dropdown -->
    <form method="get" class="w3-margin-bottom">
      <label>Sort by:</label>
      <select name="sort" class="w3-select w3-border" style="width:auto" onchange="this.form.submit()">
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
        <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
        <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name (A-Z)</option>
        <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Name (Z-A)</option>
        <option value="class_asc" <?= $sort==='class_asc'?'selected':'' ?>>Class (A-Z)</option>
      </select>
    </form>

    <table class="w3-table-all w3-hoverable w3-white" id="draftTable">
      <tr class="w3-light-grey">
        <th>Student</th><th>Class</th><th>File</th><th>Status</th><th>Action</th>
      </tr>
      <?php while($row=$result->fetch_assoc()): ?>
      <tr data-status="<?= htmlspecialchars($row['status']) ?>">
        <td><?= htmlspecialchars($row['student_name']) ?></td>
        <td><?= htmlspecialchars($row['class_name']) ?></td>
        <td>
          <a href="download.php?file=<?= urlencode($row['draft_file']) ?>" class="w3-button w3-white w3-border w3-round">
            <i class="fa fa-download"></i> Download
          </a><br>
          <small><?= htmlspecialchars($row['draft_file']) ?></small>
        </td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td>
          <button class="w3-button w3-blue w3-round"
                  onclick="openFeedbackModal(
                    <?= $row['SUBID'] ?>, 
                    '<?= htmlspecialchars(addslashes($row['comment'])) ?>',
                    '<?= $row['status'] ?>')">
            <i class="fa fa-edit"></i> Edit Feedback
          </button>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="w3-modal">
  <div class="w3-modal-content w3-animate-top w3-card-4">
    <header class="w3-container w3-blue"> 
      <span onclick="closeFeedbackModal()" 
            class="w3-button w3-display-topright">&times;</span>
      <h4><i class="fa fa-comment"></i> Edit Feedback</h4>
    </header>
    <div class="w3-container">
      <form method="post" id="feedbackForm">
        <input type="hidden" name="subid" id="modalSubid">
        <label><b>Comment:</b></label>
        <textarea name="comment" id="modalComment" class="w3-input w3-border modal-textarea"></textarea>
        <label><b>Status:</b></label>
        <select name="status" id="modalStatus" class="w3-select w3-border w3-margin-bottom">
          <option value="Pending">Pending</option>
          <option value="Viewed">Viewed</option>
        </select>
        <button type="submit" class="w3-button w3-green"><i class="fa fa-save"></i> Save</button>
      </form>
    </div>
  </div>
</div>

<script>
function w3_open(){
  document.getElementById("mySidebar").style.display="block";
  document.getElementById("myOverlay").style.display="block";
}
function w3_close(){
  document.getElementById("mySidebar").style.display="none";
  document.getElementById("myOverlay").style.display="none";
}

function openFeedbackModal(subid, comment, status) {
  document.getElementById('modalSubid').value = subid;
  document.getElementById('modalComment').value = comment;
  document.getElementById('modalStatus').value = status;
  document.getElementById('feedbackModal').style.display = 'block';
}

function closeFeedbackModal() {
  document.getElementById('feedbackModal').style.display = 'none';
}

function filterTable(status) {
  const rows = document.querySelectorAll("#draftTable tr[data-status]");
  rows.forEach(row => {
    if (status === 'all' || row.getAttribute('data-status') === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  const buttons = document.querySelectorAll(".filter-header .w3-bar-item");
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
}
</script>
</body>
</html>

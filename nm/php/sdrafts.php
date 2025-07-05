<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}

$student = $_SESSION['user'];
$sid     = $student['SID'];
$name    = htmlspecialchars($student['name']);

$conn = new mysqli('localhost', 'root', '', 'coursework_db', 3306);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['draft_file'])) {
    $filename = basename($_FILES['draft_file']['name']);
    $target   = "../uploads/" . $filename;
    $ext      = pathinfo($filename, PATHINFO_EXTENSION);

    if (strtolower($ext) !== 'docx') {
        echo "<script>alert('❌ Only .docx files allowed.');</script>";
    } elseif (move_uploaded_file($_FILES['draft_file']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO Submit (SID, draft_file, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("is", $sid, $filename);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('✅ Uploaded successfully.'); window.location.href='sdrafts.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Upload failed.');</script>";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $subid = intval($_GET['delete']);
    $res = $conn->query("SELECT draft_file FROM Submit WHERE SUBID=$subid AND SID=$sid");
    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $filepath = realpath(__DIR__ . "/../uploads/" . $row['draft_file']);
        if (file_exists($filepath)) unlink($filepath); // Delete file
        $conn->query("DELETE FROM Submit WHERE SUBID=$subid AND SID=$sid");
        echo "<script>alert('✅ Draft deleted.'); window.location.href='sdrafts.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Draft not found.');</script>";
    }
}

// Load submissions grouped by status
$submissions = ['Pending' => [], 'Viewed' => []];
$res = $conn->query("SELECT * FROM Submit WHERE SID=$sid ORDER BY SUBID ASC");
while ($row = $res->fetch_assoc()) {
    $submissions[$row['status']][] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Drafts</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
    /* Make modal box larger */
    .w3-modal-content {
      width: 60%; /* wider */
      max-width: 800px;
    }
    #commentContent {
      min-height: 200px;  /* taller */
      max-height: 400px;
      overflow-y: auto;   /* scroll if long */
      padding: 16px;
      background: #f9f9f9;
      border-radius: 4px;
    }
  </style>
</head>
<body class="w3-light-grey">

<!-- Top Bar -->
<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
  <span class="w3-bar-item w3-right">Business Coursework Management System</span>
</div>

<!-- Sidebar -->
<nav class="w3-sidebar w3-collapse w3-white w3-animate-left"
     style="z-index:3;width:300px;" id="mySidebar"><br>
  <div class="w3-container w3-row">
    <span>Welcome, <strong><?= $name ?></strong></span><br>
  </div><hr>
  <div class="w3-container"><h5>Menu</h5></div>
  <div class="w3-bar-block">
    <a href="shome.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
    <a href="schecklist.php" class="w3-bar-item w3-button w3-padding"><i class="fa fa-check-square-o fa-fw"></i> Checklist</a>
    <a href="sdrafts.php" class="w3-bar-item w3-button w3-padding w3-blue"><i class="fa fa-upload fa-fw"></i> Drafts</a>
    <a href="../php/logout.php" 
    class="w3-bar-item w3-button w3-padding">
   <i class="fa fa-sign-out fa-fw"></i> Logout
   </a>
  </div>
</nav>

<!-- Overlay -->
<div class="w3-overlay w3-hide-large w3-animate-opacity"
     onclick="w3_close()" style="cursor:pointer" title="close menu" id="myOverlay"></div>

<!-- Page Content -->
<div class="w3-main" style="margin-left:300px;margin-top:43px">
  <div class="w3-container" style="padding:22px">
    <h3><i class="fa fa-upload"></i> Upload Draft</h3>

    <form method="post" enctype="multipart/form-data" class="w3-container w3-card w3-padding w3-white w3-margin-bottom">
      <label><b>Select .docx File</b></label>
      <input class="w3-input w3-border w3-margin-bottom" type="file" name="draft_file" accept=".docx" required>
      <button class="w3-button w3-blue" type="submit">Upload</button>
    </form>

    <hr>
    <h4>My Drafts</h4>

    <?php foreach (['Pending', 'Viewed'] as $status): ?>
      <?php if (!empty($submissions[$status])): ?>
        <h5><?= $status ?> Drafts</h5>
        <table class="w3-table-all w3-hoverable w3-white w3-margin-bottom">
          <tr class="w3-light-grey">
            <th>#</th>
            <th>File</th>
            <th>Status</th>
            <th>Comment</th>
            <th>Action</th>
          </tr>
          <?php foreach ($submissions[$status] as $row): ?>
            <tr>
              <td><?= $row['SUBID'] ?></td>
              <td>
                <a href="../uploads/<?= urlencode($row['draft_file']) ?>" download>
                  <?= htmlspecialchars($row['draft_file']) ?>
                </a>
              </td>
              <td><?= $row['status'] ?></td>
              <td>
                <button onclick="showCommentModal('<?= htmlspecialchars(addslashes($row['comment'])) ?>')" 
                        class="w3-button w3-green w3-round">
                  <i class="fa fa-eye"></i> View Comment
                </button>
              </td>
              <td>
                <a href="sdrafts.php?delete=<?= $row['SUBID'] ?>"
                   onclick="return confirm('Are you sure you want to delete this draft?');"
                   class="w3-button w3-red w3-round">
                   <i class="fa fa-trash"></i> Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<!-- Comment Modal -->
<div id="commentModal" class="w3-modal">
  <div class="w3-modal-content w3-animate-top w3-card-4">
    <header class="w3-container w3-blue"> 
      <span onclick="closeCommentModal()" 
            class="w3-button w3-display-topright">&times;</span>
      <h4><i class="fa fa-comment"></i> Teacher Comment</h4>
    </header>
    <div class="w3-container" id="commentContent">
      <!-- Comment content will be injected here -->
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

function showCommentModal(comment) {
  document.getElementById('commentContent').innerText = comment || "No comment provided.";
  document.getElementById('commentModal').style.display = 'block';
}

function closeCommentModal() {
  document.getElementById('commentModal').style.display = 'none';
}
</script>
</body>
</html>

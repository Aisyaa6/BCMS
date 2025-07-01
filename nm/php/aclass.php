<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$teacher_id = $_SESSION['user']['TID'];

$conn = new mysqli('localhost', 'root', '', 'coursework_db', 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get unassigned classes (TID IS NULL)
$res = $conn->query("SELECT CID, name FROM Class WHERE TID IS NULL");
$classes = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Select Classes</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
  <style>
    .theme-header {
      background-color: #003366;
      color: white;
      padding: 64px 16px;
    }
    .theme-button {
      background-color: #003366;
      color: white;
    }
    .center-form {
      max-width: 600px;
      margin: auto;
      margin-top: 40px;
    }
    .checkbox-group {
      padding: 10px;
    }
  </style>
</head>
<body class="w3-light-grey">

<div class="w3-container theme-header w3-center">
  <h1 class="w3-jumbo">Select Classes You Teach</h1>
</div>

<div class="w3-container center-form">
  <div class="w3-card-4 w3-white w3-padding-32 w3-padding-large">
    <form action="ahandle.php" method="post">
      <h3 class="w3-center" style="color:#003366;">Available Classes</h3>
      <div class="checkbox-group">
        <?php if (count($classes) === 0): ?>
          <p class="w3-text-red">❌ No available classes to assign.</p>
        <?php else: ?>
          <?php foreach ($classes as $c): ?>
            <label class="w3-checkbox w3-block">
              <input type="checkbox" name="cid[]" value="<?= $c['CID'] ?>">
              <?= htmlspecialchars($c['name']) ?>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <br>
      <button class="w3-button w3-block theme-button" type="submit">Assign Selected Classes</button>
    </form>
  </div>
</div>

</body>
</html>

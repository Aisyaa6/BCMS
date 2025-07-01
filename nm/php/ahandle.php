<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$teacher_id = $_SESSION['user']['TID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cid'])) {
    $conn = new mysqli('localhost', 'root', '', 'coursework_db', 3306);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    foreach ($_POST['cid'] as $cid) {
        $cid = intval($cid);
        // Assign only if class is not already taken
        $conn->query("UPDATE Class SET TID = $teacher_id WHERE CID = $cid AND TID IS NULL");
    }

    header("Location: ahome.php"); // change as needed
    exit;
} else {
    header("Location: ../public/index.html");
    exit;
}


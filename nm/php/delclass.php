<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

$connection = new mysqli("localhost", "root", "", "coursework_db", 3306);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cid'])) {
    $cid = intval($_POST['cid']);
    $connection->query("DELETE FROM Student WHERE CID = $cid");
}

header("Location: ../php/astudents.php");
exit;

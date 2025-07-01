<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sid'])) {
    $tid = $_SESSION['user']['TID'];
    $sid = intval($_POST['sid']);

    $conn = new mysqli("localhost", "root", "", "coursework_db", 3306);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Verify ownership
    $check = $conn->prepare("
        SELECT Student.SID FROM Student
        JOIN Class ON Student.CID = Class.CID
        WHERE Student.SID = ? AND Class.TID = ?
    ");
    $check->bind_param("ii", $sid, $tid);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 1) {
        // Delete from child tables first
        $conn->query("DELETE FROM Student_TK WHERE SID = $sid");
        $conn->query("DELETE FROM Submit WHERE SID = $sid");

        // Then delete student
        $delete = $conn->prepare("DELETE FROM Student WHERE SID = ?");
        $delete->bind_param("i", $sid);
        $delete->execute();
        $delete->close();
    }

    $check->close();
    $conn->close();
}

header("Location: ../php/astudents.php");
exit;

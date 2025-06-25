<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'coursework_db';
    $port = 3306;

    $connection = new mysqli($host, $user, $pass, $db, $port);
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    $cid = $_POST['cid'];
    $student_id = $_SESSION['user']['SID'];

    $query = $connection->prepare("UPDATE Student SET CID = ? WHERE SID = ?");
    $query->bind_param("ii", $cid, $student_id);

    if ($query->execute()) {
        header("Location: ../public/dashboard.html");
        exit;
    } else {
        echo "❌ Failed to update class.";
    }

    $query->close();
    $connection->close();
} else {
    echo "Invalid request.";
}

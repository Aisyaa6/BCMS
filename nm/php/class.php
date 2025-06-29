<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'coursework_db';
    $port = 3306;

    $connection = new mysqli($host, $user, $pass, $db, $port);
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    $cid = intval($_POST['cid']);  // force int for safety
    $sid = $_SESSION['user']['SID'];

    $stmt = $connection->prepare("UPDATE Student SET CID = ? WHERE SID = ?");
    $stmt->bind_param("ii", $cid, $sid);

    if ($stmt->execute()) {
        // Optionally update session so it reflects new class
        $_SESSION['user']['CID'] = $cid;
        header("Location: shome.php"); // or full path if needed
        exit;
    } else {
        echo "❌ Failed to update class.";
    }

    $stmt->close();
    $connection->close();
} else {
    echo "Invalid request.";
}

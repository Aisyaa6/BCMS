<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'coursework_db';
$port = 3306;

$connection = new mysqli($host, $user, $pass, $db, $port);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['username']; // form field is still named 'username'
    $email = $_POST['email'];
    $password = md5($_POST['password']); // stored in md5
    $role = $_POST['role'];

    if ($role === 'teacher') {
        $query = $connection->prepare("INSERT INTO Teacher (name, email, password) VALUES (?, ?, ?)");
        $query->bind_param("sss", $name, $email, $password);
    } else {
        $query = $connection->prepare("INSERT INTO Student (name, email, password) VALUES (?, ?, ?)");
        $query->bind_param("sss", $name, $email, $password);
    }

    if ($query->execute()) {
        if ($role === 'student') {
            $sid = $connection->insert_id;
            $result = $connection->query("SELECT * FROM Student WHERE SID = $sid");
            $user = $result->fetch_assoc();
            $_SESSION['user'] = $user;
            $_SESSION['role'] = 'student';
            header("Location: ../public/class.html");
        } else {
            $tid = $connection->insert_id;
            $result = $connection->query("SELECT * FROM Teacher WHERE TID = $tid");
            $user = $result->fetch_assoc();
            $_SESSION['user'] = $user;
            $_SESSION['role'] = 'teacher';
            header("Location: ../php/aclass.php");
        }
        exit;
    } else {
        echo "❌ Error: " . $query->error;
    }

    $query->close();
    $connection->close();
}
?>

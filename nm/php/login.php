<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'coursework_db';
$port = 3306; // change to 3306 if your XAMPP MySQL uses the default

$connection = new mysqli($host, $user, $pass, $db, $port);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']); // use password_hash in future
    $role = $_POST['role'];

    $table = $role === 'teacher' ? "Teacher" : "Student";
    $query = $connection->prepare("SELECT * FROM $table WHERE email=? AND password=?");
    $query->bind_param("ss", $email, $password);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 1) {
        $_SESSION['user'] = $result->fetch_assoc();
        $_SESSION['role'] = $role;

        // Redirect based on role
        if ($role === 'teacher') {
            header("Location: ../php/ahome.php");
        } else {
            header("Location: ../php/shome.php");
        }
        exit;
    } else {
        echo "<script>alert('Invalid email or password.'); window.history.back();</script>";
    }
}
?>

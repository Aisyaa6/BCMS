<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    die("Unauthorized access.");
}

$data = json_decode(file_get_contents("php://input"), true);
$cid = intval($data['cid']);
$password = $data['password'];

$teacher = $_SESSION['user'];
$tid = $teacher['TID'];

// DB connection
$conn = new mysqli("localhost", "root", "", "coursework_db", 3306);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB connection failed.");
}

// Verify password
$check = $conn->prepare("SELECT * FROM Teacher WHERE TID = ? AND password = ?");
$check->bind_param("is", $tid, $password);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    die("Invalid password.");
}

// Delete all students in the class
$student_q = $conn->query("SELECT SID FROM Student WHERE CID = $cid");
while ($s = $student_q->fetch_assoc()) {
    $sid = $s['SID'];
    $conn->query("DELETE FROM Student_TK WHERE SID = $sid");
    $conn->query("DELETE FROM Submit WHERE SID = $sid");
    $conn->query("DELETE FROM Student WHERE SID = $sid");
}

echo "All students in the class deleted successfully.";

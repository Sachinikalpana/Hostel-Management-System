<?php
session_start();
include('../includes/auth_check.php');
checkRole(['admin', 'warden']);
include('../includes/db.php');

header('Content-Type: application/json');

if(!isset($_GET['id'])){
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$student_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT full_name, student_number, email, phone_number, room_number, home_address FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $student = $result->fetch_assoc();
    echo json_encode(['success' => true, 'student' => $student]);
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
}

$stmt->close();
$conn->close();
?>

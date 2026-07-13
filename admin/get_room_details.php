<?php
session_start();
include('../includes/auth_check.php');
checkRole(['admin']);
include('../includes/db.php');

header('Content-Type: application/json');

if(!isset($_GET['room_number'])){
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$room_number = (int)$_GET['room_number'];

// Get Room Info
$stmt = $conn->prepare("SELECT room_number, room_type, status FROM rooms WHERE room_number = ?");
$stmt->bind_param('i', $room_number);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $room = $result->fetch_assoc();
    
    // Get Stdents Assined to This Room
    $students_stmt = $conn->prepare("SELECT id, full_name, student_number, email, phone_number, home_address FROM users WHERE room_number = ? AND role = 'student'");
    $students_stmt->bind_param('i', $room_number);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    $students = [];
    while($student = $students_result->fetch_assoc()){
        $students[] = $student;
    }
    
    echo json_encode([
        'success' => true, 
        'room' => $room,
        'students' => $students
    ]);
    
    $students_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
}

$stmt->close();
$conn->close();
?>

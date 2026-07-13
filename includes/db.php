<?php
$host = "localhost";
$dbname = "hostel_management";
$user = "root";
$pass = "";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Check Connect
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set Charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>

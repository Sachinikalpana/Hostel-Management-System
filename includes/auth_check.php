<?php
if(!isset($_SESSION)){
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])){
    header('Location: index.php');
    exit();
}

// Func to Check User Role
function checkRole($allowed_roles){
    if(!in_array($_SESSION['role'], $allowed_roles)){
        header('Location: dashboard.php');
        exit();
    }
}

// Func to Check User Access
function canAccess($feature){
    $role = $_SESSION['role'];
    
    $permissions = [
        'admin' => ['complaints', 'announcements', 'payments', 'rooms', 'checkins', 'users', 'manage_complaints', 'manage_announcements', 'manage_payments', 'manage_requests'],
        'warden' => ['complaints', 'announcements', 'payments', 'rooms', 'checkins', 'manage_complaints', 'manage_announcements', 'view_payments', 'manage_requests'],
        'security' => ['complaints', 'announcements', 'checkins_view'],
        'student' => ['complaints', 'announcements', 'payments', 'rooms', 'checkins', 'room_change']
    ];
    
    return isset($permissions[$role]) && in_array($feature, $permissions[$role]);
}
?>

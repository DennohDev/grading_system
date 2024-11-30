<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the teacher ID from the URL
$teacher_id = $_GET['id'] ?? null;

// Check if the teacher exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($teacher) {
    // Delete related record(s) in department_teachers table
    $delete_relation_stmt = $pdo->prepare("DELETE FROM department_teachers WHERE teacher_id = ?");
    $delete_relation_stmt->execute([$teacher_id]);

    // Now delete the teacher from the users table
    $delete_teacher_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $delete_teacher_stmt->execute([$teacher_id]);

    // Optionally check if the teacher was successfully deleted
    if ($delete_teacher_stmt->rowCount() > 0) {
        echo "Teacher deleted successfully!";
    } else {
        echo "Error deleting teacher.";
    }
} else {
    echo "Teacher not found!";
}

// Redirect back to the manage teachers page
header("Location: manage_teachers.php");
exit();
?>

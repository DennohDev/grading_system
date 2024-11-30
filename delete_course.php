<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the course ID from the URL
$course_id = $_GET['id'] ?? null;

if ($course_id) {
    // Delete the course from the courses table
    $delete_stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $delete_stmt->execute([$course_id]);
}

// Redirect back to the manage courses page
header("Location: manage_courses.php");
exit();
?>

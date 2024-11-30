<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if a student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$studentId = $_GET['id'];

// Fetch the student's name before deleting
$studentQuery = "SELECT name FROM students WHERE id = ?";
$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Redirect back if the student is not found
if (!$student) {
    header("Location: manage_students.php");
    exit();
}

$studentName = $student['name'];

// Delete the student record
$deleteQuery = "DELETE FROM students WHERE id = ?";
$deleteStmt = $pdo->prepare($deleteQuery);
$deleteStmt->execute([$studentId]);

// Redirect with a success message
$_SESSION['success_message'] = "You have successfully deleted the student: $studentName.";
header("Location: manage_students.php");
exit();

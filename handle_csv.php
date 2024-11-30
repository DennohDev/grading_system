<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "includes/db.php";

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];

    if (($csvFile = fopen($fileTmpPath, 'r')) !== false) {
        fgetcsv($csvFile);  // Skip header row

        // Prepare SQL statements
        $check_course_stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
        $insert_stmt = $pdo->prepare("INSERT INTO students (name, admission_number, course_id) VALUES (?, ?, ?)");

        while (($row = fgetcsv($csvFile)) !== false) {
            if (count($row) >= 3) {
                list($name, $admission_number, $course_id) = $row;

                // Verify the course_id exists
                $check_course_stmt->execute([$course_id]);
                $course_exists = $check_course_stmt->fetchColumn();

                if ($course_exists) {
                    // Insert the student if the course_id is valid
                    $insert_stmt->execute([$name, $admission_number, $course_id]);
                } else {
                    echo "Invalid course_id ($course_id) for student $name. Skipping this entry.<br>";
                }
            }
        }

        fclose($csvFile);
        header("Location: manage_students.php");
        exit();
    }
} else {
    header("Location: manage_students.php");
    exit();
}

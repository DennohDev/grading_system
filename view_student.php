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

// Fetch student details
$studentQuery = "SELECT s.name, s.admission_number, c.name AS course_name, d.name AS department_name 
                 FROM students s 
                 JOIN courses c ON s.course_id = c.id 
                 JOIN departments d ON c.department_id = d.id 
                 WHERE s.id = ?";
$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Redirect if student not found
if (!$student) {
    header("Location: manage_students.php");
    exit();
}

// Fetch subjects and grades for the student
$gradesQuery = "SELECT sub.name AS subject_name, g.grade 
                FROM grades g
                JOIN subjects sub ON g.subject_id = sub.id
                WHERE g.student_id = ?";
$gradesStmt = $pdo->prepare($gradesQuery);
$gradesStmt->execute([$studentId]);
$grades = $gradesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" id="mainContent">
        <div class="container mt-5">
            <h2>Student Details</h2>

            <!-- Student Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title"><?php echo htmlspecialchars($student['name']); ?></h4>
                    <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
                </div>
            </div>

            <!-- Subjects and Grades -->
            <h4>Subjects and Grades</h4>
            <?php if ($grades): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Subject</th>
                            <th scope="col">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No grades available for this student.</p>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="mb-4">
                <a href="manage_transcripts.php?student_id=<?php echo $studentId; ?>" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Transcript
                </a>
                <a href="edit_student.php?id=<?php echo $studentId; ?>" class="btn btn-warning">Edit Student</a>
                <a href="delete_student.php?id=<?php echo $studentId; ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this student?');">Delete Student</a>
                <a href="manage_students.php" class="btn btn-secondary">Back to Students</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

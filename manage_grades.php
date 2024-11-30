<?php
session_start();
include 'includes/db.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize messages array
$messages = [];

// Get selected department and course
$selectedDepartment = $_GET['department_id'] ?? '';
$selectedCourse = $_GET['course_id'] ?? '';

// Fetch all departments
try {
    $departmentsStmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    $departments = $departmentsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages['error'] = "Error fetching departments: " . $e->getMessage();
    $departments = [];
}

// Fetch courses for selected department
$courses = [];
if ($selectedDepartment) {
    try {
        $coursesStmt = $pdo->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY name");
        $coursesStmt->execute([$selectedDepartment]);
        $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching courses: " . $e->getMessage();
    }
}

// Fetch students for selected course
$students = [];
if ($selectedCourse) {
    try {
        $studentsStmt = $pdo->prepare("
            SELECT id, name, admission_number 
            FROM students 
            WHERE course_id = ? 
            ORDER BY name
        ");
        $studentsStmt->execute([$selectedCourse]);
        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching students: " . $e->getMessage();
    }
}

// Fetch subjects for selected course
$subjects = [];
if ($selectedCourse) {
    try {
        $subjectsStmt = $pdo->prepare("
            SELECT * 
            FROM subjects 
            WHERE course_id = ?
            ORDER BY name
        ");
        $subjectsStmt->execute([$selectedCourse]);
        $subjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching subjects: " . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_grade'])) {
        try {
            $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
            $subject_id = filter_var($_POST['subject_id'], FILTER_VALIDATE_INT);
            $grade = trim($_POST['grade']);
            
            if (!$student_id || !$subject_id || !isValidGrade($grade)) {
                $messages['error'] = "Invalid input data";
            } else {
                // Check for existing grade
                $checkStmt = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ?");
                $checkStmt->execute([$student_id, $subject_id]);
                
                if ($checkStmt->rowCount() > 0) {
                    $messages['error'] = "Grade already exists for this student and subject";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, grade) VALUES (?, ?, ?)");
                    if ($stmt->execute([$student_id, $subject_id, $grade])) {
                        $messages['success'] = "Grade added successfully";
                    } else {
                        $messages['error'] = "Failed to add grade";
                    }
                }
            }
        } catch (PDOException $e) {
            $messages['error'] = "Database error: " . $e->getMessage();
        }
    }
    
    // ... existing update and delete handlers ...
}

// Fetch grades if course is selected
$grades = [];
if ($selectedCourse) {
    try {
        $stmt = $pdo->prepare("
            SELECT g.id, s.name as student_name, sub.name as subject_name, g.grade,
                   s.admission_number
            FROM grades g
            JOIN students s ON g.student_id = s.id
            JOIN subjects sub ON g.subject_id = sub.id
            WHERE s.course_id = ?
            ORDER BY s.name, sub.name
        ");
        $stmt->execute([$selectedCourse]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching grades: " . $e->getMessage();
    }
}

function isValidGrade($grade) {
    if (is_numeric($grade)) {
        $numGrade = floatval($grade);
        return $numGrade >= 0 && $numGrade <= 100;
    }
    return false;
}

function getLetterGrade($numericalGrade) {
    if ($numericalGrade >= 80) return 'A';
    if ($numericalGrade >= 75) return 'A-';
    if ($numericalGrade >= 70) return 'B+';
    if ($numericalGrade >= 65) return 'B';
    if ($numericalGrade >= 60) return 'B-';
    if ($numericalGrade >= 55) return 'C+';
    if ($numericalGrade >= 50) return 'C';
    if ($numericalGrade >= 45) return 'C-';
    if ($numericalGrade >= 40) return 'D+';
    if ($numericalGrade >= 35) return 'D';
    if ($numericalGrade >= 30) return 'E';
    return 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="content" id="mainContent">
        <div class="container mt-4">
            <h2>Manage Grades</h2>

            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $type => $message): ?>
                    <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Select Department and Course</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="department_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['id']; ?>" 
                                            <?php if ($selectedDepartment == $department['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($selectedDepartment): ?>
                            <div class="col-md-4">
                                <select name="course_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>"
                                                <?php if ($selectedCourse == $course['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($selectedCourse): ?>
                <!-- Add New Grade Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Grade</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <select name="student_id" class="form-control" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['admission_number'] . ' - ' . $student['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="subject_id" class="form-control" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="grade" class="form-control grade-input" 
                                       placeholder="Grade" min="0" max="100" step="0.01" required
                                       onchange="updateLetterGradePreview(this)">
                                <div class="mt-2">
                                    Letter Grade: <span class="letter-grade-preview"></span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="add_grade" class="btn btn-primary">Add Grade</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Grades Table -->
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Student</th>
                            <th>Admission Number</th>
                            <th>Subject</th>
                            <th>Grade</th>
                            <th>Letter Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($grade['admission_number']); ?></td>
                                <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline grade-form">
                                        <input type="hidden" name="id" value="<?php echo $grade['id']; ?>">
                                        <input type="number" name="grade" 
                                               value="<?php echo htmlspecialchars($grade['grade']); ?>" 
                                               class="form-control form-control-sm d-inline grade-input" 
                                               style="width: 80px" 
                                               min="0" max="100" step="0.01" required
                                               onchange="updateLetterGrade(this)">
                                        <button type="submit" name="update_grade" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                                <td class="letter-grade">
                                    <?php echo getLetterGrade($grade['grade']); ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this grade?');">
                                        <input type="hidden" name="id" value="<?php echo $grade['id']; ?>">
                                        <button type="submit" name="delete_grade" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function getLetterGrade(numericalGrade) {
        if (numericalGrade >= 70) return 'A';
        if (numericalGrade >= 60) return 'B';
        if (numericalGrade >= 50) return 'C';
        if (numericalGrade >= 40) return 'D';
        if (numericalGrade >= 30) return 'E';
        return 'F';
    }

    function updateLetterGrade(input) {
        const numericalGrade = parseFloat(input.value);
        const letterGrade = getLetterGrade(numericalGrade);
        const row = input.closest('tr');
        const letterGradeCell = row.querySelector('.letter-grade');
        letterGradeCell.textContent = letterGrade;
        
        // Add visual feedback with color
        letterGradeCell.className = 'letter-grade'; // Reset classes
        if (letterGrade === 'A') letterGradeCell.classList.add('text-success');
        else if (letterGrade === 'F') letterGradeCell.classList.add('text-danger');
        else letterGradeCell.classList.add('text-primary');
    }

    // Initialize letter grades on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.grade-input').forEach(input => {
            updateLetterGrade(input);
        });
    });
    </script>

    <style>
    .letter-grade {
        font-weight: bold;
        font-size: 1.2em;
    }
    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-primary { color: #007bff !important; }
    </style>
</body>
</html>

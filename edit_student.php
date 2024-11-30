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
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $pdo->prepare($studentQuery);
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Redirect if student not found
if (!$student) {
    header("Location: manage_students.php");
    exit();
}

// Fetch courses for dropdown
$coursesQuery = "SELECT * FROM courses";
$coursesStmt = $pdo->query($coursesQuery);
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if (isset($_POST['update_student'])) {
    $name = $_POST['name'];
    $admissionNumber = $_POST['admission_number'];
    $courseId = $_POST['course_id'];

    // Update student details
    $updateQuery = "UPDATE students SET name = ?, admission_number = ?, course_id = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$name, $admissionNumber, $courseId, $studentId]);

    // Redirect back to the view student page
    header("Location: view_student.php?id=" . $studentId);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
            <h2>Edit Student</h2>

            <form method="POST">
                <!-- Student Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Student Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                </div>

                <!-- Admission Number -->
                <div class="mb-3">
                    <label for="admission_number" class="form-label">Admission Number</label>
                    <input type="text" class="form-control" id="admission_number" name="admission_number" value="<?php echo htmlspecialchars($student['admission_number']); ?>" required>
                </div>

                <!-- Course -->
                <div class="mb-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select class="form-control" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php if ($student['course_id'] == $course['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                <a href="view_student.php?id=<?php echo $studentId; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

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

// Fetch the course to edit
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    // If the course does not exist, redirect to manage courses page
    header("Location: manage_courses.php");
    exit();
}

// Fetch all departments for the dropdown
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission to update the course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_name = $_POST['course_name'];
    $department_id = $_POST['department_id'];

    // Update course information
    $update_stmt = $pdo->prepare("UPDATE courses SET name = ?, department_id = ? WHERE id = ?");
    $update_stmt->execute([$course_name, $department_id, $course_id]);

    // Redirect back to manage courses page
    header("Location: manage_courses.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>

<body>

    <?php include 'admin_sidebar.php'; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="content" id="mainContent">
        <div class="container mt-5">
            <h2>Edit Course</h2>
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="course_name" value="<?php echo htmlspecialchars($course['name']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>" <?php echo ($course['department_id'] == $department['id']) ? 'selected' : ''; ?>><?php echo $department['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="update_course" class="btn btn-primary">Update Course</button>
            </form>
        </div>
    </div>

</body>

</html>
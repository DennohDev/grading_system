<?php
session_start();
include 'includes/db.php';
include 'admin_sidebar.php'; // Include sidebar file

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch counts for summary cards
$teachers_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$departments_count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$courses_count = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$students_count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
            <button class="btn btn-outline-light me-3" id="toggleSidebarBtn">☰</button>

            <!-- Navbar Brand (Dashboard Title) -->
            <span class="navbar-brand mb-0 h1 text-light">Admin Dashboard</span>

            <!-- Logout Button (Aligned Right) -->
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar (from included file) -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" id="mainContent">
        <div class="container mt-4">
            <h2 class="mb-4">Welcome, <?php echo $_SESSION['user_name']; ?></h2>
            
            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">
                            <h5>Total Teachers</h5>
                            <p class="display-6"><?php echo $teachers_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white mb-4">
                        <div class="card-body">
                            <h5>Total Departments</h5>
                            <p class="display-6"><?php echo $departments_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">
                            <h5>Total Courses</h5>
                            <p class="display-6"><?php echo $courses_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <h5>Total Students</h5>
                            <p class="display-6"><?php echo $students_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
</body>
</html>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch the department to edit
if (isset($_GET['id'])) {
    $department_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$department_id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {
        die("Department not found!");
    }
}

// Handle updating the department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_department'])) {
    $department_name = $_POST['department_name'];

    $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
    $stmt->execute([$department_name, $department_id]);

    header("Location: manage_departments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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

<!-- Sidebar (included here) -->
<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div class="content" id="mainContent">
    <div class="container mt-5">
        
        <!-- Edit Department Form -->
        <h2>Edit Department</h2>
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="department_name" value="<?php echo htmlspecialchars($department['name']); ?>" required>
                </div>
            </div>
            <button type="submit" name="update_department" class="btn btn-primary">Update Department</button>
        </form>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';
include 'admin_sidebar.php'; // Include sidebar

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle adding a new department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];

    $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->execute([$department_name]);

    header("Location: manage_departments.php");
    exit();
}

// Handle updating a department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_department'])) {
    $department_id = $_POST['department_id'];
    $department_name = $_POST['department_name'];

    $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
    $stmt->execute([$department_name, $department_id]);

    header("Location: manage_departments.php");
    exit();
}

// Handle deleting a department
if (isset($_GET['delete_id'])) {
    $department_id = $_GET['delete_id'];

    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$department_id]);

    header("Location: manage_departments.php");
    exit();
}

// Fetch all departments
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
        <!-- Add New Department Form -->
        <h2>Add New Department</h2>
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="department_name" placeholder="Department Name" required>
                </div>
            </div>
            <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
        </form>

        <!-- Departments Table -->
        <h2 class="mt-5">Departments List</h2>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Department Name</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($department['name']); ?></td>
                        <td>
                            <a href="edit_department.php?id=<?php echo $department['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete_id=<?php echo $department['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this department?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>

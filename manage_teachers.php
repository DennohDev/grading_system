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

// Handle adding a new teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $department_id = $_POST['department_id'];

    // Insert into the users table
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password,department_id,role) VALUES (?, ?, ?,?, 'teacher')");
    $stmt->execute([$name, $email, $password, $department_id]);

    // Get the teacher ID of the newly inserted teacher
    $teacher_id = $pdo->lastInsertId();

    // Insert into department_teachers table to link the teacher to a department
    $stmt = $pdo->prepare("INSERT INTO department_teachers (teacher_id, department_id) VALUES (?, ?)");
    $stmt->execute([$teacher_id, $department_id]);

    header("Location: manage_teachers.php");
    exit();
}


// Fetch departments for the add/edit forms
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers and their departments
$teachers = $pdo->query("
    SELECT u.id, u.name, u.email, d.name AS department
    FROM users u
    JOIN department_teachers dt ON u.id = dt.teacher_id
    JOIN departments d ON dt.department_id = d.id
    WHERE u.role = 'teacher'
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers</title>
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
    <!-- Sidebar (from included file) -->
    <?php include 'admin_sidebar.php'; ?>
    <div class="content" id="mainContent">
        <div class="container mt-5">
            <!-- Add New Teacher Form -->
            <h2>Add New Teacher</h2>
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="name" placeholder="Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-md-3">
                        
                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
            </form>

            <!-- Teachers Table -->
            <h2 class="mt-5">Teachers List</h2>
            <table class="table table-bordered table-striped mt-3">
                <thead class="table-dark">
                    <tr>
                        <th scope="col"><a href="?sort=name" class="text-white">Name</a></th>
                        <th scope="col"><a href="?sort=email" class="text-white">Email</a></th>
                        <th scope="col"><a href="?sort=department" class="text-white">Department</a></th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                            <td>
                                <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
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
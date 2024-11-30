<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle adding a new course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $course_name = $_POST['course_name'];
    $department_id = $_POST['department_id'];

    $stmt = $pdo->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
    $stmt->execute([$course_name, $department_id]);

    header("Location: manage_courses.php");
    exit();
}

// Fetch departments for the add/edit forms
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses grouped by department for display in separate tables
$courses_by_department = [];
foreach ($departments as $department) {
    $stmt = $pdo->prepare("SELECT courses.id, courses.name FROM courses WHERE department_id = ?");
    $stmt->execute([$department['id']]);
    $courses_by_department[$department['name']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
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
    <!-- Add New Course Form -->
    <h2>Add New Course</h2>
    <form method="POST" action="">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="course_name" placeholder="Course Name" required>
            </div>
            <div class="col-md-4">
                <select class="form-control" name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add_course" class="btn btn-primary">Add Course</button>
    </form>

    <!-- Courses Tables by Department -->
    <h2 class="mt-5">Courses List by Department</h2>
    <?php foreach ($courses_by_department as $department_name => $courses): ?>
        <h3><?php echo htmlspecialchars($department_name); ?></h3>
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Course Name</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['name']); ?></td>
                            <td>
                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No courses available in this department.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>
</div>

</body>
</html>

<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Display success alert if a message exists
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success text-center'>{$_SESSION['success_message']}</div>";
    unset($_SESSION['success_message']); // Clear the message after displaying
}

// Search functionality
$searchQuery = '';
if (isset($_GET['search_name'])) {
    $searchQuery = $_GET['search_name'];
}

// Pagination setup
$studentsPerPage = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $studentsPerPage;

// Filter variables
$selectedDepartment = $_GET['department_id'] ?? '';
$selectedCourse = $_GET['course_id'] ?? '';

// Filter query
$filterQuery = "WHERE 1=1";
$params = [];

if ($selectedDepartment) {
    $filterQuery .= " AND d.id = ?";
    $params[] = $selectedDepartment;
}

if ($selectedCourse) {
    $filterQuery .= " AND c.id = ?";
    $params[] = $selectedCourse;
}

if ($searchQuery) {
    $filterQuery .= " AND s.name LIKE ?";
    $params[] = '%' . $searchQuery . '%';
}

// Fetch total student count
$totalQuery = "SELECT COUNT(DISTINCT s.id) FROM students s 
               JOIN courses c ON s.course_id = c.id
               JOIN departments d ON c.department_id = d.id 
               $filterQuery";
$totalStudentsStmt = $pdo->prepare($totalQuery);
$totalStudentsStmt->execute($params);
$totalStudents = $totalStudentsStmt->fetchColumn();
$totalPages = ceil($totalStudents / $studentsPerPage);

// Fetch students with filters
$query = "SELECT s.id, s.name, s.admission_number, c.name AS course_name, d.name AS department_name
          FROM students s
          JOIN courses c ON s.course_id = c.id
          JOIN departments d ON c.department_id = d.id
          $filterQuery
          ORDER BY d.name, c.name, s.name
          LIMIT $studentsPerPage OFFSET $offset";
$studentsStmt = $pdo->prepare($query);
$studentsStmt->execute($params);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all departments
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all courses
$courses = $pdo->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
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
            <h2>Manage Students</h2>

            <!-- Action Buttons -->
            <div class="mb-4">
                <a href="import_csv.php" class="btn btn-secondary">Import Students via CSV</a>
            </div>

            <!-- Search Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-6">
                    <input type="text" name="search_name" class="form-control" placeholder="Search by name"
                        value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>

            <!-- Filter Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-3">
                    <select class="form-control" name="department_id" onchange="this.form.submit()">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>" <?php if ($selectedDepartment == $department['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="course_id" onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php if ($selectedCourse == $course['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- Students Table -->
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Admission Number</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                            <td>
                                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">View</a>
                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&department_id=<?php echo $selectedDepartment; ?>&course_id=<?php echo $selectedCourse; ?>&search_name=<?php echo htmlspecialchars($searchQuery); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all departments for the department dropdown
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);

// Handle department change to fetch courses
$courses = [];
if (isset($_GET['department_id'])) {
    $department_id = $_GET['department_id'];
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle adding a new subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $_POST['subject_name'];
    $unit_code = $_POST['unit_code'] ?? null;
    $course_id = $_POST['course_id'];

    // Insert the new subject
    $stmt = $pdo->prepare("INSERT INTO subjects (name, unit_code, course_id) VALUES (?, ?, ?)");
    $stmt->execute([$subject_name, $unit_code, $course_id]);

    // Redirect back to the manage subjects page
    header("Location: manage_subjects.php?department_id=" . $_POST['department_id']);
    exit();
}

// Handle deleting a subject
if (isset($_GET['delete_subject_id'])) {
    $subject_id = $_GET['delete_subject_id'];

    // Delete the subject
    $delete_stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $delete_stmt->execute([$subject_id]);

    // Redirect back to the manage subjects page
    header("Location: manage_subjects.php?department_id=" . $_GET['department_id']);
    exit();
}

// Handle editing a subject
if (isset($_POST['edit_subject'])) {
    $subject_id = $_POST['subject_id'];
    $subject_name = $_POST['subject_name'];
    $unit_code = $_POST['unit_code'] ?? null;
    
    // Update subject
    $update_stmt = $pdo->prepare("UPDATE subjects SET name = ?, unit_code = ? WHERE id = ?");
    $update_stmt->execute([$subject_name, $unit_code, $subject_id]);

    // Redirect back to the manage subjects page
    header("Location: manage_subjects.php?department_id=" . $_POST['department_id']);
    exit();
}

// Fetch subjects for the selected course
$subjects = [];
if (isset($_GET['department_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.name, s.unit_code, c.name as course_name 
            FROM subjects s
            JOIN courses c ON s.course_id = c.id
            WHERE c.department_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$_GET['department_id']]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching subjects: " . $e->getMessage();
    }
}

// Display the subjects table
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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
    <h2>Manage Subjects</h2>

    <!-- Department Selection -->
    <form method="GET" action="manage_subjects.php" class="mb-3">
        <div class="row">
            <div class="col-md-6">
                <select name="department_id" class="form-control" onchange="this.form.submit()" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['id']; ?>" <?php echo isset($_GET['department_id']) && $_GET['department_id'] == $department['id'] ? 'selected' : ''; ?>>
                            <?php echo $department['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <?php if (isset($_GET['department_id']) && count($courses) > 0): ?>
        <form method="POST" action="" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo $course['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="subject_name" placeholder="Subject Name" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="unit_code" placeholder="Unit Code">
                </div>
            </div>
            <input type="hidden" name="department_id" value="<?php echo $_GET['department_id']; ?>">
            <button type="submit" name="add_subject" class="btn btn-primary mt-3">Add Subject</button>
        </form>

        <h3>Subjects in the Selected Department</h3>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Unit Code</th>
                    <th>Subject Name</th>
                    <th>Course</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($subjects)): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($subject['unit_code'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                            <td><?php echo htmlspecialchars($subject['course_name']); ?></td>
                            <td>
                                <!-- Edit Subject Form -->
                                <a href="edit_subject.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                
                                <!-- Delete Subject -->
                                <a href="manage_subjects.php?department_id=<?php echo $_GET['department_id']; ?>&delete_subject_id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No subjects available for this department.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No courses available for this department.</p>
    <?php endif; ?>

</div>

</div>
</body>
</html>

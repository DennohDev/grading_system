<?php
session_start();
include 'includes/db.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$subject_id = $_GET['id'] ?? null;
if (!$subject_id) {
    header("Location: manage_subjects.php");
    exit();
}

// Fetch subject details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    header("Location: manage_subjects.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
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
            <h2>Edit Subject</h2>

            <form method="POST" action="manage_subjects.php" class="mt-4">
                <div class="mb-3">
                    <label for="subject_name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="subject_name" name="subject_name" 
                           value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="unit_code" class="form-label">Unit Code</label>
                    <input type="text" class="form-control" id="unit_code" name="unit_code" 
                           value="<?php echo htmlspecialchars($subject['unit_code'] ?? ''); ?>">
                </div>
                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                <input type="hidden" name="department_id" value="<?php echo $_GET['department_id'] ?? ''; ?>">
                <button type="submit" name="edit_subject" class="btn btn-primary">Update Subject</button>
                <a href="manage_subjects.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
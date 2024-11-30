<?php
session_start();
include 'includes/db.php';

// Handle CSV upload
if (isset($_POST['upload_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $csvFile = $_FILES['csv_file']['tmp_name'];

        // Open the file and process it
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $rowCount = 0;
            $errors = [];
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Skip the header row
                if ($rowCount == 0) {
                    $rowCount++;
                    continue;
                }

                // Map CSV columns to variables
                [$name, $admission_number, $course_id] = $data;

                // Validate and insert student data
                if ($name && $admission_number && $course_id) {
                    $stmt = $pdo->prepare("INSERT INTO students (name, admission_number, course_id) VALUES (?, ?, ?)");
                    try {
                        $stmt->execute([$name, $admission_number, $course_id]);
                        $rowCount++;
                    } catch (PDOException $e) {
                        $errors[] = "Error on row $rowCount: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "Error on row $rowCount: Missing data.";
                }
            }
            fclose($handle);

            // Set success or error messages
            if (empty($errors)) {
                $_SESSION['success_message'] = "$rowCount students were successfully imported!";
            } else {
                $_SESSION['error_message'] = implode('<br>', $errors);
            }
        } else {
            $_SESSION['error_message'] = "Unable to open the file.";
        }
    } else {
        $_SESSION['error_message'] = "Please upload a valid CSV file.";
    }

    // Redirect back to the import page
    header("Location: import_csv.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    <?php include 'admin_sidebar.php'; ?>

    <div class="content" id="mainContent">
        <div class="container mt-5">
            <h2>Import Students via CSV</h2>

            <!-- Display Success or Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div id="success-alert" class="alert alert-success text-center">
                    <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div id="error-alert" class="alert alert-danger text-center">
                    <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- CSV Upload Form -->
            <form method="post" enctype="multipart/form-data" class="mt-4">
                <div class="mb-3">
                    <label for="csvFile" class="form-label">Upload CSV File</label>
                    <input type="file" class="form-control" name="csv_file" id="csvFile" accept=".csv" required>
                </div>
                <button type="submit" name="upload_csv" class="btn btn-primary">Import Students</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Automatically hide alerts after 3 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);
    </script>
</body>

</html>

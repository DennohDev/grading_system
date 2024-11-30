<?php
session_start();
include 'includes/db.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Add this new endpoint at the top of the file, after session_start()
if (isset($_GET['ajax_search'])) {
    $search = $_GET['term'] ?? '';
    $course = $_GET['course_id'] ?? '';
    
    $query = "
        SELECT s.id, s.name, s.admission_number, c.name as course_name
        FROM students s
        JOIN courses c ON s.course_id = c.id
        WHERE (s.name LIKE ? OR s.admission_number LIKE ?)
    ";
    $params = ["%$search%", "%$search%"];
    
    if ($course) {
        $query .= " AND c.id = ?";
        $params[] = $course;
    }
    
    $query .= " LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Initialize variables
$selectedStudent = $_GET['student_id'] ?? '';
$students = [];
$transcript = [];
$studentInfo = null;

// Add course filter and search parameters
$selectedCourse = $_GET['course_id'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Modify the students query to include filtering
try {
    $query = "
        SELECT s.id, s.name, s.admission_number, c.id as course_id, c.name as course_name, d.name as department_name
        FROM students s
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE 1=1
    ";
    $params = [];

    if ($selectedCourse) {
        $query .= " AND c.id = ?";
        $params[] = $selectedCourse;
    }

    if ($searchTerm) {
        $query .= " AND (s.name LIKE ? OR s.admission_number LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }

    $query .= " ORDER BY s.name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all courses for the filter dropdown
    $coursesStmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages['error'] = "Error fetching data: " . $e->getMessage();
}

// Fetch transcript data if student is selected
if ($selectedStudent) {
    try {
        // Get student info
        $studentStmt = $pdo->prepare("
            SELECT s.*, c.name as course_name, d.name as department_name
            FROM students s
            JOIN courses c ON s.course_id = c.id
            JOIN departments d ON c.department_id = d.id
            WHERE s.id = ?
        ");
        $studentStmt->execute([$selectedStudent]);
        $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);

        // Get grades
        $gradesStmt = $pdo->prepare("
            SELECT sub.name as subject_name, sub.unit_code, g.grade
            FROM grades g
            JOIN subjects sub ON g.subject_id = sub.id
            WHERE g.student_id = ?
            ORDER BY sub.name
        ");
        $gradesStmt->execute([$selectedStudent]);
        $transcript = $gradesStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messages['error'] = "Error fetching transcript: " . $e->getMessage();
    }
}

function getGradePoint($numericalGrade) {
    if ($numericalGrade >= 80) return 1;
    if ($numericalGrade >= 75) return 2;
    if ($numericalGrade >= 65) return 3;
    if ($numericalGrade >= 55) return 4;
    if ($numericalGrade >= 45) return 5;
    if ($numericalGrade >= 40) return 6;
    return 7;
}

function getOutcome($gradePoint) {
    if ($gradePoint <= 2) return 'DISTINCTION';
    if ($gradePoint <= 4) return 'CREDIT';
    if ($gradePoint <= 6) return 'PASS';
    return 'FAIL';
}

function calculateMeanGrade($grades) {
    if (empty($grades)) return 'N/A';
    
    $totalPoints = 0;
    foreach ($grades as $grade) {
        $totalPoints += getGradePoint($grade['grade']);
    }
    
    $meanPoint = $totalPoints / count($grades);
    return getOutcome($meanPoint);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transcripts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            /* Hide browser's default headers and footers */
            @page {
                margin: 0;
                size: A4;
            }
            
            /* Hide unwanted elements */
            .no-print, 
            .navbar, 
            .admin-sidebar {
                display: none !important;
            }
            
            /* Reset body margins */
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Container styling for print */
            .transcript-container {
                margin: 20mm !important;
                padding: 0 !important;
                width: auto !important;
                height: auto !important;
            }
            
            /* Ensure table borders print correctly */
            .table {
                border-collapse: collapse !important;
                width: 100% !important;
            }
            
            /* Force background colors to print */
            .table-dark {
                background-color: #343a40 !important;
                color: white !important;
            }
            
            /* Hide any other unwanted elements */
            .print-header,
            .print-footer {
                display: none !important;
            }
        }
        .transcript-container {
            max-width: 210mm;
            margin: auto;
            padding: 20px;
        }
        .top-space {
            height: 10vh;
        }
        .bottom-space {
            height: 5vh;
        }
        
        .student-info {
            margin-bottom: 30px;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
        }
        
        
    </style>
</head>
<body>
    <div class="no-print">
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
                <h2>Manage Transcripts</h2>
                
                <!-- Student Selection Form -->
                <form method="GET" class="mb-4" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" 
                                   id="studentSearch" 
                                   class="form-control" 
                                   placeholder="Search by name or admission number"
                                   autocomplete="off">
                            <input type="hidden" name="student_id" id="selectedStudentId">
                            <div id="searchResults" class="position-absolute bg-white border rounded shadow-sm" style="display:none; z-index:1000; width:95%;"></div>
                        </div>
                        <div class="col-md-4">
                            <select name="course_id" id="courseFilter" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"
                                            <?php if ($selectedCourse == $course['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <?php if ($selectedStudent): ?>
                                <button type="button" class="btn btn-secondary" onclick="window.print()">
                                    Print
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($selectedStudent && $studentInfo): ?>
        <div class="transcript-container">
            <!-- Top Space -->
            <div class="top-space"></div>

            <h2 class="text-center mb-4"><u>TRANSCRIPT OF ACADEMIC RECORDS</u></h2>


            <!-- Student Information -->
            <div class="student-info">
                <div class="row">
                    <div class="col-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($studentInfo['name']); ?></p>
                        <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($studentInfo['admission_number']); ?></p>
                        <p><strong>Mean Grade:</strong> <?php echo calculateMeanGrade($transcript); ?></p>

                    </div>
                    <div class="col-6">
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($studentInfo['department_name']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($studentInfo['course_name']); ?></p>
                        <p><strong>Date Issued:</strong> <?php echo date('d/m/Y'); ?></p>
                    </div>
                
                </div>
            </div>

            <!-- Grades Table -->
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Unit Code</th>
                        <th>Subject</th>
                        <th>Marks (%)</th>
                        <th>Grade Point</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Update the grades query to include unit_code
                    $gradesStmt = $pdo->prepare("
                        SELECT sub.name as subject_name, sub.unit_code, g.grade
                        FROM grades g
                        JOIN subjects sub ON g.subject_id = sub.id
                        WHERE g.student_id = ?
                        ORDER BY sub.name
                    ");
                    
                    foreach ($transcript as $grade): 
                        $gradePoint = getGradePoint($grade['grade']);
                        $outcome = getOutcome($gradePoint);
                    ?>
                        <tr>
                            <td><?php echo $grade['unit_code'] ? htmlspecialchars($grade['unit_code']) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                            <td><?php echo $gradePoint; ?></td>
                            <td><?php echo $outcome; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- bottom space -->
            <div class="bottom-space"></div>
            <!-- Signature Section -->
            <div class="signature-section">
                <div>
                    <div class="signature-line">Head of Academics</div>
                </div>
                <div>
                    <div class="signature-line">Principal</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer text-center mt-5">
                <p>This is an unofficial document of Nairobi Institute of Ecommerce (NIE) Technical College</p>
                
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('studentSearch');
        const searchResults = document.getElementById('searchResults');
        const selectedStudentId = document.getElementById('selectedStudentId');
        const courseFilter = document.getElementById('courseFilter');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            if (this.value.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                const courseId = courseFilter.value;
                fetch(`manage_transcripts.php?ajax_search=1&term=${encodeURIComponent(this.value)}&course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(student => {
                                const div = document.createElement('div');
                                div.className = 'p-2 hover-bg-light cursor-pointer';
                                div.style.cursor = 'pointer';
                                div.innerHTML = `${student.admission_number} - ${student.name} (${student.course_name})`;
                                div.addEventListener('click', () => {
                                    searchInput.value = `${student.admission_number} - ${student.name}`;
                                    selectedStudentId.value = student.id;
                                    searchResults.style.display = 'none';
                                    document.getElementById('searchForm').submit();
                                });
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.style.display = 'none';
                        }
                    });
            }, 300);
        });

        courseFilter.addEventListener('change', function() {
            if (searchInput.value.length >= 2) {
                searchInput.dispatchEvent(new Event('input'));
            }
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>

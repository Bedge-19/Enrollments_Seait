<?php
// department/student_grades.php - View & Encode Student Historical Grades
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Student Grades & Transcript';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 2;

$studentId = (int)($_GET['student_id'] ?? 0);
$student = null;
$grades = [];
$academicYears = $db->query("SELECT AcademicYearID, SchoolYear, Semester FROM academic_year ORDER BY AcademicYearID DESC")->fetchAll();
$allSubjects = $db->query("SELECT SubjectID, SubjectCode, SubjectTitle, Units FROM subject ORDER BY SubjectCode")->fetchAll();

// Handle Grade Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $targetStudentId = (int)$_POST['student_id'];
    $subjectId = (int)$_POST['subject_id'];
    $acadYearId = (int)$_POST['academic_year_id'];
    $finalGrade = (float)$_POST['final_grade'];
    $gradeStatus = ($finalGrade <= 3.00 && $finalGrade >= 1.00) ? 'Passed' : 'Failed';

    try {
        $stmtIns = $db->prepare("INSERT INTO student_grades 
            (StudentID, SubjectID, AcademicYearID, FinalGrade, GradeStatus, EncodedBy, DateEncoded) 
            VALUES (?, ?, ?, ?, ?, ?, CURDATE())
            ON DUPLICATE KEY UPDATE FinalGrade = VALUES(FinalGrade), GradeStatus = VALUES(GradeStatus), DateEncoded = CURDATE()");
        $stmtIns->execute([$targetStudentId, $subjectId, $acadYearId, $finalGrade, $gradeStatus, $staffId]);

        logActivity('ENCODE_GRADE', 'Department', 'student_grades', $targetStudentId, "Encoded grade {$finalGrade} ({$gradeStatus}) for Subject ID {$subjectId}");
        setFlash('success', "Grade saved successfully ({$gradeStatus})!");
    } catch (Exception $e) {
        setFlash('danger', "Error encoding grade: " . $e->getMessage());
    }
    header("Location: " . BASE_URL . "/department/student_grades.php?student_id=" . $targetStudentId);
    exit;
}

if ($studentId > 0) {
    $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
        FROM student s 
        LEFT JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        WHERE s.StudentID = ?");
    $stmtStd->execute([$studentId]);
    $student = $stmtStd->fetch();

    if ($student) {
        $stmtG = $db->prepare("SELECT g.*, sub.SubjectCode, sub.SubjectTitle, sub.Units, ay.SchoolYear, ay.Semester 
            FROM student_grades g 
            JOIN subject sub ON sub.SubjectID = g.SubjectID 
            JOIN academic_year ay ON ay.AcademicYearID = g.AcademicYearID 
            WHERE g.StudentID = ? 
            ORDER BY ay.AcademicYearID DESC, sub.SubjectCode ASC");
        $stmtG->execute([$studentId]);
        $grades = $stmtG->fetchAll();
    }
}

// Student search helper if no student selected
$searchResults = [];
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '' && !$student) {
    $stmtS = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName 
        FROM student s 
        JOIN program p ON p.ProgramID = s.ProgramID 
        WHERE s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? 
        LIMIT 15");
    $term = "%{$searchQuery}%";
    $stmtS->execute([$term, $term, $term]);
    $searchResults = $stmtS->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Student Academic Grades & Records</h4>
        <p class="text-muted small mb-0">View historical grades, encode past term evaluations, and print grade certifications.</p>
    </div>
    <?php if ($student): ?>
        <div>
            <a href="<?= BASE_URL ?>/department/print_grades.php?student_id=<?= $student['StudentID'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-print me-1"></i> Print Grade Certification
            </a>
            <a href="<?= BASE_URL ?>/department/student_grades.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-search me-1"></i> Select Another Student
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$student): ?>
    <!-- Search Student Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i>Look up Student to Manage Grades</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Enter Student Number (e.g. 2024-00001) or Student Name..." value="<?= e($searchQuery) ?>" autofocus required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Search Student</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <div class="list-group mt-3">
                    <div class="list-group-item bg-light fw-bold small text-uppercase">Matching Students (Click to select):</div>
                    <?php foreach ($searchResults as $res): ?>
                        <a href="?student_id=<?= $res['StudentID'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= e($res['StudentNo']) ?></span> &mdash; 
                                <span class="text-dark fw-semibold"><?= e($res['LastName'] . ', ' . $res['FirstName'] . ' ' . $res['MiddleName']) ?></span>
                                <small class="text-muted d-block"><?= e($res['ProgramName']) ?></small>
                            </div>
                            <span class="btn btn-sm btn-outline-primary"><i class="fas fa-arrow-right"></i></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($searchQuery !== ''): ?>
                <div class="alert alert-warning mt-3 mb-0 small">No students found matching "<?= e($searchQuery) ?>".</div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Student Banner -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold text-dark mb-1"><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></h5>
                    <div class="text-muted small">
                        <strong>Student ID:</strong> <span class="text-primary fw-bold"><?= e($student['StudentNo']) ?></span> &bull; 
                        <strong>Program:</strong> <?= e($student['ProgramName']) ?> &bull; 
                        <strong>Type:</strong> <span class="badge bg-secondary"><?= e($student['TypeName']) ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                        <i class="fas fa-plus me-1"></i> Encode Subject Grade
                    </button>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Academic Term</th>
                        <th>Subject Code</th>
                        <th>Subject Title</th>
                        <th class="text-center">Units</th>
                        <th class="text-center">Final Grade</th>
                        <th class="text-center">Status</th>
                        <th>Date Encoded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No academic grades recorded for this student yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td class="small fw-semibold"><?= e($g['SchoolYear']) ?> (<?= e($g['Semester']) ?>)</td>
                                <td class="fw-bold text-primary"><?= e($g['SubjectCode']) ?></td>
                                <td><?= e($g['SubjectTitle']) ?></td>
                                <td class="text-center"><?= number_format((float)$g['Units'], 1) ?></td>
                                <td class="text-center fw-bold <?= ($g['FinalGrade'] <= 3.00 && $g['FinalGrade'] > 0) ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format((float)$g['FinalGrade'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $g['GradeStatus'] === 'Passed' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= e($g['GradeStatus']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= formatDate($g['DateEncoded']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Encode Grade Modal -->
    <div class="modal fade" id="addGradeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="add_grade" value="1">
                    <input type="hidden" name="student_id" value="<?= $student['StudentID'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Encode Subject Grade</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Academic Year / Semester</label>
                            <select name="academic_year_id" class="form-select form-select-sm" required>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= $ay['AcademicYearID'] ?>"><?= e($ay['SchoolYear'] . ' - ' . $ay['Semester'] . ' Sem') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Subject</label>
                            <select name="subject_id" class="form-select form-select-sm" required>
                                <?php foreach ($allSubjects as $sub): ?>
                                    <option value="<?= $sub['SubjectID'] ?>"><?= e($sub['SubjectCode'] . ' - ' . $sub['SubjectTitle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Final Grade Rating (e.g. 1.00, 1.25, 1.50, 1.75, 2.00, 3.00, 5.00)</label>
                            <input type="number" step="0.25" min="1.00" max="5.00" name="final_grade" class="form-control form-control-sm" value="1.75" required>
                            <small class="text-muted">Ratings &le; 3.00 are automatically marked as <strong>Passed</strong>. 5.00 is <strong>Failed</strong>.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Save Grade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

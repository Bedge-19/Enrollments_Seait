<?php
// student/grades.php - Academic History & Grades View
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Student');
$pageTitle = 'Academic History & Grades';
$db = getDBConnection();
$studentId = $_SESSION['student_id'];

// Fetch historical grades
$stmtGrades = $db->prepare("SELECT g.*, sub.SubjectCode, sub.SubjectTitle, sub.Units, ay.SchoolYear, ay.Semester 
    FROM student_grades g 
    JOIN subject sub ON sub.SubjectID = g.SubjectID 
    JOIN academic_year ay ON ay.AcademicYearID = g.AcademicYearID 
    WHERE g.StudentID = ? 
    ORDER BY ay.AcademicYearID DESC, sub.SubjectCode ASC");
$stmtGrades->execute([$studentId]);
$grades = $stmtGrades->fetchAll();

// Fetch credited subjects (for shifters / transferees)
$stmtCredits = $db->prepare("SELECT cs.*, sub.SubjectCode, sub.SubjectTitle 
    FROM credited_subject cs 
    JOIN subject sub ON sub.SubjectID = cs.SubjectID 
    WHERE cs.StudentID = ? 
    ORDER BY cs.CreditID DESC");
$stmtCredits->execute([$studentId]);
$credited = $stmtCredits->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">Academic Records & Grades</h4>
    <p class="text-muted small mb-0">Review your past term grades, passed subjects, and credited transfer courses.</p>
</div>

<!-- Term Grades Table -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i>Subject Grades History</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Subject Code</th>
                    <th>Subject Title</th>
                    <th class="text-center">Units</th>
                    <th class="text-center">Final Grade</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grades)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No historical grades recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($grades as $g): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($g['SchoolYear']) ?> (<?= e($g['Semester']) ?>)</td>
                            <td class="fw-bold text-dark"><?= e($g['SubjectCode']) ?></td>
                            <td><?= e($g['SubjectTitle']) ?></td>
                            <td class="text-center"><?= number_format((float)$g['Units'], 1) ?></td>
                            <td class="text-center fw-bold <?= ($g['FinalGrade'] <= 3.00 && $g['FinalGrade'] > 0) ? 'text-success' : 'text-danger' ?>">
                                <?= $g['FinalGrade'] ? number_format((float)$g['FinalGrade'], 2) : 'N/A' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($g['GradeStatus'] === 'Passed'): ?>
                                    <span class="badge bg-success">Passed</span>
                                <?php elseif ($g['GradeStatus'] === 'Failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= e($g['GradeStatus']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Credited Subjects Table (if any) -->
<?php if (!empty($credited)): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-info"><i class="fas fa-exchange-alt me-2"></i>Credited Courses (Transferee / Shifter)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Previous Institution</th>
                        <th>Original Subject & Grade</th>
                        <th>Credited Equivalent</th>
                        <th class="text-center">Units</th>
                        <th class="text-center">Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($credited as $c): ?>
                        <tr>
                            <td><?= e($c['PreviousInstitution'] ?? 'External School') ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($c['OriginalSubjectName']) ?></div>
                                <small class="text-muted">Grade: <?= e($c['OriginalGrade']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= e($c['SubjectCode']) ?></div>
                                <small class="text-muted"><?= e($c['SubjectTitle']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= number_format((float)$c['EquivalentUnits'], 1) ?></td>
                            <td class="text-center">
                                <?php if ($c['IsCredited'] == 1): ?>
                                    <span class="badge bg-success">Credited</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Credited</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e($c['Remarks'] ?? $c['ReasonIfNotCredited'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

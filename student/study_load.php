<?php
// student/study_load.php - Student Detailed Study Load and Schedule View
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Student');
$pageTitle = 'My Study Load & Schedule';
$db = getDBConnection();
$studentId = $_SESSION['student_id'];

// Fetch latest enrollment
$stmtEnr = $db->prepare("SELECT e.*, sec.SectionCode, sec.YearLevel 
    FROM enrollment e 
    LEFT JOIN blocking b ON b.EnrollmentID = e.EnrollmentID 
    LEFT JOIN section sec ON sec.SectionID = b.SectionID 
    WHERE e.StudentID = ? 
    ORDER BY e.EnrollmentID DESC LIMIT 1");
$stmtEnr->execute([$studentId]);
$enrollment = $stmtEnr->fetch();

$enrolledSubjects = [];
if ($enrollment) {
    $stmtSubjs = $db->prepare("SELECT sub.*, sec.SectionCode, sch.Day, sch.TimeStart, sch.TimeEnd, 
        inst.FirstName as InstFirst, inst.LastName as InstLast 
        FROM enrollment_subject es 
        JOIN subject sub ON sub.SubjectID = es.SubjectID 
        LEFT JOIN section sec ON sec.SectionID = es.SectionID 
        LEFT JOIN schedule sch ON sch.SubjectID = sub.SubjectID AND sch.SectionID = sec.SectionID 
        LEFT JOIN instructor inst ON inst.InstructorID = sch.InstructorID 
        WHERE es.EnrollmentID = ?");
    $stmtSubjs->execute([$enrollment['EnrollmentID']]);
    $enrolledSubjects = $stmtSubjs->fetchAll();
}

$totalUnits = 0;
foreach ($enrolledSubjects as $s) {
    $totalUnits += (float)$s['Units'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Official Study Load</h4>
        <p class="text-muted small mb-0">Enrolled subjects, class schedules, and instructors for the current semester.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/student/print_study_load.php" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-print me-1"></i> Print Study Load (COR)
        </a>
    </div>
</div>

<?php if ($enrollment): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-body bg-light border-bottom">
            <div class="row g-3">
                <div class="col-sm-3">
                    <small class="text-muted d-block">Academic Term</small>
                    <span class="fw-bold"><?= e($enrollment['SchoolYear']) ?> (<?= e($enrollment['Semester']) ?> Sem)</span>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Assigned Section / Block</small>
                    <span class="fw-bold text-primary"><?= e($enrollment['SectionCode'] ?? 'Unassigned') ?></span>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Enrollment Status</small>
                    <span class="badge bg-success"><?= e($enrollment['Status']) ?></span>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Total Enrolled Units</small>
                    <span class="fw-bold text-dark"><?= number_format($totalUnits, 1) ?> Units</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Title</th>
                        <th class="text-center">Units</th>
                        <th>Type / Class</th>
                        <th>Day</th>
                        <th>Time Schedule</th>
                        <th>Instructor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrolledSubjects)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No enrolled subjects found for this term.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrolledSubjects as $s): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= e($s['SubjectCode']) ?></td>
                                <td><?= e($s['SubjectTitle']) ?></td>
                                <td class="text-center fw-bold"><?= number_format((float)$s['Units'], 1) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($s['SubjectType']) ?></span>
                                    <small class="text-muted d-block"><?= e($s['Classification']) ?></small>
                                </td>
                                <td><?= e($s['Day'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($s['TimeStart'])): ?>
                                        <?= date('h:i A', strtotime($s['TimeStart'])) ?> - <?= date('h:i A', strtotime($s['TimeEnd'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['InstFirst'])): ?>
                                        <?= e($s['InstFirst'] . ' ' . $s['InstLast']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">TBA</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">Total Enrolled Load:</td>
                            <td class="text-center text-primary"><?= number_format($totalUnits, 1) ?> Units</td>
                            <td colspan="4"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm p-5 text-center">
        <div class="text-muted mb-3"><i class="fas fa-folder-open fa-3x"></i></div>
        <h5 class="fw-bold">No Active Enrollment Record</h5>
        <p class="text-muted small">You do not have an active study load registered for the current term.</p>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

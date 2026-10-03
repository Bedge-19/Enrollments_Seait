<?php
// registrar/index.php - Registrar Dashboard & Queue
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Registrar');
$pageTitle = 'Registrar Dashboard';
$db = getDBConnection();

// Metrics
$stmtAwaiting = $db->query("SELECT COUNT(*) 
    FROM admission a 
    JOIN student s ON s.StudentID = a.StudentID 
    LEFT JOIN evaluation ev ON ev.StudentID = s.StudentID 
    WHERE a.Status = 'Approved' AND (ev.Status IS NULL OR ev.Status = 'Pending')");
$awaitingEvaluation = (int)$stmtAwaiting->fetchColumn();

$stmtEnrolled = $db->query("SELECT COUNT(*) FROM enrollment WHERE SchoolYear = '2026-2027' AND Semester = '1st'");
$totalEnrolled = (int)$stmtEnrolled->fetchColumn();

$stmtSections = $db->query("SELECT COUNT(*) FROM section");
$totalSections = (int)$stmtSections->fetchColumn();

$stmtSchedules = $db->query("SELECT COUNT(*) FROM schedule");
$totalSchedules = (int)$stmtSchedules->fetchColumn();

// Queue of approved students awaiting evaluation & load assignment
$stmtQueue = $db->query("SELECT a.AdmissionID, a.ApprovalDate, s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, s.Sex, p.ProgramName, st.TypeName,
    ev.EvaluationID, ev.Status as EvalStatus 
    FROM admission a 
    JOIN student s ON s.StudentID = a.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN evaluation ev ON ev.StudentID = s.StudentID 
    WHERE a.Status = 'Approved' AND (ev.Status IS NULL OR ev.Status = 'Pending')
    ORDER BY a.ApprovalDate DESC, s.StudentID DESC 
    LIMIT 15");
$queue = $stmtQueue->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Registrar Evaluation & Loading</h4>
        <p class="text-muted small mb-0">Evaluate curriculum requirements, assign study loads, allocate block sections, and verify class schedules.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/registrar/schedules.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-calendar-alt me-1"></i> Schedules
        </a>
        <a href="<?= BASE_URL ?>/registrar/sections.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-layer-group me-1"></i> Sections
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Awaiting Study Load</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($awaitingEvaluation) ?></h3>
                    <small class="text-muted">Approved by Dept.</small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Study Loads Created</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalEnrolled) ?></h3>
                    <small class="text-muted">A.Y. 2026-2027 (1st Sem)</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-book-reader"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Active Sections</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($totalSections) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/registrar/sections.php" class="text-decoration-none fw-semibold">Manage blocks &rarr;</a></small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-layer-group"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card stat-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Master Schedules</span>
                    <h3 class="fw-bold my-1 text-primary"><?= number_format($totalSchedules) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/registrar/schedules.php" class="text-decoration-none fw-semibold">View master &rarr;</a></small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Evaluation Queue Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-tasks me-2 text-warning"></i>Study Load Assignment Queue (Step 2)</h6>
            <small class="text-muted">Students endorsed by Department ready for curriculum evaluation and schedule assignment</small>
        </div>
        <span class="badge bg-light text-dark border">Top <?= count($queue) ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th>Classification</th>
                    <th>Dept. Approval</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($queue)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-check-double text-success fa-2x mb-2 d-block"></i>
                            No students currently waiting for registrar evaluation.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($q['StudentNo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($q['LastName'] . ', ' . $q['FirstName'] . ' ' . $q['MiddleName']) ?></div>
                                <small class="text-muted"><?= e($q['Sex']) ?></small>
                            </td>
                            <td><?= e($q['ProgramName']) ?></td>
                            <td><span class="badge badge-soft-dark"><?= e($q['TypeName']) ?></span></td>
                            <td class="small text-muted"><i class="fas fa-check text-success me-1"></i><?= formatDate($q['ApprovalDate']) ?></td>
                            <td>
                                <span class="badge badge-soft-warning">Awaiting Load</span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/registrar/evaluate.php?student_id=<?= $q['StudentID'] ?>" class="btn btn-primary btn-sm shadow-sm">
                                    <i class="fas fa-edit me-1"></i> Assign Study Load
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

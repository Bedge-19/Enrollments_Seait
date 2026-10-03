<?php
// clinic/index.php - Clinic & Health Services Dashboard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Clinic');
$pageTitle = 'Clinic & Health Services';
$db = getDBConnection();

// Metrics
$stmtPending = $db->query("SELECT COUNT(*) FROM clinic WHERE ClearanceStatus = 'Pending'");
$pendingMedical = (int)$stmtPending->fetchColumn();

$stmtCleared = $db->query("SELECT COUNT(*) FROM clinic WHERE ClearanceStatus = 'Cleared'");
$clearedMedical = (int)$stmtCleared->fetchColumn();

$stmtRecent = $db->query("SELECT c.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, s.Sex, p.ProgramName 
    FROM clinic c 
    JOIN student s ON s.StudentID = c.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    ORDER BY c.ClinicID DESC 
    LIMIT 15");
$recentExams = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Institute Health Services &amp; Clinic</h4>
        <p class="text-muted small mb-0">Record physical examination data, calculate BMI metrics, manage medical remarks, and grant clearance.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/clinic/medical_record.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-heartbeat me-1"></i> Medical Examination / Clearance
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Medically Cleared</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($clearedMedical) ?></h3>
                    <small class="text-muted">Cleared in Clinic Step 4</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Awaiting Clearance</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($pendingMedical) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/clinic/medical_record.php" class="text-decoration-none fw-semibold">Process clearance &rarr;</a></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Health Protocols</span>
                    <h3 class="fw-bold my-1 text-info">100% Active</h3>
                    <small class="text-muted">Physical Exam & BMI Standards</small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-notes-medical"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Medical Records Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-file-medical-alt me-2 text-danger"></i>Recent Medical Evaluations</h6>
            <small class="text-muted">Latest student physical exams, height/weight, and BMI clearance records</small>
        </div>
        <a href="<?= BASE_URL ?>/clinic/medical_record.php" class="btn btn-outline-primary btn-sm">Process New Exam</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th class="text-center">Height / Weight</th>
                    <th class="text-center">BMI</th>
                    <th>Clearance Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentExams)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-heartbeat fa-2x text-muted mb-2 d-block"></i>
                            No medical records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentExams as $c): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($c['StudentNo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($c['LastName'] . ', ' . $c['FirstName']) ?></div>
                                <small class="text-muted"><?= e($c['Sex']) ?></small>
                            </td>
                            <td><?= e($c['ProgramName']) ?></td>
                            <td class="text-center small">
                                <?= $c['HeightCM'] ? $c['HeightCM'] . ' cm' : '-' ?> / 
                                <?= $c['WeightKG'] ? $c['WeightKG'] . ' kg' : '-' ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?php if ($c['BMI']): ?>
                                    <span class="badge badge-soft-dark"><?= number_format((float)$c['BMI'], 1) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $c['ClearanceStatus'] === 'Cleared' ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                                    <?= e($c['ClearanceStatus']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/clinic/medical_record.php?student_id=<?= $c['StudentID'] ?>" class="btn btn-sm btn-outline-primary" title="Update Record">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($c['ClearanceStatus'] === 'Cleared'): ?>
                                    <a href="<?= BASE_URL ?>/clinic/print_clearance.php?student_id=<?= $c['StudentID'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Clearance">
                                        <i class="fas fa-print"></i>
                                    </a>
                                <?php endif; ?>
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

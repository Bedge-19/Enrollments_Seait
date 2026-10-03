<?php
// student/id_card.php - Student ID Card View & Validation Status
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Student');
$pageTitle = 'My Student ID Card';
$db = getDBConnection();
$studentId = (int)$_SESSION['student_id'];

// Get Student details
$stmt = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName,
    sp.GuardianName, sp.GuardianContactNo, sp.Address as ProfileAddress,
    iv.ValidationDate, iv.Status as IdStatus,
    chk.OverallStatus, chk.SecurityStatus
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN student_profile sp ON sp.StudentID = s.StudentID 
    LEFT JOIN id_validation iv ON iv.StudentID = s.StudentID 
    LEFT JOIN vw_enrollment_checklist chk ON chk.StudentID = s.StudentID 
    WHERE s.StudentID = ?
    ORDER BY iv.ValidationID DESC LIMIT 1");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$isConfirmed = ($student['OverallStatus'] ?? '') === 'CONFIRMED';
$isNewOrTransferee = in_array(strtolower($student['TypeName'] ?? ''), ['new', 'transferee']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-header mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Official Student ID Card</h4>
        <p class="text-muted small mb-0">View your official campus digital identification card and term validation status issued by the Security Office.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-print me-1"></i> Print ID Card (Front & Back)
        </a>
        <?php if (!$isNewOrTransferee): ?>
            <a href="<?= BASE_URL ?>/security/print_validation_sticker.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-stamp me-1"></i> Print Validation Sticker
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Status & ID Information -->
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-alt text-primary me-2"></i>ID Validation Status</h6>
            </div>
            <div class="card-body">
                <?php if ($isConfirmed): ?>
                    <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-3">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                        <div>
                            <h6 class="fw-bold mb-0">
                                <?= $isNewOrTransferee ? 'Student ID Generated' : 'Student ID Validated' ?>
                            </h6>
                            <small>
                                <?= $isNewOrTransferee 
                                    ? 'Your new official Student ID card has been issued for Academic Year 2026-2027.' 
                                    : 'Your existing Student ID card is validated for A.Y. 2026-2027 1st Semester.' ?>
                            </small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning d-flex align-items-center gap-3 p-3 mb-3">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Awaiting Final Security Clearance</h6>
                            <small>Complete your clearances across Department, Registrar, Accounting, and Clinic to receive your Security ID confirmation.</small>
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Student Number:</span>
                        <span class="fw-bold text-primary"><?= e($student['StudentNo']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Student Classification:</span>
                        <span class="badge <?= $isNewOrTransferee ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= e($student['TypeName']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Degree Program:</span>
                        <span class="fw-bold text-end" style="max-width: 220px;"><?= e($student['ProgramName']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Security Office Approval:</span>
                        <span class="badge <?= ($student['SecurityStatus'] ?? '') === 'Completed' ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                            <?= ($student['SecurityStatus'] ?? '') === 'Completed' ? '✓ Verified & Cleared' : '⏳ Pending' ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Validation Date:</span>
                        <span class="fw-semibold"><?= formatDate($student['ValidationDate'] ?? date('Y-m-d')) ?></span>
                    </li>
                </ul>

                <div class="d-grid gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/security/print_id.php?student_id=<?= $studentId ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Open Full High-Res Printable ID
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Digital ID Preview Card -->
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-id-card text-primary me-2"></i>Digital ID Card Preview</h6>
                <span class="badge bg-light text-dark border">CR80 Official Format</span>
            </div>
            <div class="card-body text-center py-4 bg-light">
                <div class="d-inline-block text-start" style="width: 320px; height: 500px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.15); background: linear-gradient(180deg, #0f2b48 0%, #0d47a1 28%, #ffffff 28.5%, #ffffff 92%, #0d47a1 92%); border: 1px solid #cbd5e1; display: flex; flex-direction: column;">
                    <!-- Front Header -->
                    <div class="text-center text-white p-2">
                        <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="width: 40px; height: 40px; border-radius: 50%; background: white; padding: 2px; margin-bottom: 4px;">
                        <div style="font-family:'Montserrat',sans-serif; font-size: 9.5px; font-weight: 800; text-transform: uppercase;">South East Asian Institute of Technology, Inc.</div>
                        <div style="font-size: 7px; color: #fde047; font-weight: 600;">STUDENT IDENTIFICATION CARD</div>
                    </div>

                    <!-- Front Body -->
                    <div class="p-3 text-center d-flex flex-column align-items-center flex-grow-1">
                        <div style="width: 100px; height: 100px; border-radius: 12px; border: 3px solid #0d47a1; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 800; margin-bottom: 8px;">
                            <?= strtoupper(substr($student['FirstName'] ?? 'S', 0, 1) . substr($student['LastName'] ?? 'T', 0, 1)) ?>
                        </div>

                        <div style="background: #1e293b; color: white; font-family:'Montserrat',sans-serif; font-size: 12px; font-weight: 800; padding: 2px 12px; border-radius: 14px; margin-bottom: 6px;">
                            <?= e($student['StudentNo']) ?>
                        </div>

                        <div style="font-family:'Montserrat',sans-serif; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a; margin-bottom: 2px;">
                            <?= e($student['FirstName'] . ' ' . $student['LastName']) ?>
                        </div>

                        <div style="font-size: 9px; font-weight: 700; color: #0d47a1; text-transform: uppercase; margin-bottom: 6px; line-height: 1.2;">
                            <?= e($student['ProgramName']) ?>
                        </div>

                        <span class="badge <?= $isNewOrTransferee ? 'bg-info text-dark' : 'bg-secondary' ?>" style="font-size: 8px;">
                            <?= e($student['TypeName']) ?> STUDENT &bull; A.Y. 2026-2027
                        </span>

                        <div class="mt-auto pb-1 text-center w-100">
                            <div style="font-family: monospace; font-size: 14px; letter-spacing: 4px; color: #000;">||| |||| | |||||| ||||</div>
                            <small class="text-muted" style="font-size: 7.5px;">*<?= e($student['StudentNo']) ?>*</small>
                        </div>
                    </div>

                    <!-- Front Footer -->
                    <div class="text-white text-center py-1" style="background: #0d47a1; font-size: 7px; font-weight: 700; text-transform: uppercase;">
                        Official Property of SEAIT &bull; 2026-2027
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

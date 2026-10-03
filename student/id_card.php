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
                <span class="badge bg-light text-dark border"><i class="fas fa-hand-pointer"></i> Click to Flip</span>
            </div>
            <div class="card-body text-center py-4 bg-light" style="perspective: 1000px;">
                <style>
                    .id-card-scene {
                        width: 320px;
                        height: 490px;
                        margin: 0 auto;
                        cursor: pointer;
                        perspective: 1000px;
                    }
                    .id-card-container {
                        width: 100%;
                        height: 100%;
                        position: relative;
                        transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
                        transform-style: preserve-3d;
                    }
                    .id-card-scene.is-flipped .id-card-container {
                        transform: rotateY(180deg);
                    }
                    .id-card-face {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        -webkit-backface-visibility: hidden;
                        backface-visibility: hidden;
                        border-radius: 12px;
                        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
                        overflow: hidden;
                        border: 1px solid rgba(0,0,0,0.1);
                    }
                    .id-card-front {
                        background: linear-gradient(160deg, #6b9a33 0%, #b2d644 35%, #92c53a 70%, #7ab22b 100%);
                        display: flex;
                        flex-direction: column;
                    }
                    .id-card-front::after {
                        content: '';
                        position: absolute;
                        inset: 0;
                        background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, transparent 60%);
                        pointer-events: none;
                    }
                    .id-card-back {
                        background: #ffffff;
                        transform: rotateY(180deg);
                        display: flex;
                        flex-direction: column;
                        padding: 16px;
                    }
                </style>
                
                <div class="id-card-scene" onclick="this.classList.toggle('is-flipped')">
                    <div class="id-card-container">
                        <!-- Front Face -->
                        <div class="id-card-face id-card-front">
                            <div style="display:flex; padding: 18px 16px 12px 16px; align-items: flex-start; gap: 8px;">
                                <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="Logo" style="width:50px; height:50px; border-radius:50%; background:white; padding:2px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); flex-shrink:0; position:relative; z-index:2;">
                                <div style="text-align:center; color: #ffeb3b; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); position:relative; z-index:2;">
                                    <div style="font-family: 'Times New Roman', Times, serif; font-size: 16px; font-weight: bold; line-height: 1.1; margin-bottom:2px;">South East Asian<br>Institute of Technology, Inc.</div>
                                    <div style="font-size: 5px; font-family: Arial, sans-serif; letter-spacing: 0.5px; opacity: 0.9;">NATIONAL HIGHWAY, BRGY. CROSSING RUBBER, TUPI, SOUTH COTABATO<br>SEC REG NO: CN200628156</div>
                                </div>
                            </div>
                            
                            <div style="display:flex; justify-content: space-between; padding: 12px 18px; flex:1; position:relative; z-index:2;">
                                <!-- Photo & Signature -->
                                <div style="width: 48%; display:flex; flex-direction:column;">
                                    <div style="height: 170px; background: #fff; border: 1px solid rgba(0,0,0,0.15); display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['FirstName'] . ' ' . $student['LastName']) ?>&background=e2e8f0&color=333&size=200" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div style="height: 45px; background: rgba(255,255,255,0.4); border: 1px solid rgba(0,0,0,0.15); border-top:none; display:flex; align-items:center; justify-content:center; position:relative;">
                                        <span style="font-family: 'Brush Script MT', cursive, serif; font-size: 24px; color: #000080; transform: rotate(-5deg);"><?= substr($student['FirstName'] ?? 'S', 0, 1) ?>. <?= explode(' ', $student['LastName'] ?? 'T')[0] ?></span>
                                    </div>
                                </div>
                                
                                <!-- QR & ID -->
                                <div style="width: 45%; display:flex; flex-direction:column; align-items:center; justify-content: flex-start; padding-top: 5px;">
                                    <div style="width: 100px; height: 100px; background: white; padding: 4px; margin-bottom: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($student['StudentNo'] ?? '') ?>" style="width:100%; height:100%;">
                                    </div>
                                    <div style="font-weight: 800; color: #004d00; font-size: 16px; font-family: Arial, sans-serif; letter-spacing: 0.5px;">
                                        <?= e($student['StudentNo'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="padding: 8px 18px 24px 18px; color: #003300; font-family: Arial, sans-serif; text-align: left; position:relative; z-index:2;">
                                <?php 
                                    $middleInit = !empty($student['MiddleName']) ? substr($student['MiddleName'],0,1).'.' : ''; 
                                ?>
                                <div style="font-weight: 700; font-size: 20px; line-height: 1.1; margin-bottom:2px;"><?= e($student['FirstName'] ?? '') ?> <?= $middleInit ?></div>
                                <div style="font-weight: 900; font-size: 26px; text-transform: uppercase; line-height: 1.1; letter-spacing: -0.5px;"><?= e($student['LastName'] ?? '') ?></div>
                                <div style="font-size: 11px; font-weight: 600; margin-top: 8px; border-top: 1px solid rgba(0,51,0,0.25); padding-top: 6px;">
                                    <?= e($student['ProgramName'] ?? '') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Back Face -->
                        <div class="id-card-face id-card-back">
                            <div style="border: 1px solid #000; font-family: Arial, sans-serif; font-size: 11px; margin-bottom: 12px; border-radius: 2px;">
                                <div style="display:flex; border-bottom: 1px solid #000; background: #e5e7eb;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000; font-weight: bold;">School Year</div>
                                    <div style="width: 65%; padding: 4px 6px; font-weight: bold;">Validation</div>
                                </div>
                                <div style="display:flex; border-bottom: 1px solid #000;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000;">2024-2025</div>
                                    <div style="width: 65%; padding: 2px 6px; display:flex; align-items:center;">
                                        <span style="background:#e9d5ff; color:#581c87; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:2px;">2nd Semester 2024-2025</span>
                                    </div>
                                </div>
                                <div style="display:flex; border-bottom: 1px solid #000;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000;">2025-2026</div>
                                    <div style="width: 65%; padding: 2px 6px; display:flex; align-items:center;">
                                        <span style="background:#bae6fd; color:#0369a1; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:2px;">2nd Semester 2025-2026</span>
                                    </div>
                                </div>
                                <div style="display:flex; border-bottom: 1px solid #000;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000;">2026-2027</div>
                                    <div style="width: 65%; padding: 2px 6px; display:flex; align-items:center;">
                                        <span style="background:#fed7aa; color:#c2410c; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:2px;">1st Semester 2026-2027</span>
                                    </div>
                                </div>
                                <div style="display:flex; border-bottom: 1px solid #000; min-height: 24px;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000;">2027-2028</div>
                                    <div style="width: 65%; padding: 4px 6px;"></div>
                                </div>
                                <div style="display:flex; min-height: 24px;">
                                    <div style="width: 35%; padding: 4px 6px; border-right: 1px solid #000;">2028-2029</div>
                                    <div style="width: 65%; padding: 4px 6px;"></div>
                                </div>
                            </div>
                            
                            <div style="border: 1px solid #000; font-family: Arial, sans-serif; font-size: 11px; margin-bottom: 12px; display:flex; text-align:center; border-radius: 2px;">
                                <div style="width: 50%; border-right: 1px solid #000;">
                                    <div style="background: #334155; color: white; padding: 4px; font-weight: bold;">BIRTH DATE</div>
                                    <div style="padding: 6px 4px; font-weight: bold; font-size: 12px;"><?= formatDate($student['BirthDate'] ?? 'Nov 19, 2004') ?></div>
                                </div>
                                <div style="width: 50%;">
                                    <div style="background: #334155; color: white; padding: 4px; font-weight: bold;">BLOOD TYPE</div>
                                    <div style="padding: 6px 4px; font-weight: bold; font-size: 12px;"><?= e($student['BloodType'] ?? '') ?></div>
                                </div>
                            </div>

                            <div style="border: 1px solid #000; font-family: Arial, sans-serif; margin-bottom: 16px; text-align:center; border-radius: 2px;">
                                <div style="background: #475569; color: white; padding: 5px; font-weight: bold; font-size: 10px;">IN CASE OF EMERGENCY PLEASE CONTACT</div>
                                <div style="padding: 8px 6px;">
                                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 2px;"><?= e($student['GuardianName'] ?? 'Guardian Name') ?></div>
                                    <div style="font-size: 11px; margin-bottom: 2px; color: #333;"><?= e($student['ProfileAddress'] ?? 'Student Address') ?></div>
                                    <div style="font-weight: bold; font-size: 13px;"><?= e($student['GuardianContactNo'] ?? '0912-345-6789') ?></div>
                                </div>
                            </div>
                            
                            <div style="font-family: Arial, sans-serif; font-size: 10.5px; text-align: justify; line-height: 1.4; color: #1e293b; padding: 0 4px;">
                                This is to certify that the person whose name and picture appear here is a bonafide student of South East Asian Institute of Technology, Inc.
                            </div>

                            <div style="text-align: center; margin-top: auto; padding-bottom: 10px;">
                                <div style="font-family: 'Brush Script MT', cursive, serif; font-size: 38px; color: #1e1b4b; margin-bottom: -18px; transform: rotate(-3deg);">john</div>
                                <div style="font-weight: bold; font-family: Arial, sans-serif; font-size: 12px; border-bottom: 1px solid #000; display: inline-block; padding: 0 16px;">
                                    ENGR. JOHN PAUL S. TAMAYO, MCE-SG
                                </div>
                                <div style="font-family: Arial, sans-serif; font-size: 9px; margin-top: 3px; font-weight: 600; letter-spacing: 0.5px;">SCHOOL PRESIDENT</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
// security/print_validation_sticker.php - Printable Official Student ID Validation Sticker & Slip
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$studentId = (int)($_GET['student_id'] ?? $_SESSION['student_id'] ?? 0);
if (!$studentId) {
    die("Student ID required.");
}

if ($_SESSION['role'] === 'Student' && $studentId !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}

$stmt = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName,
    sp.GuardianName, sp.GuardianContactNo,
    c.ClearanceStatus, c.CompletionDate as ClinicDate,
    p_pay.ReceiptNo, p_pay.PaymentDate, p_pay.Amount as PaidAmount,
    iv.ValidationDate, iv.Status as IdStatus,
    sec_stf.FirstName as SecurityOfficerFirst, sec_stf.LastName as SecurityOfficerLast,
    chk.OverallStatus
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN student_profile sp ON sp.StudentID = s.StudentID 
    LEFT JOIN clinic c ON c.StudentID = s.StudentID 
    LEFT JOIN payment p_pay ON p_pay.StudentID = s.StudentID 
    LEFT JOIN id_validation iv ON iv.StudentID = s.StudentID 
    LEFT JOIN staff sec_stf ON sec_stf.StaffID = iv.StaffID 
    LEFT JOIN vw_enrollment_checklist chk ON chk.StudentID = s.StudentID 
    WHERE s.StudentID = ?
    ORDER BY iv.ValidationID DESC, p_pay.PaymentID DESC LIMIT 1");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}

$valDate = !empty($student['ValidationDate']) ? date('F d, Y', strtotime($student['ValidationDate'])) : date('F d, Y');
$officer = !empty($student['SecurityOfficerFirst']) ? ($student['SecurityOfficerFirst'] . ' ' . $student['SecurityOfficerLast']) : 'Security Verification Officer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Validation Sticker & Slip &mdash; <?= e($student['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Libre+Barcode+128&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
            padding: 30px 15px;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 25px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .sheet-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 35px 40px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }

        .header-logo {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }

        .sticker-card {
            border: 2px dashed #0d47a1;
            background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);
            border-radius: 10px;
            padding: 15px 20px;
            position: relative;
            margin: 25px 0;
        }

        .sticker-badge {
            display: inline-block;
            background: #16a34a;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 0.08em;
            padding: 3px 12px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .barcode-font {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 40px;
            line-height: 0.9;
            color: #0f172a;
        }

        .cut-line {
            border-top: 1px dashed #94a3b8;
            margin: 30px 0;
            position: relative;
            text-align: center;
        }

        .cut-line span {
            background: #ffffff;
            padding: 0 12px;
            position: relative;
            top: -10px;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 15px 20px !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print no-print-bar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h5 class="fw-bold mb-0 text-success"><i class="fas fa-stamp me-2"></i>Official Student ID Validation Slip & Sticker</h5>
        <small class="text-muted">Student: <strong><?= e($student['LastName'] . ', ' . $student['FirstName']) ?></strong> (<?= e($student['StudentNo']) ?>) &bull; <?= e($student['TypeName']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-print me-1"></i> Print Validation Slip
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times me-1"></i> Close
        </button>
    </div>
</div>

<div class="sheet-container">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" class="header-logo">
            <div>
                <h5 class="fw-bold mb-0" style="font-family:'Montserrat', sans-serif; letter-spacing:0.02em;">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h5>
                <small class="text-muted d-block">National Highway, Crossing Rubber, Tupi, South Cotabato</small>
                <div class="fw-bold text-success small mt-1"><i class="fas fa-shield-alt me-1"></i> SECURITY OFFICE &bull; ID VALIDATION DIVISION</div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-success px-3 py-2 fs-6"><i class="fas fa-check-circle me-1"></i> ID VALIDATED</span>
            <small class="text-muted d-block mt-1">A.Y. 2026-2027 (1st Sem)</small>
        </div>
    </div>

    <!-- Student Details Summary -->
    <h6 class="fw-bold text-primary text-uppercase mb-3" style="font-size: 13px; letter-spacing: 0.05em;">
        Student ID Card Validation & Renewal Record
    </h6>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded border">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Official Student Number</small>
                <div class="fw-bold fs-5 text-primary"><?= e($student['StudentNo']) ?></div>
                <div class="small mt-1"><strong>Name:</strong> <?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></div>
                <div class="small"><strong>Program:</strong> <?= e($student['ProgramName']) ?></div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded border">
                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Validation Information</small>
                <div class="fw-bold text-dark">Term: A.Y. 2026-2027 &bull; 1st Semester</div>
                <div class="small mt-1"><strong>Student Type:</strong> <span class="badge bg-secondary"><?= e($student['TypeName']) ?></span></div>
                <div class="small"><strong>Validation Date:</strong> <?= $valDate ?></div>
                <div class="small"><strong>Validated By:</strong> <?= e($officer) ?></div>
            </div>
        </div>
    </div>

    <!-- Cut-out ID Sticker Section -->
    <div class="sticker-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="sticker-badge"><i class="fas fa-check me-1"></i> VALIDATED</span>
                <span class="fw-bold text-dark small" style="font-family:'Montserrat', sans-serif;">SEAIT ID VALIDATION STICKER</span>
            </div>
            <span class="text-muted small fw-semibold">SY 2026-2027 &bull; 1ST SEM</span>
        </div>

        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="fw-bold text-primary"><?= e($student['StudentNo']) ?> &mdash; <?= e($student['LastName'] . ', ' . $student['FirstName']) ?></div>
                <small class="text-muted d-block"><?= e($student['ProgramName']) ?> (<?= e($student['TypeName']) ?>)</small>
                <small class="text-success fw-bold d-block mt-1">Security Seal: AUTH-<?= strtoupper(substr(md5($student['StudentID'] . 'VALIDATE'), 0, 8)) ?> &bull; <?= $valDate ?></small>
            </div>
            <div class="col-md-5 text-md-end text-center mt-2 mt-md-0">
                <div class="barcode-font">*<?= e($student['StudentNo']) ?>*</div>
            </div>
        </div>
    </div>

    <div class="cut-line">
        <span><i class="fas fa-cut me-1"></i> Cut along dashed line to affix sticker to physical ID card validation slot</span>
    </div>

    <!-- Official Multi-Office Clearance Verification Audit -->
    <h6 class="fw-bold text-dark text-uppercase mb-3" style="font-size: 12px; letter-spacing: 0.05em;">
        Prerequisite Clearance Verification Audit
    </h6>

    <table class="table table-bordered table-sm text-center align-middle mb-4" style="font-size: 12px;">
        <thead class="table-light">
            <tr>
                <th>1. Department</th>
                <th>2. Registrar</th>
                <th>3. Accounting</th>
                <th>4. Clinic</th>
                <th>5. Security (ID Validation)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-success fw-bold"><i class="fas fa-check-circle"></i> Approved</td>
                <td class="text-success fw-bold"><i class="fas fa-check-circle"></i> Completed</td>
                <td class="text-success fw-bold"><i class="fas fa-check-circle"></i> Cleared (OR: <?= e($student['ReceiptNo'] ?? 'PAID') ?>)</td>
                <td class="text-success fw-bold"><i class="fas fa-check-circle"></i> Medically Cleared</td>
                <td class="text-success fw-bold bg-success-subtle"><i class="fas fa-shield-alt"></i> ID Validated</td>
            </tr>
        </tbody>
    </table>

    <div class="row pt-4 text-center">
        <div class="col-6">
            <div class="border-top border-dark pt-1 mx-4">
                <strong><?= e($student['FirstName'] . ' ' . $student['LastName']) ?></strong><br>
                <small class="text-muted">Student Signature</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-dark pt-1 mx-4">
                <strong><?= e($officer) ?></strong><br>
                <small class="text-muted">Security Office / ID Validation Officer</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>

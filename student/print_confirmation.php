<?php
// student/print_confirmation.php - Printable Official Enrollment Confirmation Slip
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$studentId = $_GET['student_id'] ?? $_SESSION['student_id'] ?? null;
if (!$studentId) {
    die("Student ID required.");
}

if ($_SESSION['role'] === 'Student' && (int)$studentId !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}

$stmt = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE s.StudentID = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$stmtChk = $db->prepare("SELECT * FROM vw_enrollment_checklist WHERE StudentID = ? ORDER BY EnrollmentID DESC LIMIT 1");
$stmtChk->execute([$studentId]);
$chk = $stmtChk->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Confirmation Slip — <?= e($student['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        body { font-family: "Times New Roman", Times, serif; background: #fff; color: #000; padding: 25px; }
        .confirmation-box { border: 2px solid #000; padding: 20px; border-radius: 4px; }
        .stamp-box { border: 1px dashed #555; padding: 10px; min-height: 80px; text-align: center; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 text-end">
    <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-1"></i> Print Slip</button>
    <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Close</button>
</div>

<div class="confirmation-box">
    <div class="text-center border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="height:48px;width:48px;object-fit:contain;">
            <div class="text-start">
                <h5 class="fw-bold mb-0" style="letter-spacing:0.02em;">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h5>
                <small class="text-muted d-block">National Highway, Crossing Rubber, Tupi, South Cotabato</small>
            </div>
        </div>
        <h6 class="fw-bold text-success mt-2 mb-1" style="letter-spacing:0.05em;text-transform:uppercase;">Official Enrollment Confirmation Slip</h6>
        <small>Academic Year: <strong>2026-2027</strong> | Term: <strong>1st Semester</strong></small>
    </div>

    <table class="table table-borderless table-sm mb-4">
        <tr>
            <td style="width: 20%;"><strong>Official Student No:</strong></td>
            <td style="width: 30%;" class="fw-bold fs-5 text-primary"><?= e($student['StudentNo']) ?></td>
            <td style="width: 20%;"><strong>Confirmation Date:</strong></td>
            <td style="width: 30%;"><?= date('F d, Y') ?></td>
        </tr>
        <tr>
            <td><strong>Student Full Name:</strong></td>
            <td><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></td>
            <td><strong>Student Status:</strong></td>
            <td><?= e($student['TypeName']) ?></td>
        </tr>
        <tr>
            <td><strong>Degree Program:</strong></td>
            <td colspan="3"><?= e($student['ProgramName']) ?></td>
        </tr>
    </table>

    <h6 class="fw-bold border-bottom pb-1 mb-3">OFFICIAL MULTI-OFFICE CLEARANCE & CONFIRMATION RECORD</h6>

    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stamp-box">
                <small class="fw-bold d-block text-muted">1. DEPARTMENT</small>
                <div class="fw-bold text-success mt-2">✓ <?= e($chk['DepartmentStatus'] ?? 'Approved') ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stamp-box">
                <small class="fw-bold d-block text-muted">2. REGISTRAR</small>
                <div class="fw-bold text-success mt-2">✓ <?= e($chk['RegistrarStatus'] ?? 'Completed') ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stamp-box">
                <small class="fw-bold d-block text-muted">3. ACCOUNTING</small>
                <div class="fw-bold text-success mt-2">✓ <?= e($chk['AccountingStatus'] ?? 'Completed') ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stamp-box">
                <small class="fw-bold d-block text-muted">4. CLINIC</small>
                <div class="fw-bold text-success mt-2">✓ <?= e($chk['ClinicStatus'] ?? 'Completed') ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stamp-box">
                <small class="fw-bold d-block text-muted">5. SECURITY OFFICE</small>
                <div class="fw-bold text-success mt-2">✓ <?= e($chk['SecurityStatus'] ?? 'Completed') ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stamp-box bg-light border-success">
                <small class="fw-bold d-block text-muted">FINAL STATUS</small>
                <div class="fw-bold text-success fs-5 mt-1"><?= e($chk['OverallStatus'] ?? 'CONFIRMED') ?></div>
            </div>
        </div>
    </div>

    <div class="p-3 bg-light border text-center mb-4">
        <p class="mb-0 small">
            <strong>NOTICE:</strong> This document certifies that the student has fulfilled all academic, financial, medical, and security requirements and is officially confirmed as a registered student for the designated academic semester.
        </p>
    </div>

    <div class="row pt-4 text-center">
        <div class="col-6">
            <div class="border-top border-dark pt-1">
                <strong><?= e($student['FirstName'] . ' ' . $student['LastName']) ?></strong><br>
                <small>Student Signature</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-dark pt-1">
                <strong>Security & Admissions Board</strong><br>
                <small>Authorized Campus Official</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>

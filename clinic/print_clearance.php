<?php
// clinic/print_clearance.php - Printable Official Medical Clearance Certificate
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$studentId = (int)($_GET['student_id'] ?? 0);
if ($studentId <= 0) die("Student ID required.");

$stmt = $db->prepare("SELECT s.*, p.ProgramName, c.*, stf.FirstName as StaffFirst, stf.LastName as StaffLast 
    FROM student s 
    JOIN clinic c ON c.StudentID = s.StudentID 
    JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN staff stf ON stf.StaffID = c.StaffID 
    WHERE s.StudentID = ?");
$stmt->execute([$studentId]);
$record = $stmt->fetch();
if (!$record) die("Medical record not found.");

if ($_SESSION['role'] === 'Student' && (int)$record['StudentID'] !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Clearance — <?= e($record['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        body { font-family: "Times New Roman", Times, serif; background: #fff; color: #000; padding: 30px; }
        .clearance-box { border: 2px solid #000; padding: 25px; border-radius: 4px; max-width: 750px; margin: 0 auto; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> Print Clearance</button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i> Close</button>
</div>

<div class="clearance-box">
    <div class="text-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="height:48px;width:48px;object-fit:contain;">
            <div class="text-start">
                <h5 class="fw-bold mb-0" style="letter-spacing:0.02em;">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h5>
                <small class="text-muted d-block">National Highway, Crossing Rubber, Tupi, South Cotabato</small>
            </div>
        </div>
        <h6 class="mb-1 text-muted">Institute Health &amp; Clinical Services</h6>
        <h5 class="fw-bold text-success mt-2" style="letter-spacing:0.04em;">CERTIFICATE OF MEDICAL CLEARANCE</h5>
        <small>Academic Year 2026-2027 &bull; 1st Semester</small>
    </div>

    <p class="mb-3">This is to certify that the student named below has completed the required physical and clinical health evaluation:</p>

    <table class="table table-borderless table-sm mb-4">
        <tr>
            <td style="width: 25%;"><strong>Student ID:</strong></td>
            <td style="width: 35%;" class="fw-bold text-primary"><?= e($record['StudentNo']) ?></td>
            <td style="width: 18%;"><strong>Date Examined:</strong></td>
            <td style="width: 22%;"><?= formatDate($record['CompletionDate']) ?></td>
        </tr>
        <tr>
            <td><strong>Full Name:</strong></td>
            <td><?= e($record['LastName'] . ', ' . $record['FirstName'] . ' ' . $record['MiddleName']) ?></td>
            <td><strong>Sex / Age:</strong></td>
            <td><?= e($record['Sex']) ?> (<?= date_diff(date_create($record['BirthDate']), date_create('today'))->y ?> yrs)</td>
        </tr>
        <tr>
            <td><strong>Degree Program:</strong></td>
            <td colspan="3"><?= e($record['ProgramName']) ?></td>
        </tr>
    </table>

    <div class="p-3 bg-light border mb-4">
        <h6 class="fw-bold mb-2">CLINICAL FINDINGS & PHYSICAL PARAMETERS:</h6>
        <div class="row g-2 mb-2">
            <div class="col-4"><strong>Height:</strong> <?= e($record['HeightCM']) ?> cm</div>
            <div class="col-4"><strong>Weight:</strong> <?= e($record['WeightKG']) ?> kg</div>
            <div class="col-4"><strong>BMI:</strong> <?= number_format((float)$record['BMI'], 1) ?> kg/m&sup2;</div>
        </div>
        <div class="mb-2"><strong>Medical History:</strong> <?= e($record['MedicalHistory'] ?: 'None declared') ?></div>
        <div><strong>Physician Remarks:</strong> <?= e($record['Remarks']) ?></div>
    </div>

    <div class="text-center my-4 py-2 border border-success bg-light">
        <h5 class="fw-bold text-success mb-0">
            <i class="fas fa-check-circle me-1"></i> STATUS: <?= strtoupper(e($record['ClearanceStatus'])) ?> FOR ENROLLMENT
        </h5>
    </div>

    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top border-dark pt-1">
                <strong><?= e($record['FirstName'] . ' ' . $record['LastName']) ?></strong><br>
                <small>Student Signature</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-dark pt-1">
                <strong>Dr. Patricia Ramos, MD</strong><br>
                <small>Institute Physician / Clinic Head</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>

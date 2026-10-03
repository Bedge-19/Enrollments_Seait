<?php
// student/print_study_load.php - Printable Official Study Load / Certificate of Matriculation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$studentId = $_GET['student_id'] ?? $_SESSION['student_id'] ?? null;
if (!$studentId) {
    die("Student ID required.");
}

// Security: If logged in as Student, only permit printing own load
if ($_SESSION['role'] === 'Student' && (int)$studentId !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}

$stmt = $db->prepare("SELECT s.*, p.ProgramName, d.DepartmentName, st.TypeName 
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN department d ON d.departmentID = p.DepartmentID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE s.StudentID = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$stmtEnr = $db->prepare("SELECT e.*, sec.SectionCode 
    FROM enrollment e 
    LEFT JOIN blocking b ON b.EnrollmentID = e.EnrollmentID 
    LEFT JOIN section sec ON sec.SectionID = b.SectionID 
    WHERE e.StudentID = ? 
    ORDER BY e.EnrollmentID DESC LIMIT 1");
$stmtEnr->execute([$studentId]);
$enrollment = $stmtEnr->fetch();

$subjects = [];
$totalUnits = 0;
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
    $subjects = $stmtSubjs->fetchAll();
    foreach ($subjects as $s) {
        $totalUnits += (float)$s['Units'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Registration — <?= e($student['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        body { font-family: "Times New Roman", Times, serif; background: #fff; color: #000; padding: 20px; }
        .school-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .table-bordered th, .table-bordered td { border: 1px solid #000 !important; font-size: 11pt; padding: 4px 8px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 text-end">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Certificate</button>
    <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Close</button>
</div>

<div class="school-header text-center mb-3">
    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
        <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="height:48px;width:48px;object-fit:contain;">
        <div class="text-start">
            <h5 class="fw-bold mb-0" style="letter-spacing:0.02em;">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h5>
            <small class="text-muted d-block">National Highway, Crossing Rubber, Tupi, South Cotabato</small>
        </div>
    </div>
    <h6 class="mb-1 text-muted">Office of the Institute Registrar</h6>
    <h6 class="fw-bold mt-2" style="letter-spacing:0.05em;text-transform:uppercase;">Certificate of Matriculation / Official Study Load</h6>
    <small>Academic Year: <strong><?= e($enrollment['SchoolYear'] ?? '2026-2027') ?></strong> | Semester: <strong><?= e($enrollment['Semester'] ?? '1st') ?> Semester</strong></small>
</div>

<table class="table table-borderless table-sm mb-3">
    <tr>
        <td style="width: 18%;"><strong>Student No:</strong></td>
        <td style="width: 32%;"><?= e($student['StudentNo']) ?></td>
        <td style="width: 18%;"><strong>Date Printed:</strong></td>
        <td style="width: 32%;"><?= date('F d, Y h:i A') ?></td>
    </tr>
    <tr>
        <td><strong>Student Name:</strong></td>
        <td><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></td>
        <td><strong>Section / Block:</strong></td>
        <td><?= e($enrollment['SectionCode'] ?? 'Regular Block') ?></td>
    </tr>
    <tr>
        <td><strong>Program / College:</strong></td>
        <td><?= e($student['ProgramName']) ?></td>
        <td><strong>Student Type:</strong></td>
        <td><?= e($student['TypeName']) ?></td>
    </tr>
</table>

<table class="table table-bordered mb-4">
    <thead>
        <tr class="table-light text-center">
            <th style="width: 15%;">Subject Code</th>
            <th>Subject Description</th>
            <th style="width: 8%;">Units</th>
            <th style="width: 12%;">Type</th>
            <th style="width: 12%;">Day</th>
            <th style="width: 18%;">Time</th>
            <th style="width: 15%;">Instructor</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($subjects)): ?>
            <tr>
                <td colspan="7" class="text-center py-3">No subjects enrolled.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($subjects as $s): ?>
                <tr>
                    <td class="fw-bold"><?= e($s['SubjectCode']) ?></td>
                    <td><?= e($s['SubjectTitle']) ?></td>
                    <td class="text-center"><?= number_format((float)$s['Units'], 1) ?></td>
                    <td class="text-center"><?= e($s['SubjectType']) ?></td>
                    <td class="text-center"><?= e($s['Day'] ?? 'TBA') ?></td>
                    <td class="text-center">
                        <?= (!empty($s['TimeStart'])) ? date('h:i A', strtotime($s['TimeStart'])) . ' - ' . date('h:i A', strtotime($s['TimeEnd'])) : 'TBA' ?>
                    </td>
                    <td><?= (!empty($s['InstFirst'])) ? e($s['InstFirst'] . ' ' . $s['InstLast']) : 'TBA' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="fw-bold">
                <td colspan="2" class="text-end">TOTAL ENROLLED UNITS:</td>
                <td class="text-center"><?= number_format($totalUnits, 1) ?></td>
                <td colspan="4"></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="row mt-5 pt-4 text-center">
    <div class="col-4">
        <div class="border-top border-dark pt-1">
            <strong><?= e($student['FirstName'] . ' ' . $student['LastName']) ?></strong><br>
            <small>Student Signature</small>
        </div>
    </div>
    <div class="col-4">
        <div class="border-top border-dark pt-1">
            <strong>Victoria Salazar</strong><br>
            <small>Institute Registrar</small>
        </div>
    </div>
    <div class="col-4">
        <div class="border-top border-dark pt-1">
            <strong>Official Registrar Seal</strong><br>
            <small>VALID ONLY WITH SEAL</small>
        </div>
    </div>
</div>

</body>
</html>

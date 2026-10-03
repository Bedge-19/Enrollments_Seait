<?php
// department/print_grades.php - Printable Official Student Academic Transcript / Certification of Grades
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole(['Department', 'Registrar', 'Admin']);
$db = getDBConnection();

$studentId = (int)($_GET['student_id'] ?? 0);
if ($studentId <= 0) die("Student ID required.");

$stmtStd = $db->prepare("SELECT s.*, p.ProgramName, d.DepartmentName, st.TypeName 
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN department d ON d.departmentID = p.DepartmentID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    WHERE s.StudentID = ?");
$stmtStd->execute([$studentId]);
$student = $stmtStd->fetch();
if (!$student) die("Student not found.");

$stmtG = $db->prepare("SELECT g.*, sub.SubjectCode, sub.SubjectTitle, sub.Units, ay.SchoolYear, ay.Semester 
    FROM student_grades g 
    JOIN subject sub ON sub.SubjectID = g.SubjectID 
    JOIN academic_year ay ON ay.AcademicYearID = g.AcademicYearID 
    WHERE g.StudentID = ? 
    ORDER BY ay.AcademicYearID ASC, sub.SubjectCode ASC");
$stmtG->execute([$studentId]);
$grades = $stmtG->fetchAll();

$totalUnits = 0;
$totalWeighted = 0;
foreach ($grades as $g) {
    if ($g['FinalGrade'] > 0) {
        $totalUnits += (float)$g['Units'];
        $totalWeighted += ((float)$g['FinalGrade'] * (float)$g['Units']);
    }
}
$gwa = $totalUnits > 0 ? ($totalWeighted / $totalUnits) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Certification of Grades — <?= e($student['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        body { font-family: "Times New Roman", Times, serif; background: #fff; color: #000; padding: 25px; }
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
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Transcript</button>
    <button onclick="window.close()" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Close</button>
</div>

<div class="school-header text-center mb-3 pb-3 border-bottom border-dark">
    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
        <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="height:48px;width:48px;object-fit:contain;">
        <div class="text-start">
            <h5 class="fw-bold mb-0" style="letter-spacing:0.02em;">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h5>
            <small class="text-muted d-block">National Highway, Crossing Rubber, Tupi, South Cotabato</small>
        </div>
    </div>
    <h6 class="mb-1 text-muted"><?= e($student['DepartmentName'] ?? 'Academic Department') ?></h6>
    <h6 class="fw-bold mt-2" style="letter-spacing:0.05em;text-transform:uppercase;">Official Certification of Grades</h6>
</div>

<table class="table table-borderless table-sm mb-3">
    <tr>
        <td style="width: 18%;"><strong>Student No:</strong></td>
        <td style="width: 32%;"><?= e($student['StudentNo']) ?></td>
        <td style="width: 18%;"><strong>Date Issued:</strong></td>
        <td style="width: 32%;"><?= date('F d, Y') ?></td>
    </tr>
    <tr>
        <td><strong>Student Name:</strong></td>
        <td><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></td>
        <td><strong>Overall GWA:</strong></td>
        <td><strong class="text-primary"><?= number_format($gwa, 2) ?></strong></td>
    </tr>
    <tr>
        <td><strong>Degree Program:</strong></td>
        <td colspan="3"><?= e($student['ProgramName']) ?></td>
    </tr>
</table>

<table class="table table-bordered mb-4">
    <thead>
        <tr class="table-light text-center">
            <th style="width: 15%;">School Year / Term</th>
            <th style="width: 15%;">Subject Code</th>
            <th>Descriptive Title</th>
            <th style="width: 10%;">Units</th>
            <th style="width: 12%;">Final Grade</th>
            <th style="width: 12%;">Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($grades)): ?>
            <tr>
                <td colspan="6" class="text-center py-3">No grades on record.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($grades as $g): ?>
                <tr>
                    <td class="text-center"><?= e($g['SchoolYear']) ?> (<?= e($g['Semester']) ?>)</td>
                    <td class="fw-bold"><?= e($g['SubjectCode']) ?></td>
                    <td><?= e($g['SubjectTitle']) ?></td>
                    <td class="text-center"><?= number_format((float)$g['Units'], 1) ?></td>
                    <td class="text-center fw-bold"><?= number_format((float)$g['FinalGrade'], 2) ?></td>
                    <td class="text-center"><?= e($g['GradeStatus']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="row mt-5 pt-4 text-center">
    <div class="col-6">
        <div class="border-top border-dark pt-1">
            <strong>Department Chairperson</strong><br>
            <small><?= e($student['DepartmentName'] ?? 'College Department') ?></small>
        </div>
    </div>
    <div class="col-6">
        <div class="border-top border-dark pt-1">
            <strong>Victoria Salazar</strong><br>
            <small>Institute Registrar</small>
        </div>
    </div>
</div>

</body>
</html>

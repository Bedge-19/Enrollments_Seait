<?php
// registrar/schedules.php - Master Class Schedules Browser
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Registrar');
$pageTitle = 'Master Class Schedules';
$db = getDBConnection();

$programFilter = (int)($_GET['program_id'] ?? 0);
$dayFilter = trim($_GET['day'] ?? '');

$where = [];
$params = [];

if ($programFilter > 0) {
    $where[] = "sec.ProgramID = ?";
    $params[] = $programFilter;
}
if ($dayFilter !== '') {
    $where[] = "sch.Day = ?";
    $params[] = $dayFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $db->prepare("SELECT sch.*, sec.SectionCode, sub.SubjectCode, sub.SubjectTitle, sub.Units, 
    p.ProgramName, inst.FirstName as InstFirst, inst.LastName as InstLast 
    FROM schedule sch 
    JOIN section sec ON sec.SectionID = sch.SectionID 
    JOIN program p ON p.ProgramID = sec.ProgramID 
    JOIN subject sub ON sub.SubjectID = sch.SubjectID 
    LEFT JOIN instructor inst ON inst.InstructorID = sch.InstructorID 
    {$whereSql} 
    ORDER BY sec.SectionCode ASC, sch.Day ASC, sch.TimeStart ASC");
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$allPrograms = $db->query("SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName")->fetchAll();
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Class Schedules</h4>
        <p class="text-muted small mb-0">Browse section timetables, subject slots, and instructor assignments.</p>
    </div>
</div>

<!-- Filter Box -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-6">
                <select name="program_id" class="form-select form-select-sm">
                    <option value="0">All Degree Programs</option>
                    <?php foreach ($allPrograms as $p): ?>
                        <option value="<?= $p['ProgramID'] ?>" <?= $programFilter == $p['ProgramID'] ? 'selected' : '' ?>>
                            <?= e($p['ProgramName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="day" class="form-select form-select-sm">
                    <option value="">All Days</option>
                    <?php foreach ($days as $d): ?>
                        <option value="<?= $d ?>" <?= $dayFilter === $d ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>/registrar/schedules.php" class="btn btn-light btn-sm border"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Schedules Table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Subject Code & Title</th>
                    <th class="text-center">Units</th>
                    <th>Day</th>
                    <th>Time Slot</th>
                    <th>Assigned Instructor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No schedules matching the filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedules as $sch): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary fs-6"><?= e($sch['SectionCode']) ?></span>
                                <small class="text-muted d-block"><?= e($sch['ProgramName']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($sch['SubjectCode']) ?></div>
                                <small class="text-muted"><?= e($sch['SubjectTitle']) ?></small>
                            </td>
                            <td class="text-center fw-bold"><?= number_format((float)$sch['Units'], 1) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($sch['Day']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?= date('h:i A', strtotime($sch['TimeStart'])) ?> &ndash; <?= date('h:i A', strtotime($sch['TimeEnd'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($sch['InstFirst'])): ?>
                                    <div class="fw-semibold text-dark"><?= e($sch['InstFirst'] . ' ' . $sch['InstLast']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">TBA</span>
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

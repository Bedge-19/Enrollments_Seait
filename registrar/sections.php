<?php
// registrar/sections.php - Sections and Blocks Viewer
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Registrar');
$pageTitle = 'Academic Sections & Blocks';
$db = getDBConnection();

$stmt = $db->query("SELECT sec.*, p.ProgramName, 
    COUNT(DISTINCT sch.ScheduleID) as ScheduleCount,
    COUNT(DISTINCT b.EnrollmentID) as StudentCount 
    FROM section sec 
    JOIN program p ON p.ProgramID = sec.ProgramID 
    LEFT JOIN schedule sch ON sch.SectionID = sec.SectionID 
    LEFT JOIN blocking b ON b.SectionID = sec.SectionID 
    GROUP BY sec.SectionID 
    ORDER BY p.ProgramName ASC, sec.YearLevel ASC, sec.SectionCode ASC");
$sections = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Sections & Blocks Masterlist</h4>
        <p class="text-muted small mb-0">Overview of active blocks, program allocations, and enrolled student counts.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Section Code</th>
                    <th>Degree Program</th>
                    <th class="text-center">Year Level</th>
                    <th class="text-center">Scheduled Subjects</th>
                    <th class="text-center">Enrolled Students</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No sections found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sections as $s): ?>
                        <tr>
                            <td class="fw-bold text-primary fs-6"><?= e($s['SectionCode']) ?></td>
                            <td><?= e($s['ProgramName']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">Year <?= $s['YearLevel'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark"><?= (int)$s['ScheduleCount'] ?> Classes</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success"><?= number_format((int)$s['StudentCount']) ?> Students</span>
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

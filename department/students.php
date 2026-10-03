<?php
// department/students.php - Student Master Directory with Server-side Pagination & Search
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Department');
$pageTitle = 'Student Master Directory';
$db = getDBConnection();

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$programFilter = (int)($_GET['program_id'] ?? 0);
$typeFilter = (int)($_GET['type_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? OR s.Email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($programFilter > 0) {
    $where[] = "s.ProgramID = ?";
    $params[] = $programFilter;
}

if ($typeFilter > 0) {
    $where[] = "s.StudentTypeID = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== '') {
    $where[] = "s.Status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$stmtCount = $db->prepare("SELECT COUNT(*) FROM student s {$whereSql}");
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $limit));

// Fetch paginated records
$stmtList = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    {$whereSql} 
    ORDER BY s.StudentID ASC 
    LIMIT {$limit} OFFSET {$offset}");
$stmtList->execute($params);
$students = $stmtList->fetchAll();

// Programs and Types for filters
$allPrograms = $db->query("SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName")->fetchAll();
$allTypes = $db->query("SELECT StudentTypeID, TypeName FROM student_type ORDER BY StudentTypeID")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Student Master Directory</h4>
        <p class="text-muted small mb-0">Total of <strong><?= number_format($totalRecords) ?></strong> records found</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/department/register_student.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-user-plus me-1"></i> Add Student
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Search Student</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Student ID, Last Name, First Name..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Program</label>
                <select name="program_id" class="form-select form-select-sm">
                    <option value="0">All Programs</option>
                    <?php foreach ($allPrograms as $prg): ?>
                        <option value="<?= $prg['ProgramID'] ?>" <?= $programFilter == $prg['ProgramID'] ? 'selected' : '' ?>>
                            <?= e($prg['ProgramName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Student Type</label>
                <select name="type_id" class="form-select form-select-sm">
                    <option value="0">All Types</option>
                    <?php foreach ($allTypes as $typ): ?>
                        <option value="<?= $typ['StudentTypeID'] ?>" <?= $typeFilter == $typ['StudentTypeID'] ? 'selected' : '' ?>>
                            <?= e($typ['TypeName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Applicant" <?= $statusFilter === 'Applicant' ? 'selected' : '' ?>>Applicant</option>
                    <option value="Enrolled" <?= $statusFilter === 'Enrolled' ? 'selected' : '' ?>>Enrolled</option>
                    <option value="Dropped" <?= $statusFilter === 'Dropped' ? 'selected' : '' ?>>Dropped</option>
                    <option value="Irregular" <?= $statusFilter === 'Irregular' ? 'selected' : '' ?>>Irregular</option>
                    <option value="Graduated" <?= $statusFilter === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
                </select>
            </div>
            <div class="col-md-1 col-12 d-flex gap-1 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Apply Filter"><i class="fas fa-filter me-1"></i><span class="d-md-none">Filter</span></button>
                <a href="<?= BASE_URL ?>/department/students.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th>Type</th>
                    <th>Contact & Email</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No students matching the criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($s['StudentNo']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($s['LastName'] . ', ' . $s['FirstName'] . ' ' . $s['MiddleName']) ?></div>
                                <small class="text-muted"><?= e($s['Sex']) ?> &bull; <?= formatDate($s['BirthDate']) ?></small>
                            </td>
                            <td><?= e($s['ProgramName']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($s['TypeName']) ?></span></td>
                            <td class="small">
                                <div><i class="fas fa-phone me-1 text-muted"></i><?= e($s['ContactNo']) ?></div>
                                <div class="text-muted"><i class="fas fa-envelope me-1"></i><?= e($s['Email']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $s['Status'] === 'Enrolled' ? 'bg-success' : ($s['Status'] === 'Applicant' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                    <?= e($s['Status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/department/student_grades.php?student_id=<?= $s['StudentID'] ?>" class="btn btn-outline-secondary" title="View Grades & History">
                                        <i class="fas fa-award"></i>
                                    </a>
                                    <?php if ($s['StudentTypeID'] == 6): ?>
                                        <a href="<?= BASE_URL ?>/department/evaluate_transferee.php?student_id=<?= $s['StudentID'] ?>" class="btn btn-outline-info" title="Transferee Credits">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">
                Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($totalRecords) ?> total records)
            </small>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $queryParams = $_GET;
                    $prevPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);

                    $queryParams['page'] = 1;
                    $firstUrl = '?' . http_build_query($queryParams);
                    $queryParams['page'] = $prevPage;
                    $prevUrl = '?' . http_build_query($queryParams);
                    $queryParams['page'] = $nextPage;
                    $nextUrl = '?' . http_build_query($queryParams);
                    $queryParams['page'] = $totalPages;
                    $lastUrl = '?' . http_build_query($queryParams);
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $firstUrl ?>">&laquo; First</a>
                    </li>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $prevUrl ?>">Prev</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link"><?= $page ?></span>
                    </li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $nextUrl ?>">Next</a>
                    </li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $lastUrl ?>">Last &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

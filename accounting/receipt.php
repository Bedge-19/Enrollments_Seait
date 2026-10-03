<?php
// accounting/receipt.php - Payment Receipt Management & Lookup
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole(['Accounting', 'Student', 'Admin']);
$pageTitle = 'Official Receipts Directory';
$db = getDBConnection();

$paymentId = (int)($_GET['payment_id'] ?? 0);
$receipt = null;
$paymentItems = [];

if ($paymentId > 0) {
    $stmt = $db->prepare("SELECT p.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, s.Email, prg.ProgramName, stf.FirstName as StaffFirst, stf.LastName as StaffLast 
        FROM payment p 
        JOIN student s ON s.StudentID = p.StudentID 
        JOIN program prg ON prg.ProgramID = s.ProgramID 
        LEFT JOIN staff stf ON stf.StaffID = p.StaffID 
        WHERE p.PaymentID = ?");
    $stmt->execute([$paymentId]);
    $receipt = $stmt->fetch();

    if ($receipt) {
        $stmtItems = $db->prepare("SELECT * FROM payment_item WHERE PaymentID = ?");
        $stmtItems->execute([$paymentId]);
        $paymentItems = $stmtItems->fetchAll();
    }
}

// Search and filter list
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = ["p.PaymentStatus = 'Paid'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.ReceiptNo LIKE ? OR s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}

$whereSql = "WHERE " . implode(" AND ", $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM payment p JOIN student s ON s.StudentID = p.StudentID {$whereSql}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$stmtList = $db->prepare("SELECT p.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, prg.ProgramName 
    FROM payment p 
    JOIN student s ON s.StudentID = p.StudentID 
    JOIN program prg ON prg.ProgramID = s.ProgramID 
    {$whereSql} 
    ORDER BY p.PaymentID DESC 
    LIMIT {$limit} OFFSET {$offset}");
$stmtList->execute($params);
$receiptsList = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Official Payment Receipts</h4>
        <p class="text-muted small mb-0">Browse issued official payment receipts and print cashiering statements.</p>
    </div>
</div>

<?php if ($receipt): ?>
    <!-- Single Receipt View Card -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>Official Receipt: <?= e($receipt['ReceiptNo']) ?></h6>
            <a href="<?= BASE_URL ?>/accounting/print_receipt.php?payment_id=<?= $receipt['PaymentID'] ?>" target="_blank" class="btn btn-light btn-sm fw-semibold">
                <i class="fas fa-print me-1"></i> Print Official Receipt
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-3">
                    <small class="text-muted d-block">Student ID & Name</small>
                    <strong><?= e($receipt['StudentNo']) ?></strong><br>
                    <?= e($receipt['LastName'] . ', ' . $receipt['FirstName']) ?>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Degree Program</small>
                    <span><?= e($receipt['ProgramName']) ?></span>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Payment Date</small>
                    <span><?= formatDate($receipt['PaymentDate']) ?></span>
                </div>
                <div class="col-sm-3">
                    <small class="text-muted d-block">Cashier / Staff</small>
                    <span><?= e(($receipt['StaffFirst'] ?? 'Accounting') . ' ' . ($receipt['StaffLast'] ?? 'Staff')) ?></span>
                </div>
            </div>

            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Particulars / Fee Item</th>
                        <th>Description</th>
                        <th class="text-end" style="width: 20%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentItems)): ?>
                        <tr>
                            <td>Tuition & Assessment Fees</td>
                            <td>Full Semester Enrolled Load</td>
                            <td class="text-end fw-bold"><?= formatPeso($receipt['Amount']) ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentItems as $item): ?>
                            <tr>
                                <td><?= e($item['FeeType']) ?></td>
                                <td class="text-muted"><?= e($item['Description']) ?></td>
                                <td class="text-end fw-bold"><?= formatPeso($item['Amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="table-light fs-6">
                        <td colspan="2" class="text-end fw-bold">TOTAL AMOUNT PAID:</td>
                        <td class="text-end fw-bold text-success"><?= formatPeso($receipt['Amount']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Receipts Search & Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Receipt No, Student No, or Name..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3 col-12 d-flex gap-2 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-search me-1"></i> Search Receipts</button>
                <a href="<?= BASE_URL ?>/accounting/receipt.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Program</th>
                    <th>Date Paid</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receiptsList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No paid receipts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receiptsList as $r): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= e($r['ReceiptNo']) ?></td>
                            <td class="fw-bold text-primary"><?= e($r['StudentNo']) ?></td>
                            <td><?= e($r['LastName'] . ', ' . $r['FirstName']) ?></td>
                            <td><?= e($r['ProgramName']) ?></td>
                            <td class="small text-muted"><?= formatDate($r['PaymentDate']) ?></td>
                            <td class="text-end fw-bold text-success"><?= formatPeso($r['Amount']) ?></td>
                            <td class="text-end">
                                <a href="?payment_id=<?= $r['PaymentID'] ?>" class="btn btn-sm btn-outline-primary" title="View Breakdown">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/accounting/print_receipt.php?payment_id=<?= $r['PaymentID'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
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
            <small class="text-muted">Showing page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($total) ?> receipts)</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>">Prev</a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= min($totalPages, $page + 1) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

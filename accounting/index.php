<?php
// accounting/index.php - Accounting Dashboard & Financial Overview
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Accounting');
$pageTitle = 'Accounting Dashboard';
$db = getDBConnection();

// Financial metrics
$stmtRev = $db->query("SELECT COALESCE(SUM(Amount), 0) FROM payment WHERE PaymentStatus = 'Paid'");
$totalRevenue = (float)$stmtRev->fetchColumn();

$stmtPaidCount = $db->query("SELECT COUNT(*) FROM payment WHERE PaymentStatus = 'Paid'");
$paidStudents = (int)$stmtPaidCount->fetchColumn();

$stmtUnpaidCount = $db->query("SELECT COUNT(*) FROM payment WHERE PaymentStatus = 'Pending'");
$pendingPayments = (int)$stmtUnpaidCount->fetchColumn();

$stmtRecentPayments = $db->query("SELECT p.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, prg.ProgramName 
    FROM payment p 
    JOIN student s ON s.StudentID = p.StudentID 
    JOIN program prg ON prg.ProgramID = s.ProgramID 
    ORDER BY p.PaymentID DESC 
    LIMIT 15");
$recentPayments = $stmtRecentPayments->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Header -->
<div class="dashboard-header">
    <div>
        <h4 class="fw-bold text-dark mb-1">Accounting & Cashiering</h4>
        <p class="text-muted small mb-0">Manage tuition assessments, process payments, issue official receipts, and grant financial clearance.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/accounting/payment.php" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-cash-register me-1"></i> Process Student Payment
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card stat-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Collections</span>
                    <h3 class="fw-bold my-1 text-success"><?= formatPeso($totalRevenue) ?></h3>
                    <small class="text-muted">A.Y. 2026-2027</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Paid & Cleared</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($paidStudents) ?></h3>
                    <small class="text-muted">Cleared in Step 3 Accounting</small>
                </div>
                <div class="stat-icon bg-info-subtle text-info"><i class="fas fa-check-double"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card stat-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Pending Assessments</span>
                    <h3 class="fw-bold my-1 text-warning"><?= number_format($pendingPayments) ?></h3>
                    <small class="text-muted"><a href="<?= BASE_URL ?>/accounting/payment.php" class="text-decoration-none fw-semibold">Collect payment &rarr;</a></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-primary"></i>Recent Payment Transactions</h6>
            <small class="text-muted">Latest assessments and processed cash payments</small>
        </div>
        <a href="<?= BASE_URL ?>/accounting/payment.php" class="btn btn-outline-primary btn-sm">Process New Payment</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Student</th>
                    <th>Program</th>
                    <th>Assessment</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPayments)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-receipt fa-2x text-muted mb-2 d-block"></i>
                            No recent payment records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentPayments as $p): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= e($p['ReceiptNo'] ?: 'PENDING-OR') ?></td>
                            <td>
                                <div class="fw-bold text-primary"><?= e($p['StudentNo']) ?></div>
                                <small class="text-dark fw-semibold"><?= e($p['LastName'] . ', ' . $p['FirstName'] . ' ' . $p['MiddleName']) ?></small>
                            </td>
                            <td><?= e($p['ProgramName']) ?></td>
                            <td class="fw-bold text-dark"><?= formatPeso($p['Amount']) ?></td>
                            <td class="small text-muted"><?= formatDate($p['PaymentDate']) ?></td>
                            <td>
                                <span class="badge <?= $p['PaymentStatus'] === 'Paid' ? 'badge-soft-success' : 'badge-soft-warning' ?>">
                                    <?= e($p['PaymentStatus']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($p['PaymentStatus'] === 'Paid'): ?>
                                    <a href="<?= BASE_URL ?>/accounting/print_receipt.php?payment_id=<?= $p['PaymentID'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Print Official Receipt">
                                        <i class="fas fa-print me-1"></i> Receipt
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/accounting/payment.php?student_id=<?= $p['StudentID'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-hand-holding-usd me-1"></i> Collect
                                    </a>
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

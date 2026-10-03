<?php
// accounting/payment.php - Process Student Tuition & Fee Payments
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('Accounting');
$pageTitle = 'Process Student Payment';
$db = getDBConnection();
$staffId = $_SESSION['staff_id'] ?? 7;

$studentId = (int)($_GET['student_id'] ?? 0);
$student = null;
$payment = null;
$paymentItems = [];

// Handle Payment Confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $targetPaymentId = (int)$_POST['payment_id'];
    $targetStudentId = (int)$_POST['student_id'];
    $amountPaid = (float)$_POST['amount_paid'];
    $receiptNo = trim($_POST['receipt_no'] ?? '');

    if (empty($receiptNo)) {
        $receiptNo = sprintf("OR-2026-%05d", $targetPaymentId);
    }

    try {
        $db->beginTransaction();

        $stmtUpdPay = $db->prepare("UPDATE payment SET 
            Amount = ?, ReceiptNo = ?, PaymentStatus = 'Paid', PaymentDate = CURDATE(), StaffID = ? 
            WHERE PaymentID = ?");
        $stmtUpdPay->execute([$amountPaid, $receiptNo, $staffId, $targetPaymentId]);

        $db->commit();

        logActivity('COLLECT_PAYMENT', 'Accounting', 'payment', $targetPaymentId, "Recorded payment {$receiptNo} for amount {$amountPaid} for Student ID {$targetStudentId}");
        setFlash('success', "Payment successfully recorded with Receipt No: {$receiptNo}! Accounting checklist cleared.");
        header("Location: " . BASE_URL . "/accounting/receipt.php?payment_id=" . $targetPaymentId);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', "Error processing payment: " . $e->getMessage());
    }
}

if ($studentId > 0) {
    $stmtStd = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName 
        FROM student s 
        LEFT JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
        WHERE s.StudentID = ?");
    $stmtStd->execute([$studentId]);
    $student = $stmtStd->fetch();

    if ($student) {
        $stmtPay = $db->prepare("SELECT * FROM payment WHERE StudentID = ? ORDER BY PaymentID DESC LIMIT 1");
        $stmtPay->execute([$studentId]);
        $payment = $stmtPay->fetch();

        if ($payment) {
            $stmtItems = $db->prepare("SELECT * FROM payment_item WHERE PaymentID = ?");
            $stmtItems->execute([$payment['PaymentID']]);
            $paymentItems = $stmtItems->fetchAll();
        }
    }
}

// Student search helper if no student selected
$searchResults = [];
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '' && !$student) {
    $stmtS = $db->prepare("SELECT s.StudentID, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, p.ProgramName, pmt.PaymentStatus, pmt.PaymentID 
        FROM student s 
        JOIN program p ON p.ProgramID = s.ProgramID 
        LEFT JOIN payment pmt ON pmt.StudentID = s.StudentID 
        WHERE s.StudentNo LIKE ? OR s.LastName LIKE ? OR s.FirstName LIKE ? 
        ORDER BY pmt.PaymentID DESC 
        LIMIT 15");
    $term = "%{$searchQuery}%";
    $stmtS->execute([$term, $term, $term]);
    $searchResults = $stmtS->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Process Student Payment</h4>
        <p class="text-muted small mb-0">Review assessed fees, accept payments, generate official receipts, and update checklist status.</p>
    </div>
    <?php if ($student): ?>
        <a href="<?= BASE_URL ?>/accounting/payment.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-search me-1"></i> Choose Another Student
        </a>
    <?php endif; ?>
</div>

<?php if (!$student): ?>
    <!-- Search Student Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-search me-2"></i>Search Student for Payment Processing</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Enter Student Number (e.g. 2024-00001) or Student Name..." value="<?= e($searchQuery) ?>" autofocus required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Search Student</button>
                </div>
            </form>

            <?php if (!empty($searchResults)): ?>
                <div class="list-group mt-3">
                    <div class="list-group-item bg-light fw-bold small text-uppercase">Select Student:</div>
                    <?php foreach ($searchResults as $res): ?>
                        <a href="?student_id=<?= $res['StudentID'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary"><?= e($res['StudentNo']) ?></span> &mdash; 
                                <span class="text-dark fw-semibold"><?= e($res['LastName'] . ', ' . $res['FirstName'] . ' ' . $res['MiddleName']) ?></span>
                                <small class="text-muted d-block"><?= e($res['ProgramName']) ?></small>
                            </div>
                            <div>
                                <span class="badge <?= ($res['PaymentStatus'] ?? '') === 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?> me-2">
                                    <?= e($res['PaymentStatus'] ?? 'Unassessed') ?>
                                </span>
                                <span class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($searchQuery !== ''): ?>
                <div class="alert alert-warning mt-3 mb-0 small">No students found matching "<?= e($searchQuery) ?>".</div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Student Overview Banner -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light border-bottom">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold text-dark mb-1"><?= e($student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']) ?></h5>
                    <div class="text-muted small">
                        <strong>Student ID:</strong> <span class="text-primary fw-bold"><?= e($student['StudentNo']) ?></span> &bull; 
                        <strong>Program:</strong> <?= e($student['ProgramName']) ?> &bull; 
                        <strong>Type:</strong> <span class="badge bg-secondary"><?= e($student['TypeName']) ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <?php if ($payment && $payment['PaymentStatus'] === 'Paid'): ?>
                        <span class="badge bg-success p-2 fs-6"><i class="fas fa-check-circle me-1"></i> Fully Paid (<?= e($payment['ReceiptNo']) ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark p-2 fs-6"><i class="fas fa-clock me-1"></i> Awaiting Payment</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$payment): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No fee assessment found for this student. The student must first be evaluated and study loaded by the Registrar.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Fee Assessment Items Breakdown -->
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-alt me-2"></i>Statement of Account / Fee Assessment</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Fee Classification</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentItems)): ?>
                                    <tr>
                                        <td>Tuition & Lab Fees</td>
                                        <td class="text-muted">Assessed Units & Lab Load</td>
                                        <td class="text-end fw-bold"><?= formatPeso($payment['Amount']) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentItems as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= e($item['FeeType']) ?></td>
                                            <td class="small text-muted"><?= e($item['Description']) ?></td>
                                            <td class="text-end fw-bold"><?= formatPeso($item['Amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr class="table-light fs-6">
                                    <td colspan="2" class="text-end fw-bold">TOTAL ASSESSED AMOUNT:</td>
                                    <td class="text-end fw-bold text-primary"><?= formatPeso($payment['Amount']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Payment Action Form -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-cash-register me-2"></i>Payment Collection Form</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($payment['PaymentStatus'] === 'Paid'): ?>
                            <div class="alert alert-success py-3 text-center">
                                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                                <h6 class="fw-bold mb-1">Payment Completed</h6>
                                <p class="small text-muted mb-3">Official Receipt No: <strong><?= e($payment['ReceiptNo']) ?></strong> on <?= formatDate($payment['PaymentDate']) ?></p>
                                <a href="<?= BASE_URL ?>/accounting/print_receipt.php?payment_id=<?= $payment['PaymentID'] ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-print me-1"></i> Print Official Receipt
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="confirm_payment" value="1">
                                <input type="hidden" name="payment_id" value="<?= $payment['PaymentID'] ?>">
                                <input type="hidden" name="student_id" value="<?= $student['StudentID'] ?>">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Official Receipt Number (Auto-assigned if blank)</label>
                                    <input type="text" name="receipt_no" class="form-control" placeholder="OR-2026-<?= sprintf('%05d', $payment['PaymentID']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Payment Mode</label>
                                    <select name="payment_mode" class="form-select">
                                        <option value="Cash">Cash (Institute Cashier)</option>
                                        <option value="Online / Bank Transfer">Online / Bank Transfer</option>
                                        <option value="Scholarship / Grant">Scholarship / Grant</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-semibold">Total Amount Received</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">₱</span>
                                        <input type="number" step="0.01" name="amount_paid" class="form-control fs-5 fw-bold text-success" value="<?= $payment['Amount'] ?>" required>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success py-2 fw-semibold shadow-sm" onclick="return confirm('Confirm payment receipt? This will immediately clear Accounting on the student checklist.');">
                                        <i class="fas fa-check-circle me-1"></i> Confirm & Issue Receipt
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

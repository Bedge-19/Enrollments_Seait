<?php
// accounting/print_receipt.php - Printable Official Cashiering Receipt
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$paymentId = (int)($_GET['payment_id'] ?? 0);
if ($paymentId <= 0) die("Payment ID required.");

$stmt = $db->prepare("SELECT p.*, s.StudentNo, s.LastName, s.FirstName, s.MiddleName, prg.ProgramName, stf.FirstName as StaffFirst, stf.LastName as StaffLast 
    FROM payment p 
    JOIN student s ON s.StudentID = p.StudentID 
    JOIN program prg ON prg.ProgramID = s.ProgramID 
    LEFT JOIN staff stf ON stf.StaffID = p.StaffID 
    WHERE p.PaymentID = ?");
$stmt->execute([$paymentId]);
$receipt = $stmt->fetch();
if (!$receipt) die("Receipt record not found.");

if ($_SESSION['role'] === 'Student' && (int)$receipt['StudentID'] !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}

$stmtItems = $db->prepare("SELECT * FROM payment_item WHERE PaymentID = ?");
$stmtItems->execute([$paymentId]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt — <?= e($receipt['ReceiptNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <style>
        body { font-family: "Courier New", Courier, monospace; background: #fff; color: #000; padding: 25px; }
        .receipt-container { max-width: 600px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .table-bordered th, .table-bordered td { border: 1px solid #000 !important; font-size: 10pt; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print me-1"></i> Print Receipt</button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i> Close</button>
</div>

<div class="receipt-container">
    <div class="text-center border-bottom pb-2 mb-3">
        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT" style="height:36px;width:36px;object-fit:contain;">
            <div class="text-start">
                <h6 class="fw-bold mb-0">SOUTH EAST ASIAN INSTITUTE OF TECHNOLOGY, INC.</h6>
                <small class="text-muted d-block" style="font-size:8pt;">Tupi, South Cotabato, Philippines</small>
            </div>
        </div>
        <small class="d-block text-muted">Office of Accounting &amp; Student Financial Services</small>
        <h6 class="fw-bold mt-2" style="letter-spacing:0.04em;">OFFICIAL RECEIPT</h6>
        <div class="fs-6 fw-bold">OR No: <u><?= e($receipt['ReceiptNo']) ?></u></div>
    </div>

    <table class="table table-borderless table-sm mb-3" style="font-size: 10pt;">
        <tr>
            <td style="width: 25%;"><strong>Student No:</strong></td>
            <td style="width: 35%;"><?= e($receipt['StudentNo']) ?></td>
            <td style="width: 15%;"><strong>Date:</strong></td>
            <td style="width: 25%;"><?= formatDate($receipt['PaymentDate']) ?></td>
        </tr>
        <tr>
            <td><strong>Name:</strong></td>
            <td colspan="3"><?= e($receipt['LastName'] . ', ' . $receipt['FirstName'] . ' ' . $receipt['MiddleName']) ?></td>
        </tr>
        <tr>
            <td><strong>Program:</strong></td>
            <td colspan="3"><?= e($receipt['ProgramName']) ?></td>
        </tr>
    </table>

    <table class="table table-bordered table-sm mb-3">
        <thead>
            <tr class="table-light">
                <th>Particulars</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td>Tuition & Miscellaneous Fees Assessment</td>
                    <td class="text-end fw-bold"><?= formatPeso($receipt['Amount']) ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <strong><?= e($it['FeeType']) ?></strong>
                            <?php if (!empty($it['Description'])): ?>
                                <small class="d-block text-muted"><?= e($it['Description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold"><?= formatPeso($it['Amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="fw-bold">
                <td class="text-end">TOTAL PAID:</td>
                <td class="text-end"><?= formatPeso($receipt['Amount']) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="row pt-4 text-center" style="font-size: 10pt;">
        <div class="col-6">
            <div>Status: <strong>PAID IN FULL</strong></div>
            <small>Checklist Cleared</small>
        </div>
        <div class="col-6">
            <div class="border-top border-dark pt-1">
                <strong><?= e(($receipt['StaffFirst'] ?? 'University') . ' ' . ($receipt['StaffLast'] ?? 'Cashier')) ?></strong><br>
                <small>Authorized Cashier</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>

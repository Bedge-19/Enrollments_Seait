<?php
// security/print_id.php - Printable Official Student ID Card (Front & Back)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$db = getDBConnection();

$studentId = (int)($_GET['student_id'] ?? $_SESSION['student_id'] ?? 0);
if (!$studentId) {
    die("Student ID required.");
}

// Student can only view their own ID
if ($_SESSION['role'] === 'Student' && $studentId !== (int)$_SESSION['student_id']) {
    die("Unauthorized access.");
}

// Fetch complete student and profile information
$stmt = $db->prepare("SELECT s.*, p.ProgramName, st.TypeName,
    sp.GuardianName, sp.GuardianContactNo, sp.Address as ProfileAddress,
    c.HeightCM, c.WeightKG, c.BMI, c.ClearanceStatus,
    iv.ValidationDate, iv.Status as IdStatus,
    sec.SectionCode
    FROM student s 
    LEFT JOIN program p ON p.ProgramID = s.ProgramID 
    LEFT JOIN student_type st ON st.StudentTypeID = s.StudentTypeID 
    LEFT JOIN student_profile sp ON sp.StudentID = s.StudentID 
    LEFT JOIN clinic c ON c.StudentID = s.StudentID 
    LEFT JOIN id_validation iv ON iv.StudentID = s.StudentID 
    LEFT JOIN enrollment e ON e.StudentID = s.StudentID 
    LEFT JOIN blocking b ON b.EnrollmentID = e.EnrollmentID 
    LEFT JOIN section sec ON sec.SectionID = b.SectionID 
    WHERE s.StudentID = ?
    ORDER BY e.EnrollmentID DESC, iv.ValidationID DESC LIMIT 1");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}

$isNewOrTransferee = in_array(strtolower($student['TypeName'] ?? ''), ['new', 'transferee']);
$guardianName = !empty($student['GuardianName']) ? $student['GuardianName'] : 'Parent / Legal Guardian';
$guardianContact = !empty($student['GuardianContactNo']) ? $student['GuardianContactNo'] : $student['ContactNo'];
$address = !empty($student['ProfileAddress']) ? $student['ProfileAddress'] : $student['Address'];
$initials = strtoupper(substr($student['FirstName'], 0, 1) . substr($student['LastName'], 0, 1));
$issueDate = !empty($student['ValidationDate']) ? date('M d, Y', strtotime($student['ValidationDate'])) : date('M d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Student ID Card &mdash; <?= e($student['StudentNo']) ?></title>
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Libre+Barcode+128&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --id-primary: #0d47a1;
            --id-accent: #ffd600;
            --id-dark: #1a237e;
            --id-bg: #f8fafc;
        }

        body {
            background-color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            padding: 30px 15px;
        }

        .no-print-bar {
            max-width: 900px;
            margin: 0 auto 25px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin: 0 auto;
            max-width: 900px;
        }

        /* CR80 Standard ID Card Ratio: 85.6mm x 54mm (approx 340px x 530px vertical or 530px x 330px horizontal) */
        .id-card {
            width: 340px;
            height: 535px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.25), 0 8px 10px -6px rgba(0,0,0,0.2);
            background: #ffffff;
            page-break-inside: avoid;
            box-sizing: border-box;
            user-select: none;
        }

        /* FRONT CARD STYLING */
        .id-card-front {
            background: linear-gradient(180deg, #0f2b48 0%, #0d47a1 28%, #ffffff 28.5%, #ffffff 92%, #0d47a1 92%);
            border: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
        }

        .front-header {
            padding: 14px 12px 10px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .front-header-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #ffffff;
            padding: 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            object-fit: contain;
            display: block;
            margin: 0 auto 5px;
        }

        .school-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 10.5px;
            letter-spacing: 0.03em;
            line-height: 1.2;
            text-transform: uppercase;
            color: #ffffff;
        }

        .school-sub {
            font-size: 7.5px;
            opacity: 0.9;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #fde047;
            font-weight: 600;
        }

        .card-type-banner {
            background: #ffd600;
            color: #0f172a;
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            font-size: 9px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 2px 0;
            text-align: center;
            margin-top: 6px;
            border-radius: 3px;
        }

        .front-body {
            padding: 12px 16px 8px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-frame {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            border: 3px solid #0d47a1;
            box-shadow: 0 4px 8px rgba(13, 71, 161, 0.2);
            overflow: hidden;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
            position: relative;
        }

        .student-no-badge {
            background: #1e293b;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.08em;
            padding: 3px 14px;
            border-radius: 20px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .student-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 14.5px;
            color: #0f172a;
            line-height: 1.2;
            text-transform: uppercase;
            margin-bottom: 2px;
            letter-spacing: 0.02em;
        }

        .program-title {
            font-size: 10px;
            font-weight: 700;
            color: #0d47a1;
            line-height: 1.25;
            max-width: 280px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .type-pill {
            display: inline-block;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 2px 8px;
            border-radius: 10px;
            background: <?= $isNewOrTransferee ? '#dbeafe' : '#fef3c7' ?>;
            color: <?= $isNewOrTransferee ? '#1e40af' : '#92400e' ?>;
            border: 1px solid <?= $isNewOrTransferee ? '#93c5fd' : '#fde68a' ?>;
            margin-bottom: 8px;
        }

        .barcode-section {
            width: 100%;
            text-align: center;
            margin-top: auto;
            padding-bottom: 6px;
        }

        .barcode-visual {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 46px;
            line-height: 0.9;
            color: #000;
            letter-spacing: 2px;
        }

        .barcode-subtext {
            font-size: 8px;
            font-family: monospace;
            color: #64748b;
            font-weight: 600;
        }

        .front-footer {
            background: #0d47a1;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: 700;
            text-align: center;
            padding: 5px 10px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* BACK CARD STYLING */
        .id-card-back {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
            padding: 16px 14px 10px;
        }

        .back-header {
            border-bottom: 2px solid #0d47a1;
            padding-bottom: 6px;
            margin-bottom: 8px;
            text-align: center;
        }

        .back-terms {
            font-size: 6.8px;
            line-height: 1.35;
            color: #475569;
            text-align: justify;
            margin-bottom: 8px;
        }

        .emergency-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #dc2626;
            border-radius: 6px;
            padding: 6px 8px;
            margin-bottom: 10px;
        }

        .emergency-title {
            font-size: 7.5px;
            font-weight: 800;
            color: #dc2626;
            text-transform: uppercase;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .emergency-info {
            font-size: 7.5px;
            line-height: 1.3;
            color: #1e293b;
        }

        .validation-matrix {
            margin-bottom: 10px;
        }

        .validation-title {
            font-size: 7.5px;
            font-weight: 800;
            color: #0d47a1;
            text-transform: uppercase;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .validation-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }

        .val-cell {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            padding: 4px 2px;
            text-align: center;
            font-size: 6.5px;
            min-height: 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .val-cell.validated {
            background: #f0fdf4;
            border: 1px solid #16a34a;
            color: #166534;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-box {
            text-align: center;
            width: 46%;
        }

        .sig-line {
            border-bottom: 1px solid #334155;
            height: 24px;
            margin-bottom: 2px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-family: 'Brush Script MT', cursive, sans-serif;
            font-size: 14px;
            color: #0f172a;
        }

        .sig-label {
            font-size: 6.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        .back-footer {
            margin-top: 6px;
            text-align: center;
            font-size: 6.5px;
            color: #64748b;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .cards-container {
                gap: 20px;
                max-width: 100%;
            }
            .id-card {
                box-shadow: none !important;
                border: 1px solid #94a3b8;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print no-print-bar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-id-card me-2"></i>Official Student Identification Card</h5>
        <small class="text-muted">Student: <strong><?= e($student['LastName'] . ', ' . $student['FirstName']) ?></strong> (<?= e($student['StudentNo']) ?>) &bull; <?= e($student['TypeName']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm shadow-sm">
            <i class="fas fa-print me-1"></i> Print ID Card (Front & Back)
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times me-1"></i> Close
        </button>
    </div>
</div>

<div class="cards-container">
    <!-- FRONT OF ID CARD -->
    <div class="id-card id-card-front" id="cardFront">
        <div class="front-header">
            <img src="<?= BASE_URL ?>/photo/logo.jpg" alt="SEAIT Logo" class="front-header-logo">
            <div class="school-name">South East Asian Institute of Technology, Inc.</div>
            <div class="school-sub">Tupi, South Cotabato &bull; Philippines</div>
            <div class="card-type-banner">STUDENT IDENTIFICATION CARD</div>
        </div>

        <div class="front-body">
            <div class="photo-frame">
                <?= $initials ?>
            </div>

            <div class="student-no-badge">
                <?= e($student['StudentNo']) ?>
            </div>

            <div class="student-name">
                <?= e($student['FirstName'] . ' ' . ($student['MiddleName'] ? substr($student['MiddleName'], 0, 1) . '. ' : '') . $student['LastName']) ?>
            </div>

            <div class="program-title">
                <?= e($student['ProgramName']) ?>
            </div>

            <span class="type-pill">
                <?= e($student['TypeName']) ?> STUDENT &bull; A.Y. 2026-2027
            </span>

            <div class="barcode-section">
                <div class="barcode-visual">*<?= e($student['StudentNo']) ?>*</div>
                <div class="barcode-subtext">CARD SERIAL NO: <?= strtoupper(substr(md5($student['StudentID'] . $student['StudentNo']), 0, 12)) ?></div>
            </div>
        </div>

        <div class="front-footer">
            Official Student ID &bull; South East Asian Institute of Technology
        </div>
    </div>

    <!-- BACK OF ID CARD -->
    <div class="id-card id-card-back" id="cardBack">
        <div class="back-header">
            <h6 class="fw-bold mb-0 text-primary" style="font-size: 8.5px; letter-spacing: 0.05em; text-transform: uppercase;">
                Terms and Conditions
            </h6>
        </div>

        <div class="back-terms">
            1. This card is non-transferable and must be worn at all times within campus premises.<br>
            2. In case of loss or damage, report immediately to the Security Office & Registrar.<br>
            3. Surrender this card upon graduation, withdrawal, or dismissal from the institution.<br>
            4. If found, please return to the Security Office, SEAIT, Crossing Rubber, Tupi, South Cotabato.
        </div>

        <div class="emergency-box">
            <div class="emergency-title">
                <i class="fas fa-exclamation-triangle"></i> In Case of Emergency, Notify:
            </div>
            <div class="emergency-info">
                <strong><?= e($guardianName) ?></strong><br>
                <span>Phone: <?= e($guardianContact) ?></span><br>
                <span>Address: <?= e($address) ?></span>
            </div>
        </div>

        <div class="validation-matrix">
            <div class="validation-title">
                <span>Semester Validation Sticker</span>
                <span>A.Y. 2026-2027</span>
            </div>
            <div class="validation-grid">
                <div class="val-cell validated">
                    <strong style="font-size: 7px;">1ST SEMESTER</strong>
                    <span style="font-size: 6px; font-weight: 800; color: #15803d;">✓ VALIDATED</span>
                    <span style="font-size: 5.5px;"><?= $issueDate ?></span>
                </div>
                <div class="val-cell">
                    <strong style="font-size: 7px; color: #64748b;">2ND SEMESTER</strong>
                    <span style="font-size: 5.5px; color: #94a3b8;">Pending</span>
                </div>
                <div class="val-cell">
                    <strong style="font-size: 7px; color: #64748b;">SUMMER TERM</strong>
                    <span style="font-size: 5.5px; color: #94a3b8;">Pending</span>
                </div>
            </div>
        </div>

        <div class="signature-row">
            <div class="sig-box">
                <div class="sig-line">
                    <small style="font-size: 11px;"><?= e($student['FirstName'] . ' ' . $student['LastName']) ?></small>
                </div>
                <div class="sig-label">Student Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-line">
                    <small style="font-size: 11px; color: #0d47a1; font-weight: bold;">SEAIT Security</small>
                </div>
                <div class="sig-label">Security Director / Registrar</div>
            </div>
        </div>

        <div class="back-footer">
            Official Property of SEAIT &bull; Issue Date: <?= $issueDate ?>
        </div>
    </div>
</div>

</body>
</html>

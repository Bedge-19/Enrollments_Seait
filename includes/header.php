<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$pageTitle = $pageTitle ?? 'Enrollment System';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — SEAIT Enrollment Portal</title>
    <meta name="description" content="South East Asian Institute of Technology — Student Enrollment and Confirmation System">
    <!-- Work Sans font (Aura Radiant design system) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fonts/fonts.css">
    <!-- Bootstrap 5.3 CSS -->
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
    <!-- SEAIT Aura Radiant Design System -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<!-- ═══ Page Loading Skeleton Overlay ═══ -->
<div id="pageSkeletonLoader" aria-hidden="true">
    <div class="skeleton-loader-sidebar">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="skeleton-circle" style="width:38px;height:38px;"></div>
            <div style="flex:1;">
                <div class="skeleton-text" style="height:14px;width:75%;margin-bottom:4px;"></div>
                <div class="skeleton-text" style="height:10px;width:50%;margin-bottom:0;"></div>
            </div>
        </div>
        <div class="skeleton-text" style="height:10px;width:40%;margin-top:12px;"></div>
        <div class="skeleton-text" style="height:38px;border-radius:8px;margin-bottom:6px;"></div>
        <div class="skeleton-text" style="height:38px;border-radius:8px;margin-bottom:6px;"></div>
        <div class="skeleton-text" style="height:38px;border-radius:8px;margin-bottom:6px;"></div>
        <div class="skeleton-text" style="height:10px;width:40%;margin-top:16px;"></div>
        <div class="skeleton-text" style="height:38px;border-radius:8px;margin-bottom:6px;"></div>
        <div class="skeleton-text" style="height:38px;border-radius:8px;margin-bottom:6px;"></div>
        <div class="mt-auto">
            <div class="skeleton-box" style="height:56px;border-radius:10px;"></div>
        </div>
    </div>
    <div class="skeleton-loader-main">
        <div class="skeleton-loader-topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="skeleton-circle" style="width:28px;height:28px;"></div>
                <div class="skeleton-title" style="width:160px;height:18px;margin-bottom:0;"></div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="skeleton-text d-none d-sm-block" style="width:110px;height:14px;margin-bottom:0;"></div>
                <div class="skeleton-circle" style="width:36px;height:36px;"></div>
            </div>
        </div>
        <div class="skeleton-loader-body">
            <div class="skeleton-stat-grid">
                <div class="skeleton-stat-card">
                    <div style="flex:1;">
                        <div class="skeleton-text" style="height:12px;width:60%;margin-bottom:8px;"></div>
                        <div class="skeleton-title" style="height:26px;width:70px;margin-bottom:0;"></div>
                    </div>
                    <div class="skeleton-circle" style="width:42px;height:42px;"></div>
                </div>
                <div class="skeleton-stat-card">
                    <div style="flex:1;">
                        <div class="skeleton-text" style="height:12px;width:60%;margin-bottom:8px;"></div>
                        <div class="skeleton-title" style="height:26px;width:70px;margin-bottom:0;"></div>
                    </div>
                    <div class="skeleton-circle" style="width:42px;height:42px;"></div>
                </div>
                <div class="skeleton-stat-card">
                    <div style="flex:1;">
                        <div class="skeleton-text" style="height:12px;width:60%;margin-bottom:8px;"></div>
                        <div class="skeleton-title" style="height:26px;width:70px;margin-bottom:0;"></div>
                    </div>
                    <div class="skeleton-circle" style="width:42px;height:42px;"></div>
                </div>
                <div class="skeleton-stat-card">
                    <div style="flex:1;">
                        <div class="skeleton-text" style="height:12px;width:60%;margin-bottom:8px;"></div>
                        <div class="skeleton-title" style="height:26px;width:70px;margin-bottom:0;"></div>
                    </div>
                    <div class="skeleton-circle" style="width:42px;height:42px;"></div>
                </div>
            </div>

            <div class="skeleton-table-wrap">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="skeleton-title" style="width:200px;height:20px;margin-bottom:0;"></div>
                    <div class="skeleton-button" style="width:110px;height:32px;"></div>
                </div>
                <div class="skeleton-table-row">
                    <div class="skeleton-circle" style="width:34px;height:34px;"></div>
                    <div class="skeleton-text" style="width:55%;height:14px;margin-bottom:0;"></div>
                    <div class="skeleton-pill ms-auto"></div>
                </div>
                <div class="skeleton-table-row">
                    <div class="skeleton-circle" style="width:34px;height:34px;"></div>
                    <div class="skeleton-text" style="width:75%;height:14px;margin-bottom:0;"></div>
                    <div class="skeleton-pill ms-auto"></div>
                </div>
                <div class="skeleton-table-row">
                    <div class="skeleton-circle" style="width:34px;height:34px;"></div>
                    <div class="skeleton-text" style="width:60%;height:14px;margin-bottom:0;"></div>
                    <div class="skeleton-pill ms-auto"></div>
                </div>
                <div class="skeleton-table-row">
                    <div class="skeleton-circle" style="width:34px;height:34px;"></div>
                    <div class="skeleton-text" style="width:45%;height:14px;margin-bottom:0;"></div>
                    <div class="skeleton-pill ms-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="app-wrapper">

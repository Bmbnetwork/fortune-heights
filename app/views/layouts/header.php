<?php
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';
require_once APP_PATH . '/helpers/Functions.php';
require_once APP_PATH . '/helpers/Security.php';
require_once APP_PATH . '/helpers/Auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fortune Heights Montessori School - Parent Teacher Communication System">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= e($pageTitle ?? SCHOOL_NAME) ?> - <?= SCHOOL_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/responsive.css">
    
    <!-- CSRF Token Meta -->
    <meta name="csrf-token" content="<?= Security::generateCsrfToken() ?>">
</head>
<body>
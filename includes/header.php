<?php
require_once 'config/db.php';
require_once 'includes/CurrencyConverter.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user's preferred currency and verify user exists
$stmt = $pdo->prepare("SELECT currency FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

if (!$user_data) {
    // User ID in session doesn't exist in DB (likely due to DB reset)
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit;
}

$user_currency = $user_data['currency'] ?: 'USD';
$_SESSION['currency'] = $user_currency;
$currency_symbol = CurrencyConverter::getSymbol($user_currency);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title ?? 'Dashboard'; ?> | Expenses Tracker
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
                <div class="page-title">
                    <h2>
                        <?php echo $page_title ?? 'Dashboard'; ?>
                    </h2>
                    <p>Welcome back,
                        <?php echo $_SESSION['username']; ?>!
                    </p>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                </div>
            </header>
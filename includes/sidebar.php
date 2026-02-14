<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-wallet"></i>
        <span>Expenses Tracker</span>
    </div>

    <nav class="nav-links">
        <div class="nav-item">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="transactions.php"
                class="nav-link <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Transactions</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="add-transaction.php"
                class="nav-link <?php echo $current_page == 'add-transaction.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Record</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Monthly Reports</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="nav-item">
            <a href="logout.php" class="nav-link" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
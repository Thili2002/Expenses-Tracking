<?php
require_once 'config/db.php';
$page_title = 'Dashboard';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$target_currency = $_SESSION['currency'];

// Helper for currency conversion within SQL (or we bring it to PHP)
// For accurate results with multiple currencies, we should fetch transactions and convert in PHP
// Or join with a rates table. For this simple app, we'll fetch and convert in PHP for stats.

$all_transactions = $pdo->prepare("SELECT amount, type, currency, transaction_date, category_id FROM transactions WHERE user_id = ?");
$all_transactions->execute([$user_id]);
$txs = $all_transactions->fetchAll();

$total_income = 0;
$total_expense = 0;
$today_expense = 0;
$month_expense = 0;
$prev_month_expense = 0;
$weekly_spending = array_fill(0, 7, 0); // last 7 days
$category_spending = [];
$daily_count = [];

$today = date('Y-m-d');
$first_day_month = date('Y-m-01');
$first_day_prev_month = date('Y-m-01', strtotime('first day of last month'));
$last_day_prev_month = date('Y-m-t', strtotime('last month'));

foreach ($txs as $t) {
    $amount_converted = CurrencyConverter::convert($t['amount'], $t['currency'] ?: 'USD', $target_currency);

    if ($t['type'] == 'income') {
        $total_income += $amount_converted;
    } else {
        $total_expense += $amount_converted;

        // Today
        if ($t['transaction_date'] == $today) {
            $today_expense += $amount_converted;
        }

        // This Month
        if ($t['transaction_date'] >= $first_day_month) {
            $month_expense += $amount_converted;

            // Category Wise
            $cat_id = $t['category_id'] ?: 0;
            $category_spending[$cat_id] = ($category_spending[$cat_id] ?? 0) + $amount_converted;

            // Daily for Avg
            $daily_count[$t['transaction_date']] = ($daily_count[$t['transaction_date']] ?? 0) + $amount_converted;
        }

        // Prev Month
        if ($t['transaction_date'] >= $first_day_prev_month && $t['transaction_date'] <= $last_day_prev_month) {
            $prev_month_expense += $amount_converted;
        }

        // Weekly Trend
        $date_diff = (strtotime($today) - strtotime($t['transaction_date'])) / (60 * 60 * 24);
        if ($date_diff >= 0 && $date_diff < 7) {
            $weekly_spending[6 - (int) $date_diff] += $amount_converted;
        }
    }
}

$balance = $total_income - $total_expense;

// Advanced Stats
$income_expense_ratio = $total_expense > 0 ? ($total_income / $total_expense) : 0;
$savings_percentage = $total_income > 0 ? (($total_income - $total_expense) / $total_income) * 100 : 0;
$avg_daily_expense = count($daily_count) > 0 ? $month_expense / date('j') : 0;

// Get Highest Category
arsort($category_spending);
$highest_cat_id = !empty($category_spending) ? key($category_spending) : null;
$highest_cat_name = "N/A";
if ($highest_cat_id) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$highest_cat_id]);
    $highest_cat_name = $stmt->fetchColumn();
}

// Chart Labels for Weekly
$weekly_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $weekly_labels[] = date('D', strtotime("-$i days"));
}

// Recent Transactions (Limit 5)
$stmt = $pdo->prepare("SELECT t.*, c.name as category_name, c.icon as category_icon 
                       FROM transactions t 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       WHERE t.user_id = ? 
                       ORDER BY t.transaction_date DESC, t.id DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

// Category Pie Chart Data
$pie_labels = [];
$pie_values = [];
foreach ($category_spending as $id => $val) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $pie_labels[] = $stmt->fetchColumn() ?: 'Other';
    $pie_values[] = $val;
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-info">
            <h3>Total Balance</h3>
            <div><?php echo $currency_symbol; ?> <?php echo number_format($balance, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3>Today's Expense</h3>
            <div><?php echo $currency_symbol; ?> <?php echo number_format($today_expense, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-red">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-info">
            <h3>This Month</h3>
            <div><?php echo $currency_symbol; ?> <?php echo number_format($month_expense, 2); ?></div>
        </div>
    </div>
</div>

<div class="stats-grid" style="margin-top: 1.5rem;">
    <div class="stat-card mini">
        <div class="stat-info">
            <p>Avg Daily Expense</p>
            <h4><?php echo $currency_symbol; ?> <?php echo number_format($avg_daily_expense, 2); ?></h4>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-info">
            <p>Highest Category</p>
            <h4><?php echo htmlspecialchars($highest_cat_name); ?></h4>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-info">
            <p>Savings Rate</p>
            <h4><?php echo number_format(max(0, $savings_percentage), 1); ?>%</h4>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-info">
            <p>I/E Ratio</p>
            <h4><?php echo number_format($income_expense_ratio, 2); ?></h4>
        </div>
    </div>
</div>

<div class="dashboard-sections">
    <div class="content-card">
        <div class="card-header">
            <h3>Weekly Spending Trend</h3>
        </div>
        <div style="height: 300px;">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3>Expense by Category</h3>
        </div>
        <div style="height: 300px;">
            <?php if (empty($pie_values)): ?>
                <p class="text-center" style="margin-top: 100px; color: var(--gray-400);">No data for this month</p>
            <?php else: ?>
                <canvas id="categoryChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dashboard-sections">
    <div class="content-card">
        <div class="card-header">
            <h3>Recent Transactions</h3>
            <a href="transactions.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_transactions)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No transactions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $t): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                            <i class="fas <?php echo $t['category_icon']; ?>"></i>
                                        </div>
                                        <?php echo $t['category_name']; ?>
                                    </div>
                                </td>
                                <td><?php echo date('M d', strtotime($t['transaction_date'])); ?></td>
                                <td
                                    style="font-weight: 600; color: <?php echo $t['type'] == 'income' ? 'var(--success)' : 'var(--danger)'; ?>">
                                    <?php
                                    $disp_amount = CurrencyConverter::convert($t['amount'], $t['currency'] ?: 'USD', $target_currency);
                                    echo ($t['type'] == 'income' ? '+' : '-') . ' ' . $currency_symbol . ' ' . number_format($disp_amount, 2);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3>Monthly Comparison</h3>
        </div>
        <div style="height: 300px;">
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Weekly Trend Chart
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($weekly_labels); ?>,
            datasets: [{
                label: 'Spending',
                data: <?php echo json_encode($weekly_spending); ?>,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Category Chart
    <?php if (!empty($pie_values)): ?>
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($pie_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pie_values); ?>,
                    backgroundColor: ['#7c3aed', '#10b981', '#ef4444', '#f59e0b', '#3b82f6', '#ec4899'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                },
                cutout: '70%'
            }
        });
    <?php endif; ?>

    // Monthly Comparison Chart
    const compCtx = document.getElementById('comparisonChart').getContext('2d');
    new Chart(compCtx, {
        type: 'bar',
        data: {
            labels: ['Last Month', 'This Month'],
            datasets: [{
                label: 'Spending',
                data: [<?php echo $prev_month_expense; ?>, <?php echo $month_expense; ?>],
                backgroundColor: ['#e5e7eb', '#7c3aed'],
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
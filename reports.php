<?php
require_once 'config/db.php';
$page_title = 'Monthly Reports';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$target_curr = $_SESSION['currency'];
$month_param = $_GET['month'] ?? date('Y-m');

// Fetch all transactions for the month to convert accurately in PHP
$stmt = $pdo->prepare("SELECT amount, type, currency, category_id, transaction_date 
                       FROM transactions 
                       WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$stmt->execute([$user_id, $month_param]);
$txs = $stmt->fetchAll();

$total_income = 0;
$total_expense = 0;
$category_totals = [];
$daily_trend = [];

foreach ($txs as $t) {
    $amount_converted = CurrencyConverter::convert($t['amount'], $t['currency'] ?: 'USD', $target_curr);

    if ($t['type'] == 'income') {
        $total_income += $amount_converted;
    } else {
        $total_expense += $amount_converted;

        $day = date('d', strtotime($t['transaction_date']));
        $daily_trend[$day] = ($daily_trend[$day] ?? 0) + $amount_converted;

        $cat_id = $t['category_id'] ?: 0;
        $category_totals[$cat_id] = ($category_totals[$cat_id] ?? 0) + $amount_converted;
    }
}

// Get category names
$breakdown = [];
foreach ($category_totals as $id => $total) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $breakdown[] = [
        'name' => $stmt->fetchColumn() ?: 'Other',
        'total' => $total
    ];
}

ksort($daily_trend);
$trend_labels = array_keys($daily_trend);
$trend_values = array_values($daily_trend);
?>

<div class="filter-section content-card mb-4">
    <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <label>Select Month</label>
            <input type="month" name="month" class="form-control" value="<?php echo $month_param; ?>">
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
        <button type="button" onclick="window.print()" class="btn btn-outline btn-print"><i class="fas fa-print"></i>
            Print PDF</button>
    </form>
</div>

<div id="report-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-info">
                <h3>Total Income</h3>
                <div style="color: var(--success);"><?php echo $currency_symbol; ?>
                    <?php echo number_format($total_income, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-red"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-info">
                <h3>Total Expenses</h3>
                <div style="color: var(--danger);"><?php echo $currency_symbol; ?>
                    <?php echo number_format($total_expense, 2); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fas fa-balance-scale"></i></div>
            <div class="stat-info">
                <h3>Net Savings</h3>
                <div><?php echo $currency_symbol; ?> <?php echo number_format($total_income - $total_expense, 2); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="content-card">
            <div class="card-header">
                <h3>Daily Expense Trend (<?php echo $_SESSION['currency']; ?>)</h3>
            </div>
            <div style="height: 300px;">
                <?php if (empty($trend_values)): ?>
                    <p class="text-center" style="margin-top: 100px; color: var(--gray-400);">No data for this month</p>
                <?php else: ?>
                    <canvas id="trendChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3>Expense by Category</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total (<?php echo $_SESSION['currency']; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($breakdown)): ?>
                            <tr>
                                <td colspan="2" class="text-center">No expenses.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($breakdown as $row): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td style="font-weight: 600;"><?php echo $currency_symbol; ?>
                                        <?php echo number_format($row['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (!empty($trend_values)): ?>
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?php echo json_encode($trend_values); ?>,
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
                    y: { beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
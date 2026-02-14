<?php
require_once 'config/db.php';
$page_title = 'Transactions';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$month = $_GET['month'] ?? '';

// Build Query
$query = "SELECT t.*, c.name as category_name, c.icon as category_icon 
          FROM transactions t 
          LEFT JOIN categories c ON t.category_id = c.id 
          WHERE t.user_id = ?";
$params = [$user_id];

if (!empty($search)) {
    $query .= " AND t.note LIKE ?";
    $params[] = "%$search%";
}
if (!empty($type)) {
    $query .= " AND t.type = ?";
    $params[] = $type;
}
if (!empty($category)) {
    $query .= " AND t.category_id = ?";
    $params[] = $category;
}
if (!empty($month)) {
    $query .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
    $params[] = $month;
}

$query .= " ORDER BY t.transaction_date DESC, t.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get categories for filter
$cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="content-card mb-4">
    <form method="GET"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search notes..."
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Type</label>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="income" <?php echo $type == 'income' ? 'selected' : ''; ?>>Income</option>
                <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>Expense</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Category</label>
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Month</label>
            <input type="month" name="month" class="form-control" value="<?php echo $month; ?>">
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-filter"></i> Filter</button>
            <a href="transactions.php" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Note</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No transactions found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <?php echo date('d M Y', strtotime($t['transaction_date'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas <?php echo $t['category_icon']; ?>" style="color: var(--primary);"></i>
                                    <?php echo $t['category_name']; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($t['note']); ?>
                            </td>
                            <td>
                                <span
                                    class="type-badge <?php echo $t['type'] == 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                    <?php echo ucfirst($t['type']); ?>
                                </span>
                            </td>
                            <td
                                style="font-weight: 600; color: <?php echo $t['type'] == 'income' ? 'var(--success)' : 'var(--danger)'; ?>">
                                <?php
                                $disp_currency = $t['currency'] ?: 'USD';
                                echo CurrencyConverter::getSymbol($disp_currency) . ' ' . number_format($t['amount'], 2);
                                ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="edit-transaction.php?id=<?php echo $t['id']; ?>" class="btn btn-outline btn-sm"
                                        style="padding: 0.25rem 0.5rem; color: #3b82f6;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete-transaction.php?id=<?php echo $t['id']; ?>" class="btn btn-outline btn-sm"
                                        style="padding: 0.25rem 0.5rem; color: #ef4444;"
                                        onclick="return confirm('Are you sure you want to delete this transaction?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
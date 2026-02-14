<?php
require_once 'config/db.php';
$page_title = 'Add Transaction';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_transaction'])) {
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category_id = $_POST['category_id'];
    $date = $_POST['date'];
    $currency = $_POST['currency'];
    $note = trim($_POST['note']);

    if (empty($amount) || empty($type) || empty($category_id) || empty($date) || empty($currency)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, transaction_date, currency, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $amount, $type, $category_id, $date, $currency, $note])) {
            $success = "Transaction added successfully!";
        } else {
            $error = "Failed to add transaction.";
        }
    }
}

// Fetch categories based on type (AJAX would be better, but for simplicity we'll fetch all or use JS to filter)
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3>New Transaction</h3>
    </div>

    <?php if ($error): ?>
        <div style="background: #fef2f2; color: #ef4444; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #ecfdf5; color: #10b981; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
            </div>
            <div class="form-group" style="width: 120px;">
                <label>Currency</label>
                <select name="currency" class="form-control" required>
                    <?php foreach (CurrencyConverter::$supported_currencies as $curr): ?>
                        <option value="<?php echo $curr; ?>" <?php echo $curr == $_SESSION['currency'] ? 'selected' : ''; ?>>
                            <?php echo $curr; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Transaction Type</label>
            <div style="display: flex; gap: 1rem;">
                <label style="flex: 1; cursor: pointer;">
                    <input type="radio" name="type" value="expense" checked style="display: none;" id="type-expense">
                    <div class="btn btn-outline btn-block type-btn" onclick="selectType('expense')" id="btn-expense">
                        Expense</div>
                </label>
                <label style="flex: 1; cursor: pointer;">
                    <input type="radio" name="type" value="income" style="display: none;" id="type-income">
                    <div class="btn btn-outline btn-block type-btn" onclick="selectType('income')" id="btn-income">
                        Income</div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" id="category-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                        <?php echo $cat['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label>Note (Optional)</label>
            <textarea name="note" class="form-control" rows="3" placeholder="What's this for?"></textarea>
        </div>

        <button type="submit" name="add_transaction" class="btn btn-primary btn-block">
            <i class="fas fa-plus"></i> Save Transaction
        </button>
    </form>
</div>

<script>
    function selectType(type) {
        document.getElementById('type-' + type).checked = true;

        // UI update
        document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('btn-primary'));
        document.querySelectorAll('.type-btn').forEach(btn => btn.classList.add('btn-outline'));

        const activeBtn = document.getElementById('btn-' + type);
        activeBtn.classList.remove('btn-outline');
        activeBtn.classList.add('btn-primary');

        // Filter categories
        const select = document.getElementById('category-select');
        const options = select.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value === "") return;
            if (opt.getAttribute('data-type') === type) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
        select.value = "";
    }

    // Initial call
    selectType('expense');
</script>

<?php include 'includes/footer.php'; ?>
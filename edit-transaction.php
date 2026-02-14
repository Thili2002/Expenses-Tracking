<?php
require_once 'config/db.php';
$page_title = 'Edit Transaction';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;

// Fetch transaction
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    echo "<div class='content-card'><p class='text-center'>Transaction not found.</p></div>";
    include 'includes/footer.php';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaction'])) {
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category_id = $_POST['category_id'];
    $date = $_POST['date'];
    $note = trim($_POST['note']);

    if (empty($amount) || empty($type) || empty($category_id) || empty($date)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("UPDATE transactions SET amount = ?, type = ?, category_id = ?, transaction_date = ?, note = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$amount, $type, $category_id, $date, $note, $id, $user_id])) {
            $success = "Transaction updated successfully!";
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $transaction = $stmt->fetch();
        } else {
            $error = "Failed to update transaction.";
        }
    }
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3>Edit Transaction</h3>
        <a href="transactions.php" class="btn btn-outline btn-sm">Cancel</a>
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
        <div class="form-group">
            <label>Amount ($)</label>
            <input type="number" step="0.01" name="amount" class="form-control"
                value="<?php echo $transaction['amount']; ?>" required>
        </div>

        <div class="form-group">
            <label>Transaction Type</label>
            <div style="display: flex; gap: 1rem;">
                <label style="flex: 1; cursor: pointer;">
                    <input type="radio" name="type" value="expense" <?php echo $transaction['type'] == 'expense' ? 'checked' : ''; ?> style="display: none;" id="type-expense">
                    <div class="btn <?php echo $transaction['type'] == 'expense' ? 'btn-primary' : 'btn-outline'; ?> btn-block type-btn"
                        onclick="selectType('expense')" id="btn-expense">Expense</div>
                </label>
                <label style="flex: 1; cursor: pointer;">
                    <input type="radio" name="type" value="income" <?php echo $transaction['type'] == 'income' ? 'checked' : ''; ?> style="display: none;" id="type-income">
                    <div class="btn <?php echo $transaction['type'] == 'income' ? 'btn-primary' : 'btn-outline'; ?> btn-block type-btn"
                        onclick="selectType('income')" id="btn-income">Income</div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control" id="category-select" required>
                <option value="">Select Category</option>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>" <?php echo $transaction['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo $transaction['transaction_date']; ?>"
                required>
        </div>

        <div class="form-group">
            <label>Note</label>
            <textarea name="note" class="form-control"
                rows="3"><?php echo htmlspecialchars($transaction['note']); ?></textarea>
        </div>

        <button type="submit" name="update_transaction" class="btn btn-primary btn-block">
            <i class="fas fa-save"></i> Update Transaction
        </button>
    </form>
</div>

<script>
    function selectType(type) {
        document.getElementById('type-' + type).checked = true;
        document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('btn-primary'));
        document.querySelectorAll('.type-btn').forEach(btn => btn.classList.add('btn-outline'));
        const activeBtn = document.getElementById('btn-' + type);
        activeBtn.classList.remove('btn-outline');
        activeBtn.classList.add('btn-primary');

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
    }
    window.onload = function () {
        selectType('<?php echo $transaction['type']; ?>');
    };
</script>

<?php include 'includes/footer.php'; ?>
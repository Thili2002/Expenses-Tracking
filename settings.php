<?php
require_once 'config/db.php';
$page_title = 'Settings';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $new_currency = $_POST['currency'];

    $stmt = $pdo->prepare("UPDATE users SET currency = ? WHERE id = ?");
    if ($stmt->execute([$new_currency, $user_id])) {
        $_SESSION['currency'] = $new_currency;
        $success = "Settings updated successfully!";
        // Refresh symbols
        $currency_symbol = CurrencyConverter::getSymbol($new_currency);
    } else {
        $error = "Failed to update settings.";
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3>General Settings</h3>
    </div>

    <?php if ($success): ?>
        <div style="background: #ecfdf5; color: #10b981; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Preferred Base Currency</label>
            <p style="font-size: 0.8125rem; color: var(--gray-500); margin-bottom: 0.5rem;">
                All totals and reports will be converted and displayed in this currency.
            </p>
            <select name="currency" class="form-control" required>
                <?php foreach (CurrencyConverter::$supported_currencies as $curr): ?>
                    <option value="<?php echo $curr; ?>" <?php echo $curr == $user['currency'] ? 'selected' : ''; ?>>
                        <?php echo $curr; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid #f3f4f6; padding-top: 1.5rem;">
            <button type="submit" name="update_settings" class="btn btn-primary">
                Save Changes
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
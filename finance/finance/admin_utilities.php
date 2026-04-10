<?php 
include 'header.php'; 
include 'db_connect.php';

// Security: Only allow 'admin' role to access this utility page
restrictTo(['admin']);

// Fetch Current Departments and Accounts for the UI
$depts = $conn->query("SELECT * FROM departments ORDER BY dept_name ASC");
$accounts = $conn->query("SELECT * FROM chart_of_accounts ORDER BY account_type ASC, account_name ASC");
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-dark"><i class="bi bi-shield-lock text-danger"></i> Admin Utilities</h2>
            <p class="text-muted">High-level system overrides for Budget Allocations and the General Ledger.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="dashboard.php" class="btn btn-outline-secondary fw-bold">
                <i class="bi bi-arrow-left"></i> Exit to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Departmental Budget Override</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-4">Use this to increase or decrease a department's total allowed spending for the fiscal year.</p>
                    
                    <form id="adminBudgetForm">
                        <input type="hidden" name="action" value="update_budget">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Select Department</label>
                            <select name="dept_id" class="form-select form-select-lg">
                                <?php while($d = $depts->fetch_assoc()): ?>
                                    <option value="<?= $d['dept_id'] ?>">
                                        <?= $d['dept_name'] ?> (Current: $<?= number_format($d['total_budget'], 2) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase">New Total Budget ($)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">$</span>
                                <input type="number" name="new_amount" class="form-control" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2">
                            Update Allocation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-layout-text-sidebar-reverse"></i> Ledger Balance Adjustment</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-4">Directly modify the balance of an account in the Chart of Accounts. <strong>Use with caution.</strong></p>
                    
                    <form id="adminLedgerForm">
                        <input type="hidden" name="action" value="adjust_account">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase">Select Account</label>
                            <select name="account_id" class="form-select form-select-lg">
                                <?php while($a = $accounts->fetch_assoc()): ?>
                                    <option value="<?= $a['account_id'] ?>">
                                        [<?= $a['account_type'] ?>] <?= $a['account_name'] ?> ($<?= number_format($a['balance'], 2) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase">Force New Balance ($)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">$</span>
                                <input type="number" name="balance" class="form-control" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-bold py-2">
                            Adjust Account Balance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning mt-5 border-0 shadow-sm rounded-3">
        <div class="d-flex">
            <div class="me-3">
                <i class="bi bi-exclamation-triangle-fill display-6"></i>
            </div>
            <div>
                <h5 class="fw-bold">Administrator Warning</h5>
                <p class="mb-0 small">Manual adjustments do not generate automatic journal entries. These overrides should only be used for correcting errors or setting up opening balances. All actions taken on this page are restricted to the <strong>Admin</strong> role.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Shared AJAX handler for both forms
    $('#adminBudgetForm, #adminLedgerForm').on('submit', function(e) {
        e.preventDefault();
        
        if(!confirm("Are you sure you want to perform this manual override? This action affects institutional financial data directly.")) {
            return;
        }

        $.post('api_handler.php', $(this).serialize(), function(response) {
            alert(response);
            location.reload();
        });
    });
});
</script>
</body>
</html>

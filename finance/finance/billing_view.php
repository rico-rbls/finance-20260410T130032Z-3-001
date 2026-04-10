<?php 
include 'header.php'; 
include 'db_connect.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Logic: Students see only their bills; Staff see everything.
if ($role === 'student') {
    $query = "SELECT * FROM invoices WHERE student_name = (SELECT full_name FROM users WHERE user_id = $user_id)";
} else {
    $query = "SELECT * FROM invoices ORDER BY invoice_id DESC";
}
$result = $conn->query($query);
?>

<div class="modal fade" id="newInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Issue New Student Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="api_handler.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_invoice">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Student Name</label>
                        <input type="text" name="student_name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Total Amount ($)</label>
                        <input type="number" name="total_amount" class="form-control" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success fw-bold w-100">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark"><i class="bi bi-wallet2 text-success"></i> Student Billing & AR</h2>
            <p class="text-muted mb-0">Track tuition invoices, miscellaneous fees, and student payment history.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
            <a href="dashboard.php" class="btn btn-secondary fw-bold shadow-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <?php if($role != 'student'): ?>
            <button class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newInvoiceModal">
                <i class="bi bi-plus-circle"></i> Create New Invoice
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span class="mb-0">Accounts Receivable Ledger</span>
            <span class="badge bg-white text-success"><?= $result->num_rows ?> Records Found</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Invoice #</th>
                            <th>Student Name</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Status</th>
                            <th>Date Issued</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-success">INV-<?= $row['invoice_id'] ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td class="text-end fw-bold">$<?= number_format($row['total_amount'], 2) ?></td>
                            <td class="text-center">
                                <?php if($row['status'] == 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">PAID</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">UNPAID</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($row['date_issued'])) ?></td>
                            <td class="text-end pe-3">
                                <?php if($row['status'] != 'paid' && $role != 'student'): ?>
                                    <button class="btn btn-sm btn-primary fw-bold pay-trigger" 
                                            data-id="<?= $row['invoice_id'] ?>" 
                                            data-amt="<?= $row['total_amount'] ?>">
                                        Record Payment
                                    </button>
                                <?php elseif($row['status'] == 'paid'): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <span class="text-muted small">Awaiting Payment</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No invoices found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Post Payment to Ledger</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="payment">
                    <input type="hidden" name="inv_id" id="modal_inv_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amount Received ($)</label>
                        <input type="number" name="pay_amt" id="modal_pay_amt" class="form-control form-control-lg" step="0.01" required>
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i> This will automatically generate a Journal Entry: <strong>Debit Cash / Credit AR</strong>.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Post Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.pay-trigger').click(function() {
        $('#modal_inv_id').val($(this).data('id'));
        $('#modal_pay_amt').val($(this).data('amt'));
        new bootstrap.Modal('#paymentModal').show();
    });

    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        $.post('api_handler.php', $(this).serialize(), function(res) {
            alert(res);
            location.reload();
        });
    });
});
</script>
</body>
</html>
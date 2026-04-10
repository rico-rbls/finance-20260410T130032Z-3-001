<?php 
include 'header.php'; 
include 'db_connect.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Logic: Students see only their bills; Staff see everything.
if ($role === 'student') {
    $query = "SELECT * FROM invoices WHERE student_name = (SELECT full_name FROM users WHERE user_id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM invoices ORDER BY invoice_id DESC";
    $result = $conn->query($query);
}
?>

<div class="modal fade" id="newInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Issue New Student Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createInvoiceForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_invoice">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Student Name</label>
                        <input type="text" name="student_name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Total Amount (PHP)</label>
                        <input type="number" name="total_amount" id="invoice_amount" class="form-control" step="0.01" min="0.01" required>
                        <div class="invalid-feedback">Enter an amount greater than 0.00.</div>
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
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="mb-0">Accounts Receivable Ledger</span>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="billingSearch" class="form-control form-control-sm" placeholder="Search invoice or student" style="min-width: 220px;">
                <select id="billingStatusFilter" class="form-select form-select-sm" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
                </select>
                <select id="billingSort" class="form-select form-select-sm" style="min-width: 180px;">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="amount_desc">Amount: High to Low</option>
                    <option value="amount_asc">Amount: Low to High</option>
                </select>
                <button type="button" id="billingExportBtn" class="btn btn-sm btn-outline-light fw-bold">Export CSV</button>
                <button type="button" id="billingExportExcelBtn" class="btn btn-sm btn-light fw-bold">Export Excel</button>
                <span class="badge bg-white text-success" id="billingVisibleCount"><?= $result->num_rows ?> Records</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="billingTable">
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
                        <tr class="invoice-row" data-status="<?= strtolower($row['status']) ?>" data-id="<?= $row['invoice_id'] ?>" data-amount="<?= (float) $row['total_amount'] ?>" data-date="<?= htmlspecialchars($row['date_issued']) ?>">
                            <td class="ps-3 fw-bold text-success">INV-<?= $row['invoice_id'] ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td class="text-end fw-bold"><?= formatCurrency($row['total_amount']) ?></td>
                            <td class="text-center">
                                <?php if($row['status'] == 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">PAID</span>
                                <?php elseif($row['status'] == 'partial'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info">PARTIAL</span>
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
                                    <?php if($row['status'] == 'unpaid'): ?>
                                        <button class="btn btn-sm btn-outline-danger fw-bold delete-invoice-btn" data-id="<?= $row['invoice_id'] ?>">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                <?php elseif($row['status'] == 'paid'): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <span class="text-muted small">Awaiting Payment</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No invoices found.
                                <?php if($role != 'student'): ?>
                                    <button class="btn btn-link btn-sm fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#newInvoiceModal">Create your first invoice</button>
                                <?php else: ?>
                                    <a href="dashboard.php" class="fw-bold text-decoration-none ms-1">Return to dashboard</a>
                                <?php endif; ?>
                            </td>
                        </tr>
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
                        <label class="form-label small fw-bold">Amount Received (PHP)</label>
                        <input type="number" name="pay_amt" id="modal_pay_amt" class="form-control form-control-lg" step="0.01" min="0.01" required>
                        <div class="invalid-feedback">Enter an amount greater than 0.00.</div>
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
    function applyBillingFilters() {
        const query = ($('#billingSearch').val() || '').toLowerCase().trim();
        const status = ($('#billingStatusFilter').val() || '').toLowerCase();
        const sort = ($('#billingSort').val() || 'newest').toLowerCase();
        let visible = 0;
        const rows = $('.invoice-row').get();

        rows.sort(function(a, b) {
            const aId = parseInt($(a).data('id'), 10) || 0;
            const bId = parseInt($(b).data('id'), 10) || 0;
            const aAmt = parseFloat($(a).data('amount')) || 0;
            const bAmt = parseFloat($(b).data('amount')) || 0;
            const aTime = Date.parse($(a).data('date')) || 0;
            const bTime = Date.parse($(b).data('date')) || 0;

            if (sort === 'oldest') return aTime - bTime || aId - bId;
            if (sort === 'amount_desc') return bAmt - aAmt || bId - aId;
            if (sort === 'amount_asc') return aAmt - bAmt || aId - bId;
            return bTime - aTime || bId - aId;
        });

        rows.forEach(function(row) {
            $('#billingTable tbody').append(row);
        });

        $('.invoice-row').each(function() {
            const rowText = $(this).text().toLowerCase();
            const rowStatus = ($(this).data('status') || '').toString().toLowerCase();
            const matchText = !query || rowText.indexOf(query) !== -1;
            const matchStatus = !status || rowStatus === status;
            const show = matchText && matchStatus;
            $(this).toggle(show);
            if (show) visible++;
        });

        $('#billingVisibleCount').text(visible + ' Records');
    }

    function validatePositiveAmount(inputSelector) {
        const input = $(inputSelector);
        const val = parseFloat(input.val());
        if (isNaN(val) || val <= 0) {
            input.addClass('is-invalid');
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    }

    $('#billingSearch, #billingStatusFilter, #billingSort').on('input change', applyBillingFilters);

    $('#billingExportBtn').on('click', function() {
        exportVisibleTableToCsv('billingTable', 'billing_records.csv');
    });

    $('#billingExportExcelBtn').on('click', function() {
        exportVisibleTableToXlsx('billingTable', 'billing_records.xlsx');
    });

    $('.pay-trigger').click(function() {
        $('#modal_inv_id').val($(this).data('id'));
        $('#modal_pay_amt').val($(this).data('amt'));
        $('#modal_pay_amt').removeClass('is-invalid');
        new bootstrap.Modal('#paymentModal').show();
    });

    $('#createInvoiceForm').submit(function(e) {
        e.preventDefault();
        if (!validatePositiveAmount('#invoice_amount')) return;
        $.post('api_handler.php', $(this).serialize(), function(res) {
            showAppToast(res, 'success');
            location.reload();
        });
    });

    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        if (!validatePositiveAmount('#modal_pay_amt')) return;
        $.post('api_handler.php', $(this).serialize(), function(res) {
            showAppToast(res, 'success');
            location.reload();
        });
    });

    $(document).on('click', '.delete-invoice-btn', function() {
        const id = $(this).data('id');
        if (!confirm('Delete this unpaid invoice? This will remove the invoice record and related ledger posting.')) {
            return;
        }

        $.post('api_handler.php', { action: 'delete_invoice', inv_id: id }, function(res) {
            showAppToast(res, res.toLowerCase().indexOf('success') !== -1 ? 'success' : 'error');
            location.reload();
        });
    });

    applyBillingFilters();
});
</script>
</body>
</html>
<?php 
include 'header.php'; 
include 'db_connect.php';
restrictTo(['admin', 'finance_officer']);

// Fetch Vendors and POs
$vendors = $conn->query("SELECT * FROM vendors");
$pos = $conn->query("SELECT po.*, v.vendor_name, d.dept_name 
                     FROM purchase_orders po 
                     JOIN vendors v ON po.vendor_id = v.vendor_id 
                     JOIN departments d ON po.dept_id = d.dept_id 
                     WHERE po.status != 'received' AND po.status != 'cancelled' 
                     ORDER BY po.po_id DESC");
?>

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark"><i class="bi bi-cart-plus"></i> Procurement Management</h2>
            <p class="text-muted mb-0">Generate new purchase orders and manage supply chain receipts.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
            <a href="dashboard.php" class="btn btn-secondary fw-bold shadow-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="collapse" data-bs-target="#newPOForm">
                <i class="bi bi-plus-lg"></i> Create New PO
            </button>
        </div>
    </div>

    <div class="collapse mb-4" id="newPOForm">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">New Purchase Order Details</div>
            <div class="card-body">
                <form id="createPOForm" class="row g-3" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_po">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php 
                            $depts = $conn->query("SELECT * FROM departments");
                            while($d = $depts->fetch_assoc()) echo "<option value='{$d['dept_id']}'>{$d['dept_name']}</option>"; 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Vendor / Supplier</label>
                        <select name="vendor_id" class="form-select" required>
                            <?php while($v = $vendors->fetch_assoc()) echo "<option value='{$v['vendor_id']}'>{$v['vendor_name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Total Amount (PHP)</label>
                        <input type="number" name="amount" id="po_amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text small">PDF/Images max 2MB.</div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success px-4 fw-bold">Submit for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="mb-0">Active Purchase Orders</span>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="poSearch" class="form-control form-control-sm" placeholder="Search PO, dept, vendor" style="min-width: 220px;">
                <select id="poStatusFilter" class="form-select form-select-sm" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
                <select id="poSort" class="form-select form-select-sm" style="min-width: 190px;">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="amount_desc">Amount: High to Low</option>
                    <option value="amount_asc">Amount: Low to High</option>
                    <option value="dept_asc">Department: A to Z</option>
                </select>
                <button type="button" id="poExportBtn" class="btn btn-sm btn-outline-dark fw-bold">Export CSV</button>
                <button type="button" id="poExportExcelBtn" class="btn btn-sm btn-light fw-bold">Export Excel</button>
                <span class="badge bg-dark" id="poVisibleCount"><?= $pos->num_rows ?> Orders</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="poTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">PO #</th>
                            <th>Department</th>
                            <th>Vendor</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pos->num_rows > 0): while($row = $pos->fetch_assoc()): ?>
                        <tr class="po-row" data-status="<?= strtolower($row['status']) ?>" data-id="<?= $row['po_id'] ?>" data-amount="<?= (float) $row['total_amount'] ?>" data-dept="<?= htmlspecialchars(strtolower($row['dept_name'])) ?>">
                            <td class="ps-3 fw-bold text-primary">PO-<?= $row['po_id'] ?></td>
                            <td><?= $row['dept_name'] ?></td>
                            <td><?= $row['vendor_name'] ?></td>
                            <td class="text-end fw-bold"><?= formatCurrency($row['total_amount']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $row['status'] == 'approved' ? 'bg-info' : ($row['status'] == 'received' ? 'bg-success' : 'bg-secondary') ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <?php if($row['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-warning fw-bold approve-po-btn me-1" data-id="<?= $row['po_id'] ?>">
                                        Approve
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger fw-bold cancel-po-btn" data-id="<?= $row['po_id'] ?>">
                                        Cancel
                                    </button>
                                <?php elseif($row['status'] == 'approved'): ?>
                                    <button class="btn btn-sm btn-success fw-bold receive-btn" data-id="<?= $row['po_id'] ?>">
                                        Mark Received
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">Awaiting Approval</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No active purchase orders found.
                                <button class="btn btn-link btn-sm fw-bold text-decoration-none" data-bs-toggle="collapse" data-bs-target="#newPOForm">Create your first PO</button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    function applyPoFilters() {
        const query = ($('#poSearch').val() || '').toLowerCase().trim();
        const status = ($('#poStatusFilter').val() || '').toLowerCase();
        const sort = ($('#poSort').val() || 'newest').toLowerCase();
        let visible = 0;
        const rows = $('.po-row').get();

        rows.sort(function(a, b) {
            const aId = parseInt($(a).data('id'), 10) || 0;
            const bId = parseInt($(b).data('id'), 10) || 0;
            const aAmt = parseFloat($(a).data('amount')) || 0;
            const bAmt = parseFloat($(b).data('amount')) || 0;
            const aDept = (($(a).data('dept') || '') + '').toLowerCase();
            const bDept = (($(b).data('dept') || '') + '').toLowerCase();

            if (sort === 'oldest') return aId - bId;
            if (sort === 'amount_desc') return bAmt - aAmt || bId - aId;
            if (sort === 'amount_asc') return aAmt - bAmt || aId - bId;
            if (sort === 'dept_asc') return aDept.localeCompare(bDept) || bId - aId;
            return bId - aId;
        });

        rows.forEach(function(row) {
            $('#poTable tbody').append(row);
        });

        $('.po-row').each(function() {
            const rowText = $(this).text().toLowerCase();
            const rowStatus = ($(this).data('status') || '').toString().toLowerCase();
            const matchText = !query || rowText.indexOf(query) !== -1;
            const matchStatus = !status || rowStatus === status;
            const show = matchText && matchStatus;
            $(this).toggle(show);
            if (show) visible++;
        });

        $('#poVisibleCount').text(visible + ' Orders');
    }

    function validatePoAmount() {
        const input = $('#po_amount');
        const value = parseFloat(input.val());
        if (isNaN(value) || value <= 0) {
            input.addClass('is-invalid');
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    }

    $('#poSearch, #poStatusFilter, #poSort').on('input change', applyPoFilters);

    $('#poExportBtn').on('click', function() {
        exportVisibleTableToCsv('poTable', 'purchase_orders.csv');
    });

    $('#poExportExcelBtn').on('click', function() {
        exportVisibleTableToXlsx('poTable', 'purchase_orders.xlsx');
    });

    // Submit New PO
    $('#createPOForm').on('submit', function(e) {
        e.preventDefault();
        if (!validatePoAmount()) return;
        
        let formData = new FormData(this);
        $.ajax({
            url: 'api_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.toLowerCase().indexOf('created') !== -1 || response.toLowerCase().indexOf('success') !== -1) {
                    showAppToast(response, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAppToast(response, 'error');
                }
            },
            error: function() {
                showAppToast('Network error while creating PO.', 'error');
            }
        });
    });

    $(document).on('click', '.approve-po-btn', function() {
        let poId = $(this).data('id');
        if(confirm('Approve this purchase order?')) {
            $.post('api_handler.php', {action: 'approve_po', po_id: poId}, function(response) {
                showAppToast(response, 'info');
                location.reload();
            });
        }
    });

    $(document).on('click', '.cancel-po-btn', function() {
        let poId = $(this).data('id');
        if(confirm('Cancel this pending purchase order? The linked budget reservation will also be cancelled.')) {
            $.post('api_handler.php', {action: 'cancel_po', po_id: poId}, function(response) {
                showAppToast(response, response.toLowerCase().indexOf('cancelled') !== -1 ? 'success' : 'error');
                location.reload();
            });
        }
    });

    // Mark as Received
    $(document).on('click', '.receive-btn', function() {
        let poId = $(this).data('id');
        if(confirm("Confirm receipt of goods? This will update the General Ledger and hit the department budget.")) {
            $.post('api_handler.php', {action: 'receive_po', po_id: poId}, function(response) {
                showAppToast(response, 'success');
                location.reload();
            });
        }
    });

    applyPoFilters();
});
</script>
</body>
</html>
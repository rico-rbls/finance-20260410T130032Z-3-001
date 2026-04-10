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
                <form id="createPOForm" class="row g-3">
                    <input type="hidden" name="action" value="create_po">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Department</label>
                        <select name="dept_id" class="form-select" required>
                            <?php 
                            $depts = $conn->query("SELECT * FROM departments");
                            while($d = $depts->fetch_assoc()) echo "<option value='{$d['dept_id']}'>{$d['dept_name']}</option>"; 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Vendor / Supplier</label>
                        <select name="vendor_id" class="form-select" required>
                            <?php while($v = $vendors->fetch_assoc()) echo "<option value='{$v['vendor_id']}'>{$v['vendor_name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Total Amount ($)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success px-4 fw-bold">Submit for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <span class="mb-0">Active Purchase Orders</span>
            <span class="badge bg-dark"><?= $pos->num_rows ?> Orders</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        <tr>
                            <td class="ps-3 fw-bold text-primary">PO-<?= $row['po_id'] ?></td>
                            <td><?= $row['dept_name'] ?></td>
                            <td><?= $row['vendor_name'] ?></td>
                            <td class="text-end fw-bold">$<?= number_format($row['total_amount'], 2) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $row['status'] == 'approved' ? 'bg-info' : 'bg-secondary' ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <?php if($row['status'] == 'approved'): ?>
                                    <button class="btn btn-sm btn-success fw-bold receive-btn" data-id="<?= $row['po_id'] ?>">
                                        Mark Received
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">Awaiting Approval</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No active purchase orders found.</td></tr>
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
    // Submit New PO
    $('#createPOForm').on('submit', function(e) {
        e.preventDefault();
        $.post('api_handler.php', $(this).serialize(), function(response) {
            alert(response);
            location.reload();
        });
    });

    // Mark as Received
    $(document).on('click', '.receive-btn', function() {
        let poId = $(this).data('id');
        if(confirm("Confirm receipt of goods? This will update the General Ledger and hit the department budget.")) {
            $.post('api_handler.php', {action: 'receive_po', po_id: poId}, function(response) {
                alert(response);
                location.reload();
            });
        }
    });
});
</script>
</body>
</html>
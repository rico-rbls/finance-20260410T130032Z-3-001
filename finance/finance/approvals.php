<?php 
include 'header.php'; 
include 'db_connect.php';

// Only Admins and Finance Officers should access this
restrictTo(['admin', 'finance_officer']);

// Fetch only pending reservations
$query = "SELECT r.*, d.dept_name 
          FROM budget_reservations r 
          JOIN departments d ON r.dept_id = d.dept_id 
          WHERE r.status = 'pending' 
          ORDER BY r.res_id ASC";
$pending = $conn->query($query);
?>

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark"><i class="bi bi-shield-check text-warning"></i> Pending Approvals</h2>
            <p class="text-muted mb-0">Review and authorize departmental fund encumbrances.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <a href="dashboard.php" class="btn btn-secondary fw-bold shadow-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Awaiting Authorization</h5>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="approvalSearch" class="form-control form-control-sm" placeholder="Search ID, dept, purpose" style="min-width: 220px;">
                <select id="approvalStatusFilter" class="form-select form-select-sm" style="min-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                </select>
                <select id="approvalSort" class="form-select form-select-sm" style="min-width: 190px;">
                    <option value="oldest">Oldest First</option>
                    <option value="newest">Newest First</option>
                    <option value="amount_desc">Amount: High to Low</option>
                    <option value="amount_asc">Amount: Low to High</option>
                    <option value="dept_asc">Department: A to Z</option>
                </select>
                <button type="button" id="approvalExportBtn" class="btn btn-sm btn-outline-light fw-bold">Export CSV</button>
                <button type="button" id="approvalExportExcelBtn" class="btn btn-sm btn-light fw-bold">Export Excel</button>
                <span class="badge bg-warning text-dark" id="approvalVisibleCount"><?= $pending->num_rows ?> Requests</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="approvalTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending->num_rows > 0): while($row = $pending->fetch_assoc()): ?>
                        <tr class="approval-row" data-status="<?= strtolower($row['status']) ?>" data-id="<?= $row['res_id'] ?>" data-amount="<?= (float) $row['amount_reserved'] ?>" data-dept="<?= htmlspecialchars(strtolower($row['dept_name'])) ?>" id="row-<?= $row['res_id'] ?>">
                            <td class="ps-3 text-muted">#<?= $row['res_id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['dept_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="fw-bold text-primary"><?= formatCurrency($row['amount_reserved']) ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-success btn-sm fw-bold shadow-sm approve-btn d-flex align-items-center justify-content-center w-50" data-id="<?= $row['res_id'] ?>" title="Approve Request" style="max-width: 100px;">
                                        <i class="bi bi-check-lg me-1"></i> Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm fw-bold shadow-sm reject-btn d-flex align-items-center justify-content-center w-50" data-id="<?= $row['res_id'] ?>" title="Reject Request" style="max-width: 100px;">
                                        <i class="bi bi-x-lg me-1"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="mt-2 text-muted mb-1">No pending reservations found.</p>
                                    <a href="budget.php" class="fw-bold text-decoration-none">Go to Budget Reservations</a>
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
<script>
function applyApprovalFilters() {
    const query = ($('#approvalSearch').val() || '').toLowerCase().trim();
    const status = ($('#approvalStatusFilter').val() || '').toLowerCase();
    const sort = ($('#approvalSort').val() || 'oldest').toLowerCase();
    let visible = 0;
    const rows = $('.approval-row').get();

    rows.sort(function(a, b) {
        const aId = parseInt($(a).data('id'), 10) || 0;
        const bId = parseInt($(b).data('id'), 10) || 0;
        const aAmt = parseFloat($(a).data('amount')) || 0;
        const bAmt = parseFloat($(b).data('amount')) || 0;
        const aDept = (($(a).data('dept') || '') + '').toLowerCase();
        const bDept = (($(b).data('dept') || '') + '').toLowerCase();

        if (sort === 'newest') return bId - aId;
        if (sort === 'amount_desc') return bAmt - aAmt || aId - bId;
        if (sort === 'amount_asc') return aAmt - bAmt || aId - bId;
        if (sort === 'dept_asc') return aDept.localeCompare(bDept) || aId - bId;
        return aId - bId;
    });

    rows.forEach(function(row) {
        $('#approvalTable tbody').append(row);
    });

    $('.approval-row').each(function() {
        const rowText = $(this).text().toLowerCase();
        const rowStatus = ($(this).data('status') || '').toString().toLowerCase();
        const matchText = !query || rowText.indexOf(query) !== -1;
        const matchStatus = !status || rowStatus === status;
        const show = matchText && matchStatus;
        $(this).toggle(show);
        if (show) visible++;
    });

    $('#approvalVisibleCount').text(visible + ' Requests');
}

$('#approvalSearch, #approvalStatusFilter, #approvalSort').on('input change', applyApprovalFilters);

$('#approvalExportBtn').on('click', function() {
    exportVisibleTableToCsv('approvalTable', 'pending_approvals.csv');
});

$('#approvalExportExcelBtn').on('click', function() {
    exportVisibleTableToXlsx('approvalTable', 'pending_approvals.xlsx');
});

$('.approve-btn').click(function() {
    const id = $(this).data('id');
    const btn = $(this);
    
    if(confirm('Authorize this expenditure?')) {
        btn.prop('disabled', true).html('Processing...');
        
        $.post('api_handler.php', { action: 'approve_reservation', res_id: id }, function(response) {
            if(response.trim() === "success") {
                $('#row-' + id).fadeOut();
                showAppToast('Reservation approved successfully.', 'success');
            } else {
                showAppToast('Error: ' + response, 'error');
                btn.prop('disabled', false).html('Approve');
            }
        });
    }
});

$('.reject-btn').click(function() {
    const id = $(this).data('id');
    const btn = $(this);
    
    if(confirm('Reject this reservation?')) {
        btn.prop('disabled', true).html('Processing...');
        
        $.post('api_handler.php', { action: 'reject_reservation', res_id: id }, function(response) {
            if(response.trim() === "success") {
                $('#row-' + id).fadeOut();
                showAppToast('Reservation rejected.', 'info');
            } else {
                showAppToast('Error: ' + response, 'error');
                btn.prop('disabled', false).html('Reject');
            }
        });
    }
});

applyApprovalFilters();
</script>
<?php 
include 'header.php'; 
include 'db_connect.php';
include 'finance_logic.php';

// Restricted to roles that manage budgets
restrictTo(['admin', 'finance_officer', 'dept_head']);

// Fetch active departments for the dropdown
$depts = $conn->query("SELECT * FROM departments ORDER BY dept_name ASC");

// Fetch recent reservations for the table
$reservations = $conn->query("SELECT r.*, d.dept_name 
                              FROM budget_reservations r 
                              JOIN departments d ON r.dept_id = d.dept_id 
                              ORDER BY r.res_id DESC LIMIT 10");

// Fetch Quick Stats
$total_reserved = $conn->query("SELECT SUM(amount_reserved) as total FROM budget_reservations")->fetch_assoc()['total'] ?? 0;
$pending_count = $conn->query("SELECT COUNT(*) as total FROM budget_reservations WHERE status='pending'")->fetch_assoc()['total'];
?>

<div class="container py-4">
    <div class="row align-items-center mb-5">
        <div class="col-md-7">
            <h1 class="display-6 fw-bold text-dark mb-1">Budget Reservations</h1>
            <p class="text-muted mb-0">Commit departmental funds and track institutional encumbrances.</p>
        </div>
        <div class="col-md-5 text-md-end mt-4 mt-md-0">
            <div class="d-inline-flex align-items-center bg-white p-3 rounded-4 shadow-sm border border-light">
                <div class="rounded-circle bg-primary-subtle p-3 me-3 text-primary">
                    <i class="bi bi-piggy-bank fs-4"></i>
                </div>
                <div class="text-start">
                    <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total Reserved</div>
                    <div class="h4 fw-bold mb-0 text-primary"><?= formatCurrency($total_reserved) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg p-3" id="reservation-form-card" style="border-radius: 24px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-4 text-dark">New Fund Reservation</h5>
                    
                    <form id="resForm">
                        <input type="hidden" name="action" value="reserve">
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">DEPARTMENT</label>
                            <select name="dept_id" class="form-select border-0 bg-light p-3 rounded-3" required>
                                <option value="" selected disabled>Select Department...</option>
                                <?php while($d = $depts->fetch_assoc()): ?>
                                    <option value="<?= $d['dept_id'] ?>"><?= $d['dept_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">RESERVATION AMOUNT</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light p-3">PHP</span>
                                <input type="number" name="amount" class="form-control border-0 bg-light p-3 rounded-end-3" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">PURPOSE / DESCRIPTION</label>
                            <textarea name="desc" class="form-control border-0 bg-light p-3 rounded-3" rows="3" placeholder="e.g. Q3 Laboratory Supplies" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm">
                            <i class="bi bi-plus-circle me-2"></i> Commit Funds
                        </button>
                    </form>
                    <div id="resResult" class="mt-3"></div>
                </div>
            </div>
            
            <div class="mt-4 p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-10">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill text-primary mt-1 me-3"></i>
                    <p class="small text-muted mb-0">
                        Submitting this form creates an <strong>Encumbrance</strong>. Funds will be locked until the final invoice is paid and the request is fully cleared.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="fw-bold mb-0">Activity Ledger</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="text" id="budgetSearch" class="form-control form-control-sm border-0 bg-white shadow-sm p-2 px-3 rounded-pill" placeholder="Search..." style="width: 180px;">
                    <select id="budgetStatusFilter" class="form-select form-select-sm border-0 bg-white shadow-sm p-2 rounded-pill" style="width: 120px;">
                        <option value="">Status</option>
                        <option value="pending">Pending</option>
                        <option value="committed">Committed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button type="button" id="budgetExportExcelBtn" class="btn btn-sm btn-white bg-white shadow-sm border p-2 px-3 rounded-pill fw-bold text-muted hov-primary"><i class="bi bi-file-earmark-excel me-1 text-success"></i> Excel</button>
                    <button type="button" id="budgetExportBtn" class="btn btn-sm btn-white bg-white shadow-sm border p-2 px-3 rounded-pill fw-bold text-muted hov-primary"><i class="bi bi-file-earmark-text me-1 text-primary"></i> CSV</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle" id="budgetTable">
                    <thead>
                        <tr>
                            <th>REF ID</th>
                            <th>DEPARTMENT</th>
                            <th>DESCRIPTION</th>
                            <th class="text-end">AMOUNT</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-end">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($reservations->num_rows > 0): while($row = $reservations->fetch_assoc()): ?>
                        <tr class="budget-row" data-id="<?= $row['res_id'] ?>" data-status="<?= strtolower($row['status']) ?>" data-amount="<?= (float) $row['amount_reserved'] ?>" id="res-row-<?= $row['res_id'] ?>">
                            <td class="small text-muted fw-bold">#<?= $row['res_id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= $row['dept_name'] ?></div>
                            </td>
                            <td style="max-width: 200px;" class="text-truncate" title="<?= htmlspecialchars($row['description']) ?>">
                                <?= htmlspecialchars($row['description']) ?>
                            </td>
                            <td class="text-end fw-bold text-dark"><?= formatCurrency($row['amount_reserved']) ?></td>
                            <td class="text-center">
                                <?php if($row['status'] == 'pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning">PENDING</span>
                                <?php elseif($row['status'] == 'cancelled'): ?>
                                    <span class="badge bg-danger-subtle text-danger">CANCELLED</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">COMMITTED</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if($row['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-light text-danger rounded-3 cancel-reservation-btn border" data-id="<?= $row['res_id'] ?>" title="Cancel Reservation">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <i class="bi bi-lock-fill text-muted opacity-50"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            No reservations recorded yet.
                                            <a href="#reservation-form-card" class="fw-bold text-decoration-none ms-1">Create your first reservation</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-center py-3">
                    <a href="reports.php" class="small text-decoration-none fw-bold">View Full Budget Report <i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function applyBudgetFilters() {
        const query = ($('#budgetSearch').val() || '').toLowerCase().trim();
        const status = ($('#budgetStatusFilter').val() || '').toLowerCase();
        const sort = ($('#budgetSort').val() || 'newest').toLowerCase();
        let visible = 0;
        let pending = 0;

        const rows = $('.budget-row').get();
        rows.sort(function(a, b) {
            const aId = parseInt($(a).data('id'), 10) || 0;
            const bId = parseInt($(b).data('id'), 10) || 0;
            const aAmt = parseFloat($(a).data('amount')) || 0;
            const bAmt = parseFloat($(b).data('amount')) || 0;

            if (sort === 'oldest') return aId - bId;
            if (sort === 'amount_desc') return bAmt - aAmt || bId - aId;
            if (sort === 'amount_asc') return aAmt - bAmt || aId - bId;
            return bId - aId;
        });

        rows.forEach(function(row) {
            $('#budgetTable tbody').append(row);
        });

        $('.budget-row').each(function() {
            const rowText = $(this).text().toLowerCase();
            const rowStatus = ($(this).data('status') || '').toString().toLowerCase();
            const matchText = !query || rowText.indexOf(query) !== -1;
            const matchStatus = !status || rowStatus === status;
            const show = matchText && matchStatus;
            $(this).toggle(show);
            if (show) visible++;
            if (show && rowStatus === 'pending') pending++;
        });

        $('#budgetVisibleCount').text(visible + ' Visible | ' + pending + ' Pending');
    }

    $('#resForm').submit(function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.post('api_handler.php', $(this).serialize(), function(data) {
            showAppToast(data, 'success');
            $('#resResult').html('<div class="alert alert-success small shadow-sm py-2"><strong>Success!</strong> ' + data + '</div>');
            setTimeout(() => { location.reload(); }, 1500);
        });
    });

    $(document).on('click', '.cancel-reservation-btn', function() {
        const id = $(this).data('id');
        if (!confirm('Cancel this pending reservation?')) return;

        $.post('api_handler.php', { action: 'cancel_reservation', res_id: id }, function(res) {
            showAppToast(res, res.toLowerCase().indexOf('cancelled') !== -1 ? 'success' : 'error');
            location.reload();
        });
    });

    $('#budgetSearch, #budgetStatusFilter, #budgetSort').on('input change', applyBudgetFilters);

    $('#budgetExportBtn').on('click', function() {
        exportVisibleTableToCsv('budgetTable', 'budget_reservations.csv');
    });

    $('#budgetExportExcelBtn').on('click', function() {
        exportVisibleTableToXlsx('budgetTable', 'budget_reservations.xlsx');
    });

    applyBudgetFilters();
</script>

</body>
</html>
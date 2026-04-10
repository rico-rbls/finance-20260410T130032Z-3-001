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
        <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0">Awaiting Authorization</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        <tr id="row-<?= $row['res_id'] ?>">
                            <td class="ps-3 text-muted">#<?= $row['res_id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['dept_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td class="fw-bold text-primary">$<?= number_format($row['amount_reserved'], 2) ?></td>
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
                                    <p class="mt-2 text-muted">No pending reservations found.</p>
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
$('.approve-btn').click(function() {
    const id = $(this).data('id');
    const btn = $(this);
    
    if(confirm('Authorize this expenditure?')) {
        btn.prop('disabled', true).html('Processing...');
        
        $.post('api_handler.php', { action: 'approve_reservation', res_id: id }, function(response) {
            if(response.trim() === "success") {
                $('#row-' + id).fadeOut();
            } else {
                alert("Error: " + response);
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
            } else {
                alert("Error: " + response);
                btn.prop('disabled', false).html('Reject');
            }
        });
    }
});
</script>
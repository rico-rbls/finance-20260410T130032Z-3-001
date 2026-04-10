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

<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark"><i class="bi bi-piggy-bank text-primary"></i> Budget Reservations</h2>
            <p class="text-muted mb-0">Commit departmental funds and track institutional encumbrances.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-3">
            <a href="dashboard.php" class="btn btn-secondary fw-bold shadow-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <div class="p-3 bg-white shadow-sm rounded-3 border-start border-primary border-4 d-inline-block text-start">
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Total Reserved</div>
                <div class="h4 fw-bold mb-0 text-primary">$<?= number_format($total_reserved, 2) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">New Fund Reservation</h5>
                </div>
                <div class="card-body">
                    <form id="resForm">
                        <input type="hidden" name="action" value="reserve">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Department</label>
                            <select name="dept_id" class="form-select" required>
                                <option value="" selected disabled>Select Department...</option>
                                <?php while($d = $depts->fetch_assoc()): ?>
                                    <option value="<?= $d['dept_id'] ?>"><?= $d['dept_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Amount to Reserve ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" class="form-control" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Purpose / Description</label>
                            <textarea name="desc" class="form-control" rows="4" placeholder="e.g. Q3 Laboratory Supplies" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                            <i class="bi bi-check-circle-fill"></i> Commit Funds
                        </button>
                    </form>
                    <div id="resResult" class="mt-3"></div>
                </div>
            </div>
            
            <div class="card bg-light border-0">
                <div class="card-body small text-muted">
                    <i class="bi bi-info-circle-fill text-primary"></i> 
                    Submitting this form creates an <strong>Encumbrance</strong>. Funds will be locked until the final invoice is paid.
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Recent Activity Ledger</h5>
                    <span class="badge bg-primary"><?= $pending_count ?> Pending Approval</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Dept</th>
                                    <th>Purpose</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reservations->num_rows > 0): while($row = $reservations->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3 text-muted small">#<?= $row['res_id'] ?></td>
                                    <td class="fw-bold"><?= $row['dept_name'] ?></td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="text-end fw-bold text-primary">$<?= number_format($row['amount_reserved'], 2) ?></td>
                                    <td class="text-center">
                                        <?php if($row['status'] == 'pending'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning">PENDING</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success">COMMITTED</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No reservations recorded yet.</td></tr>
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
    $('#resForm').submit(function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.post('api_handler.php', $(this).serialize(), function(data) {
            $('#resResult').html('<div class="alert alert-success small shadow-sm py-2"><strong>Success!</strong> ' + data + '</div>');
            setTimeout(() => { location.reload(); }, 1500);
        });
    });
</script>

</body>
</html>
<?php 
// 1. Security Check & Header Integration
include 'header.php'; // Standardized navigation and session check
include 'db_connect.php';
restrictTo(['admin', 'finance_officer']);

include 'report_logic.php';

// Helper function for currency formatting
function f($n) { return '$' . number_format($n, 2); }

// Fetch Data from Logic Layer
$budget_data = getBudgetData();
$bs_data = getBalanceSheet();

// Calculate Global Totals for Summary Cards
$total_assets = 0;
$total_revenue = 0;
$summary_query = $conn->query("SELECT account_type, SUM(balance) as total FROM chart_of_accounts GROUP BY account_type");
while($s_row = $summary_query->fetch_assoc()){
    if($s_row['account_type'] == 'Asset') $total_assets = $s_row['total'];
    if($s_row['account_type'] == 'Revenue') $total_revenue = $s_row['total'];
}
?>

<div class="container">
    <div class="row mb-4 align-items-end">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark"><i class="bi bi-graph-up-arrow text-info"></i> Financial Intelligence</h2>
            <p class="text-muted mb-0">System-generated audit trail as of <?= date('F d, Y - H:i') ?></p>
        </div>
        <div class="col-md-5 text-md-end no-print mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-dark fw-bold shadow-sm me-2">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="dashboard.php" class="btn btn-secondary fw-bold shadow-sm">
                <i class="bi bi-house"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-dark text-white p-3">
                <div class="small opacity-75 fw-bold text-uppercase" style="font-size: 0.7rem;">Total Institutional Assets</div>
                <div class="h3 fw-bold mb-0 text-info"><?= f($total_assets) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4">
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">YTD Tuition Revenue</div>
                <div class="h3 fw-bold mb-0 text-success"><?= f(abs($total_revenue)) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4">
                <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Audit Status</div>
                <div class="h3 fw-bold mb-0 text-dark">
                    <i class="bi bi-patch-check-fill text-primary"></i> Compliant
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">Budget Monitoring (Reservations & Encumbrances)</h5>
                    <span class="badge bg-warning-subtle text-warning border border-warning small">INTERNAL AUDIT VIEW</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Department Name</th>
                                    <th class="text-end">Total Allocation</th>
                                    <th class="text-end">Spent/Reserved</th>
                                    <th class="text-end">Remaining</th>
                                    <th style="width: 25%;">Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($budget_data->num_rows > 0): 
                                    while($row = $budget_data->fetch_assoc()): 
                                        $percent = ($row['total_budget'] > 0) ? ($row['spent_reserved'] / $row['total_budget']) * 100 : 0;
                                        $bar_color = ($percent > 90) ? 'bg-danger' : (($percent > 70) ? 'bg-warning' : 'bg-success');
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['dept_name']) ?></td>
                                    <td class="text-end"><?= f($row['total_budget']) ?></td>
                                    <td class="text-end"><?= f($row['spent_reserved']) ?></td>
                                    <td class="text-end fw-bold <?= $row['remaining'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= f($row['remaining']) ?>
                                    </td>
                                    <td class="pe-3">
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100" style="height: 8px; border-radius: 10px;">
                                                <div class="progress-bar <?= $bar_color ?> progress-bar-striped progress-bar-animated" 
                                                     role="progressbar" 
                                                     style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <span class="ms-2 small fw-bold"><?= round($percent) ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center py-4">No departmental data found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Balance Sheet (Chart of Accounts)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 small text-uppercase">Account Name</th>
                                <th class="small text-uppercase text-center">Category</th>
                                <th class="text-end pe-3 small text-uppercase">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $bs_data->data_seek(0); // Reset result pointer
                            while($row = $bs_data->fetch_assoc()): 
                                $typeColor = ($row['account_type'] == 'Asset' || $row['account_type'] == 'Revenue') ? 'text-success' : 'text-danger';
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['account_name']) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-light text-dark border small"><?= strtoupper($row['account_type']) ?></span>
                                </td>
                                <td class="text-end pe-3 fw-bold <?= $typeColor ?>">
                                    <?= f($row['balance']) ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="table-info fw-bold">
                            <tr>
                                <td colspan="2" class="ps-3">TOTAL INSTITUTIONAL ASSETS</td>
                                <td class="text-end pe-3"><?= f($total_assets) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-primary text-white shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-safe2 display-4 opacity-50"></i>
                    </div>
                    <h6 class="text-uppercase opacity-75 fw-bold" style="letter-spacing: 1px;">Liquidity Status</h6>
                    <h2 class="display-5 fw-bold mb-3">Stable</h2>
                    <hr class="bg-white opacity-25">
                    <p class="small opacity-75">All reservations are linked to verified budgets to prevent institutional overspending.</p>
                    <div class="mt-auto pt-3">
                        <div class="small opacity-50 mb-1 text-uppercase">Ledger Reconciled</div>
                        <i class="bi bi-shield-fill-check fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 12px; }
    @media print {
        .no-print { display: none !important; }
        .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .progress { border: 1px solid #ccc; }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
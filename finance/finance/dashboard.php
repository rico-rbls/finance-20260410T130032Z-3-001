<?php 
include 'header.php'; 
include 'db_connect.php';

$role = $_SESSION['role'] ?? 'guest';
$user_full_name = $_SESSION['full_name'] ?? 'Authorized User';

// Quick Stats for the Header Cards
$unpaid_inv = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE status='unpaid'")->fetch_assoc()['total'] ?? 0;
$pending_pos = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$low_budgets = $conn->query("SELECT COUNT(*) as total FROM departments WHERE total_budget < 5000")->fetch_assoc()['total'] ?? 0;

$budget_labels = [];
$budget_values = [];
$budget_colors = [];

// Distinct Color Palette for the 5 Colleges
$color_map = [
    'Computing Science' => '#e11d48', // CCSE - Modern Red
    'Tourism'           => '#d97706', // CTHM - Amber/Orange
    'Human Kinetics'    => '#1d4ed8', // CHK - Royal Blue
    'Teacher Education' => '#9333ea', // CTE - Purple
    'Business'          => '#059669'  // CBA - Emerald
];

$dept_query = $conn->query("SELECT d.dept_name, d.total_budget, 
                            IFNULL(SUM(r.amount_reserved), 0) as spent_reserved
                            FROM departments d
                            LEFT JOIN budget_reservations r ON d.dept_id = r.dept_id AND r.status != 'cancelled'
                            GROUP BY d.dept_id LIMIT 6");
while ($drow = $dept_query->fetch_assoc()) {
    $name = $drow['dept_name'];
    $budget_labels[] = $name;
    $budget_values[] = (float)$drow['spent_reserved'];
    
    // Auto-match color or fallback to primary green
    $matched = false;
    foreach($color_map as $key => $color) {
        if (stripos($name, $key) !== false) {
            $budget_colors[] = $color;
            $matched = true;
            break;
        }
    }
    if(!$matched) $budget_colors[] = '#1e6b3e'; 
}

// Analytics Data: Weekly Cash Flow (Last 7 Days)
$cash_activity = [];
$cash_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $cash_labels[] = date('D', strtotime($d));
    
    // Get total transactions for this day via ledger
    $day_q = $conn->prepare("SELECT COUNT(*) as count FROM journal_entries WHERE DATE(transaction_date) = ?");
    $day_q->bind_param("s", $d);
    $day_q->execute();
    $cash_activity[] = (int)($day_q->get_result()->fetch_assoc()['count'] ?? 0);
}

// Stats for Activity Monitor
$recent_approvals_count = $conn->query("SELECT COUNT(*) as total FROM budget_reservations WHERE status='committed'")->fetch_assoc()['total'] ?? 0;

$weekly_revenue = $conn->query("SELECT SUM(credit - debit) as total FROM ledger_details ld 
                                JOIN journal_entries je ON ld.entry_id = je.entry_id 
                                WHERE ld.account_id = 6 AND je.transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;

// NEW: Count for Budget Approvals
$pending_approvals = $conn->query("SELECT COUNT(*) as total FROM budget_reservations WHERE status='pending'")->fetch_assoc()['total'] ?? 0;

$quick_actions = [];
if ($role === 'admin' || $role === 'finance_officer') {
    $quick_actions = [
        ['label' => 'Review Approvals', 'link' => 'approvals.php', 'icon' => 'bi-shield-check', 'style' => 'btn-primary'],
        ['label' => 'Create Student Invoice', 'link' => 'billing_view.php', 'icon' => 'bi-receipt-cutoff', 'style' => 'btn-success'],
        ['label' => 'Create Purchase Order', 'link' => 'procurement.php', 'icon' => 'bi-truck', 'style' => 'btn-warning']
    ];
} elseif ($role === 'dept_head') {
    $quick_actions = [
        ['label' => 'Reserve Department Funds', 'link' => 'budget.php', 'icon' => 'bi-piggy-bank', 'style' => 'btn-primary'],
        ['label' => 'View Budget Report', 'link' => 'reports.php', 'icon' => 'bi-bar-chart-line', 'style' => 'btn-outline-secondary']
    ];
} elseif ($role === 'student') {
    $quick_actions = [
        ['label' => 'View My Billing', 'link' => 'billing_view.php', 'icon' => 'bi-wallet2', 'style' => 'btn-success']
    ];
}
?>

<div class="container py-4">
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h1 class="display-5 fw-bold text-dark mb-1">System Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-bold text-primary"><?= htmlspecialchars($user_full_name) ?></span>. Here is your institutional overview for <span class="text-dark fw-semibold">FY <?= date('Y') ?></span>.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex gap-2 p-2 bg-white rounded-4 shadow-sm align-items-center">
                <div class="bg-success rounded-circle" style="width: 10px; height: 10px;"></div>
                <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.65rem;">System Operational</span>
            </div>
        </div>
    </div>

    <?php if(in_array($role, ['admin', 'finance_officer'])): ?>
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card h-100 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-primary-subtle p-3 me-3 text-primary">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Approvals</div>
                            <h3 class="mb-0 fw-bold"><?= $pending_approvals ?></h3>
                        </div>
                    </div>
                    <a href="approvals.php" class="stretched-link text-decoration-none small text-primary fw-bold">Review Now <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-warning-subtle p-3 me-3 text-warning">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Orders</div>
                            <h3 class="mb-0 fw-bold"><?= $pending_pos ?></h3>
                        </div>
                    </div>
                    <a href="procurement.php" class="stretched-link text-decoration-none small text-warning fw-bold">View Queue <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-success-subtle p-3 me-3 text-success">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Billed</div>
                            <h3 class="mb-0 fw-bold"><?= $unpaid_inv ?></h3>
                        </div>
                    </div>
                    <a href="billing_view.php" class="stretched-link text-decoration-none small text-success fw-bold">Check Status <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 overflow-hidden shadow-none border-danger border-opacity-25" style="background-color: #fffafb;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-danger-subtle p-3 me-3 text-danger">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Low Funds</div>
                            <h3 class="mb-0 fw-bold"><?= $low_budgets ?></h3>
                        </div>
                    </div>
                    <a href="budget.php" class="stretched-link text-decoration-none small text-danger fw-bold">Top Up <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <!-- Quick Actions Card -->
        <div class="col-lg-8">
            <div class="card border-0 p-4 h-100">
                <div class="card-header border-0 bg-transparent px-0 pt-0 mb-4">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                    <p class="text-muted small">Standardized workflows based on your role.</p>
                </div>
                <div class="row g-3">
                    <?php foreach ($quick_actions as $qa): ?>
                    <div class="col-md-4">
                        <div class="card text-center border p-3 bg-light bg-opacity-10 h-100" style="border-style: dashed !important;">
                            <div class="mb-3">
                                <div class="bg-white rounded-3 p-3 shadow-sm d-inline-block">
                                    <i class="bi <?= $qa['icon'] ?> fs-3 text-primary"></i>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2"><?= $qa['label'] ?></h6>
                            <a href="<?= $qa['link'] ?>" class="btn btn-primary btn-sm rounded-3">Execute</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 p-4 h-100" style="background: linear-gradient(180deg, #1e6b3e 0%, #14492a 100%); color: white;">
                <h5 class="fw-bold mb-4 mt-2">Activity Monitor</h5>
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-2 px-3 me-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="small fw-semibold opacity-75">Recent Approvals</div>
                            <div class="fw-bold"><?= $recent_approvals_count ?> Successfully Processed</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-2 px-3 me-3">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <div class="small fw-semibold opacity-75">Weekly Revenue</div>
                            <div class="fw-bold"><?= formatCurrency(abs($weekly_revenue)) ?></div>
                        </div>
                    </div>
                    <!-- Mini Sparkline in the Sidebar -->
                    <div class="mt-2" style="height: 60px;">
                         <canvas id="miniSparkline"></canvas>
                    </div>
                    <div class="mt-4 pt-4 border-top border-white border-opacity-10 text-center">
                        <p class="small opacity-75 mb-3">All financial reports are up to date and indexed.</p>
                        <a href="reports.php" class="btn btn-outline-light w-100 border-0 bg-white bg-opacity-10">
                            Comprehensive Reports <i class="bi bi-chevron-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard Row -->
    <?php if(in_array($role, ['admin', 'finance_officer'])): ?>
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Financial Performance Overview</h6>
                    <span class="badge bg-light text-muted fw-normal small">Live System Data</span>
                </div>
                <div class="card-body" style="min-height: 350px;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-success me-2"></i> Budget Utilization</h6>
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="budgetPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Init Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Main Performance Chart (Bar)
            new Chart(document.getElementById('performanceChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($budget_labels) ?>,
                    datasets: [{
                        label: 'Utilized Budget (PHP)',
                        data: <?= json_encode($budget_values) ?>,
                        backgroundColor: <?= json_encode($budget_colors) ?>,
                        borderRadius: 8,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { callback: v => '₱' + v.toLocaleString() } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Budget Utilization (Doughnut)
            new Chart(document.getElementById('budgetPieChart'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($budget_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($budget_values) ?>,
                        backgroundColor: <?= json_encode($budget_colors) ?>,
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { 
                        legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, padding: 20 } } 
                    },
                    cutout: '70%'
                }
            });

            // Mini Sparkline in the Green Card
            new Chart(document.getElementById('miniSparkline'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($cash_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($cash_activity) ?>,
                        borderColor: 'rgba(255,255,255,0.8)',
                        borderWidth: 2,
                        fill: true,
                        backgroundColor: 'rgba(255,255,255,0.1)',
                        pointRadius: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { display: false }, y: { display: false } }
                }
            });
        });
    </script>
    <?php endif; ?>

    <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem; color: #6c757d;">Financial Modules</h5>
    <div class="row g-4 mb-5">
        <?php 
        $modules = [
            [
                'title' => 'Approvals & Authorizations',
                'desc' => 'Authorize pending budget reservations and expenditures.',
                'icon' => 'bi-shield-check',
                'link' => 'approvals.php',
                'color' => '#2f7a4d',
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'Budgeting & Reservations',
                'desc' => 'Allocate funds and manage departmental spending limits.',
                'icon' => 'bi-piggy-bank',
                'link' => 'budget.php',
                'color' => '#2a9d62',
                'roles' => ['admin', 'finance_officer', 'dept_head']
            ],
            [
                'title' => 'Student Billing (AR)',
                'desc' => 'Invoice management and student payment processing.',
                'icon' => 'bi-receipt-cutoff',
                'link' => 'billing_view.php',
                'color' => '#3aa86a',
                'roles' => ['admin', 'finance_officer', 'student']
            ],
            [
                'title' => 'Procurement (AP)',
                'desc' => 'Vendor purchase orders and supply chain receipts.',
                'icon' => 'bi-truck',
                'link' => 'procurement.php',
                'color' => '#4c9f76',
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'Financial Reports',
                'desc' => 'Balance Sheets, Audit Logs, and Budget vs Actuals.',
                'icon' => 'bi-bar-chart-line',
                'link' => 'reports.php',
                'color' => '#2d8f67',
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'System Utilities',
                'desc' => 'Manual ledger overrides and budget adjustments.',
                'icon' => 'bi-shield-lock',
                'link' => 'admin_utilities.php',
                'color' => '#5a8c73',
                'roles' => ['admin']
            ]
        ];

        foreach($modules as $mod):
            if(in_array($role, $mod['roles'])):
        ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?= $mod['link'] ?>" class="text-decoration-none h-100 d-block">
                <div class="card h-100 border-0 shadow-sm card-hover p-2" style="border-left: 5px solid <?= $mod['color'] ?> !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <i class="bi <?= $mod['icon'] ?> fs-2" style="color: <?= $mod['color'] ?>;"></i>
                            <i class="bi bi-arrow-right-short fs-4 text-muted"></i>
                        </div>
                        <h5 class="fw-bold text-dark"><?= $mod['title'] ?></h5>
                        <p class="text-muted small mb-0"><?= $mod['desc'] ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>

<style>
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.1) !important;
        background-color: #fff;
    }
</style>

</body>
</html>

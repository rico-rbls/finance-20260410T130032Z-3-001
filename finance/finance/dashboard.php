<?php 
include 'header.php'; 
include 'db_connect.php';

$role = $_SESSION['role'];
$user_full_name = $_SESSION['full_name'] ?? 'Authorized User';

// Quick Stats for the Header Cards
$unpaid_inv = $conn->query("SELECT COUNT(*) as total FROM invoices WHERE status='unpaid'")->fetch_assoc()['total'];
$pending_pos = $conn->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status='pending'")->fetch_assoc()['total'];
$low_budgets = $conn->query("SELECT COUNT(*) as total FROM departments WHERE total_budget < 5000")->fetch_assoc()['total'];

// NEW: Count for Budget Approvals
$pending_approvals = $conn->query("SELECT COUNT(*) as total FROM budget_reservations WHERE status='pending'")->fetch_assoc()['total'];
?>

<div class="container py-4">
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h1 class="fw-bold text-dark mb-1">System Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <span class="fw-bold text-primary"><?= htmlspecialchars($user_full_name) ?></span>. Here is your institutional overview.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-inline-block text-start me-4">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">System Status</div>
                <div class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Operational</div>
            </div>
            <div class="d-inline-block text-start">
                <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Fiscal Period</div>
                <div class="text-dark fw-bold">FY <?= date('Y') ?> / Q<?= ceil(date('n')/3) ?></div>
            </div>
        </div>
    </div>

    <?php if(in_array($role, ['admin', 'finance_officer'])): ?>
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle p-3 me-3">
                        <i class="bi bi-shield-check text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $pending_approvals ?></h4>
                        <div class="text-muted small">Awaiting Approval</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle p-3 me-3">
                        <i class="bi bi-hourglass-split text-warning fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $pending_pos ?></h4>
                        <div class="text-muted small">Pending POs</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle p-3 me-3">
                        <i class="bi bi-currency-dollar text-success fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $unpaid_inv ?></h4>
                        <div class="text-muted small">Unpaid Invoices</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger-subtle p-3 me-3">
                        <i class="bi bi-exclamation-octagon text-danger fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?= $low_budgets ?></h4>
                        <div class="text-muted small">Budget Alerts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <h5 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem; color: #6c757d;">Financial Modules</h5>
    <div class="row g-4">
        
        <?php 
        $modules = [
            [
                'title' => 'Approvals & Authorizations',
                'desc' => 'Authorize pending budget reservations and expenditures.',
                'icon' => 'bi-shield-check',
                'link' => 'approvals.php',
                'color' => '#6610f2', // Indigo
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'Budgeting & Reservations',
                'desc' => 'Allocate funds and manage departmental spending limits.',
                'icon' => 'bi-piggy-bank',
                'link' => 'budget.php',
                'color' => '#0d6efd',
                'roles' => ['admin', 'finance_officer', 'dept_head']
            ],
            [
                'title' => 'Student Billing (AR)',
                'desc' => 'Invoice management and student payment processing.',
                'icon' => 'bi-receipt-cutoff',
                'link' => 'billing_view.php',
                'color' => '#198754',
                'roles' => ['admin', 'finance_officer', 'student']
            ],
            [
                'title' => 'Procurement (AP)',
                'desc' => 'Vendor purchase orders and supply chain receipts.',
                'icon' => 'bi-truck',
                'link' => 'procurement.php',
                'color' => '#f59e0b',
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'Financial Reports',
                'desc' => 'Balance Sheets, Audit Logs, and Budget vs Actuals.',
                'icon' => 'bi-bar-chart-line',
                'link' => 'reports.php',
                'color' => '#0dcaf0',
                'roles' => ['admin', 'finance_officer']
            ],
            [
                'title' => 'System Utilities',
                'desc' => 'Manual ledger overrides and budget adjustments.',
                'icon' => 'bi-shield-lock',
                'link' => 'admin_utilities.php',
                'color' => '#dc3545',
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
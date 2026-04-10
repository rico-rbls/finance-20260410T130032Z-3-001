<?php
session_start();
include_once 'error_handler.php'; // Initialize Centralized Error Handling

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php"); exit();
}
function restrictTo($roles) {
    if (!in_array($_SESSION['role'], $roles)) die("Access Denied.");
}

function formatCurrency($amount, $withCode = true) {
    $formatted = number_format((float) $amount, 2, '.', ',');
    return $withCode ? ('PHP ' . $formatted) : $formatted;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finance ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Global Utility for Toasts (using Bootstrap Alerts)
        window.showAppToast = function(msg, type='info') {
            let container = document.getElementById('toast-container');
            if(!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = 'position:fixed; top:85px; right:20px; z-index:9999;';
                document.body.appendChild(container);
            }
            const wrapper = document.createElement('div');
            const alertClass = type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'primary');
            wrapper.innerHTML = `
                <div class="alert alert-${alertClass} alert-dismissible fade show shadow-lg border-0 rounded-4 mb-2" role="alert" style="min-width: 250px; backdrop-filter: blur(8px); background-color: rgba(var(--bs-${alertClass}-rgb), 0.9); color: white;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')} me-2 fs-5"></i>
                        <div class="small fw-semibold">${msg}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
            const alertNode = wrapper.firstElementChild;
            container.appendChild(alertNode);
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertNode);
                bsAlert.close();
            }, 6000);
        };
    </script>
    <style>
        :root {
            --primary: #1e6b3e; /* Forest Green */
            --primary-light: #f0fdf4;
            --sidebar-bg: #ffffff;
            --main-bg: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --card-radius: 20px;
            --accent: #2ecc71;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--main-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 107, 62, 0.03) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 107, 62, 0.03) 0px, transparent 50%);
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* SaaS Sidebar - Refined spacing */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            border-right: 1px solid rgba(0,0,0,0.06);
            z-index: 1050;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 32px 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 0 12px 40px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--primary);
        }

        .nav-section { margin-bottom: 28px; }
        .nav-label { 
            font-size: 0.75rem; 
            font-weight: 800; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1.2px; 
            padding: 0 12px 14px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--text-main);
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 16px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 8px;
            gap: 14px;
            text-decoration: none !important;
            letter-spacing: -0.01em;
        }

        .nav-link i { font-size: 1.25rem; color: var(--text-muted); transition: color 0.2s; }
        .nav-link:hover { background: var(--primary-light); color: var(--primary); }
        .nav-link:hover i { color: var(--primary); }
        .nav-link.active { background: var(--primary); color: white; box-shadow: 0 8px 16px -4px rgba(30, 107, 62, 0.25); }
        .nav-link.active i { color: white; }

        /* Logout Link Styling */
        .sidebar .mt-auto .nav-link:hover {
            background: rgba(220, 53, 69, 0.08); /* Light Red Soft BG */
            color: #dc3545 !important;
        }

        /* SaaS Top Header - Glassmorphism */
        .top-header {
            position: fixed;
            top: 0; right: 0; left: 280px;
            height: 80px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            z-index: 1040;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .main-content { 
            margin-left: 280px; 
            padding: 125px 48px 48px 48px; 
            min-height: 100vh;
            background: var(--main-bg); 
        }

        /* SaaS Cards - Modern UI & Proper Weight */
        .card {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 24px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 8px; /* Inner spacing */
        }
        .card-header {
            background: transparent;
            border-bottom: 2px solid #f8fafc;
            padding: 24px 32px;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
        }
        .card-body { padding: 32px; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }

        /* SaaS Inputs - Standardized Spacing */
        .form-control {
            border-radius: 14px;
            padding: 12px 20px;
            border: 1.5px solid #edf2f7;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 107, 62, 0.08);
        }

        /* Footer Modernization */
        .app-footer {
            margin-left: 280px;
            padding: 32px 48px;
            background: transparent;
            border-top: 1.5px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Search Bar Refinement */
        .search-container { position: relative; width: 420px; }
        .search-bar {
            background: #f1f5f9;
            border: 1px solid transparent !important;
            border-radius: 16px;
            padding: 12px 16px 12px 48px !important;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .search-bar:focus { 
            background: white; 
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(30, 107, 62, 0.1); 
            outline: none;
        }
        .search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; font-size: 1.1rem; }

        /* Corner Rounding Utilities */
        .rounded-xl { border-radius: 16px !important; }
        .rounded-2xl { border-radius: 24px !important; }
        
        .btn { border-radius: 12px; padding: 10px 20px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { box-shadow: 0 4px 12px rgba(30, 107, 62, 0.15); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(30, 107, 62, 0.2); }
    </style>
</head>
<body>

<?php if(basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
<!-- Sidebar Navigation -->
<aside class="sidebar" id="mainSidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="bi bi-bank2 fs-5"></i>
        </div>
        <div>
            <span class="d-block fw-bold h5 mb-0" style="font-family: 'Outfit';">UniFinance</span>
            <span class="badge bg-soft-success text-success p-0" style="font-size: 0.65rem;">ENTERPRISE</span>
        </div>
    </a>

    <div class="nav-section mt-3">
        <div class="nav-label">Main Menu</div>
        <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="budget.php" class="nav-link <?= $current_page == 'budget.php' ? 'active' : '' ?>">
            <i class="bi bi-pie-chart-fill"></i> Budget Control
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Operations</div>
        <a href="procurement.php" class="nav-link <?= $current_page == 'procurement.php' ? 'active' : '' ?>">
            <i class="bi bi-cart-check-fill"></i> Procurement
        </a>
        <a href="approvals.php" class="nav-link <?= $current_page == 'approvals.php' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Approvals
        </a>
        <a href="billing_view.php" class="nav-link <?= $current_page == 'billing_view.php' ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i> Billing
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-label">Intelligence</div>
        <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> Data Center
        </a>
    </div>

    <div class="mt-auto pt-4 border-top border-light-subtle">
        <a href="logout.php" class="nav-link text-danger fw-bold">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</aside>

<!-- Top Navigation -->
<header class="top-header">
    <div class="search-container">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="globalSearch" class="search-bar" placeholder="Search anything (Press '/') " onkeyup="updateRecommendations()">
        <div id="searchRecommendations" class="dropdown-menu shadow-lg border-0 w-100 rounded-4 mt-2" style="display: none; max-height: 480px; overflow-y: auto;"></div>
    </div>

    <div class="d-flex align-items-center gap-4">
        <div class="d-flex gap-2 text-muted">
            <button class="btn btn-light rounded-circle p-2" title="Notifications">
                <i class="bi bi-bell-fill fs-5"></i>
            </button>
        </div>
        
        <div class="vr" style="height: 32px; opacity: 0.1;"></div>

        <div class="dropdown">
            <div class="user-dropdown d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 700;">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1))) ?>
                </div>
                <div class="d-none d-md-block text-start">
                    <span class="d-block small fw-bold mb-0"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                    <span class="d-block text-muted" style="font-size: 0.7rem;">Administrator</span>
                </div>
                <i class="bi bi-chevron-down small text-muted ms-1"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3" style="min-width: 200px;">
                <li><a class="dropdown-item py-2 px-3 rounded-top-4" href="#"><i class="bi bi-person me-2 text-muted"></i> My Profile</a></li>
                <li><a class="dropdown-item py-2 px-3" href="#"><i class="bi bi-activity me-2 text-muted"></i> System Logs</a></li>
                <li><hr class="dropdown-divider opacity-50"></li>
                <li><a class="dropdown-item py-2 px-3 text-danger rounded-bottom-4" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
            </ul>
        </div>
    </div>
</header>
<?php endif; ?>

<main class="<?= basename($_SERVER['PHP_SELF']) != 'login.php' ? 'main-content' : '' ?>">

<div id="appToastContainer" class="app-toast-container" aria-live="polite" aria-atomic="true"></div>

<script>
function exportVisibleTableToCsv(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        showAppToast('No table found for export.', 'error');
        return;
    }

    const headers = [];
    table.querySelectorAll('thead th').forEach(function(th, index) {
        const text = (th.textContent || '').trim();
        if (text.toLowerCase() !== 'actions' && text.toLowerCase() !== 'action') {
            headers.push({ text: text, index: index });
        }
    });

    const rows = [];
    const visibleRows = table.querySelectorAll('tbody tr');
    visibleRows.forEach(function(row) {
        if (row.style.display === 'none') return;
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;
        const rowData = headers.map(function(h) {
            const val = (cells[h.index] ? cells[h.index].textContent : '').trim().replace(/\s+/g, ' ');
            return '"' + val.replace(/"/g, '""') + '"';
        });
        rows.push(rowData.join(','));
    });

    if (!rows.length) {
        showAppToast('No visible records to export.', 'info');
        return;
    }

    const csv = headers.map(function(h) { return '"' + h.text.replace(/"/g, '""') + '"'; }).join(',') + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

function exportVisibleTableToXlsx(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        showAppToast('No table found for export.', 'error');
        return;
    }

    if (typeof XLSX === 'undefined') {
        showAppToast('Excel export library failed to load. Please try CSV export.', 'error');
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: 'Data', display: true });
    XLSX.writeFile(workbook, filename);
}

function routeGlobalSearch(query) {
    const q = (query || '').trim();
    if (!q) return;

    sessionStorage.setItem('globalSearchQuery', q);
    
    // Check if query is a feature keyword
    const features = {
        'budget': 'budget.php',
        'reservation': 'budget.php',
        'allocate': 'budget.php',
        'student': 'billing_view.php',
        'billing': 'billing_view.php',
        'invoice': 'billing_view.php',
        'tuition': 'billing_view.php',
        'procurement': 'procurement.php',
        'purchase': 'procurement.php',
        'order': 'procurement.php',
        'po': 'procurement.php',
        'approval': 'approvals.php',
        'authorize': 'approvals.php',
        'report': 'reports.php',
        'intelligence': 'reports.php',
        'audit': 'reports.php',
        'utility': 'admin_utilities.php',
        'ledger': 'admin_utilities.php'
    };

    const searchLower = q.toLowerCase();
    for (const key in features) {
        if (searchLower.includes(key)) {
            window.location.href = features[key] + '?search=' + encodeURIComponent(q);
            return;
        }
    }

    // Default to search on current page or redirect to billing if looks like student name
    const current = window.location.pathname.toLowerCase();
    const localFields = ['billingSearch', 'poSearch', 'approvalSearch', 'budgetSearch'];
    for (let i = 0; i < localFields.length; i++) {
        const field = document.getElementById(localFields[i]);
        if (field) {
            field.value = q;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            showAppToast('Applied global search on this page.', 'info');
            return;
        }
    }

    window.location.href = 'billing_view.php?search=' + encodeURIComponent(q);
}

function updateRecommendations(query) {
    const q = (query || '').trim().toLowerCase();
    const container = document.getElementById('searchRecommendations');
    if (!container) return;

    if (!q || q.length < 2) {
        container.classList.add('d-none');
        return;
    }

    const availableFeatures = [
        { label: 'Budget & Reservations', type: 'Feature', link: 'budget.php', icon: 'bi-piggy-bank' },
        { label: 'Student Billing / Invoices', type: 'Feature', link: 'billing_view.php', icon: 'bi-receipt' },
        { label: 'Procurement / Purchase Orders', type: 'Feature', link: 'procurement.php', icon: 'bi-truck' },
        { label: 'Awaiting Approvals', type: 'Feature', link: 'approvals.php', icon: 'bi-shield-check' },
        { label: 'Financial Intelligence Reports', type: 'Feature', link: 'reports.php', icon: 'bi-bar-chart-line' },
        { label: 'System Utilities & Ledger', type: 'Feature', link: 'admin_utilities.php', icon: 'bi-shield-lock' }
    ];

    let html = '';
    const filteredFeatures = availableFeatures.filter(f => f.label.toLowerCase().includes(q));
    
    filteredFeatures.forEach(f => {
        html += `<div class="search-recommend-item" onclick="window.location.href='${f.link}'">
                    <i class="bi ${f.icon}"></i>
                    <span class="item-label">${f.label}</span>
                    <span class="item-type">${f.type}</span>
                 </div>`;
    });

    // Smart Keyword Suggestions
    const shortcuts = [
        { trigger: 'inv', label: 'Search Student Invoices', link: 'billing_view.php' },
        { trigger: 'stu', label: 'Find a Student Record', link: 'billing_view.php' },
        { trigger: 'po', label: 'View Pending Orders', link: 'procurement.php' },
        { trigger: 'res', label: 'Manage Reservations', link: 'budget.php' }
    ];

    shortcuts.filter(s => s.trigger.indexOf(q) === 0).forEach(s => {
        html += `<div class="search-recommend-item" onclick="window.location.href='${s.link}?search=${encodeURIComponent(q)}'">
                    <i class="bi bi-lightning-charge"></i>
                    <span class="item-label">${s.label}: <strong>${q}</strong></span>
                    <span class="item-type">Action</span>
                 </div>`;
    });

    // Fetch dynamic database entities (Students, Specific Records)
    fetch('search_entities.php?q=' + encodeURIComponent(q))
        .then(response => response.json())
        .then(entities => {
            entities.forEach(e => {
                html += `<div class="search-recommend-item" onclick="window.location.href='${e.link}'">
                            <i class="bi ${e.icon}"></i>
                            <span class="item-label">${e.label}</span>
                            <span class="item-type">${e.type}</span>
                         </div>`;
            });

            if (html) {
                container.innerHTML = html;
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        })
        .catch(() => {
            // Fallback to static if endpoint fails
            if (html) {
                container.innerHTML = html;
                container.classList.remove('d-none');
            }
        });
}

function applyGlobalSearchFromState() {
    const params = new URLSearchParams(window.location.search);
    const queryFromUrl = (params.get('search') || '').trim();
    const saved = (sessionStorage.getItem('globalSearchQuery') || '').trim();
    const q = queryFromUrl || saved;
    if (!q) return;

    const globalInput = document.getElementById('globalSearchInput');
    if (globalInput) globalInput.value = q;

    const localFields = ['billingSearch', 'poSearch', 'approvalSearch', 'budgetSearch'];
    for (let i = 0; i < localFields.length; i++) {
        const field = document.getElementById(localFields[i]);
        if (field && !field.value) {
            field.value = q;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}

function showAppToast(message, type) {
    const toastType = type || 'info';
    const container = document.getElementById('appToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'app-toast ' + toastType;
    toast.innerHTML = '<div class="toast-body">' + message + '</div>';
    container.appendChild(toast);

    requestAnimationFrame(function() {
        toast.classList.add('show');
    });

    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 220);
    }, 2600);
}

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('globalSearchForm');
    const searchInput = document.getElementById('globalSearchInput');
    const recommendations = document.getElementById('searchRecommendations');

    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            routeGlobalSearch(searchInput.value);
        });

        searchInput.addEventListener('input', function(e) {
            updateRecommendations(e.target.value);
        });

        // Close recommendations when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !recommendations.contains(e.target)) {
                recommendations.classList.add('d-none');
            }
        });

        // Re-open if query is present on focus
        searchInput.addEventListener('focus', function(e) {
            if (e.target.value.length >= 2) {
                updateRecommendations(e.target.value);
            }
        });
    }

    applyGlobalSearchFromState();

    if (!document.getElementById('appGlobalFooter') && basename($_SERVER['PHP_SELF']) != 'login.php') {
        const footer = document.createElement('footer');
        footer.id = 'appGlobalFooter';
        footer.className = 'app-footer';
        footer.innerHTML = `
            <div><strong>UniFinance ERP</strong> &bull; Institutional Financial Suite</div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-soft-primary p-2">v4.2.0 Stable</span>
                <span>&copy; ${new Date().getFullYear()} All Rights Reserved</span>
            </div>
        `;
        document.body.appendChild(footer);
    }
});
</script>

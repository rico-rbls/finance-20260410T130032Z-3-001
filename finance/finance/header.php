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
            --primary-light: #2da15f;
            --primary-dark: #14492a;
            --accent: #d4fce4;
            --bg-glass: rgba(255, 255, 255, 0.85);
            --surface: #ffffff;
            --body-bg: #f8faf9;
            --text-main: #1a202c;
            --text-muted: #64748b;
            
            --bs-primary: var(--primary);
            --bs-primary-rgb: 30, 107, 62;
        }

        body {
            background-color: var(--body-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 107, 62, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(45, 161, 95, 0.05) 0px, transparent 50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 78px;
            letter-spacing: -0.01em;
        }

        h1, h2, h3, h4, h5, .navbar-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        /* Glassmorphism Navbar */
        .navbar {
            background: var(--bg-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(30, 107, 62, 0.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            color: var(--primary) !important;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        /* Modern Cards */
        .card {
            border: 1px solid rgba(30, 107, 62, 0.08);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(30, 107, 62, 0.04), 0 8px 10px -6px rgba(30, 107, 62, 0.04);
            background: var(--surface);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 20px 25px -5px rgba(30, 107, 62, 0.08), 0 10px 10px -5px rgba(30, 107, 62, 0.04);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(30, 107, 62, 0.08);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        /* Inputs & Search */
        .global-search-container {
            position: relative;
            width: 300px;
        }

        .global-search-input {
            background: #f1f5f2 !important;
            border: 1px solid transparent !important;
            border-radius: 12px !important;
            padding: 0.6rem 1rem 0.6rem 2.5rem !important;
            font-size: 0.9rem;
            transition: all 0.2s ease !important;
        }

        .global-search-input:focus {
            background: #fff !important;
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(30, 107, 62, 0.1) !important;
        }

        .search-icon-fixed {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            z-index: 5;
        }

        /* Custom Modern Buttons */
        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(30, 107, 62, 0.2);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(30, 107, 62, 0.25);
        }

        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            border-radius: 12px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table thead th {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
        }

        .table tbody tr {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: #fdfdfd;
            transform: scale(1.002);
            box-shadow: 0 4px 8px rgba(30, 107, 62, 0.05);
        }

        .table td {
            border: none;
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
        }

        .table td:first-child { border-radius: 12px 0 0 12px; }
        .table td:last-child { border-radius: 0 12px 12px 0; }

        /* Badges */
        .badge {
            padding: 0.5em 0.8em;
            border-radius: 8px;
            font-weight: 600;
        }

        .bg-primary-subtle { background-color: #e3f5eb !important; color: #1e6b3e !important; }
        .bg-warning-subtle { background-color: #fef3c7 !important; color: #92400e !important; }
        .bg-success-subtle { background-color: #dcfce7 !important; color: #166534 !important; }
        .bg-danger-subtle { background-color: #fee2e2 !important; color: #991b1b !important; }

        /* Footer */
        .app-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1040;
            background: var(--surface);
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 0.75rem 0;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Toast Modernization */
        .app-toast {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .app-toast.success::before { content: ""; width: 6px; height: 100%; background: #198754; position: absolute; left: 0; }
        .app-toast.error::before { content: ""; width: 6px; height: 100%; background: #dc3545; position: absolute; left: 0; }

        .app-toast-container { position: fixed; top: 1.5rem; right: 1.5rem; z-index: 2000; }

        /* Search Recommendations Styles */
        .global-search-container { position: relative; }
        .search-recommendations {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            border-radius: 12px;
            margin-top: 8px;
            overflow: hidden;
            z-index: 1100;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .search-recommend-item {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f1f5f2;
            transition: background 0.2s;
        }
        .search-recommend-item:last-child { border-bottom: none; }
        .search-recommend-item:hover { background: #f8faf9; }
        .search-recommend-item i { margin-right: 12px; color: var(--primary); }
        .search-recommend-item .item-label { font-size: 0.9rem; font-weight: 500; }
        .search-recommend-item .item-type { font-size: 0.7rem; color: #64748b; margin-left: auto; text-transform: uppercase; }
        .bg-primary { background-color: var(--primary) !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-2 me-2" style="width: 40px; height: 40px;">
                <i class="bi bi-bank2"></i>
            </div>
            <span>UniFinance</span>
        </a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="ms-md-4 me-auto">
                <div class="global-search-container">
                    <form id="globalSearchForm" role="search" autocomplete="off">
                        <i class="bi bi-search search-icon-fixed"></i>
                        <input id="globalSearchInput" type="search" class="form-control global-search-input" placeholder="Search features, students, or POs..." aria-label="Global Search">
                    </form>
                    <div id="searchRecommendations" class="search-recommendations shadow-lg d-none">
                        <!-- Dynamic Recommendations -->
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-lg-block">
                    <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
                    <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <?= str_replace('_', ' ', $_SESSION['role']) ?>
                    </div>
                </div>
                <div class="vr mx-2 d-none d-lg-block" style="height: 30px;"></div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger border-0">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

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

    if (!document.getElementById('appGlobalFooter')) {
        const footer = document.createElement('footer');
        footer.id = 'appGlobalFooter';
        footer.className = 'app-footer';
        footer.innerHTML = '<div class="container footer-wrap">Contact: ricdrobles@gmail.com (Group Programmer)</div>';
        document.body.appendChild(footer);
    }
});
</script>

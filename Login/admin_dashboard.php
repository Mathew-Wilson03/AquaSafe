<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'] ?? 'user';
$role_lower = strtolower(trim((string)$user_role));
if (!in_array($role_lower, ['administrator', 'admin', 'superadmin'], true)) {
    header("Location: user_dashboard.php");
    exit;
}

// Get current user info
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
$user_email = $_SESSION['email'];

// Fetch all users for Management Tab
require_once 'config.php';
$table = 'users'; 
try {
    $r = mysqli_query($link, "SHOW TABLES LIKE 'user'");
    if ($r && mysqli_num_rows($r) > 0) $table = 'user';
} catch (Throwable $e) {}

$role_col = 'role';
try {
    $r = mysqli_query($link, "SHOW COLUMNS FROM `$table` LIKE 'user_role'");
    if ($r && mysqli_num_rows($r) > 0) $role_col = 'user_role';
} catch (Throwable $e) {}

$users_sql = "SELECT id, name, email, `$role_col` AS user_role FROM `$table` ORDER BY name ASC";
$users_result = mysqli_query($link, $users_sql);
$all_users = [];
if ($users_result) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $all_users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AquaSafe</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

        :root {
            --bg-body: #13141b;
            --bg-surface: #1e2029;
            --bg-hover: #252836;
            --primary-grad: linear-gradient(135deg, #c054ff 0%, #6d28d9 100%);
            --secondary-grad: linear-gradient(135deg, #00e5ff 0%, #2979ff 100%);
            --text-main: #ffffff;
            --text-muted: #94a3b8;
        }

        /* Fix for Dropdown visibility */
        select option {
            background: #1e2029;
            color: #fff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: 100vh;
            padding: 20px;
            gap: 20px;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            width: 280px;
            background: var(--bg-surface);
            border: 1px solid #2d2f39; /* Subtle borders */
            border-radius: 24px;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 50px;
            font-size: 24px;
            font-weight: 800; /* Matched User Dashboard */
            color: #c054ff; /* Matched Theme */
            padding-left: 10px;
        }

        .interactive-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .sidebar-nav {
            flex: 1;
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 15px;
            padding: 14px 20px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }

        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--primary-grad);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(192, 84, 255, 0.4);
            transform: translateX(5px);
        }

        .sidebar-logout {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* --- Icon Interactive Animations --- */
        @keyframes spin-slow { 100% { transform: rotate(360deg); } }
        @keyframes bounce-gentle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
        
        /* Apply to Emoji/Icon in Sidebar */
        .sidebar-nav a:hover {
            /* Existing hover styles... */
        }
        
        .sidebar-nav a:hover svg, .sidebar-nav a:hover i { 
            animation: bounce-gentle 1s infinite ease-in-out;
            color: #4ab5c4;
        }

        .interactive-logo {
            transition: transform 1s ease-in-out;
        }
        .interactive-logo:hover {
            transform: rotate(360deg) scale(1.1);
        }

        /* Card Icons */
        .card h3 i, .card h3 svg {
            transition: transform 0.3s ease;
        }
        .card:hover h3 i, .card:hover h3 svg {
            transform: scale(1.2) rotate(10deg);
            color: #4ab5c4;
        }

        .sidebar-logout a {
            background: rgba(231, 76, 60, 0.1);
            color: #ff8d85;
            padding: 14px 20px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .sidebar-logout a:hover {
            background: rgba(231, 76, 60, 0.2);
            box-shadow: 0 0 20px rgba(231, 76, 60, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Header */
        .header {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        .header-left h1 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(to right, #ffffff, #b2bec3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .last-updated {
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            color: #4ab5c4;
            border: 1px solid rgba(74, 181, 196, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #4ab5c4;
            border-radius: 50%;
            animation: pulse-green 2s infinite;
        }

        /* Custom Premium Area Selector */
        .area-selector-wrapper {
            position: relative;
            min-width: 200px;
        }

        .area-selector-wrapper select {
            width: 100%;
            padding: 10px 40px 10px 16px;
            font-size: 14px;
            font-family: inherit;
            color: #ffffff;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .area-selector-wrapper select:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(74, 181, 196, 0.5);
            box-shadow: 0 0 15px rgba(74, 181, 196, 0.2);
        }

        .area-selector-wrapper select:focus {
            outline: none;
            border-color: #4ab5c4;
            box-shadow: 0 0 20px rgba(74, 181, 196, 0.3);
        }

        .area-selector-wrapper select option {
            background-color: #1a3a4a; /* Solid background for visibility */
            color: #ffffff;
            padding: 10px;
        }

        .area-selector-wrapper::after {
            content: "";
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234ab5c4' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        .area-selector-wrapper:focus-within::after {
            transform: translateY(-50%) rotate(180deg);
        }

        /* Dashboard Grid */
        .dashboard-content {
            flex: 1;
            overflow-y: auto;
            border-radius: 24px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: var(--bg-surface);
            border: 1px solid #2d2f39;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .card > * {
            position: relative;
            z-index: 2;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transition: opacity 0.4s;
            pointer-events: none;
            z-index: 0;
        }

        .card:hover {
            transform: translateY(-5px) scale(1.01);
            border-color: rgba(74, 181, 196, 0.3);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card h3 {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Map Styling */
        .map-container {
            height: 300px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .map-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                linear-gradient(rgba(74, 181, 196, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74, 181, 196, 0.1) 1px, transparent 1px);
            background-size: 40px 40px;
            transform: perspective(500px) rotateX(60deg) translateY(-100px) translateZ(-200px);
            animation: moveGrid 20s linear infinite;
        }

        @keyframes moveGrid {
            0% { transform: perspective(500px) rotateX(60deg) translateY(0) translateZ(-200px); }
            100% { transform: perspective(500px) rotateX(60deg) translateY(40px) translateZ(-200px); }
        }

        /* Status Cards */
        .status-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            z-index: 10;
            overflow: hidden;
        }

        .status-card:hover {
            transform: translateY(-8px);
            background: rgba(74, 181, 196, 0.1);
            border-color: rgba(74, 181, 196, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .status-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-value {
            font-size: 20px;
            font-weight: 700;
        }

        .safe-text { color: #2ecc71; text-shadow: 0 0 10px rgba(46, 204, 113, 0.3); }
        .warning-text { color: #f1c40f; text-shadow: 0 0 10px rgba(241, 196, 15, 0.3); }
        .danger-text { color: #e74c3c; text-shadow: 0 0 10px rgba(231, 76, 60, 0.3); }
        .info-text { color: #4ab5c4; text-shadow: 0 0 10px rgba(74, 181, 196, 0.3); }

        .alert-btn-wrapper button:hover {
            background: rgba(74, 181, 196, 0.2) !important;
            border-color: rgba(74, 181, 196, 0.5) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card, .status-card { animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
        .status-card:nth-child(1) { animation-delay: 0.1s; }
        .status-card:nth-child(2) { animation-delay: 0.2s; }
        .status-card:nth-child(3) { animation-delay: 0.3s; }
        .status-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.6); }
            70% { box-shadow: 0 0 0 15px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.6); }
            70% { box-shadow: 0 0 0 15px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        /* Map Pins */
        .map-pin {
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
            cursor: pointer;
            transition: transform 0.3s;
            z-index: 2;
        }

        .map-pin:hover { transform: scale(1.5); }
        .map-pin.safe { background: #2ecc71; animation: pulse-green 3s infinite; }
        .map-pin.danger { background: #e74c3c; animation: pulse-red 2s infinite; }
        .map-pin.warning { background: #f1c40f; }

        .pin-tooltip {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .map-pin:hover .pin-tooltip {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Toggle Switch */
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; margin-bottom: 0; }
        .switch input { opacity: 0; width: 0; height: 0; appearance: none; position: absolute; }
        .switch .slider {
            position: absolute; cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255,255,255,0.2);
            transition: .4s;
            border-radius: 34px;
        }
        .switch .slider:before {
            position: absolute; content: "";
            height: 26px; width: 26px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        .switch input:checked + .slider { background-color: #2ecc71; }
        .switch input:focus + .slider { box-shadow: 0 0 1px #2ecc71; }
        .switch input:checked + .slider:before { transform: translateX(26px); }

        /* Small variant for channels */
        .switch.small { width: 50px; height: 28px; }
        .switch.small .slider:before { height: 20px; width: 20px; bottom: 4px; left: 4px; }
        .switch.small input:checked + .slider:before { transform: translateX(22px); }

        /* Mobile Responsive Improvements */
        @media (max-width: 1024px) {
            .container { flex-direction: column; padding: 10px; }
            .sidebar { 
                position: fixed;
                left: -300px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                width: 280px;
                transition: left 0.3s ease;
                border-radius: 0 24px 24px 0;
                overflow-y: auto; /* Enable vertical scroll for long menus on mobile */
                max-height: 100vh;
            }
            .sidebar.active { left: 0; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .status-row { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
            .header { padding: 15px 20px; flex-wrap: wrap; gap: 10px; }
            .header-left h1 { font-size: 20px; }
            .mobile-toggle { display: block !important; }

            /* Table Responsiveness (Manage Users & Sensors) */
            table, thead, tbody, th, td, tr { 
                display: block; 
            }
            
            thead tr { 
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr { border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 10px; padding: 10px; background: rgba(255,255,255,0.02); }
            
            td { 
                border: none;
                border-bottom: 1px solid rgba(255,255,255,0.05); 
                position: relative;
                padding-left: 50% !important; 
                text-align: left !important;
                min-height: 40px;
                display: flex;
                align-items: center;
            }

            td:last-child { border-bottom: none; justify-content: flex-start; }
            
            td:before { 
                position: absolute;
                left: 15px;
                width: 45%; 
                padding-right: 10px; 
                white-space: nowrap;
                font-weight: 600;
                color: #4ab5c4;
                font-size: 13px;
                content: attr(data-label);
            }
        }

        @media (max-width: 480px) {
            .status-row { grid-template-columns: 1fr; }
            .header-right { gap: 10px; }
            .last-updated { padding: 6px 12px; font-size: 11px; }
            #clock { display: none; }
        }

        .mobile-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px;
            border-radius: 12px;
            cursor: pointer;
            margin-right: 15px;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 999;
        }

        .sidebar-overlay.active { display: block; }
        /* Tab Logic */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .content-section.active {
            display: block;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #2c3e50;
            width: 500px;
            max-width: 90%;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        .modal h2 { margin-bottom: 20px; color: #4ab5c4; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; opacity: 0.8; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #4ab5c4; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .btn-cancel { padding: 10px 20px; background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; cursor: pointer; }
        .btn-save { padding: 10px 20px; background: #4ab5c4; border: none; color: white; border-radius: 8px; cursor: pointer; font-weight: 600; }

        /* Notification Badge */
        .nav-link { position: relative; }
        #alertBadge, #helpdeskBadge {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #e74c3c;
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 18px;
            text-align: center;
            display: none;
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
            animation: badgePulse 2s infinite;
        }
        @keyframes badgePulse {
            0% { transform: translateY(-50%) scale(1); }
            50% { transform: translateY(-50%) scale(1.1); }
            100% { transform: translateY(-50%) scale(1); }
        }
    </style>
</head>
<body>
    <!-- AquaSafe Priority Handlers Bridge -->
    <script>
        (function() {
            window.aquaSafeExportCSV = function() {
                try {
                    const csv = "Date,Report,Status\nDec 19,Daily Summary,Completed";
                    const blob = new Blob([csv], {type: 'text/csv'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = "AquaSafe_Report.csv";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    console.log("[AquaSafe] CSV Export Completed");
                } catch(e) { console.error("Export Error:", e); }
            };
            window.aquaSafeDownloadPDF = function(name, btn) {
                const old = btn.innerHTML;
                btn.innerText = "⏳ Preparing...";
                
                setTimeout(() => {
                    try {
                        const pdfContent = "%PDF-1.4\n1 0 obj\<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj\<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj\<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer\<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";
                        const blob = new Blob([pdfContent], { type: 'application/pdf' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = "AquaSafe_" + name.replace(/\s+/g, '_') + "_Report.pdf";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        console.log("[AquaSafe] PDF Generation Completed:", name);
                    } catch(e) {
                        console.error("PDF Error:", e);
                    }
                    btn.innerHTML = old;
                }, 1000);
            };
            console.log("[AquaSafe] Priority Bridge Established.");
        })();
    </script>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/logo.png" alt="AquaSafe Logo" class="interactive-logo">
                AquaSafe
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" id="nav-dashboard" class="nav-link active" onclick="switchTab('dashboard', this)">📊 Dashboard</a></li>
                <li><a href="#" id="nav-sensors" class="nav-link" onclick="switchTab('sensors', this)">📡 Sensors</a></li>
                <li><a href="#" id="nav-alerts" class="nav-link" onclick="switchTab('alerts', this)">🚨 Alerts <span id="alertBadge"></span></a></li>
                <li><a href="#" id="nav-map" class="nav-link" onclick="switchTab('map', this)">🗺️ Map</a></li>
                <li><a href="#" id="nav-evacuation" class="nav-link" onclick="switchTab('evacuation', this)">📍 Evacuation Points</a></li>
                <li><a href="#" id="nav-reports" class="nav-link" onclick="switchTab('reports', this)">📊 Reports</a></li>
                <li><a href="#" id="nav-helpdesk" class="nav-link" onclick="switchTab('helpdesk', this)">🆘 Help Desk <span id="helpdeskBadge"></span></a></li>
                <li><a href="#" id="nav-notifications" class="nav-link" onclick="switchTab('notifications', this)">🔔 Notifications</a></li>
                <li><a href="#" id="nav-users" class="nav-link" onclick="switchTab('users', this)">👥 Manage Users</a></li>
                <li><a href="#" id="nav-settings" class="nav-link" onclick="switchTab('settings', this)">⚙️ Settings</a></li>
            </ul>
            <div class="sidebar-logout">
                <a href="logout.php">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <div style="display: flex; align-items: center;">
                        <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
                        <h1 id="pageTitle">Admin Dashboard</h1>
                    </div>
                </div>
                <div class="header-right">
                    <div class="last-updated" id="lastUpdated">
                        <div class="pulse-dot"></div>
                        <span style="font-weight: 500;">Live</span>
                        <span id="clock" style="opacity: 0.7; font-size: 12px; margin-left: 5px;"></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div id="dashboard" class="content-section active">
                <div class="dashboard-content">
                    <div class="dashboard-grid">
                        <!-- Chart -->
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px;">
                                <h3 id="currentGraphTitle" style="margin-bottom: 0;">📈 Real-time Water Levels - South Reservoir</h3>
                                <div class="area-selector-wrapper">
                                    <select id="areaSelector" onchange="window.switchAreaGraph(this.value)">
                                        <option value="South Reservoir">South Reservoir</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="waterLevelChart"></canvas>
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="card">
                            <h3>📍 Sensor Network</h3>
                            <div class="map-container">
                                <div class="map-grid"></div>
                                
                                <!-- Pins -->
                                <div class="map-pin safe" style="top: 30%; left: 40%;">
                                    <div class="pin-tooltip">Zone A: Stable (85%)</div>
                                </div>
                                <div class="map-pin danger" style="top: 50%; left: 35%;">
                                    <div class="pin-tooltip">Zone B: Critical (92%)!</div>
                                </div>
                                <div class="map-pin warning" style="top: 65%; left: 70%;">
                                    <div class="pin-tooltip">Zone C: Warning (76%)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Cards -->
                    <div class="status-row">
                        <div class="status-card" onclick="window.switchTab('reports')">
                            <div class="status-label">Overall Safety</div>
                            <div class="status-value safe-text" id="overallSafetyValue">98%</div>
                        </div>
                        <div class="status-card" onclick="window.switchTab('alerts')">
                            <div class="status-label">Active Alerts</div>
                            <div class="status-value warning-text" id="activeAlertCount">0 New</div>
                        </div>
                        <div class="status-card" onclick="window.switchTab('map')">
                            <div class="status-label">Critical Zones</div>
                            <div class="status-value danger-text" id="criticalZoneCount">0 Zones</div>
                        </div>
                        <div class="status-card" onclick="window.switchTab('sensors')">
                            <div class="status-label">System Health</div>
                            <div class="status-value info-text" id="systemHealthText">Optimal</div>
                        </div>
                    </div>

                    <!-- Recent Alerts Summary for Dashboard -->
                    <div class="card" style="margin-top: 20px;">
                        <h3>🚨 Recent Critical Alerts</h3>
                        <div id="dashboardRecentAlerts" style="margin-top: 15px;">
                            <p style="opacity: 0.5; font-size: 14px;">Loading latest alerts...</p>
                        </div>
                        <div class="alert-btn-wrapper" style="margin-top: 25px; text-align: left; position: relative; z-index: 10;">
                            <button id="mainShowAlertsBtn" 
                                    onclick="window.switchTab('alerts')" 
                                    style="background: rgba(74, 181, 196, 0.2); color: #4ab5c4; border: 1px solid rgba(74, 181, 196, 0.5); padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; position: relative; z-index: 11;">
                                Show All Alerts 
                                <span style="font-size: 18px; line-height: 1;">→</span>
                            </button>
                        </div>
                 </div>
                    

                </div>
            </div>

            <!-- Sensors Section -->
            <div id="sensors" class="content-section">
                <div class="card">
                    <h3>📡 Sensor Status</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; color: rgba(255,255,255,0.8);">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                <th style="padding: 15px;">ID</th>
                                <th style="padding: 15px;">Location</th>
                                <th style="padding: 15px;">Status</th>
                                <th style="padding: 15px;">Battery</th>
                                <th style="padding: 15px;">Last Ping</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 15px;">SNS-001</td>
                                <td style="padding: 15px;">Nellimala</td>
                                <td style="padding: 15px;"><span class="safe-text">Active</span></td>
                                <td style="padding: 15px;">98%</td>
                                <td style="padding: 15px;">Just now</td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 15px;">SNS-002</td>
                                <td style="padding: 15px;">Churakullam</td>
                                <td style="padding: 15px;"><span class="danger-text">Offline</span></td>
                                <td style="padding: 15px;">0%</td>
                                <td style="padding: 15px;">2 hrs ago</td>
                            </tr>
                             <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 15px;">SNS-003</td>
                                <td style="padding: 15px;">Kakkikavala</td>
                                <td style="padding: 15px;"><span class="warning-text">Maintenance</span></td>
                                <td style="padding: 15px;">45%</td>
                                <td style="padding: 15px;">5 mins ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

             <!-- Alerts Section -->
            <div id="alerts" class="content-section">
                <div class="card">
                    <h3>🚨 System Alerts</h3>
                     <div style="margin-top: 20px;">
                        <div style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; padding: 15px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                            <strong style="color: #e74c3c;">Critical Water Level</strong>
                            <p style="font-size: 14px; margin-top: 5px; opacity: 0.8;">South Reservoir exceeded 95% capacity. Automated drainage sequence initiated.</p>
                            <div style="font-size: 12px; opacity: 0.5; margin-top: 5px;">10:42 AM</div>
                        </div>
                        <div style="background: rgba(241, 196, 15, 0.1); border-left: 4px solid #f1c40f; padding: 15px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                            <strong style="color: #f1c40f;">Connection Unstable</strong>
                            <p style="font-size: 14px; margin-top: 5px; opacity: 0.8;">Sensor SNS-003 reporting intermittent signal loss.</p>
                             <div style="font-size: 12px; opacity: 0.5; margin-top: 5px;">09:15 AM</div>
                        </div>
                     </div>
                </div>
            </div>

             <!-- Map Section -->
            <div id="map" class="content-section">
                 <div class="card" style="height: 600px; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>🗺️ Live Sensor Network</h3>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="mapSetView(10.8505, 76.2711)" style="padding: 5px 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 4px; cursor: pointer;">Kerala</button>
                            <button onclick="mapSetView(0,0,2)" style="padding: 5px 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 4px; cursor: pointer;">World</button>
                        </div>
                    </div>
                    <div id="leaflet-map" style="width: 100%; flex: 1; border-radius: 12px; z-index: 1;"></div>
                </div>
            </div>

             <!-- Evacuation Section -->
            <div id="evacuation" class="content-section">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>📍 Evacuation Points</h3>
                        <button style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: background 0.3s; position: relative; z-index: 10;" onclick="window.openAddModal()">+ Add Point</button>
                    </div>
                    <p style="opacity: 0.7; margin-bottom: 20px;">Admins can manage and update evacuation points dynamically based on flood severity.</p>
                    
                    <div id="evacuationList" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- Dynamic Content Loaded via JS -->
                    </div>
                </div>
            </div>

            <!-- Modal for Evacuation Points -->
            <div class="modal-overlay" id="evacuationModal">
                <div class="modal">
                    <h2 id="modalTitle">Add Evacuation Point</h2>
                    <form id="evacuationForm" onsubmit="saveEvacuationPoint(event)">
                        <input type="hidden" id="pointId" name="id">
                        <div class="form-group">
                            <label>Location Name</label>
                            <input type="text" id="pointName" name="name" required placeholder="e.g. City Hall">
                        </div>
                        <div class="form-group">
                            <label>Area / Address</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="pointLocation" name="location" required placeholder="e.g. Downtown" style="flex: 1;">
                                <button type="button" onclick="window.autoLocate()" style="padding: 0 15px; background: rgba(74, 181, 196, 0.2); border: 1px solid #4ab5c4; color: #4ab5c4; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; white-space: nowrap; transition: all 0.3s;" id="autoLocateBtn">📍 Auto-Locate</button>
                            </div>
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex:1;">
                                <label>Latitude</label>
                                <input type="number" step="any" id="pointLat" name="latitude" placeholder="10.8505">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Longitude</label>
                                <input type="number" step="any" id="pointLng" name="longitude" placeholder="76.2711">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Capacity (Persons)</label>
                            <input type="number" id="pointCapacity" name="capacity" required min="1" step="1" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 10;">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="pointStatus" name="status">
                                <option value="Available">Available</option>
                                <option value="Full">Full</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assigned Sensor</label>
                            <input type="text" id="pointSensor" name="sensor" placeholder="e.g. SNS-001">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn-save">Save Point</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports" class="content-section">
                <!-- Filter Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: rgba(255,255,255,0.05); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                    <h2 style="font-size: 20px; font-weight: 600; color: #fff;">Analytics Overview</h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="font-size: 14px; opacity: 0.7;">Time Range:</span>
                        <select id="reportTimeRange" onchange="renderReportCharts()" style="padding: 8px 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.2); outline: none;">
                            <option value="24h">Last 24 Hours</option>
                            <option value="7d">Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                        </select>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                     <div class="status-card">
                         <div class="status-label">Total Alerts</div>
                         <div id="statTotalAlerts" class="status-value warning-text">14</div>
                     </div>
                     <div class="status-card">
                         <div class="status-label">Flood Events</div>
                         <div id="statFloodEvents" class="status-value danger-text">3</div>
                     </div>
                     <div class="status-card">
                         <div class="status-label">Safe Recoveries</div>
                         <div id="statSafeRecoveries" class="status-value safe-text">98%</div>
                     </div>
                </div>

                <!-- Charts Area -->
                <div class="dashboard-grid">
                     <div class="card">
                        <h3>📊 Water Level Trends (System Average)</h3>
                        <div class="chart-container">
                            <canvas id="floodTrendChart"></canvas>
                        </div>
                     </div>
                     <div class="card">
                        <h3>🚨 Alert Severity Distribution</h3>
                        <div class="chart-container">
                            <canvas id="alertFreqChart"></canvas>
                        </div>
                     </div>
                </div>

                <!-- Event History Log -->
                <div class="card" style="margin-bottom: 25px;">
                    <h3>📋 Detailed Event History</h3>
                    <div style="overflow-x: auto; max-height: 300px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; color: rgba(255,255,255,0.9);">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding: 12px; position: sticky; top: 0; background: #1a1a2e;">Time</th>
                                    <th style="padding: 12px; position: sticky; top: 0; background: #1a1a2e;">Event</th>
                                    <th style="padding: 12px; position: sticky; top: 0; background: #1a1a2e;">Location</th>
                                    <th style="padding: 12px; position: sticky; top: 0; background: #1a1a2e;">Severity</th>
                                    <th style="padding: 12px; position: sticky; top: 0; background: #1a1a2e;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="eventLogBody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Generated Reports Table -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                         <h3>📑 Recent Generated Reports</h3>
                         <div style="position: relative; z-index: 100;">
                             <button type="button" 
                                     id="finalExportBtnCSV"
                                     onclick="window.aquaSafeExportCSV()" 
                                     style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 700; cursor: pointer; position: relative; z-index: 101; transition: all 0.3s; box-shadow: 0 4px 15px rgba(74, 181, 196, 0.4);">
                                 Export All (CSV)
                             </button>
                         </div>
                    </div>
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 20px; color: rgba(255,255,255,0.9);">
                        <thead>
                            <tr>
                                <th style="padding: 12px; text-align: left; opacity: 0.6; font-weight: 500;">Date</th>
                                <th style="padding: 12px; text-align: left; opacity: 0.6; font-weight: 500;">Report Type</th>
                                <th style="padding: 12px; text-align: left; opacity: 0.6; font-weight: 500;">Status</th>
                                <th style="padding: 12px; text-align: right; opacity: 0.6; font-weight: 500;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background: rgba(255,255,255,0.03);">
                                <td style="padding: 15px; border-radius: 10px 0 0 10px;">Dec 19, 2025</td>
                                <td style="padding: 15px;">Daily Operations Summary</td>
                                <td style="padding: 15px;"><span style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; padding: 4px 10px; border-radius: 20px; font-size: 13px;">Completed</span></td>
                                <td style="padding: 15px; text-align: right; border-radius: 0 10px 10px 0;">
                                    <div style="position: relative; z-index: 100;">
                                        <button type="button" 
                                                onclick="window.aquaSafeDownloadPDF('Daily Operations', this)" 
                                                style="background: rgba(74, 181, 196, 0.15); border: 1px solid #4ab5c4; color: #4ab5c4; padding: 6px 15px; border-radius: 8px; font-weight: 700; cursor: pointer; position: relative; z-index: 101; transition: all 0.3s; font-size: 13px;">
                                            Download PDF
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr style="background: rgba(255,255,255,0.03);">
                                <td style="padding: 15px; border-radius: 10px 0 0 10px;">Dec 18, 2025</td>
                                <td style="padding: 15px;">Critical Incident Log - North Zone</td>
                                <td style="padding: 15px;"><span style="background: rgba(241, 196, 15, 0.15); color: #f1c40f; padding: 4px 10px; border-radius: 20px; font-size: 13px;">Review Needed</span></td>
                                <td style="padding: 15px; text-align: right; border-radius: 0 10px 10px 0;">
                                    <div style="position: relative; z-index: 100;">
                                        <button type="button" 
                                                onclick="window.aquaSafeDownloadPDF('Incident Log', this)" 
                                                style="background: rgba(74, 181, 196, 0.15); border: 1px solid #4ab5c4; color: #4ab5c4; padding: 6px 15px; border-radius: 8px; font-weight: 700; cursor: pointer; position: relative; z-index: 101; transition: all 0.3s; font-size: 13px;">
                                            Download PDF
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Help Desk Section -->
            <div id="helpdesk" class="content-section">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>🆘 User Requests / Help Desk</h3>
                        <div id="helpdesk-stats">
                             <span style="background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 12px; font-size: 13px; margin-right: 10px;" id="pendingCount">Pending: 0</span>
                             <span style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; padding: 5px 10px; border-radius: 12px; font-size: 13px;" id="resolvedCount">Resolved: 0</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;" id="adminHelpdeskList">
                        <div style="text-align:center; padding:30px; opacity:0.5;">Loading user requests...</div>
                    </div>
                </div>
            </div>

            <!-- User Management Section -->
            <div id="users" class="content-section">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>👥 User Management</h3>
                        <div style="font-size: 14px; opacity: 0.7;">Total Users: <?php echo count($all_users); ?></div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; color: rgba(255,255,255,0.8);">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding: 15px;">Name</th>
                                    <th style="padding: 15px;">Email</th>
                                    <th style="padding: 15px;">Current Role</th>
                                    <th style="padding: 15px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_users as $u): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 15px;" data-label="Name"><?php echo htmlspecialchars((string)$u['name']); ?></td>
                                    <td style="padding: 15px;" data-label="Email"><?php echo htmlspecialchars((string)$u['email']); ?></td>
                                    <td style="padding: 15px;" data-label="Role">
                                        <span class="<?php echo ($u['user_role'] === 'admin' || $u['user_role'] === 'administrator') ? 'danger-text' : 'info-text'; ?>" style="font-weight: 600;">
                                            <?php 
                                                $display_role = !empty($u['user_role']) ? ucfirst($u['user_role']) : 'Not Set';
                                                if ($display_role === 'Administrator') echo 'Administrator';
                                                else echo $display_role;
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: right;" data-label="Action">
                                        <?php if($u['email'] !== $user_email): ?>
                                            <?php 
                                                $role = strtolower(trim((string)$u['user_role']));
                                                $is_admin = ($role === 'admin' || $role === 'administrator');
                                            ?>
                                            <?php if($is_admin): ?>
                                                <button type="button" class="role-update-btn" data-id="<?php echo $u['id']; ?>" data-role="user" style="padding: 8px 16px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 6px; cursor: pointer; position: relative; z-index: 10;">Demote to User</button>
                                            <?php else: ?>
                                                <button type="button" class="role-update-btn" data-id="<?php echo $u['id']; ?>" data-role="administrator" style="padding: 8px 16px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 6px; cursor: pointer; position: relative; z-index: 10;">Promote to Admin</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="opacity: 0.5; font-size: 12px; font-style: italic;">(You)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notification Control Section -->
            <div id="notifications" class="content-section">
                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <h3>🔔 Notification Control</h3>
                    <p style="opacity: 0.7; margin-bottom: 30px;">Admins can control how and when alerts are triggered.</p>

                    <!-- Master Switch -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 20px;">
                        <div>
                            <strong style="display: block; font-size: 16px;">Master Alert System</strong>
                            <span style="font-size: 13px; opacity: 0.6;">Toggle all outgoing notifications</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="masterToggle" onchange="updateMasterToggle()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- Thresholds -->
                        <div style="padding: 20px; background: rgba(255,255,255,0.05); border-radius: 12px;">
                            <h4 style="margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Severity Thresholds</h4>
                            
                            <div style="margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <label>Warning Level</label>
                                    <span style="color: #f1c40f;" id="warningVal">75%</span>
                                </div>
                                <input type="range" id="warningSlider" min="0" max="100" value="75" style="width: 100%; height: 6px; background: #ddd; border-radius: 5px; outline: none; opacity: 0.7;" oninput="document.getElementById('warningVal').innerText = this.value + '%'" onchange="updateThresholds()">
                            </div>
                             <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <label>Critical Level</label>
                                    <span style="color: #e74c3c;" id="criticalVal">90%</span>
                                </div>
                                <input type="range" id="criticalSlider" min="0" max="100" value="90" style="width: 100%; height: 6px; background: #ddd; border-radius: 5px; outline: none; opacity: 0.7;" oninput="document.getElementById('criticalVal').innerText = this.value + '%'" onchange="updateThresholds()">
                            </div>
                        </div>

                        <!-- Channels -->
                        <div style="padding: 20px; background: rgba(255,255,255,0.05); border-radius: 12px;">
                            <h4 style="margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Delivery Channels</h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label for="sms_ch" style="cursor: pointer;">SMS Alerts</label>
                                    <label class="switch small">
                                        <input type="checkbox" id="sms_ch" onchange="updateChannels()">
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label for="email_ch" style="cursor: pointer;">Email Notifications</label>
                                    <label class="switch small">
                                        <input type="checkbox" id="email_ch" onchange="updateChannels()">
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label for="app_ch" style="cursor: pointer;">In-App Push</label>
                                    <label class="switch small">
                                        <input type="checkbox" id="app_ch" onchange="updateChannels()">
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label for="public_ch" style="cursor: pointer;">Public Sirens</label>
                                    <label class="switch small">
                                        <input type="checkbox" id="public_ch" onchange="updateChannels()">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manual Broadcast Section (NEW) -->
                    <div style="margin-top: 20px; padding: 20px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); border-radius: 12px;">
                        <h4 style="color: #e74c3c; margin-bottom: 15px;">📢 Manual Emergency Broadcast</h4>
                        <div style="margin-bottom: 15px;">
                            <textarea id="broadcastMessage" placeholder="Type emergency message here (e.g., 'Flash Flood Warning for Downtown Area')" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; resize: vertical; min-height: 80px;"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                             <select id="broadcastArea" style="padding: 10px; border-radius: 8px; background: #1e2029; color: white; border: 1px solid rgba(255,255,255,0.1); flex: 1;">
                                <option value="System Wide">🌍 All Areas (System Wide)</option>
                                <option value="Central City">🏙️ Central City</option>
                                <option value="North District">🏗️ North District</option>
                                <option value="South Reservoir">🌊 South Reservoir</option>
                                <option value="West Bank">🏖️ West Bank</option>
                                <option value="East Valley">🏘️ East Valley</option>
                            </select>
                             <select id="broadcastSeverity" style="padding: 10px; border-radius: 8px; background: #1e2029; color: white; border: 1px solid rgba(255,255,255,0.1);">
                                <option value="Info">ℹ️ Info</option>
                                <option value="Warning">⚠️ Warning</option>
                                <option value="Critical">🚨 Critical</option>
                            </select>
                            <button onclick="broadcastAlert()" style="padding: 10px 20px; background: #e74c3c; border: none; color: white; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s; white-space: nowrap;">Send Broadcast</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="settings" class="content-section">
                 <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="card">
                        <h3>⚙️ System Settings</h3>
                        <div style="margin-top: 20px;">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-size: 14px;">Notification Email</label>
                                <input type="email" value="admin@aquasafe.com" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-size: 14px;">Refresh Rate (seconds)</label>
                                <input type="number" value="2" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                            </div>
                             <button style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">Save Changes</button>
                        </div>
                    </div>
                    <div class="card">
                         <h3>👤 Admin Profile</h3>
                          <div style="display: flex; align-items: center; gap: 15px; margin-top: 20px;">
                            <div style="width: 60px; height: 60px; background: #4ab5c4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">A</div>
                            <div>
                                <div style="font-weight: 600;">Super Administrator</div>
                                <div style="font-size: 13px; opacity: 0.6;">Access Level: Root</div>
                            </div>
                          </div>
                    </div>
                 </div>
            </div>

        </div>
    </div>

    <script>
        // MOVED TO PRIORITY BRIDGE AT BODY START
        
        window.toggleSidebar = function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        };

        // Primary navigation function
        // --- GLOBAL UTILITIES ---
        function log(msg) {
            console.log('[AquaSafe]', msg);
        }

        // Custom Notification System
        window.showNotification = function(message, type = 'success') {
            const modal = document.getElementById('customNotificationModal');
            const icon = document.getElementById('notificationIcon');
            const title = document.getElementById('notificationTitle');
            const msg = document.getElementById('notificationMessage');
            const modalBox = modal.querySelector('div');
            
            // Set content
            msg.textContent = message;
            
            // Set style based on type
            if (type === 'success') {
                icon.textContent = '✅';
                title.textContent = 'Success';
                title.style.color = '#2ecc71';
                modalBox.style.borderColor = '#2ecc71';
                modalBox.style.boxShadow = '0 0 40px rgba(46,204,113,0.4)';
            } else if (type === 'error') {
                icon.textContent = '❌';
                title.textContent = 'Error';
                title.style.color = '#e74c3c';
                modalBox.style.borderColor = '#e74c3c';
                modalBox.style.boxShadow = '0 0 40px rgba(231,76,60,0.4)';
            } else if (type === 'warning') {
                icon.textContent = '⚠️';
                title.textContent = 'Warning';
                title.style.color = '#f1c40f';
                modalBox.style.borderColor = '#f1c40f';
                modalBox.style.boxShadow = '0 0 40px rgba(241,196,15,0.4)';
            } else if (type === 'info') {
                icon.textContent = '📡';
                title.textContent = 'Information';
                title.style.color = '#4ab5c4';
                modalBox.style.borderColor = '#4ab5c4';
                modalBox.style.boxShadow = '0 0 40px rgba(74,181,196,0.4)';
            }
            
            modal.style.display = 'flex';
        };

        window.closeNotification = function() {
            document.getElementById('customNotificationModal').style.display = 'none';
        };

        window.showConfirm = function(message, onConfirm, onCancel) {
            const modal = document.getElementById('customConfirmModal');
            const msg = document.getElementById('confirmMessage');
            const confirmBtn = document.getElementById('confirmOkBtn');
            const cancelBtn = document.getElementById('confirmCancelBtn');
            
            msg.textContent = message;
            modal.style.display = 'flex';
            
            // Remove old listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            const newCancelBtn = cancelBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            
            // Add new listeners
            newConfirmBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                if (onConfirm) onConfirm();
            });
            
            newCancelBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                if (onCancel) onCancel();
            });
        };

        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
            return false;
        };

        async function fetchWithTimeout(resource, options = {}) {
            const { timeout = 15000 } = options;
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);
            const response = await fetch(resource, { ...options, signal: controller.signal });
            clearTimeout(id);
            return response;
        }

        // --- EVENT DELEGATION (Robust Interaction) ---
        document.addEventListener('click', function(e) {
            // Reply Button
            const replyBtn = e.target.closest('.btn-reply');
            if (replyBtn) {
                const id = replyBtn.dataset.id;
                console.log("Delegated Click: Reply", id);
                if(window.openReplyModal) window.openReplyModal(id);
                else alert("Error: Reply function not ready.");
            }

            // Resolve Button
            const resolveBtn = e.target.closest('.btn-resolve');
            if (resolveBtn) {
                const id = resolveBtn.dataset.id;
                console.log("Delegated Click: Resolve", id);
                if(window.resolveRequest) window.resolveRequest(id);
                else alert("Error: Resolve function not ready.");
            }
        });

        // --- CORE HELP DESK ACTIONS (GLOBAL START) ---
        window.openReplyModal = function(id) {
            const modal = document.getElementById('adminReplyModal');
            if(!modal) {
                window.showNotification('Modal not found. Please refresh the page.', 'error');
                return;
            }
            document.getElementById('currentReplyId').value = id;
            document.getElementById('adminReplyText').value = '';
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('adminReplyText').focus(), 100);
        };

        window.closeReplyModal = function() {
            const modal = document.getElementById('adminReplyModal');
            if(modal) modal.style.display = 'none';
        };

        window.submitAdminReply = async function() {
            const id = document.getElementById('currentReplyId').value;
            const reply = document.getElementById('adminReplyText').value;
            if(!reply.trim()) {
                window.showNotification('Please enter a reply before sending.', 'warning');
                return;
            }

            const btn = document.getElementById('submitReplyBtn');
            const originalText = btn ? btn.innerText : "Send";
            if(btn) { btn.innerText = "⏳ Sending..."; btn.disabled = true; }

            const formData = new FormData();
            formData.append('action', 'reply');
            formData.append('id', id);
            formData.append('reply', reply);

            try {
                const res = await fetchWithTimeout('manage_helpdesk.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    window.closeReplyModal();
                    window.showNotification('Reply sent successfully! The user will see your message on their dashboard.', 'success');
                    window.fetchHelpdeskRequests();
                } else {
                    window.showNotification('Error: ' + json.message, 'error');
                }
            } catch (err) {
                window.showNotification('Connectivity issue. Please check your connection and try again.', 'error');
            } finally {
                if(btn) { btn.innerText = originalText; btn.disabled = false; }
            }
        };

        window.resolveRequest = async function(id) {
            window.showConfirm(
                'Are you sure you want to mark this request as resolved? This action will update the status and notify the user.',
                async function() {
                    const formData = new FormData();
                    formData.append('action', 'resolve');
                    formData.append('id', id);

                    try {
                        const res = await fetchWithTimeout('manage_helpdesk.php', { method: 'POST', body: formData });
                        const json = await res.json();
                        
                        if (json.status === 'success') {
                            window.showNotification('Request marked as resolved successfully!', 'success');
                            window.fetchHelpdeskRequests();
                        } else {
                            window.showNotification('Error: ' + json.message, 'error');
                        }
                    } catch (err) {
                        window.showNotification('Network error. Please try again.', 'error');
                    }
                }
            );
        };

        // --- HELP DESK ADMIN LOGIC (PRE-DEFINED) ---
        window.fetchHelpdeskRequests = async function() {
            const listEl = document.getElementById('adminHelpdeskList');
            if(!listEl) return;

            if(listEl.innerHTML.includes('Loading') || listEl.innerHTML.includes('found') || listEl.innerHTML === '') {
                listEl.innerHTML = '<div style="text-align:center; padding:30px; opacity:0.8; color:var(--info);">📡 Fetching user requests... (Please wait)</div>';
            }

            try {
                const res = await fetchWithTimeout('manage_helpdesk.php?action=fetch_all', { timeout: 15000 });
                const json = await res.json();
                
                if(json.status === 'success') {
                    let html = '';
                    let pending = 0;
                    let resolved = 0;

                    if(json.data.length > 0) {
                        json.data.forEach(req => {
                            if(req.status === 'Resolved') resolved++; else pending++;
                            const borderColor = req.status === 'Resolved' ? '#2ecc71' : (req.status === 'Pending' ? '#f1c40f' : '#4ab5c4');
                            const statusLabel = req.status;
                            const isResolved = req.status === 'Resolved';
                            html += `
                                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border-left: 4px solid ${borderColor}; margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong style="color: #fff;">${req.user_name}</strong>
                                            <span style="font-size: 11px; opacity: 0.5; margin-left: 8px;">(${req.user_email})</span>
                                        </div>
                                        <span style="font-size: 11px; opacity: 0.6;">${req.created_at}</span>
                                    </div>
                                    <div style="margin: 10px 0; color: #4ab5c4; font-weight: 600;">Subject: ${req.title}</div>
                                    <p style="margin-bottom: 15px; opacity: 0.8; font-size: 14px;">"${req.details}"</p>
                                    ${req.admin_reply ? `
                                        <div style="background: rgba(74, 181, 196, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed rgba(74, 181, 196, 0.3);">
                                            <div style="font-size: 11px; font-weight: 800; color: #4ab5c4; margin-bottom: 5px;">YOUR REPLY:</div>
                                            <div style="font-size: 13px;">${req.admin_reply}</div>
                                        </div>
                                    ` : ''}
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        ${!isResolved ? `
                                            <button class="btn-reply" data-id="${req.id}" style="padding: 8px 18px; background: rgba(74, 181, 196, 0.2); border: 2px solid #4ab5c4; color: #4ab5c4; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700; transition:all 0.2s; position:relative; z-index:100;">Reply</button>
                                            <button class="btn-resolve" data-id="${req.id}" style="padding: 8px 18px; background: rgba(46, 204, 113, 0.2); border: 2px solid #2ecc71; color: #2ecc71; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700; transition:all 0.2s; position:relative; z-index:100;">Mark Resolved</button>
                                        ` : `
                                            <span style="color: #2ecc71; font-size: 13px; font-weight: 800; display:flex; align-items:center; gap:6px;"><i class="fas fa-check-circle"></i> RESOLVED</span>
                                        `}
                                        <span style="font-size: 12px; color: ${borderColor}; margin-left: auto; font-weight: 800; text-transform:uppercase; letter-spacing:1px;">${statusLabel}</span>
                                    </div>
                                </div>
                            `;
                        });
                        listEl.innerHTML = html;
                    } else {
                        listEl.innerHTML = '<div style="text-align:center; padding:50px; opacity:0.3;">No help requests found in the system.</div>';
                    }

                    const pCount = document.getElementById('pendingCount');
                    const rCount = document.getElementById('resolvedCount');
                    if(pCount) pCount.innerText = 'Pending: ' + pending;
                    if(rCount) rCount.innerText = 'Resolved: ' + resolved;

                    const hBadge = document.getElementById('helpdeskBadge');
                    if (hBadge) {
                        if (pending > 0 && !document.getElementById('helpdesk').classList.contains('active')) {
                            hBadge.innerText = pending;
                            hBadge.style.display = 'block';
                        }
                    }
                } else {
                    listEl.innerHTML = `<div style="text-align:center; padding:50px; color:#e74c3c;">Error: ${json.message}</div>`;
                }
            } catch (err) {
                console.error("Helpdesk fetch error:", err);
                listEl.innerHTML = `<div style="text-align:center; padding:30px; color:#e74c3c;">
                    <p>Failed to connect to Help Desk service.</p>
                    <small style="opacity:0.5;">${err.name === 'AbortError' ? 'Request Timed Out' : err.message}</small>
                    <br><br>
                    <button onclick="fetchHelpdeskRequests()" style="padding:8px 20px; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:white; border-radius:8px; cursor:pointer;">Retry</button>
                </div>`;
            }
        };

        // Consolidating logic
        // MOVED TO TOP OF SCRIPT

        window.switchTab = function(tabId, element) {
            console.log("[AquaSafe] Navigation Engine: Targeting", tabId);
            
            try {
                // 1. Hide all sections & activate target
                document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
                const target = document.getElementById(tabId);
                if (target) {
                    target.classList.add('active');
                } else {
                    log("Navigation Error: Section '" + tabId + "' not found!");
                    return;
                }
                
                // 2. Clear all sidebar active states
                document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
                
                // 3. Handle Sidebar Highlighting
                let activeLink = element;
                if (!activeLink) {
                    // Use new IDs for robust matching
                    activeLink = document.getElementById('nav-' + tabId);
                }
                if (activeLink) activeLink.classList.add('active');

                // 4. Mobile Handling
                if (window.innerWidth <= 1024) {
                    const sidebar = document.querySelector('.sidebar');
                    if(sidebar && sidebar.classList.contains('active')) toggleSidebar();
                }

                // 5. Scroll & Title Updates
                window.scrollTo({ top: 0, behavior: 'auto' });
                
                const titles = {
                    'dashboard': 'Admin Dashboard', 'sensors': 'Sensor Management', 'alerts': 'System Alerts',
                    'map': 'Live Map', 'evacuation': 'Evacuation Management', 'reports': 'Reports & Analytics',
                    'helpdesk': 'Help Desk', 'notifications': 'Notification Control', 'users': 'User Management', 'settings': 'System Settings'
                };
                const titleEl = document.getElementById('pageTitle');
                if (titleEl) titleEl.innerText = titles[tabId] || 'Admin Dashboard';

                // 6. Data Refresh Logic
                if(tabId === 'map') initMap();
                if(tabId === 'reports') renderReportCharts();
                if(tabId === 'evacuation') fetchEvacuationPoints();
                if(tabId === 'alerts') {
                    fetchSystemAlerts();
                }
                if(tabId === 'sensors') fetchSensorStatus();
                if(tabId === 'helpdesk') {
                    fetchHelpdeskRequests();
                    const hb = document.getElementById('helpdeskBadge');
                    if(hb) hb.style.display = 'none';
                }
                if(tabId === 'dashboard') {
                    fetchSystemAlerts();
                    fetchSensorStatus();
                }
            } catch (err) {
                log("switchTab CRITICAL ERROR:", err);
            }
        };

        // Support for local switchTab() calls
        var switchTab = window.switchTab;

        // 1. GLOBAL STORE & DIAGNOSTICS
        var allEvacPoints = {}; 
        var waterChart; 
        var chartDataStore = {
            'South Reservoir': {
                labels: ['10:00', '10:05', '10:10', '10:15', '10:20', '10:25'],
                data: [45, 48, 52, 50, 55, 58]
            }
        };
        var currentArea = 'South Reservoir';
        var currentArea = 'South Reservoir';
        console.log("AquaSafe Admin JS Loading...");

        // 2. GLOBAL EVACUATION FUNCTIONS (Explicit window assignment)
        // 2. GLOBAL EVACUATION FUNCTIONS (Explicit window assignment)
        window.openAddModal = function() {
            console.log("Opening Add Modal");
            const modal = document.getElementById('evacuationModal');
            if(!modal) return alert("System Error: Modal Overlay not found in DOM!");
            
            document.getElementById('modalTitle').innerText = 'Add Evacuation Point';
            document.getElementById('pointId').value = ''; 
            document.getElementById('evacuationForm').reset();
            modal.classList.add('active');
        };

        window.openEditModal = function(id) {
            console.log("openEditModal called for ID:", id);
            
            const pt = allEvacPoints[id] || allEvacPoints[String(id)] || allEvacPoints[parseInt(id)];

            if(!pt) {
                console.log("Lookup failed for ID:", id, "Cache content:", allEvacPoints);
                return alert("Critical Error: Point data not found in browser memory for #" + id);
            }

            console.log("Editing Point:", pt.name);
            const modal = document.getElementById('evacuationModal');
            if(!modal) return alert("System Error: Modal Overlay not found!");

            document.getElementById('modalTitle').innerText = 'Edit Evacuation Point';
            document.getElementById('pointId').value = pt.id;
            document.getElementById('pointName').value = pt.name || '';
            document.getElementById('pointLocation').value = pt.location || '';
            document.getElementById('pointLat').value = pt.latitude || '';
            document.getElementById('pointLng').value = pt.longitude || '';
            document.getElementById('pointCapacity').value = pt.capacity || '';
            document.getElementById('pointStatus').value = pt.status || 'Available';
            document.getElementById('pointSensor').value = pt.assigned_sensor || '';
            
            modal.classList.add('active');
            log("Modal should now be visible (class .active added)");
        };

        window.closeModal = function() {
            const modal = document.getElementById('evacuationModal');
            if(modal) modal.classList.remove('active');
        };

        window.saveEvacuationPoint = async function(e) {
            if(e) e.preventDefault();
            const id = document.getElementById('pointId').value;
            const action = id ? 'update' : 'add';
            log("Save triggered:", { action, id });

            const form = document.getElementById('evacuationForm');
            const formData = new FormData(form);
            formData.append('action', action);
            if(id) formData.append('id', id);
            
            // Critical: Ensure status is captured from select
            formData.set('status', document.getElementById('pointStatus').value);

            try {
                const res = await fetch('manage_evacuation.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.status === 'success') {
                    alert("SUCCESS: " + (data.message || "Data saved."));
                    closeModal();
                    // Refreshes
                    await fetchEvacuationPoints();
                    if(typeof window.refreshMapMarkers === 'function') {
                        await window.refreshMapMarkers();
                    }
                } else {
                    alert("SERVER ERROR: " + data.message);
                }
            } catch(err) {
                alert("NETWORK ERROR: " + err.message);
            }
        };

        window.deletePoint = async function(id) {
            if(!confirm("Permanently remove this location?")) return;
            log("Delete triggered for ID:", id);

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const res = await fetch('manage_evacuation.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.status === 'success') {
                    alert("Location deleted.");
                    await fetchEvacuationPoints();
                    if(typeof window.refreshMapMarkers === 'function') {
                        await window.refreshMapMarkers();
                    }
                } else {
                    alert("Delete failed: " + data.message);
                }
            } catch(err) {
                alert("Network error: " + err.message);
            }
        };

        window.fetchEvacuationPoints = async function() {
            const listEl = document.getElementById('evacuationList');
            if(!listEl) return;
            listEl.innerHTML = '<div style="color:white; text-align:center; padding: 20px;">Refreshing list...</div>';

            try {
                const res = await fetch('manage_evacuation.php?action=fetch_all');
                const json = await res.json();
                listEl.innerHTML = '';
                allEvacPoints = {}; 

                if(json.data && json.data.length > 0) {
                    json.data.forEach(pt => {
                        allEvacPoints[pt.id] = pt;
                        const statusColor = pt.status === 'Available' ? 'safe-text' : (pt.status === 'Full' ? 'danger-text' : 'warning-text');
                        
                        const card = document.createElement('div');
                        card.style.cssText = "background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); position: relative; z-index: 10;";
                        card.innerHTML = `
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <strong style="font-size: 18px;">${pt.name}</strong>
                                <span class="${statusColor}" style="font-size: 14px; background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 20px;">${pt.status}</span>
                            </div>
                            <div style="margin-bottom: 10px; font-size: 14px; opacity: 0.8;">
                                <div>Location: <strong>${pt.location}</strong></div>
                                <div>Capacity: <strong>${pt.capacity}</strong></div>
                                <div>Assigned Sensor: <strong>${pt.assigned_sensor || 'N/A'}</strong></div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button onclick="window.openEditModal('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; cursor: pointer; font-weight: 500; position: relative; z-index: 11;">Edit</button>
                                <button onclick="window.deletePoint('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 8px; cursor: pointer; font-weight: 500; position: relative; z-index: 11;">Remove</button>
                            </div>
                        `;
                        listEl.appendChild(card);
                    });
                }
                
                const addCard = document.createElement('div');
                addCard.style.cssText = "background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; border: 2px dashed rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; min-height: 180px; transition: all 0.3s;";
                addCard.innerHTML = '<div style="text-align: center; opacity: 0.6;"><div style="font-size: 30px; margin-bottom: 5px;">+</div><div style="font-weight: 500;">Add New Location</div></div>';
                addCard.onclick = window.openAddModal;
                addCard.onmouseover = function(){ this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(74, 181, 196, 0.4)'; };
                addCard.onmouseout = function(){ this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.1)'; };
                listEl.appendChild(addCard);

            } catch (err) {
                log("Fetch Error:", err);
                listEl.innerHTML = '<p style="color:#e74c3c; text-align:center;">Failed to load evacuation points.</p>';
            }
        };

        window.autoLocate = async function() {
            const address = document.getElementById('pointLocation').value;
            const btn = document.getElementById('autoLocateBtn');
            const latInput = document.getElementById('pointLat');
            const lngInput = document.getElementById('pointLng');

            if (!address || address.length < 3) {
                return alert("Please enter a more specific location address first.");
            }

            const originalText = btn.innerHTML;
            btn.innerHTML = "⏳ Searching...";
            btn.style.opacity = "0.7";
            btn.disabled = true;

            try {
                log("Requesting Server-Side Geocode for:", address);
                const formData = new FormData();
                formData.append('action', 'geocode');
                formData.append('address', address);

                const response = await fetch('manage_evacuation.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.status === 'success' && res.data) {
                    const result = res.data;
                    latInput.value = parseFloat(result.lat).toFixed(6);
                    lngInput.value = parseFloat(result.lon).toFixed(6);
                    log("Location Found via Proxy:", result.display_name);
                    
                    // Flash success
                    latInput.style.borderColor = "#4ab5c4";
                    lngInput.style.borderColor = "#4ab5c4";
                    setTimeout(() => {
                        latInput.style.borderColor = "";
                        lngInput.style.borderColor = "";
                    }, 2000);
                } else {
                    alert(res.message || "Could not find coordinates for this address. Try adding more details like a district name.");
                }
            } catch (err) {
                log("Geocoding Proxy Error:", err);
                alert("Search service currently unavailable. Please enter coordinates manually.");
            } finally {
                btn.innerHTML = originalText;
                btn.style.opacity = "1";
                btn.disabled = false;
            }
        };

        window.showConfirm = function(message, onConfirm) {
            const modal = document.getElementById('customConfirmModal');
            const msgEl = document.getElementById('confirmMessage');
            const okBtn = document.getElementById('confirmOkBtn');
            const cancelBtn = document.getElementById('confirmCancelBtn');

            if(!modal || !msgEl || !okBtn || !cancelBtn) {
                console.warn("Custom confirm modal elements missing, falling back to native confirm.");
                if(confirm(message)) onConfirm(); 
                return;
            }

            msgEl.innerText = message;
            modal.style.display = 'flex';

            // Clone buttons to clear previous event listeners
            const newOk = okBtn.cloneNode(true);
            const newCancel = cancelBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOk, okBtn);
            cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);

            newOk.addEventListener('click', function() {
                modal.style.display = 'none';
                if (typeof onConfirm === 'function') onConfirm();
            });

            newCancel.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        };

        window.deleteAlert = function(id) {
            console.log("Delete alert requested for ID:", id);
            
            window.showConfirm(
                "Are you sure you want to permanently DELETE this alert?",
                async function() {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    try {
                        const res = await fetch('manage_alerts.php', { method: 'POST', body: formData });
                        const json = await res.json();
                        if(json.status === 'success') {
                            if(typeof window.showNotification === 'function') {
                                window.showNotification("Alert deleted successfully", 'success');
                            } else {
                                alert("Alert deleted successfully");
                            }
                            fetchSystemAlerts(); // Refresh list
                        } else {
                            if(typeof window.showNotification === 'function') {
                                window.showNotification(json.message, 'error');
                            } else {
                                alert(json.message);
                            }
                        }
                    } catch(e) { 
                        console.error("Delete failed:", e);
                        if(typeof window.showNotification === 'function') {
                            window.showNotification("Delete Failed: " + e.message, 'error'); 
                        } else {
                            alert("Delete Failed: " + e.message);
                        }
                    }
                }
            );
        };

        window.fetchSystemAlerts = async function() {
            const container = document.querySelector('#alerts .card > div');
            const dashboardContainer = document.getElementById('dashboardRecentAlerts');
            const countEl = document.getElementById('activeAlertCount');
            const badgeEl = document.getElementById('alertBadge');
            if(!container && !dashboardContainer) return;

            try {
                const res = await fetch('manage_alerts.php?action=fetch_all');
                const json = await res.json();
                
                if(json.status === 'success') {
                    const alerts = json.data || [];
                    
                    // Update Count on Dashboard
                    if(countEl) countEl.innerText = alerts.length + " New";

                    // Update Sidebar Badge (Smart Logic)
                    if(badgeEl) {
                        const isAlertsActive = document.getElementById('alerts').classList.contains('active');
                        const lastSeenCount = parseInt(localStorage.getItem('seenAlertCount') || '0');
                        
                        if (isAlertsActive) {
                            // If user is currently viewing alerts, mark all as seen
                            localStorage.setItem('seenAlertCount', alerts.length);
                            badgeEl.style.display = 'none';
                        } else {
                            // Calculate new alerts since last visit
                            const newAlerts = alerts.length - lastSeenCount;
                            
                            if (newAlerts > 0) {
                                badgeEl.innerText = newAlerts; // Show only the NEW count
                                badgeEl.style.display = 'block';
                            } else {
                                badgeEl.style.display = 'none';
                            }
                        }
                    }
                    
                    // Update Main Alert List (Full View)
                    if(container) {
                        if(alerts.length > 0) {
                            let html = '';
                            alerts.forEach(alert => {
                                const bgCol = alert.severity === 'Critical' ? 'rgba(231, 76, 60, 0.1)' : (alert.severity === 'Warning' ? 'rgba(241, 196, 15, 0.1)' : 'rgba(74, 181, 196, 0.1)');
                                const borderCol = alert.severity === 'Critical' ? '#e74c3c' : (alert.severity === 'Warning' ? '#f1c40f' : '#4ab5c4');
                                const textCol = borderCol;
                                
                                html += `
                                    <div style="background: ${bgCol}; border-left: 4px solid ${borderCol}; padding: 15px; margin-bottom: 15px; border-radius: 0 8px 8px 0; display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <strong style="color: ${textCol};">${alert.severity} Alert</strong>
                                            <p style="font-size: 14px; margin-top: 5px; opacity: 0.8;">${alert.message}</p>
                                            <div style="font-size: 12px; opacity: 0.5; margin-top: 5px;">
                                                <i data-lucide="map-pin" style="width: 10px; height: 10px;"></i> ${alert.location || 'System Wide'} • 
                                                ${new Date(alert.timestamp).toLocaleTimeString()}
                                            </div>
                                        </div>
                                        <button onclick="deleteAlert(${alert.id})" style="background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.4); transition: color 0.2s;" title="Delete Alert">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<p style="text-align:center; opacity:0.5;">No active alerts at this time.</p>';
                        }
                    }

                    // Update Dashboard Summary (Top 3)
                    if(dashboardContainer) {
                        if(alerts.length > 0) {
                            let html = '';
                            alerts.slice(0, 3).forEach(alert => {
                                const dotCol = alert.severity === 'Critical' ? '#e74c3c' : '#f1c40f';
                                html += `
                                    <div style="display: flex; gap: 10px; margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;">
                                        <div style="width: 8px; height: 8px; background: ${dotCol}; border-radius: 50%; margin-top: 5px; flex-shrink: 0;"></div>
                                        <div>
                                            <div style="font-weight: 600;">${alert.message}</div>
                                            <div style="font-size: 11px; opacity: 0.5;">${new Date(alert.timestamp).toLocaleTimeString()}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            dashboardContainer.innerHTML = html;
                        } else {
                            dashboardContainer.innerHTML = '<p style="opacity: 0.5; font-size: 14px;">No recent alerts.</p>';
                        }
                    }
                }
            } catch (err) {
                log("Alert Fetch Error:", err);
            }
        };

        window.fetchSensorStatus = async function() {
            const tbody = document.querySelector('#sensors tbody');
            const zoneEl = document.getElementById('criticalZoneCount');
            if(!tbody && !zoneEl) return;

            try {
                const res = await fetch('manage_alerts.php?action=fetch_sensors');
                const json = await res.json();
                
                if(json.status === 'success') {
                    const sensors = json.data || [];
                    let criticalCount = 0;

                    if(tbody) tbody.innerHTML = '';
                    
                    sensors.forEach(s => {
                        const isCritical = (s.status === 'Offline' || s.status === 'Maintenance');
                        if(isCritical) criticalCount++;

                        if(tbody) {
                            const statusClass = s.status === 'Active' ? 'safe-text' : (s.status === 'Offline' ? 'danger-text' : 'warning-text');
                            const tr = document.createElement('tr');
                            tr.style.cssText = "border-bottom: 1px solid rgba(255,255,255,0.05);";
                            tr.innerHTML = `
                                <td style="padding: 15px;" data-label="ID">${s.sensor_id}</td>
                                <td style="padding: 15px;" data-label="Location">${s.location_name}</td>
                                <td style="padding: 15px;" data-label="Status"><span class="${statusClass}">${s.status}</span></td>
                                <td style="padding: 15px;" data-label="Battery">${s.battery_level}%</td>
                                <td style="padding: 15px;" data-label="Last Ping">${new Date(s.last_ping).toLocaleTimeString()}</td>
                            `;
                            tbody.appendChild(tr);
                        }
                    });

                    if(tbody) {
                        zoneEl.innerText = criticalCount + (criticalCount === 1 ? " Zone" : " Zones");
                        
                        // Calculate Overall Safety % (Ratio of Active sensors)
                        const safetyPercent = sensors.length > 0 ? Math.round(((sensors.length - criticalCount) / sensors.length) * 100) : 100;
                        const safetyEl = document.getElementById('overallSafetyValue');
                        if (safetyEl) {
                            safetyEl.innerText = safetyPercent + "%";
                            safetyEl.className = 'status-value ' + (safetyPercent > 90 ? 'safe-text' : (safetyPercent > 70 ? 'warning-text' : 'danger-text'));
                        }

                        // Update System Health Text
                        const healthEl = document.getElementById('systemHealthText');
                        if (healthEl) {
                            if (criticalCount === 0) {
                                healthEl.innerText = "Optimal";
                                healthEl.className = 'status-value safe-text';
                            } else if (criticalCount < sensors.length / 2) {
                                healthEl.innerText = "Degraded";
                                healthEl.className = 'status-value warning-text';
                            } else {
                                healthEl.innerText = "Critical";
                                healthEl.className = 'status-value danger-text';
                            }
                        }
                    }

                    // Dynamic Graph Area Support: Populate dropdown
                    const areaDropdown = document.getElementById('areaSelector');
                    if(areaDropdown && sensors.length > 0) {
                        const currentVal = areaDropdown.value;
                        const uniqueAreas = [...new Set(['South Reservoir', ...sensors.map(s => s.location_name)])].sort();
                        
                        // Only update DOM if the set of areas has changed to prevent flickering/focus loss
                        const existingOptions = Array.from(areaDropdown.options).map(o => o.value).sort();
                        const areasString = uniqueAreas.join('|');
                        const existingString = existingOptions.join('|');

                        if(areasString !== existingString) {
                            log("Updating Area Selector Options...");
                            let optionsHtml = '';
                            uniqueAreas.forEach(area => {
                                optionsHtml += `<option value="${area}">${area}</option>`;
                            });
                            areaDropdown.innerHTML = optionsHtml;
                            
                            // Restore value if it still exists, else default to South Reservoir
                            if (uniqueAreas.includes(currentVal)) {
                                areaDropdown.value = currentVal;
                            } else {
                                areaDropdown.value = 'South Reservoir';
                            }
                        }

                        // Init data for new areas if not exists
                        uniqueAreas.forEach(area => {
                            if(!chartDataStore[area]) {
                                chartDataStore[area] = {
                                    labels: Array.from({length:6}, (_,i) => new Date(Date.now() - (5-i)*300000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})),
                                    data: Array.from({length:6}, () => 40 + Math.random() * 20)
                                };
                            }
                        });
                    }
                }
            } catch (err) {
                log("Sensor Fetch Error:", err);
            }
        };

        window.switchAreaGraph = function(area) {
            if(!waterChart || !chartDataStore[area]) return;
            currentArea = area;
            
            // Update Title
            const titleEl = document.getElementById('currentGraphTitle');
            if(titleEl) titleEl.innerText = `📈 Real-time Water Levels - ${area}`;

            // Update Chart Data
            waterChart.data.labels = chartDataStore[area].labels;
            waterChart.data.datasets[0].data = chartDataStore[area].data;
            waterChart.update();
            log("Switched graph to area: " + area);
        };


        // Simplified global click tracking (Optional but helpful)
        document.addEventListener('click', function(e) {
            log("Global Interaction:", e.target.tagName, e.target.className);
        });


        // -----------------------------------------------------

        window.onerror = function(msg, url, line) {
            log("GLOBAL ERROR: " + msg + " at " + url + ":" + line);
            return false;
        };

        window.pingJS = function() {
            log("Ping triggered!");
        };

        // 2. Role Management
        window.updateRole = async function(userId, newRole) {
            log("updateRole clicked for ID: " + userId + " (Goal: " + newRole + ")");
            if(!confirm("Change user role?")) return;

            try {
                log("Sending request to update_role.php...");
                const response = await fetch('update_role.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, new_role: newRole })
                });
                
                log("Response status: " + response.status);
                const text = await response.text();
                log("Raw Result: " + text.substring(0, 50) + "...");
                
                const result = JSON.parse(text);
                if(result.success) {
                    log('Role updated successfully!');
                    window.location.hash = "users";
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                log("FETCH CATCH: " + e.message);
                alert('Connection failed: ' + e.message);
            }
        };

        // REMOVED DUPLICATE switchTab LOGIC - NOW AT TOP OF SCRIPT


        // 4. Map Logic (Leaflet)
        let map;
        let markersObj = {}; // Track markers

        function initMap() {
            if (map) {
                 refreshMapMarkers(); // Just refresh if already init
                 return;
            }
            
            setTimeout(() => {
                const mapEl = document.getElementById('leaflet-map');
                if(!mapEl) return;
                
                map = L.map('leaflet-map').setView([10.8505, 76.2711], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                refreshMapMarkers();
                
                map.invalidateSize();
            }, 300);
        }

        window.refreshMapMarkers = async function() {
            if(!map) return;
            
            try {
                // 1. Fetch Evacuation Points
                const evacPromise = fetch(`manage_evacuation.php?action=fetch_all&t=${Date.now()}`);
                // 2. Fetch Sensor Statuses (to overlay critical zone info)
                const sensorPromise = fetch(`manage_alerts.php?action=fetch_sensors&t=${Date.now()}`);

                const [evacRes, sensorRes] = await Promise.all([evacPromise, sensorPromise]);
                const evacJson = await evacRes.json();
                const sensorJson = await sensorRes.json();

                // Create Sensor Lookup Map
                const sensorMap = {};
                if(sensorJson.status === 'success' && sensorJson.data) {
                    sensorJson.data.forEach(s => {
                        sensorMap[s.sensor_id] = s.status; // e.g. "Active", "Offline", "Maintenance"
                    });
                }

                if(evacJson.data) {
                    // Clear existing markers
                    for(let id in markersObj) {
                        map.removeLayer(markersObj[id]);
                    }
                    markersObj = {};

                    const markerGroup = [];

                    evacJson.data.forEach(p => {
                        let lat = parseFloat(p.latitude);
                        let lng = parseFloat(p.longitude);

                        // Fallback & Randomization for overlapping points
                        if(isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                            lat = 10.8505 + (Math.random() - 0.5) * 0.1; 
                            lng = 76.2711 + (Math.random() - 0.5) * 0.1;
                        }

                        // Determine Status & Color
                        const assignedSensor = p.assigned_sensor;
                        const sensorStatus = assignedSensor ? (sensorMap[assignedSensor] || 'Unknown') : 'N/A';
                        
                        // Priority: Critical Sensor > Evacuation Status
                        let color = '#2ecc71'; // Default Safe (Green)
                        let statusText = `Evac Status: ${p.status}`;
                        let isCritical = false;

                        // Check Sensor Health (Critical Zone Logic)
                        // If sensor is Offline or Maintenance -> It's a Critical Zone
                        const isSensorCritical = (sensorStatus === 'Offline' || sensorStatus === 'Maintenance');
                        
                        if (isSensorCritical) {
                            color = '#e74c3c'; // Red for Critical
                            statusText += `<br><strong style="color:#e74c3c">⚠️ Critical Zone: Sensor ${sensorStatus}</strong>`;
                            isCritical = true;
                        } else {
                            // Normal Evacuation Status Logic
                            if (p.status === 'Full') color = '#f1c40f'; // Yellow
                            else if (p.status === 'Closed') color = '#95a5a6'; // Grey
                            
                            statusText += `<br>Sensor: ${sensorStatus}`;
                        }

                        const marker = L.circleMarker([lat, lng], {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.8,
                            radius: isCritical ? 14 : 10 // Larger radius for critical zones
                        }).addTo(map).bindPopup(`
                            <div style="font-family: 'Outfit', sans-serif;">
                                <strong style="font-size:14px; color:#4ab5c4;">${p.name}</strong><br>
                                <div style="margin-top:5px; font-size:12px;">
                                    ${statusText}<br>
                                    Cap: ${p.capacity}
                                </div>
                            </div>
                        `);
                        
                        markersObj[p.id] = marker;
                        markerGroup.push([lat, lng]);
                    });

                    // Auto-fit bounds if we have markers
                    if (markerGroup.length > 0) {
                        map.fitBounds(L.latLngBounds(markerGroup), { padding: [50, 50] });
                    }
                }
            } catch (e) {
                console.log("Map Marker Refresh Error:", e);
            }
        };

        window.mapSetView = function(lat, lng, zoom = 7) {
            if(map) map.setView([lat, lng], zoom);
        };

        // 5. Reports Logic (Chart.js)
        let floodChart, alertChart;
        window.renderReportCharts = function() {
            const ctx1 = document.getElementById('floodTrendChart');
            const ctx2 = document.getElementById('alertFreqChart');
            if (!ctx1 || !ctx2) return;

            if (floodChart) floodChart.destroy();
            if (alertChart) alertChart.destroy();

            const range = document.getElementById('reportTimeRange') ? document.getElementById('reportTimeRange').value : '24h';
            
            let labels, dataPoints, alertData;
            
            if (range === '24h') {
                labels = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '23:59'];
                dataPoints = [35, 38, 45, 50, 48, 42, 40];
                alertData = [2, 1, 4, 3, 2, 1, 1];
                // Update stats
                if(document.getElementById('statTotalAlerts')) {
                    document.getElementById('statTotalAlerts').innerText = "14";
                    document.getElementById('statFloodEvents').innerText = "3";
                    document.getElementById('statSafeRecoveries').innerText = "98%";
                }
            } else if (range === '7d') {
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                dataPoints = [40, 55, 45, 60, 65, 50, 55];
                alertData = [12, 18, 15, 22, 25, 14, 16];
                if(document.getElementById('statTotalAlerts')) {
                    document.getElementById('statTotalAlerts').innerText = "122";
                    document.getElementById('statFloodEvents').innerText = "15";
                    document.getElementById('statSafeRecoveries').innerText = "96%";
                }
            } else { // 30d
                labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                dataPoints = [45, 62, 58, 52];
                alertData = [85, 120, 95, 110];
                if(document.getElementById('statTotalAlerts')) {
                    document.getElementById('statTotalAlerts').innerText = "410";
                    document.getElementById('statFloodEvents').innerText = "42";
                    document.getElementById('statSafeRecoveries').innerText = "94%";
                }
            }

            // Update Event Log Table
            const eventBody = document.getElementById('eventLogBody');
            if (eventBody) {
                eventBody.innerHTML = ''; // Clear previous
                let events = [];
                
                if (range === '24h') {
                    events = [
                        { time: '20:15', event: 'Water Level Critical', location: 'South Reservoir', severity: 'Critical', status: 'Active' },
                        { time: '18:30', event: 'Sensor Malfunction', location: 'River A-2', severity: 'Warning', status: 'Resolved' },
                        { time: '14:45', event: 'Water Level High', location: 'North Dam', severity: 'Warning', status: 'Monitoring' },
                        { time: '09:20', event: 'Flow Rate Spike', location: 'Canal Zone', severity: 'Warning', status: 'Resolved' },
                        { time: '04:10', event: 'Water Level Critical', location: 'South Reservoir', severity: 'Critical', status: 'Resolved' }
                    ];
                } else if (range === '7d') {
                    events = [
                        { time: 'Mon 14:20', event: 'Flash Flood Warning', location: 'East Valley', severity: 'Critical', status: 'Resolved' },
                        { time: 'Sun 09:15', event: 'Water Level Critical', location: 'South Reservoir', severity: 'Critical', status: 'Active' },
                        { time: 'Sat 22:10', event: 'Dam Gate #3 Error', location: 'Main Dam', severity: 'Warning', status: 'In Progress' },
                        { time: 'Fri 18:45', event: 'High Rainfall Alert', location: 'All Zones', severity: 'Warning', status: 'Resolved' },
                        { time: 'Thu 11:30', event: 'Communication Loss', location: 'Sensor Node 4', severity: 'Critical', status: 'Fixed' }
                    ];
                } else {
                    events = [
                        { time: 'Dec 28', event: 'System Wide Test', location: 'All Sites', severity: 'Info', status: 'Completed' },
                        { time: 'Dec 25', event: 'Rapid Water Rise', location: 'West Bank', severity: 'Critical', status: 'Evacuated' },
                        { time: 'Dec 20', event: 'Sensor Battery Low', location: 'Node 12', severity: 'Warning', status: 'Replaced' },
                        { time: 'Dec 15', event: 'Flood Gate Opened', location: 'North Dam', severity: 'Info', status: 'Logged' },
                        { time: 'Dec 10', event: 'Preliminary Flood Alert', location: 'South Zone', severity: 'Warning', status: 'Dismissed' }
                    ];
                }

                events.forEach(ev => {
                    const sevColor = ev.severity === 'Critical' ? '#e74c3c' : (ev.severity === 'Warning' ? '#f1c40f' : '#3498db');
                    const row = `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 15px;">${ev.time}</td>
                            <td style="padding: 15px;">${ev.event}</td>
                            <td style="padding: 15px;">${ev.location}</td>
                            <td style="padding: 15px;"><span style="color: ${sevColor}; font-weight: 600;">${ev.severity}</span></td>
                            <td style="padding: 15px;">${ev.status}</td>
                        </tr>
                    `;
                    eventBody.innerHTML += row;
                });
            }

            floodChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Avg Water Level (%)',
                        data: dataPoints,
                        borderColor: '#4ab5c4',
                        backgroundColor: 'rgba(74, 181, 196, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'rgba(255,255,255,0.7)' } },
                        x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)' } }
                    }
                }
            });

            alertChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Alert Counts',
                        data: alertData,
                        backgroundColor: '#e74c3c',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'rgba(255,255,255,0.7)' } },
                        x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)' } }
                    }
                }
            });
        };

        // REMOVED LOGIC - MOVED TO TOP OF SCRIPT (Global Definition)


        // REMOVED DUPLICATE LOGIC - NOW CONSOLIDATED ABOVE


         // 7. Clock & Utilities
        function updateTime() {
            const clockEl = document.getElementById('clock');
            if(clockEl) {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateTime, 1000);
        updateTime();

        // REMOVED DUPLICATE toggleSidebar LOGIC - NOW AT TOP OF SCRIPT
        document.getElementById('sidebarOverlay').addEventListener('click', () => window.toggleSidebar());

        // 9. Charts on Dashboard (Safe Init)
        try {
            const chartCanvas = document.getElementById('waterLevelChart');
            if (chartCanvas && typeof Chart !== 'undefined') {
                const ctx = chartCanvas.getContext('2d');
                
                // Cyber Theme Colors
                const colorAccent = '#00e5ff'; // Neon Cyan
                const colorPrimary = '#c054ff'; // Neon Purple

                waterChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartDataStore[currentArea].labels,
                        datasets: [{
                            label: 'Water Level (ft)',
                            data: chartDataStore[currentArea].data,
                            borderColor: colorAccent,
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(0, 229, 255, 0.4)');
                                gradient.addColorStop(1, 'rgba(0, 229, 255, 0)');
                                return gradient;
                            },
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#13141b',
                            pointBorderColor: colorAccent,
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e2029',
                                titleColor: '#fff',
                                bodyColor: colorAccent,
                                borderColor: '#2d2f39',
                                borderWidth: 1,
                                displayColors: false,
                                padding: 10
                            }
                        },
                        scales: {
                            y: {
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        }
                    }
                });

                setInterval(() => {
                    if (!waterChart) return;
                    
                    // Update ALL areas in background to keep history fresh
                    for(let area in chartDataStore) {
                        const store = chartDataStore[area];
                        const lastVal = store.data[store.data.length - 1];
                        let newVal = Math.max(30, Math.min(90, lastVal + (Math.random() - 0.5) * 8));
                        
                        store.labels.push(new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
                        store.data.push(newVal);
                        
                        if (store.labels.length > 10) {
                            store.labels.shift();
                            store.data.shift();
                        }
                    }
                    
                    // Refresh current view
                    waterChart.update('none'); 
                }, 3000);
            }
        } catch (e) { log("Chart Error: " + e.message); }

        // Tab persistence & Hash Navigation
        function handleHash() {
            const hash = window.location.hash.replace('#', '');
            if(hash) {
                console.log("[AquaSafe] Hash Change Detected:", hash);
                const targetLink = document.querySelector(`.nav-link[onclick*="'${hash}'"]`) || document.getElementById('nav-' + hash);
                window.switchTab(hash, targetLink);
            }
        }
        window.addEventListener('load', handleHash);
        window.addEventListener('hashchange', handleHash);


        // --- NOTIFICATION CONTROL LOGIC ---
        window.fetchNotificationSettings = async function() {
            try {
                const res = await fetch('manage_notifications.php?action=fetch');
                const json = await res.json();
                
                if (json.status === 'success') {
                    const data = json.data;
                    
                    // Master Toggle
                    const master = document.getElementById('masterToggle');
                    if(master) master.checked = data.master_enabled;
                    
                    // Thresholds
                    const warmSlider = document.getElementById('warningSlider');
                    const critSlider = document.getElementById('criticalSlider');
                    if(warmSlider) { warmSlider.value = data.warning_threshold; document.getElementById('warningVal').innerText = data.warning_threshold + '%'; }
                    if(critSlider) { critSlider.value = data.critical_threshold; document.getElementById('criticalVal').innerText = data.critical_threshold + '%'; }
                    
                    // Channels
                    if(document.getElementById('sms_ch')) document.getElementById('sms_ch').checked = data.sms_enabled;
                    if(document.getElementById('email_ch')) document.getElementById('email_ch').checked = data.email_enabled;
                    if(document.getElementById('app_ch')) document.getElementById('app_ch').checked = data.push_enabled;
                    if(document.getElementById('public_ch')) document.getElementById('public_ch').checked = data.siren_enabled;
                }
            } catch (err) {
                log("Fetch Notifications Error: " + err);
            }
        };

        window.updateMasterToggle = async function() {
            const enabled = document.getElementById('masterToggle').checked ? 1 : 0;
            const formData = new FormData();
            formData.append('action', 'update_master');
            formData.append('enabled', enabled);
            
            try {
                const res = await fetch('manage_notifications.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.status === 'success') {
                    window.showNotification(json.message, enabled ? 'success' : 'warning');
                } else {
                    window.showNotification(json.message, 'error');
                }
            } catch(e) { window.showNotification("Connection failed", 'error'); }
        };

        window.updateThresholds = async function() {
            const warning = document.getElementById('warningSlider').value;
            const critical = document.getElementById('criticalSlider').value;
            
            const formData = new FormData();
            formData.append('action', 'update_thresholds');
            formData.append('warning', warning);
            formData.append('critical', critical);
            
            try {
                const res = await fetch('manage_notifications.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.status === 'success') {
                    log("Thresholds saved");
                } else {
                    window.showNotification(json.message, 'error');
                }
            } catch(e) { log("Threshold Save Error: " + e); }
        };

        window.updateChannels = async function() {
            const sms = document.getElementById('sms_ch').checked ? 1 : 0;
            const email = document.getElementById('email_ch').checked ? 1 : 0;
            const push = document.getElementById('app_ch').checked ? 1 : 0;
            const siren = document.getElementById('public_ch').checked ? 1 : 0;
            
            const formData = new FormData();
            formData.append('action', 'update_channels');
            formData.append('sms', sms);
            formData.append('email', email);
            formData.append('push', push);
            formData.append('siren', siren);
            
            try {
                const res = await fetch('manage_notifications.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.status === 'success') {
                    log("Channels updated");
                }
            } catch(e) { log("Channel Update Error"); }
        };

        window.broadcastAlert = function() {
            console.log("Broadcast button clicked");
            const msgInput = document.getElementById('broadcastMessage');
            const sevInput = document.getElementById('broadcastSeverity');
            const areaInput = document.getElementById('broadcastArea');

            if (!msgInput || !sevInput || !areaInput) {
                console.error("Critical: Broadcast inputs missing");
                return alert("Error: UI components missing. Please refresh.");
            }

            const message = msgInput.value;
            const severity = sevInput.value;
            const area = areaInput.value;
            
            if(!message.trim()) {
                window.showNotification("Please enter a message to broadcast.", 'warning');
                return;
            }
            
            // Use custom confirm modal instead of native confirm
            window.showConfirm(
                `Are you sure you want to BROADCAST this alert to ${area}?`,
                async function() {
                    // On Confirm
                    const formData = new FormData();
                    formData.append('action', 'broadcast');
                    formData.append('message', message);
                    formData.append('severity', severity);
                    formData.append('location', area);
                    
                    try {
                        const res = await fetch('manage_alerts.php', { method: 'POST', body: formData });
                        const json = await res.json();
                        if(json.status === 'success') {
                            window.showNotification("Broadcast Sent Successfully!", 'success');
                            msgInput.value = ''; // Clear input
                            if(typeof fetchSystemAlerts === 'function') fetchSystemAlerts(); 
                        } else {
                            window.showNotification(json.message, 'error');
                        }
                    } catch(e) { 
                        console.error("Broadcast error:", e);
                        window.showNotification("Broadcast Failed: " + e.message, 'error'); 
                    }
                }
            );
        };

        // Initialize Notifications
        fetchNotificationSettings();
        
        // Initialize
        updateTime();
        setInterval(updateTime, 1000);
        
        // Initial data load for dashboard
        fetchSystemAlerts();
        fetchSensorStatus();

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            if (document.getElementById('dashboard').classList.contains('active')) {
                fetchSystemAlerts();
                fetchSensorStatus();
            } else if (document.getElementById('alerts').classList.contains('active')) {
                fetchSystemAlerts();
            } else if (document.getElementById('sensors').classList.contains('active')) {
                fetchSensorStatus();
            }
        }, 30000);

        // 10. Removed problematic centralized listener - sticking to robust inline calls
        // Global Export Diagnostic
        console.log("[AquaSafe] Export Definitions Check:", {
            csv: typeof window.exportReportCSV,
            pdf: typeof window.downloadReportPDF
        });
        
        log("AquaSafe Admin System: LOADED SUCCESSFULLY!");

        // Initial check for Help Desk notifications
        fetchHelpdeskRequests();
        setInterval(fetchHelpdeskRequests, 60000); // Check every minute
    </script>
    <!-- Admin Reply Modal -->
    <div id="adminReplyModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:1px solid #4ab5c4; padding:25px; border-radius:15px; width:90%; max-width:500px; box-shadow:0 0 30px rgba(74,181,196,0.3); animation:fadeInUp 0.3s ease;">
            <h3 style="color:#4ab5c4; margin-top:0; display:flex; align-items:center; gap:10px; font-size:18px;"><i data-lucide="reply"></i> Send Reply to User</h3>
            <p style="color:rgba(255,255,255,0.7); font-size:13px; margin:10px 0 15px;">Your reply will be visible to the user immediately on their dashboard.</p>
            
            <input type="hidden" id="currentReplyId">
            <textarea id="adminReplyText" rows="5" style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; padding:15px; border-radius:8px; resize:vertical; font-family:inherit; font-size:14px;" placeholder="Type your response here..."></textarea>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button onclick="window.closeReplyModal()" style="padding:10px 20px; background:transparent; border:1px solid rgba(255,255,255,0.2); color:#fff; border-radius:8px; cursor:pointer; font-weight:600;">Cancel</button>
                <button onclick="window.submitAdminReply()" id="submitReplyBtn" style="padding:10px 25px; background:#4ab5c4; border:none; color:#032023; font-weight:700; border-radius:8px; cursor:pointer;">Send Reply</button>
            </div>
        </div>
    </div>

    <!-- Custom Notification Modal -->
    <div id="customNotificationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:2px solid #4ab5c4; padding:30px; border-radius:15px; width:90%; max-width:450px; box-shadow:0 0 40px rgba(74,181,196,0.4); animation:fadeInUp 0.3s ease; position:relative;">
            <div id="notificationIcon" style="font-size:48px; text-align:center; margin-bottom:15px;">✅</div>
            <h3 id="notificationTitle" style="color:#4ab5c4; margin:0 0 15px 0; text-align:center; font-size:20px;">Success</h3>
            <p id="notificationMessage" style="color:rgba(255,255,255,0.9); text-align:center; font-size:15px; line-height:1.6; margin-bottom:25px;">Operation completed successfully!</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button id="notificationOkBtn" onclick="window.closeNotification()" style="padding:12px 30px; background:#4ab5c4; border:none; color:#032023; font-weight:700; border-radius:8px; cursor:pointer; font-size:14px;">OK</button>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="customConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:2px solid #f1c40f; padding:30px; border-radius:15px; width:90%; max-width:450px; box-shadow:0 0 40px rgba(241,196,15,0.4); animation:fadeInUp 0.3s ease;">
            <div style="font-size:48px; text-align:center; margin-bottom:15px;">⚠️</div>
            <h3 style="color:#f1c40f; margin:0 0 15px 0; text-align:center; font-size:20px;">Confirm Action</h3>
            <p id="confirmMessage" style="color:rgba(255,255,255,0.9); text-align:center; font-size:15px; line-height:1.6; margin-bottom:25px;">Are you sure?</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button id="confirmCancelBtn" style="padding:12px 24px; background:transparent; border:1px solid rgba(255,255,255,0.3); color:#fff; font-weight:600; border-radius:8px; cursor:pointer; font-size:14px;">Cancel</button>
                <button id="confirmOkBtn" style="padding:12px 24px; background:#f1c40f; border:none; color:#032023; font-weight:700; border-radius:8px; cursor:pointer; font-size:14px;">Confirm</button>
            </div>
        </div>
    </div>

</body>
</html>```
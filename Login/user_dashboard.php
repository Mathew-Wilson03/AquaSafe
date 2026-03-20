<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["name"] ?? "User");
$email = htmlspecialchars($_SESSION["email"] ?? "");

require_once 'config.php';
$user_id = $_SESSION["id"] ?? 0;
// Default location if not set
$db_location = 'System Wide'; 

if($user_id){
    $stmt = mysqli_prepare($link, "SELECT location FROM users WHERE id = ?");
    if($stmt){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $loc);
        if(mysqli_stmt_fetch($stmt)){
             if(!empty($loc)) $db_location = $loc;
        }
        mysqli_stmt_close($stmt);
    }
}
// Expose to JS
echo "<script>window.userLocation = '" . htmlspecialchars($db_location) . "';</script>";

// Mock data (replace with real DB/API integration later)
$flood_status = 'Warning'; // Safe | Warning | Danger
$flood_explanation = (
    $flood_status === 'Safe' ? 'No immediate flood risk in your area.' :
    ($flood_status === 'Warning' ? 'Water levels rising — stay alert and follow evacuation instructions.' :
    'Severe flooding — evacuate to nearest shelter immediately.')
);
$last_checked = date('Y-m-d H:i:s');

$evac_points = [
    ['name'=>'Community Hall, Riverbend','distance'=>'1.2 km','query'=>'Community+Hall+Riverbend'],
    ['name'=>'Town School, Central','distance'=>'2.8 km','query'=>'Town+School+Central']
];

// Alerts will be fetched via AJAX
$alerts = [];

// Load recent help requests (if file exists)
$help_file = __DIR__ . '/help_requests.json';
$help_requests = [];
if (file_exists($help_file)) {
    $json = @file_get_contents($help_file);
    $decoded = @json_decode($json, true);
    if (is_array($decoded)) $help_requests = array_reverse($decoded);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - AquaSafe</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --primary: #c054ff; /* Neon Purple */
            --primary-dark: #6d28d9;
            --accent: #00e5ff; /* Neon Cyan */
            --bg-body: #13141b;
            --glass: #1e2029; /* Surface */
            --glass-border: #2d2f39;
            --safe: #2ecc71;
            --warning: #f1c40f;
            --danger: #e74c3c;

            /* Professional Safety Assistant Additions */
            --hero-bg: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --hero-border: rgba(176, 251, 255, 0.2);
            --teal-accent: #b0fbff;
            --soft-blue: #3b82f6;
            --soft-gray: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg-body); 
            color: #fff; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container { display: flex; gap: 24px; padding: 24px; max-width: 1600px; margin: 0 auto; }

        /* Glassmorphism Utility */
        .glass {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Sidebar Enhancements */
        .sidebar { 
            width: 280px; 
            height: calc(100vh - 48px);
            position: sticky;
            top: 24px;
            padding: 28px; 
            display: flex; 
            flex-direction: column; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header { 
            font-size: 26px; 
            font-weight: 800; 
            color: var(--primary); 
            margin-bottom: 30px; 
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .interactive-logo {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .sidebar-menu { 
            flex: 1; 
            overflow-y: auto; 
            margin-right: -10px; 
            padding-right: 10px;
        }
        
        .sidebar-menu::-webkit-scrollbar { width: 4px; }
        .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
        .sidebar-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .sidebar-menu a, .sidebar-footer a { 
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px; 
            margin: 10px 0; 
            color: rgba(255,255,255,0.7); 
            text-decoration: none; 
            border-radius: 14px; 
            transition: all 0.25s ease;
            font-weight: 500;
            position: relative;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active, .sidebar-footer a:hover { 
            background: rgba(74,181,196,0.15); 
            color: var(--primary); 
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sidebar-footer { 
            border-top: 1px solid rgba(255,255,255,0.1); 
            padding-top: 20px; 
        }

        /* Hero Card & Safety Asst Styles */
        .hero-card {
            background: var(--hero-bg);
            border: 1px solid var(--hero-border);
            border-radius: 24px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            margin-bottom: 24px;
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(176, 251, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .safety-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-safe { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .badge-warning { background: rgba(241, 196, 15, 0.15); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .badge-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

        .emergency-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .emergency-btn {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            padding: 15px 10px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .emergency-btn:hover {
            background: rgba(255,255,255,0.08);
            transform: translateY(-5px);
            border-color: var(--teal-accent);
        }

        .emergency-btn.sos {
            background: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.3);
        }
        .emergency-btn.sos i { color: #e74c3c; }

        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .timeline-item:last-child { border-bottom: none; }

        .item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .intelligence-reading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dotted rgba(255,255,255,0.1);
        }

        /* --- Alert & Timeline Enhancements --- */
        .alert-card-professional {
            padding: 18px;
            border-radius: 16px;
            margin-top: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .alert-theme-critical {
            background: rgba(231, 76, 60, 0.08);
            border: 1px solid rgba(231, 76, 60, 0.3);
            box-shadow: 0 0 20px rgba(231, 76, 60, 0.1);
        }

        .alert-theme-warning {
            background: rgba(241, 196, 15, 0.08);
            border: 1px solid rgba(241, 196, 15, 0.3);
        }

        .alert-theme-safe {
            background: rgba(46, 204, 113, 0.08);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert-theme-evac {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.1);
        }

        .alert-theme-system {
            background: rgba(168, 85, 247, 0.08);
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .alert-header-prof {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .severity-pill {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pill-critical { background: #e74c3c; color: #fff; box-shadow: 0 0 10px rgba(231,76,60,0.4); }
        .pill-warning { background: #f1c40f; color: #13141b; }
        .pill-safe { background: #2ecc71; color: #fff; }
        .pill-evac { background: #3b82f6; color: #fff; box-shadow: 0 0 10px rgba(59,130,246,0.4); }
        .pill-system { background: #a855f7; color: #fff; }

        .alert-pulse {
            animation: alert-pulse-animation 2s infinite;
        }

        @keyframes alert-pulse-animation {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }

        /* Grouped History Styles */
        .date-group-header {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--primary);
            margin: 25px 0 15px;
            padding-left: 10px;
            border-left: 3px solid var(--primary);
            opacity: 0.8;
        }

        .history-item-prof {
            padding: 20px;
            border-radius: 18px;
            background: rgba(30,32,41,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }

        .history-item-prof:hover {
            background: rgba(30,32,41,0.7);
            border-color: rgba(255,255,255,0.15);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .alert-details-panel {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            margin-top: 0;
            padding: 0 5px;
        }

        .history-item-prof.expanded .alert-details-panel {
            max-height: 500px;
            opacity: 1;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .intelligence-tag {
            background: rgba(176, 251, 255, 0.1);
            border: 1px solid rgba(176, 251, 255, 0.2);
            color: var(--teal-accent);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .trend-icon { font-size: 16px; vertical-align: middle; }

        .control-panel {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 16px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .ctrl-group { display: flex; align-items: center; gap: 10px; }
        .ctrl-label { font-size: 11px; font-weight: 700; opacity: 0.5; text-transform: uppercase; }

        .terminal-input {
            background: #13141b;
            border: 1px solid #2d2f39;
            color: #fff;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 12px;
            outline: none;
            transition: all 0.2s;
        }

        .terminal-input:focus { border-color: var(--primary); box-shadow: 0 0 15px rgba(192, 84, 255, 0.2); }

        .terminal-select {
            background: #13141b;
            border: 1px solid #2d2f39;
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            cursor: pointer;
        }

        .btn-terminal {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-terminal:hover { background: rgba(255,255,255,0.1); border-color: var(--primary); }
        .btn-terminal.active { background: var(--primary); color: #1e2029; }

        /* --- Original Functional Styles --- */
        .main { flex: 1; display: flex; flex-direction: column; gap: 0; min-width: 0; overflow: visible; }
        .header { padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-left h1 { font-size: 24px; font-weight: 700; color: #fff; }
        .last-updated { background: rgba(0,0,0,0.4); padding: 10px 16px; border-radius: 50px; font-size: 13px; color: var(--primary); border: 1px solid rgba(74,181,196,0.3); display:flex; align-items:center; gap:8px; font-weight: 600; }
        .user-profile { display: flex; align-items: center; gap: 12px; padding: 6px 16px; background: rgba(255,255,255,0.05); border-radius: 50px; border: 1px solid rgba(255,255,255,0.1); }
        .user-avatar { width: 36px; height: 36px; background: var(--primary); color: #032023; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; position: relative; z-index: 10; }

        .content-section { display: none; width: 100%; }
        .content-section.active { display: block; animation: fadeInUp 0.5s ease both; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
        .card { padding: 24px; height: 100%; display: flex; flex-direction: column; }
        .card h3 { margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--primary); }
        .list-container { display: flex; flex-direction: column; gap: 12px; }
        .list-item { padding: 16px; border-radius: 14px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; display: flex; justify-content: space-between; align-items: center; }
        .small-btn { padding: 8px 16px; border-radius: 10px; background: rgba(74,181,196,0.15); color: var(--primary); border: 1px solid rgba(74,181,196,0.2); text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .small-btn:hover { background: var(--primary); color: #032023; }
        
        .weather-widget { display: flex; align-items: center; gap: 15px; padding: 15px 25px; background: rgba(74,181,196,0.1); border-radius: 20px; border: 1px solid rgba(74,181,196,0.2); margin-bottom: 20px; }
        #map { height: 400px; border-radius: 16px; margin-top: 10px; z-index: 10; }
        .chart-container { position: relative; height: 300px; width: 100%; }

        /* Safety Navbar Rework */
        .safety-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            margin-bottom: 24px;
            background: rgba(30, 32, 41, 0.8); /* Slightly more opaque for stickiness */
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            margin-top: -5px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .navbar-info { display: flex; align-items: center; gap: 20px; }
        .nav-item { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.7); }
        .nav-item i { width: 16px; color: var(--primary); }
        .nav-weather { display: flex; align-items: center; gap: 10px; padding: 6px 14px; background: rgba(0,0,0,0.2); border-radius: 50px; border: 1px solid rgba(255,255,255,0.05); }

        /* Unified Card Styling */
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h3 { margin-bottom: 0 !important; }

        /* Contacts Grid */
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .contact-card { 
            padding: 20px; border-radius: 18px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease; display: flex; flex-direction: column; gap: 12px;
        }
        .contact-card:hover { border-color: var(--primary); transform: translateY(-3px); }
        .contact-cat { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--primary); opacity: 0.6; }

        /* Help Desk Ticketing */
        .ticket-item { padding: 20px; border-radius: 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px; }
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .ticket-status { font-size: 11px; padding: 4px 10px; border-radius: 50px; font-weight: 700; }

        /* Settings Tabs */
        .settings-layout { display: flex; gap: 24px; }
        .settings-nav { width: 200px; display: flex; flex-direction: column; gap: 5px; }
        .settings-nav button { 
            text-align: left; padding: 12px 16px; border-radius: 10px; background: transparent; border: none; color: rgba(255,255,255,0.6); 
            cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s;
        }
        .settings-nav button.active { background: rgba(192, 84, 255, 0.1); color: var(--primary); }
        .settings-content { flex: 1; padding: 5px; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 1100px) { 
            .container { flex-direction: column; padding: 15px; }
            .sidebar { position: fixed; left: -320px; top: 0; height: 100vh; z-index: 2000; width: 280px; transition: left 0.3s ease; border-radius: 0 20px 20px 0; overflow-y: auto; max-height: 100vh; }
            .sidebar.active { left: 0; }
            .grid { grid-template-columns: 1fr; gap: 15px; }
            .mobile-toggle { display: block !important; }
            .safety-navbar { flex-wrap: wrap; gap: 10px; }
        }
        .mobile-toggle { display: none; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white; padding: 10px; border-radius: 12px; cursor: pointer; margin-right: 15px; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); z-index: 1999; }
        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>
    <!-- Emergency Alarm Banner -->
    <div id="alarmBanner" style="display:none; position:fixed; top:0; left:0; width:100%; padding:20px; background:#e74c3c; color:#fff; z-index:20000; justify-content:center; align-items:center; flex-direction:column; gap:15px; box-shadow:0 10px 30px rgba(0,0,0,0.5); animation:slideDown 0.5s ease;">
        <div style="font-size:40px; animation: pulse 1s infinite;">🚨 EMERGENCY ALARM 🚨</div>
        <div id="alarmStatus" style="font-size:18px; font-weight:700; text-align:center;">Flood Alert Detected!</div>
        <button onclick="window.stopEmergencyAlarm()" style="padding:15px 40px; background:#fff; color:#e74c3c; border:none; border-radius:50px; font-weight:900; font-size:18px; cursor:pointer; box-shadow:0 4px 15px rgba(0,0,0,0.2); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            STOP ALARM
        </button>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="container">
        <div class="sidebar glass animate__animated animate__fadeInLeft" id="sidebar">
            <div class="sidebar-header">
                <img src="../assets/logo.png" alt="AquaSafe Logo" class="interactive-logo">
                AquaSafe
            </div>
            <div class="sidebar-menu">
                <a href="#section-flood" class="active" data-target="section-flood">📊 Flood Status</a>
                <a href="#section-evac" data-target="section-evac">📍 Evacuation</a>
                <a href="#section-contacts" data-target="section-contacts">📞 Contacts</a>
                <a href="#section-alerts" data-target="section-alerts" style="position:relative;">
                    🚨 Alerts
                    <span id="alertBadge" style="display:none; position:absolute; top:8px; right:8px; background:#e74c3c; color:#fff; border-radius:50%; width:20px; height:20px; font-size:11px; font-weight:700; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(231,76,60,0.5);"></span>
                </a>
                <a href="#section-help" data-target="section-help" id="helpDeskLink">
                    🆘 Help Desk
                    <span id="helpdeskNotificationBadge" style="display:none; position:absolute; top:8px; right:8px; background:#e74c3c; color:#fff; border-radius:50%; width:20px; height:20px; font-size:11px; font-weight:700; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(231,76,60,0.5);"></span>
                </a>
                <a href="#section-safety" data-target="section-safety">🛡️ Safety Tips</a>
                <a href="#section-water" data-target="section-water">📡 Water Levels</a>
                <a href="#section-map" data-target="section-map">🗺️ Map View</a>
                <a href="#section-settings" data-target="section-settings">⚙️ Settings</a>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php">🚪 Sign Out</a>
            </div>
        </div>

        <div class="main">
            <!-- Compact Safety Navbar -->
            <div class="safety-navbar animate__animated animate__fadeInDown">
                <div class="navbar-info">
                    <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
                    <div class="nav-item">
                        <i data-lucide="map-pin"></i>
                        <span id="nav-location"><?php echo $db_location; ?></span>
                    </div>
                    <div class="nav-item">
                        <i data-lucide="clock"></i>
                        <span id="nav-clock">00:00:00</span>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; align-items: center;">
                    <div class="nav-weather">
                        <span style="font-size: 18px;">🌤️</span>
                        <span style="font-size: 12px; font-weight: 700;">28°C</span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                        <div class="user-info" style="line-height: 1.2;">
                            <div style="font-size: 13px; font-weight: 700;"><?php echo $name; ?></div>
                            <div style="font-size: 10px; opacity: 0.6;"><?php echo $email; ?></div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="grid">
                    <div id="section-flood" class="content-section active"> <!-- Made active by default for redesigned dashboard -->
                        <!-- Hero Safety Card -->
                        <div class="hero-card animate__animated animate__fadeIn" id="heroSafetyCard">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                                <div>
                                    <div class="safety-badge badge-safe" id="heroBadge">
                                        <i data-lucide="shield-check"></i> <span>Safety Status: Safe</span>
                                    </div>
                                    <h2 style="font-size: 42px; margin: 15px 0 5px; font-weight: 800; color: #fff;" id="heroLevel">0 ft</h2>
                                    <div style="color: var(--teal-accent); font-size: 14px; font-weight: 600;" id="heroLocation">
                                        <i data-lucide="map-pin" style="width: 14px; vertical-align: middle; display: inline-block;"></i> Initializing...
                                    </div>
                                </div>
                                <div style="text-align: right; min-width: 120px; display:flex; flex-direction:column; align-items:flex-end;">
                                    <div id="heroTrend" style="font-size: 18px; font-weight: 700; color: var(--safe);">--</div>
                                    <div style="width: 100px; height: 30px; margin: 5px 0;">
                                        <canvas id="miniFloodChart"></canvas>
                                    </div>
                                    <div id="heroUpdated" style="font-size: 12px; opacity: 0.6; margin-top: 5px;">Updated: --</div>
                                </div>
                            </div>
                            <p id="heroGuidance" style="margin-top: 25px; font-size: 15px; color: rgba(255,255,255,0.9); line-height: 1.6; max-width: 600px;">
                                Loading safety intelligence...
                            </p>
                        </div>

                        <!-- Intelligence Grid -->
                        <div class="grid">
                            <!-- Admin Alert Card -->
                            <div class="card glass animate__animated animate__fadeInUp" id="adminAlertCard" style="animation-delay: 0.1s;">
                                <h3><i data-lucide="megaphone" style="color: #e74c3c;"></i> Latest Alert</h3>
                                <div id="adminAlertContent">
                                    <div style="text-align: center; padding: 20px; opacity: 0.5; font-size: 14px;">No active broadcast alerts.</div>
                                </div>
                            </div>

                            <!-- Water Level Intelligence -->
                            <div class="card glass animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                                <h3><i data-lucide="activity"></i> Level Intelligence</h3>
                                <div id="intelligenceList" class="list-container">
                                    <div style="text-align: center; padding: 20px; opacity: 0.5; font-size: 14px;">Fetching trends...</div>
                                </div>
                                <div style="margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; font-size: 11px;">
                                    <span>Sensor Health</span>
                                    <span style="color: var(--safe);" id="sensorHealth">● Healthy</span>
                                </div>
                            </div>

                            <!-- Nearest Evacuation -->
                            <div class="card glass animate__animated animate__fadeInUp" id="nearestEvacCard" style="animation-delay: 0.3s;">
                                <h3><i data-lucide="navigation"></i> Nearest Evacuation</h3>
                                <div id="nearestEvacContent">
                                    <div style="font-weight: 700; font-size: 18px; color: var(--teal-accent);" id="evacName">--</div>
                                    <div style="font-size: 13px; opacity: 0.7; margin: 5px 0 15px;" id="evacLoc">--</div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span class="safety-badge badge-safe" style="font-size: 10px; padding: 4px 10px;" id="evacStatus">Available</span>
                                        <a href="#" class="small-btn" id="evacButton" target="_blank">Directions</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Mini IQ Timeline -->
                            <div class="card glass animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                                <h3><i data-lucide="history"></i> Notification Timeline</h3>
                                <div id="iqTimeline" class="list-container">
                                    <div style="text-align: center; padding: 20px; opacity: 0.5; font-size: 14px;">Loading IQ events...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Action Panel -->
                        <div class="emergency-grid animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
                            <a href="#" class="emergency-btn" onclick="window.openHelpModal(); return false;">
                                <i data-lucide="help-circle" style="color: var(--teal-accent);"></i>
                                <span>Request Help</span>
                            </a>
                            <a href="tel:108" class="emergency-btn sos">
                                <i data-lucide="phone-call" style="color: #e74c3c;"></i>
                                <span>SOS Call</span>
                            </a>
                            <a href="#section-map" class="emergency-btn" data-target="section-map" onclick="window.showSection('section-map'); return false;">
                                <i data-lucide="map" style="color: var(--teal-accent);"></i>
                                <span>Live Map</span>
                            </a>
                            <a href="#" class="emergency-btn" id="shareLocBtn" onclick="window.shareLocation(); return false;">
                                <i data-lucide="share-2" style="color: var(--teal-accent);"></i>
                                <span>Share Location</span>
                            </a>
                        </div>
                    </div>

                    <div id="section-evac" class="content-section">
                        <div class="card glass">
                            <div class="card-header">
                                <h3><i data-lucide="map-pin" style="color: var(--teal-accent);"></i> Safe Shelters</h3>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="evacSearch" onkeyup="filterEvacuationPoints()" placeholder="Search Shelters..." 
                                           style="padding: 8px 12px; font-size: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; width: 160px;">
                                    <select id="evacFilter" onchange="filterEvacuationPoints()" 
                                            style="padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; cursor: pointer; font-size: 12px;">
                                        <option value="All">All Status</option>
                                        <option value="Open">Available</option>
                                        <option value="Full">Full</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid" id="evacuation-list-container" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                                <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.4); font-size:13px;">Scanning for active shelters...</div>
                            </div>
                        </div>
                    </div>

                    <div id="section-alerts" class="content-section">
                        <div class="card glass">
                            <div class="card-header">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: rgba(231, 76, 60, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #e74c3c;">
                                        <i data-lucide="shield-alert" style="width: 24px; height: 24px;"></i>
                                    </div>
                                    <div>
                                        <h3 style="margin: 0; font-size: 20px;">Safety Intelligence Terminal</h3>
                                        <div style="font-size: 11px; opacity: 0.5; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Official Warning & Alert Registry</div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn-terminal" onclick="window.refreshSafetyDashboard(); fetchUserAlerts();">
                                        <i data-lucide="refresh-cw" style="width: 14px;"></i> Sync Terminal
                                    </button>
                                </div>
                            </div>

                            <!-- Terminal Control Panel -->
                            <div class="control-panel animate__animated animate__fadeIn">
                                <div class="ctrl-group" style="flex: 1; min-width: 250px;">
                                    <span class="ctrl-label">Registry Search</span>
                                    <input type="text" id="alertTerminalSearch" class="terminal-input" placeholder="Filter by event, location, or source..." style="flex: 1;" onkeyup="window.filterAlertTerminal()">
                                </div>
                                
                                <div class="ctrl-group">
                                    <span class="ctrl-label">Severity Filter</span>
                                    <select id="alertSeverityFilter" class="terminal-select" onchange="window.filterAlertTerminal()">
                                        <option value="ALL">All Levels</option>
                                        <option value="CRITICAL">Critical Only</option>
                                        <option value="WARNING">Warnings</option>
                                        <option value="SAFE">Safe Status</option>
                                        <option value="EVACUATION">Evacuation</option>
                                        <option value="SYSTEM">System</option>
                                    </select>
                                </div>

                                <div class="ctrl-group">
                                    <span class="ctrl-label">Temporal Sort</span>
                                    <button id="alertSortBtn" class="btn-terminal" onclick="window.toggleAlertSort()" data-sort="desc">
                                        <i data-lucide="arrow-down-narrow-wide" style="width: 14px;"></i> 
                                        <span>Newest First</span>
                                    </button>
                                </div>
                            </div>

                            <div id="userAlertsList" class="list-container" style="min-height: 400px; padding: 0 5px;">
                                <div style="text-align:center; padding: 100px 40px; color:rgba(255,255,255,0.2); font-size: 14px;">
                                    <i data-lucide="cpu" style="width: 40px; height: 40px; margin-bottom: 20px; opacity: 0.2;"></i>
                                    <br>Connecting to AquaSafe Data Pipeline...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="section-contacts" class="content-section">
                        <div class="card glass">
                            <div class="card-header">
                                <h3><i data-lucide="phone-call" style="color: var(--teal-accent);"></i> Emergency Contacts</h3>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" id="contactSearch" onkeyup="filterContacts()" placeholder="Search..." 
                                           style="padding: 8px 12px; font-size: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; width: 180px;">
                                </div>
                            </div>
                            
                            <div class="contact-grid" id="contacts-list-container">
                                <!-- Emergency Category -->
                                <div class="contact-card">
                                    <span class="contact-cat">Emergency</span>
                                    <div style="font-weight: 700; font-size: 16px;">Medical Emergency</div>
                                    <div style="font-size: 12px; opacity: 0.6;">Ambulance (108/102)</div>
                                    <a class="small-btn" href="tel:108" style="text-align: center;">Call 108</a>
                                </div>
                                <div class="contact-card">
                                    <span class="contact-cat">Emergency</span>
                                    <div style="font-weight: 700; font-size: 16px;">Fire & Rescue</div>
                                    <div style="font-size: 12px; opacity: 0.6;">Local Fire Station</div>
                                    <a class="small-btn" href="tel:101" style="text-align: center;">Call 101</a>
                                </div>
                                <!-- Rescue Category -->
                                <div class="contact-card">
                                    <span class="contact-cat">Rescue</span>
                                    <div style="font-weight: 700; font-size: 16px;">Disaster Relief</div>
                                    <div style="font-size: 12px; opacity: 0.6;">24/7 National Hotline</div>
                                    <a class="small-btn" href="tel:1070" style="text-align: center;">Call 1070</a>
                                </div>
                                <div class="contact-card">
                                    <span class="contact-cat">Community</span>
                                    <div style="font-weight: 700; font-size: 16px;">Panchayat Office</div>
                                    <div style="font-size: 12px; opacity: 0.6;">Churakullam Regional</div>
                                    <a class="small-btn" href="tel:04869200100" style="text-align: center;">Call Office</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div id="section-help" class="content-section">
                        <div class="card glass">
                            <div class="card-header">
                                <h3><i data-lucide="help-circle" style="color: var(--teal-accent);"></i> Support Ticket Center</h3>
                                <div id="helpdesk-status-msg"></div>
                            </div>
                            
                            <div style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 24px;">
                                <h4 style="font-size: 14px; margin-bottom: 15px; opacity: 0.8;">Open a new request</h4>
                                <form class="help-form" id="helpRequestForm" method="POST" onsubmit="window.submitHelpRequest(event)">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                        <input type="text" name="title" id="helpTitle" placeholder="Problem Summary (e.g., Blocked Drain)" required
                                               style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                                        <select id="helpPriority" name="priority" style="padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; cursor: pointer;">
                                            <option value="Normal">Normal Priority</option>
                                            <option value="High">High Priority</option>
                                            <option value="Urgent">Urgent / Emergency</option>
                                        </select>
                                    </div>
                                    <textarea name="details" id="helpDetails" rows="3" placeholder="Explain the situation in detail..." required
                                              style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin-bottom: 15px;"></textarea>
                                    <button type="submit" id="helpSubmitBtn" style="width: 100%; padding: 14px; background: var(--primary); border: none; border-radius: 12px; color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                        <i data-lucide="send" style="width: 18px;"></i> Submit Rescue Ticket
                                    </button>
                                </form>
                            </div>

                            <h4 style="margin-bottom: 15px; font-size: 14px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="list" style="width:16px;"></i> My Active Tickets
                            </h4>
                            <div id="my-requests-list">
                                <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.4); font-size:13px;">Fetching your ticket history...</div>
                            </div>
                        </div>
                    </div>

                    <div id="section-safety" class="content-section">
                        <div class="card glass">
                            <h3>🛡️ Safety Guidelines</h3>
                            <div class="list-container">
                                <div class="list-item">
                                    <div style="display:flex; gap:12px; align-items: flex-start;">
                                        <div style="min-width: 30px; height: 30px; background: rgba(192, 84, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #c054ff; font-weight: bold;">1</div>
                                        <div>
                                            <strong style="color: #fff; display: block; margin-bottom: 4px;">Move to Higher Ground</strong>
                                            <span style="font-size: 13px; color: rgba(255,255,255,0.6);">If water levels rise rapidly, do not wait for official instructions. Move to the nearest high ground or designated evacuation point immediately.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div style="display:flex; gap:12px; align-items: flex-start;">
                                        <div style="min-width: 30px; height: 30px; background: rgba(0, 229, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #00e5ff; font-weight: bold;">2</div>
                                        <div>
                                            <strong style="color: #fff; display: block; margin-bottom: 4px;">Turn Off Utilities</strong>
                                            <span style="font-size: 13px; color: rgba(255,255,255,0.6);">Switch off main power switches and gas valves. Do not touch electrical equipment if you are wet or standing in water.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div style="display:flex; gap:12px; align-items: flex-start;">
                                        <div style="min-width: 30px; height: 30px; background: rgba(231, 76, 60, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #e74c3c; font-weight: bold;">3</div>
                                        <div>
                                            <strong style="color: #fff; display: block; margin-bottom: 4px;">Avoid Flood Waters</strong>
                                            <span style="font-size: 13px; color: rgba(255,255,255,0.6);">6 inches of moving water can make you fall. Do not walk, swim, or drive through flood waters. Turn Around, Don't Drown!</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div style="display:flex; gap:12px; align-items: flex-start;">
                                        <div style="min-width: 30px; height: 30px; background: rgba(241, 196, 15, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #f1c40f; font-weight: bold;">4</div>
                                        <div>
                                            <strong style="color: #fff; display: block; margin-bottom: 4px;">Emergency Kit</strong>
                                            <span style="font-size: 13px; color: rgba(255,255,255,0.6);">Keep a kit ready with medicines, documents, torch, battery radio, and non-perishable food for at least 3 days.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="section-water" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="droplets"></i> Water Level Trends</h3>
                            <div class="chart-container">
                                <canvas id="waterLevelChart"></canvas>
                            </div>
                            <p style="margin-top:15px; font-size:12px; color:rgba(255,255,255,0.5);">Real-time sensor data from Checkpoint Alpha (Riverbend).</p>
                        </div>
                    </div>

                    <div id="section-map" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="map"></i> Interactive Map</h3>
                            <div id="map"></div>
                            <div style="margin-top:15px; display:flex; gap:20px; font-size:12px;">
                                <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:50%; background:#2ecc71;"></span> Safe</span>
                                <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:50%; background:#f1c40f;"></span> Warning</span>
                                <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:50%; background:#e74c3c;"></span> Danger</span>
                            </div>
                        </div>
                    </div>

                    <div id="section-settings" class="content-section">
                        <div class="card glass">
                            <div class="card-header">
                                <h3><i data-lucide="settings"></i> Personal Safety Settings</h3>
                            </div>
                            
                            <div class="settings-layout">
                                <div class="settings-nav">
                                    <button class="active" onclick="switchSettingsTab('profile')">👤 Profile Info</button>
                                    <button onclick="switchSettingsTab('alerts')">🔔 Notifications</button>
                                    <button onclick="switchSettingsTab('security')">🔒 Security</button>
                                </div>
                                <div class="settings-content" id="settings-dynamic-content">
                                    <!-- Profile Tab (Default) -->
                                    <div id="tab-profile" class="tab-pane">
                                        <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                                            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                                                <div style="width: 80px; height: 80px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; color: #1e2029;">
                                                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h4 style="font-size: 20px;"><?php echo $name; ?></h4>
                                                    <p style="opacity: 0.6; font-size: 14px;"><?php echo $email; ?></p>
                                                </div>
                                            </div>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                                <div>
                                                    <label style="display: block; font-size: 12px; margin-bottom: 8px; opacity: 0.6;">Primary Location</label>
                                                    <div style="padding: 12px; background: rgba(0,0,0,0.3); border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);"><?php echo $db_location; ?></div>
                                                </div>
                                                <div>
                                                    <label style="display: block; font-size: 12px; margin-bottom: 8px; opacity: 0.6;">Account Status</label>
                                                    <div style="padding: 12px; background: rgba(0,0,0,0.3); border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); color: var(--safe); font-weight: 700;">Verified User</div>
                                                </div>
                                            </div>
                                            <button class="small-btn" onclick="document.getElementById('editProfileModal').style.display='flex'" style="margin-top: 25px; padding: 12px 24px;">Update Profile Details</button>
                                        </div>
                                    </div>

                                    <div id="tab-alerts" class="tab-pane" style="display: none;">
                                        <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                                            <div class="list-item" style="margin-bottom: 12px;">
                                                <div>
                                                    <div style="font-weight: 700;">Browser Notifications</div>
                                                    <div style="font-size: 11px; opacity: 0.6;">Real-time desktop alerts for flood warnings</div>
                                                </div>
                                                <button class="small-btn" onclick="requestNotificationPermission()">Manage</button>
                                            </div>
                                            <div class="list-item">
                                                <div>
                                                    <div style="font-weight: 700;">SMS Alerts</div>
                                                    <div style="font-size: 11px; opacity: 0.6;">Receive emergency texts on your registered mobile</div>
                                                </div>
                                                <span class="safety-badge badge-warning" style="font-size: 9px;">Inactive</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="tab-security" class="tab-pane" style="display: none;">
                                        <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                                            <div class="list-item" style="margin-bottom: 12px;">
                                                <div>
                                                    <div style="font-weight: 700;">Password</div>
                                                    <div style="font-size: 11px; opacity: 0.6;">Last changed 3 months ago</div>
                                                </div>
                                                <button class="small-btn" onclick="alert('Password reset link sent to your email.')">Reset</button>
                                            </div>
                                            <div class="list-item">
                                                <div>
                                                    <div style="font-weight: 700;">Two-Factor Auth</div>
                                                    <div style="font-size: 11px; opacity: 0.6;">Secure your account with 2FA</div>
                                                </div>
                                                <button class="small-btn">Enable</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- Closes .grid -->
            </div> <!-- Closes .main -->
        </div> <!-- Closes .container -->
    </div>
    <script>
        // --- ULTIMATE DIAGNOSTICS ---
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error("Global Error:", msg, "at", url, ":", lineNo);
            return false;
        };

        // --- CHART/MAP STANDBYS (Prevent ReferenceErrors) ---
        if (typeof window.initChart === 'undefined') window.initChart = function() { console.log("Main Chart standby."); };
        if (typeof window.initMiniChart === 'undefined') window.initMiniChart = function() { console.log("Mini Chart standby."); };
        if (typeof window.initMap === 'undefined') window.initMap = function() { console.log("Map standby."); };
        
        // Initial rendering of icons on page load
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // --- GLOBAL HELP DESK LOGIC (READY IMMEDIATELY) ---
        window.fetchWithTimeout = async function(resource, options = {}) {
            const { timeout = 15000 } = options;
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);
            const response = await fetch(resource, { ...options, signal: controller.signal });
            clearTimeout(id);
            return response;
        };

        window.showStatusMsg = function(msg, color) {
            try {
                const el = document.getElementById('helpdesk-status-msg');
                if(!el) return;
                el.style.cssText = `padding:12px; border-radius:10px; background:rgba(255,255,255,0.05); color:${color}; margin-bottom:15px; border:1px solid ${color}; font-size:13px; font-weight:600;`;
                el.innerText = msg;
                el.style.display = 'block';
                setTimeout(() => { if(el) el.style.display = 'none'; }, 5000);
            } catch(e) { console.error("StatusMsg Error", e); }
        };

        // --- SWEETALERT CONFIRM WRAPPER ---
        window.showConfirm = function(message, onConfirm) {
            if(typeof Swal === 'undefined') {
                // Fallback if generic alert
                if(confirm(message)) onConfirm();
                return;
            }

            Swal.fire({
                title: 'Confirm Action',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f1c40f',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed',
                background: '#0f2027',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        };

        window.submitHelpRequest = function(e) {
            if(e) e.preventDefault();
            const btn = document.getElementById('helpSubmitBtn');
            const title = document.getElementById('helpTitle').value;
            const details = document.getElementById('helpDetails').value;

            if(!title || !details) return alert("Please fill in both the title and details.");

            window.showConfirm("Are you sure you want to SUBMIT this help request?", async function() {
                const originalText = btn.innerHTML;
                btn.innerText = "⏳ Sending...";
                btn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'submit');
                formData.append('title', title);
                formData.append('details', details);

                try {
                    const res = await fetchWithTimeout('manage_helpdesk.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        document.getElementById('helpRequestForm').reset();
                        window.showStatusMsg("Request sent successfully!", "var(--safe)");
                        window.loadMyRequests();
                        // SweetAlert Success Fallback
                        if(typeof Swal !== 'undefined') Swal.fire('Sent!', 'Your request has been submitted.', 'success');
                    } else {
                        alert("❌ ERROR: " + data.message);
                    }
                } catch (err) {
                    alert("📡 NETWORK ERROR: " + err.message);
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        };

        window.deleteHelpRequest = function(id) {
            window.showConfirm("Are you sure you want to CANCEL this request?", async function() {
                const formData = new FormData();
                formData.append('action', 'delete'); 
                formData.append('id', id);

                try {
                    const res = await fetch('manage_helpdesk.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if(json.status === 'success') {
                        if(typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Cancelled',
                                text: 'Your request has been removed.',
                                icon: 'success',
                                background: '#1e2029',
                                color: '#fff'
                            });
                        } else {
                            alert("Request cancelled.");
                        }
                        window.loadMyRequests();
                    } else {
                        // Better error reporting
                        const errorMsg = json.message || "Could not cancel request. It might be already resolved.";
                        if(typeof Swal !== 'undefined') {
                            Swal.fire('Error', errorMsg, 'error');
                        } else {
                            alert("Failed: " + errorMsg);
                        }
                    }
                } catch(e) {
                    console.error("Delete Request Error:", e);
                    alert("Network Error: Could not reach server.");
                }
            });
        };

        // --- LOAD REQUESTS (UPDATED WITH BETTER UI) ---
        window.loadMyRequests = async function() {
            const container = document.getElementById('my-requests-list');
            if(!container) return;

            // Visual feedback
            if(container.innerText.includes('Fetching') || container.innerHTML === '') {
                container.innerHTML = '<div style="text-align:center; padding:15px; opacity:0.6; color:var(--primary); font-size:13px;">📡 Updating your request list...</div>';
            }

            try {
                const res = await fetchWithTimeout('manage_helpdesk.php?action=fetch_user', { timeout: 10000 });
                const json = await res.json();
                
                if(json.status === 'success') {
                    if(json.data.length > 0) {
                        let html = '';
                        json.data.forEach(req => {
                            const statusColor = req.status === 'Resolved' ? 'var(--safe)' : (req.status === 'Pending' ? 'var(--warning)' : 'var(--primary)');
                            const replyHtml = req.admin_reply ? `
                                <div style="margin-top:10px; padding:10px; background:rgba(74,181,196,0.1); border-radius:8px; border-left:3px solid var(--primary);">
                                    <div style="font-size:11px; font-weight:700; color:var(--primary); margin-bottom:4px;">ADMIN REPLY:</div>
                                    <div style="font-size:12px; color:#fff;">${req.admin_reply}</div>
                                </div>` : '';

                            const isUnresolved = req.status !== 'Resolved';
                            const deleteBtn = isUnresolved ? `
                                <button onclick="window.deleteHelpRequest(${req.id})" 
                                    style="margin-top:5px; padding:6px 12px; background:rgba(231,76,60,0.15); border:1px solid #e74c3c; color:#e74c3c; border-radius:8px; font-size:11px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:5px; transition:all 0.2s;" 
                                    onmouseover="this.style.background='rgba(231,76,60,0.3)'" 
                                    onmouseout="this.style.background='rgba(231,76,60,0.15)'"
                                    title="Cancel this request">
                                    <i data-lucide="trash-2" style="width:12px;"></i> Cancel
                                </button>` : '';

                            html += `
                                <div class="list-item" style="flex-direction:column; align-items:flex-start; gap:8px;">
                                    <div style="width:100%; display:flex; justify-content:space-between;">
                                        <strong style="color:var(--primary);">${req.title}</strong>
                                        <span style="font-size:10px; padding:2px 8px; border-radius:20px; border:1px solid ${statusColor}; color:${statusColor};">${req.status}</span>
                                    </div>
                                    <p style="font-size:12px; color:rgba(255,255,255,0.7);">${req.details}</p>
                                    ${replyHtml}
                                    <div style="display:flex; justify-content:space-between; width:100%; align-items:flex-end;">
                                        <div style="font-size:10px; opacity:0.4; margin-top:5px;">Req #${req.id} • ${req.created_at}</div>
                                        ${deleteBtn}
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                        if(window.lucide) lucide.createIcons();
                    } else {
                        container.innerHTML = '<div style="text-align:center; padding:20px; color:rgba(255,255,255,0.4); font-size:13px;">No requests found. Start a new one above!</div>';
                    }
                } else {
                    container.innerHTML = '<div style="text-align:center; padding:15px; color:var(--danger); font-size:13px;">Server Error: ' + json.message + '</div>';
                }
            } catch (err) {
                console.error("Fetch Error:", err);
                container.innerHTML = '<div style="text-align:center; padding:15px; color:rgba(255,255,255,0.4); font-size:12px;">Connection lost.</div>';
            }
        };

        // Note: deleteHelpRequest is already defined above with window.showConfirm support.
        // We do NOT need to redefine it here. Redefining it would overwrite the good version with potential bad logic.
        // So this block ends here.

        // --- NOTIFICATION BADGE SYSTEM ---
        window.checkForNewReplies = async function() {
            try {
                const res = await fetchWithTimeout('manage_helpdesk.php?action=fetch_user', { timeout: 10000 });
                const json = await res.json();
                
                if(json.status === 'success' && json.data) {
                    // Get previously seen reply IDs from localStorage
                    const seenReplies = JSON.parse(localStorage.getItem('seenHelpdeskReplies') || '[]');
                    
                    // Count new replies (requests with admin_reply that haven't been seen)
                    let newReplyCount = 0;
                    json.data.forEach(req => {
                        if(req.admin_reply && !seenReplies.includes(req.id)) {
                            newReplyCount++;
                        }
                    });
                    
                    // Update badge
                    const badge = document.getElementById('helpdeskNotificationBadge');
                    if(badge) {
                        if(newReplyCount > 0) {
                            badge.textContent = newReplyCount;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch(err) {
                console.log('Notification check failed:', err);
            }
        };

        // --- SETTINGS TAB SWITCHER ---
        window.switchSettingsTab = function(tabId) {
            // Update buttons
            document.querySelectorAll('.settings-nav button').forEach(btn => {
                btn.classList.remove('active');
                if(btn.textContent.toLowerCase().includes(tabId)) btn.classList.add('active');
            });
            // Update panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            const activePane = document.getElementById('tab-' + tabId);
            if(activePane) activePane.style.display = 'block';
        };

        // --- NOTIFICATION BADGE SYSTEM ---

        document.addEventListener('DOMContentLoaded', function(){
            // Initialize Lucide Icons
            lucide.createIcons();

            // Section Navigation
            const links = document.querySelectorAll('.sidebar-menu a');
            const sections = document.querySelectorAll('.content-section');
            
            window.showSection = function(id){
                sections.forEach(s => {
                    s.classList.toggle('active', s.id === id);
                if(s.id === id) {
                    // Trigger specific initializations logic with tiny delay to allow CSS transitions
                    setTimeout(() => {
                        if(id === 'section-water' && typeof initChart === 'function') initChart();
                        if(id === 'section-flood' && typeof initMiniChart === 'function') initMiniChart();
                        if(id === 'section-map' && typeof initMap === 'function') initMap();
                        if(id === 'section-help') {
                            loadMyRequests();
                            markHelpdeskAsRead(); 
                        }
                        if(id === 'section-alerts') {
                            // Clear Alert Badge when viewing
                            const badge = document.getElementById('alertBadge');
                            if(badge) {
                                badge.style.display = 'none';
                                badge.textContent = '';
                            }
                            // Update lastSeenAlertId to mark as read
                            fetch('manage_alerts.php?action=fetch_all&user_location=' + encodeURIComponent(window.userLocation || ''))
                                .then(r => r.json())
                                .then(json => {
                                    if(json.status === 'success' && json.data && json.data.length > 0) {
                                        const latest = json.data[0];
                                        localStorage.setItem('lastSeenAlertId', latest.id);
                                    }
                                });
                        }
                    }, 50);
                }
                });
                links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + id));
                try{ history.replaceState(null, '', '#' + id); } catch(e){}
            }

            links.forEach(link => {
                link.addEventListener('click', function(e){
                    e.preventDefault();
                    const target = this.getAttribute('href').replace('#','');
                    window.showSection(target);
                    if (window.innerWidth <= 1100) {
                        toggleSidebar();
                    }
                });
            });

            // Initialize first section
            window.showSection('section-flood');

            // Check for new Help Desk replies on page load
            checkForNewReplies();
            
            // Auto-refresh notification badge every 60 seconds
            setInterval(checkForNewReplies, 60000);

            // Sidebar Toggle Logic
            window.toggleSidebar = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            };

            document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

            // Live Clock (Targeting Navbar Version)
            function updateClock() {
                const now = new Date();
                const clock = document.getElementById('nav-clock');
                if(clock) {
                    clock.innerText = now.toLocaleTimeString();
                }
            }
            setInterval(updateClock, 1000);
            updateClock();

            // Settings Button Handlers
            const editProfileBtn = document.getElementById('editProfileBtn');
            const manageNotificationsBtn = document.getElementById('manageNotificationsBtn');
            const updatePasswordBtn = document.getElementById('updatePasswordBtn');

            if(editProfileBtn) {
                editProfileBtn.addEventListener('click', function() {
                    // Pre-fill location
                    const locSelect = document.getElementById('editLocation');
                    if(window.userLocation && locSelect) {
                        locSelect.value = window.userLocation;
                        if(window.checkSensorSupport) window.checkSensorSupport(window.userLocation);
                    }
                    document.getElementById('editProfileModal').style.display = 'flex';
                });
            }

            window.submitProfileUpdate = async function(e) {
                e.preventDefault();
                const form = document.getElementById('profileUpdateForm');
                const formData = new FormData(form);
                const btn = document.getElementById('saveProfileBtn');
                const originalText = btn.innerText;

                btn.innerText = "Saving...";
                btn.disabled = true;

                try {
                    const res = await fetch('update_profile.php', { method: 'POST', body: formData });
                    const json = await res.json();

                    if(json.status === 'success') {
                        // Update UI
                        window.showStatusMsg("✅ Profile Updated!", "var(--safe)");
                        document.getElementById('editProfileModal').style.display = 'none';
                        
                        // Update Global Location Variable
                        const newLoc = formData.get('location');
                        window.userLocation = newLoc;
                        
                        // Refresh Alerts immediately
                        fetchUserAlerts();
                        
                        // Update Profile Name in UI if possible
                        // (Would need to reload page or target specific elements, reloading is safer for name)
                        setTimeout(() => location.reload(), 1000); 
                    } else {
                        alert("❌ Error: " + json.message);
                    }
                } catch(err) {
                    alert("Network Error: " + err.message);
                } finally {
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            };

            if(manageNotificationsBtn) {
                manageNotificationsBtn.addEventListener('click', function() {
                    alert('🔔 Notification Preferences\n\nConfigure how and when you receive flood alerts and system notifications.\n\n(Feature coming soon!)');
                });
            }

            if(updatePasswordBtn) {
                updatePasswordBtn.addEventListener('click', function() {
                    alert('🔒 Security Settings\n\nUpdate your password and manage security preferences.\n\n(Feature coming soon!)');
                });
            }

            // Chart Initialization
            let chartInstance = null;
            let miniChartInstance = null;

            function initMiniChart(realData = null) {
                const ctxCanvas = document.getElementById('miniFloodChart');
                if(!ctxCanvas) return;
                
                if(miniChartInstance) {
                    miniChartInstance.destroy();
                    miniChartInstance = null;
                }

                let data = realData;
                if (!data) {
                    const location = window.userLocation || 'Central City';
                    const trends = {
                        'Central City': [3.1, 3.2, 3.2, 3.3, 3.2, 3.1],
                        'South Reservoir': [17.5, 17.8, 18.0, 18.1, 18.2, 18.3], 
                        'East Valley': [9.0, 9.5, 9.8, 10.2, 10.5, 10.8],
                        'West Bank': [2.8, 2.9, 3.0, 3.1, 3.1, 3.2],
                        'North District': [5.0, 5.2, 5.4, 5.5, 5.8, 6.0]
                    };
                    data = trends[location] || trends['Central City'];
                }

                const color = (data[data.length-1] > 18) ? '#e74c3c' : ((data[data.length-1] > 10) ? '#e67e22' : '#00e5ff');

                miniChartInstance = new Chart(ctxCanvas, {
                    type: 'line',
                    data: {
                        labels: data.map((_, i) => i),
                        datasets: [{
                            data: data,
                            borderColor: color,
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 0,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 0 },
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        scales: { x: { display: false }, y: { display: false } }
                    }
                });
            }

            function initChart(realLevels = null, realLabels = null) {
                const ctxCanvas = document.getElementById('waterLevelChart');
                if(!ctxCanvas) return;
                
                if(chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                const colorPrimary = '#c054ff'; 
                const colorAccent = '#00e5ff'; 

                let chartData = realLevels;
                let chartLabels = realLabels;

                if (!chartData) {
                    const location = window.userLocation || 'Central City';
                    const dataProfiles = {
                        'Central City': { data: [3.2, 3.4, 3.1, 3.5, 3.3, 3.4, 3.2], label: 'Safe' },
                        'North District': { data: [4.5, 4.8, 5.2, 5.0, 5.5, 5.8, 6.0], label: 'Moderate' },
                        'South Reservoir': { data: [12.5, 13.0, 14.2, 13.8, 15.5, 17.0, 18.2], label: 'Critical' },
                        'West Bank': { data: [2.1, 2.3, 2.2, 2.4, 2.8, 3.0, 3.1], label: 'Safe' },
                        'East Valley': { data: [8.5, 8.8, 9.2, 9.5, 10.1, 10.5, 10.8], label: 'Warning' }
                    };
                    const profile = dataProfiles[location] || dataProfiles['Central City'];
                    chartData = profile.data;
                    chartLabels = ['12 AM', '4 AM', '8 AM', '12 PM', '4 PM', '8 PM', 'Now'];
                    
                    // Update Title
                    const chartTitle = document.querySelector('#section-water h3');
                    if(chartTitle) chartTitle.innerHTML = `🌊 Water Levels - ${location} <span style="font-size:12px; opacity:0.7; margin-left:10px; color:#fff;">(${profile.label})</span>`;
                }

                chartInstance = new Chart(ctxCanvas, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: `Water Level (ft)`,
                            data: chartData,
                            borderColor: colorAccent,
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                                gradient.addColorStop(0, 'rgba(0, 229, 255, 0.4)');
                                gradient.addColorStop(1, 'rgba(0, 229, 255, 0)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#13141b',
                            pointBorderColor: colorAccent,
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 0 },
                        plugins: {
                            legend: { display: false },
                            annotation: {
                                annotations: {
                                    criticalLine: {
                                        type: 'line',
                                        yMin: 18,
                                        yMax: 18,
                                        borderColor: 'rgba(231, 76, 60, 0.5)',
                                        borderWidth: 2,
                                        borderDash: [5, 5],
                                        label: { content: 'Critical (18+)', display: true, position: 'end', backgroundColor: 'rgba(231, 76, 60, 0.8)', color: '#fff', font: { size: 10 } }
                                    },
                                    warningLine: {
                                        type: 'line',
                                        yMin: 10,
                                        yMax: 10,
                                        borderColor: 'rgba(241, 196, 15, 0.5)',
                                        borderWidth: 2,
                                        borderDash: [5, 5],
                                        label: { content: 'Warning (10+)', display: true, position: 'end', backgroundColor: 'rgba(241, 196, 15, 0.8)', color: '#fff', font: { size: 10 } }
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } },
                                beginAtZero: true,
                                max: 25
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } }
                            }
                        }
                    }
                });
            }

            // Map Initialization
            let mapInstance = null;
            function initMap() {
                if (mapInstance) {
                    // Just refresh size if already exists
                    setTimeout(() => mapInstance.invalidateSize(), 100);
                    return;
                }
                
                // Initialize Map
                const mapEl = document.getElementById('map');
                if(!mapEl) return;
                
                // Generic center to start
                mapInstance = L.map('map').setView([10.8505, 76.2711], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapInstance);

                // Layer Group for easy refreshing
                if (!window.mapMarkersLayer) {
                    window.mapMarkersLayer = L.layerGroup().addTo(mapInstance);
                }
                window.mapMarkersLayer.clearLayers();

                // Fetch Live Points
                fetch('fetch_evacuation_points.php')
                    .then(r => r.json())
                    .then(res => {
                        if(res.status === 'success' && res.data.length > 0) {
                            const bounds = [];
                            res.data.forEach(pt => {
                                let lat = parseFloat(pt.latitude);
                                let lng = parseFloat(pt.longitude);
                                
                                // Fallback for invalid coords (same as admin)
                                if(isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                                    lat = 10.8505 + (Math.random() - 0.5) * 0.1;
                                    lng = 76.2711 + (Math.random() - 0.5) * 0.1;
                                }

                                // Status Colors
                                let color = '#2ecc71'; // Available (Green)
                                if (pt.status === 'Full') color = '#e74c3c'; // Red
                                else if (pt.status === 'Closed') color = '#95a5a6'; // Grey
                                else if (pt.status !== 'Available') color = '#f1c40f'; // Warning (Yellow) for unknown states

                                const markerHtml = `<div style='background-color:${color}; width:12px; height:12px; border:2px solid #fff; border-radius:50%; box-shadow:0 0 10px ${color};'></div>`;
                                
                                const icon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: markerHtml,
                                    iconSize: [14, 14],
                                    iconAnchor: [7, 7]
                                });

                                const popupContent = `
                                    <div style="font-family: 'Outfit', sans-serif; color: #032023;">
                                        <b style="color:${color}">${pt.name}</b><br>
                                        <span style="font-size:12px;">${pt.location}</span><br>
                                        <span style="font-size:11px; font-weight:bold; color:${color}">${pt.status}</span>
                                    </div>
                                `;

                                L.marker([lat, lng], {icon: icon})
                                    .addTo(window.mapMarkersLayer)
                                    .bindPopup(popupContent);
                                
                                bounds.push([lat, lng]);
                            });

                            if(bounds.length > 0) {
                                mapInstance.fitBounds(bounds, {padding: [50, 50]});
                            }
                        }
                    })
                    .catch(e => console.error("Map Data Error:", e));

                // Force map to recalculate size after being shown - crucial for tabs!
                setTimeout(() => {
                    if(mapInstance) mapInstance.invalidateSize();
                }, 200);
            }

            // Initial Section
            const initial = (location.hash && location.hash.replace('#','')) || 'section-flood';
            showSection(initial);

            // Fetch Evacuation Points (Dynamic Grid)
            function loadEvacuationPoints() {
                fetch('fetch_evacuation_points.php')
                    .then(r => r.json())
                    .then(res => {
                        const container = document.getElementById('evacuation-list-container');
                        if(res.status === 'success' && res.data.length > 0) {
                            let html = '';
                            res.data.forEach(pt => {
                                const statusColor = pt.status === 'Available' ? 'var(--safe)' : (pt.status === 'Full' ? '#e74c3c' : '#f1c40f');
                                const statusLabel = pt.status === 'Available' ? 'Available' : pt.status;
                                
                                html += `
                                <div class="contact-card animate__animated animate__fadeInUp">
                                    <span class="contact-cat" style="background:${statusColor}20; color:${statusColor}">${statusLabel}</span>
                                    <div style="font-weight: 700; font-size: 16px; margin: 10px 0 5px;">${pt.name}</div>
                                    <div style="font-size: 12px; opacity: 0.7; margin-bottom: 15px;">
                                        <i data-lucide="map-pin" style="width:12px;"></i> ${pt.location}<br>
                                        <i data-lucide="users" style="width:12px;"></i> Capacity: ${pt.current_occupancy || 0}/${pt.capacity}
                                    </div>
                                    <a class="small-btn" href="https://www.google.com/maps/search/?api=1&query=${pt.query}" target="_blank" style="text-align: center;">
                                        <i data-lucide="navigation" style="width:14px;"></i> Get Directions
                                    </a>
                                </div>`;
                            });
                            container.innerHTML = html;
                            if(typeof lucide !== 'undefined') lucide.createIcons();
                        } else {
                            container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; opacity:0.5;">No safe points found nearby.</div>';
                        }
                    })
                    .catch(() => {
                        document.getElementById('evacuation-list-container').innerHTML = '<div style="color:var(--danger)">Failed to load data.</div>';
                    });
            }
            loadEvacuationPoints();

            // Initial load if starting on help section
            if(window.location.hash === '#section-help') window.loadMyRequests();

            // Real-time updates: Check for admin replies every 60s
            setInterval(() => {
                const hSection = document.getElementById('section-help');
                if (hSection && hSection.classList.contains('active')) {
                    window.loadMyRequests();
                }
            }, 60000);
            
            // Clear Alert Badge on Click
            const alertLink = document.querySelector('a[href="#section-alerts"]');
            if(alertLink) {
                alertLink.addEventListener('click', () => {
                   document.getElementById('alertBadge').style.display = 'none';
                   // Update seen time to now (or fetch latest alert time if cleaner)
                   // For simplicity, we assume if they clicked, they saw whatever was latest.
                   fetch('manage_alerts.php?action=fetch_all').then(r=>r.json()).then(json=>{
                       if(json.data && json.data.length > 0) {
                           localStorage.setItem('lastSeenAlertTime', new Date(json.data[0].timestamp).getTime());
                       }
                   });
                });
            }
        });
    </script>
    
        <script>
        // Existing script logic...

        // --- WEB AUDIO API (Professional & Reliable) ---
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        
        // GLOBAL UNLOCK: Verify audio context is ready on ANY interaction
        function resumeAudioContext() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume().then(() => {
                    console.log("Audio Context Resumed by Interaction");
                    if (pendingAlarmData && !isAlarmPlaying) {
                        playEmergencyAlarm(pendingAlarmData.severity, pendingAlarmData.message);
                    }
                });
            }
        }
        document.addEventListener('click', resumeAudioContext);
        document.addEventListener('keydown', resumeAudioContext);
        document.addEventListener('touchstart', resumeAudioContext);

        // --- EMERGENCY ALARM SYSTEM ---
        let alarmOscillators = [];
        let alarmInterval = null;
        let isAlarmPlaying = false;
        let pendingAlarmData = null; // Stores {severity, message} if audio blocked

        function playEmergencyAlarm(severity, message, alertId = null, shouldSiren = true) {
            // 1. Visual Popup (SweetAlert2) - Independent of Siren
            const currentAlertId = alertId || localStorage.getItem('lastAlertId');
            if (lastSwalAlertId !== currentAlertId) {
                lastSwalAlertId = currentAlertId;
                Swal.fire({
                    title: `🚨 ${severity} ALERT!`,
                    text: message,
                    icon: (severity || '').toUpperCase() === 'CRITICAL' ? 'error' : 'warning',
                    confirmButtonText: 'I UNDERSTAND',
                    background: '#1e2029',
                    color: '#fff',
                    confirmButtonColor: '#e74c3c',
                    backdrop: `rgba(231, 76, 60, 0.4)`
                });
            }

            // 2. Audible Siren Logic
            if (!shouldSiren || isAlarmPlaying) return;

            if (audioCtx.state === 'suspended') {
                console.warn("Audio Context Suspended. Alarm will start on next interaction.");
                pendingAlarmData = { severity, message };
                // Still show UI banner so user knows something is wrong
                const banner = document.getElementById('alarmBanner');
                if(banner) {
                    banner.style.display = 'flex';
                    document.getElementById('alarmStatus').textContent = severity + ": " + message;
                }
                return;
            }

            isAlarmPlaying = true;
            pendingAlarmData = null;
            
            // Show UI
            const banner = document.getElementById('alarmBanner');
            if(banner) {
                banner.style.display = 'flex';
                document.getElementById('alarmStatus').textContent = severity + ": " + message;
            }

            function createSiren(freq, startTime) {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, startTime);
                gain.gain.setValueAtTime(0, startTime);
                gain.gain.linearRampToValueAtTime(1.0, startTime + 0.1);
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(startTime);
                return { osc, gain };
            }

            // Two-tone siren loop
            alarmInterval = setInterval(() => {
                const now = audioCtx.currentTime;
                // Tone 1
                const t1 = createSiren(960, now);
                t1.gain.gain.setValueAtTime(1.0, now + 0.4);
                t1.gain.gain.linearRampToValueAtTime(0, now + 0.5);
                t1.osc.stop(now + 0.5);
                
                // Tone 2
                const t2 = createSiren(800, now + 0.5);
                t2.gain.gain.setValueAtTime(1.0, now + 0.9);
                t2.gain.gain.linearRampToValueAtTime(0, now + 1.0);
                t2.osc.stop(now + 1.0);
            }, 1000);
        }

        let lastSwalAlertId = null; // Track which alert we've shown a popup for

        window.stopEmergencyAlarm = function() {
            isAlarmPlaying = false;
            pendingAlarmData = null;
            if(alarmInterval) clearInterval(alarmInterval);
            alarmInterval = null;
            
            // Hide UI
            const banner = document.getElementById('alarmBanner');
            if(banner) banner.style.display = 'none';

            // Mark highest seen alert ID as "silenced"
            const latestId = localStorage.getItem('lastAlertId');
            if(latestId) localStorage.setItem('silencedAlertId', latestId);
        }

        function playSystemBeep() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume(); 
            }
            
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(523.25, audioCtx.currentTime); 
            oscillator.frequency.exponentialRampToValueAtTime(1046.5, audioCtx.currentTime + 0.1); 
            
            gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.5);
        }

        // Check Permission on Load and Update Button
        function updateNotificationButton() {
            const btn = document.getElementById('enableAlertsBtn');
            if(!btn) return;
            
            if (!("Notification" in window)) {
                btn.style.display = 'none';
                return;
            }

            if (Notification.permission === 'granted') {
                btn.innerHTML = '✅ Alerts Active';
                btn.style.display = 'flex';
                btn.style.borderColor = '#2ecc71';
                btn.style.color = '#2ecc71';
                btn.style.background = 'rgba(46, 204, 113, 0.1)';
                btn.style.cursor = 'default';
                btn.disabled = false; 
                // We keep it clickable just for manual testing if they really want, but looks passive
                btn.onclick = () => { playSystemBeep(); };
            } else if (Notification.permission === 'denied') {
                btn.innerHTML = '🚫 Alerts Blocked';
                btn.style.display = 'flex';
                btn.style.borderColor = '#e74c3c';
                btn.style.color = '#e74c3c';
                btn.style.background = 'rgba(231, 76, 60, 0.1)';
                btn.title = "Please enable notifications in your browser settings.";
                btn.disabled = true;
            } else {
                // Default
                btn.innerHTML = '🔔 Enable Alerts';
                btn.style.display = 'flex';
                btn.style.borderColor = '#e74c3c';
                btn.style.color = '#e74c3c';
                btn.style.background = 'rgba(231, 76, 60, 0.2)';
                btn.disabled = false;
                btn.style.cursor = 'pointer';
            }
        }

        document.addEventListener('DOMContentLoaded', updateNotificationButton);

        function requestNotificationPermission() {
             if (!("Notification" in window)) {
                alert("This browser does not support desktop notifications");
                return;
            }

            Notification.requestPermission().then(permission => {
                updateNotificationButton();
                if (permission === 'granted') {
                    // Start Audio Context logic
                    audioCtx.resume().then(() => {
                        playSystemBeep();
                    });
                }
            });
        }
        
// (Redundant fetchUserAlerts logic removed, consolidated in the Dashboard Sync Engine at the end of the file)

        window.renderAlertTerminal = function() {
            const container = document.getElementById('userAlertsList');
            const searchTerm = (document.getElementById('alertTerminalSearch')?.value || '').toUpperCase();
            const sevFilter = document.getElementById('alertSeverityFilter')?.value || 'ALL';
            
            let filtered = rawAlertRegistry.filter(a => {
                const content = `${a.message} ${a.location} ${a.severity}`.toUpperCase();
                const matchesSearch = content.includes(searchTerm);
                const matchesSev = sevFilter === 'ALL' || (a.severity || '').toUpperCase() === sevFilter;
                return matchesSearch && matchesSev;
            });

            // Improved Sort Logic
            filtered.sort((a, b) => {
                const dateA = new Date(a.timestamp.replace(/-/g, '/').replace(' ', 'T'));
                const dateB = new Date(b.timestamp.replace(/-/g, '/').replace(' ', 'T'));
                
                // Fallback for invalid dates
                const timeA = isNaN(dateA.getTime()) ? 0 : dateA.getTime();
                const timeB = isNaN(dateB.getTime()) ? 0 : dateB.getTime();
                
                return terminalAlertSort === 'desc' ? timeB - timeA : timeA - timeB;
            });

            if(filtered.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:100px; opacity:0.3;">NO ENTRIES MATCHING REGISTRY FILTERS</div>';
                return;
            }

            // Group by Date
            const groups = { 'TODAY': [], 'YESTERDAY': [], 'EARLIER': [] };
            const today = new Date(); today.setHours(0,0,0,0);
            const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);

            filtered.forEach(alert => {
                const d = new Date(alert.timestamp.replace(' ', 'T'));
                d.setHours(0,0,0,0);
                if(d.getTime() === today.getTime()) groups['TODAY'].push(alert);
                else if(d.getTime() === yesterday.getTime()) groups['YESTERDAY'].push(alert);
                else groups['EARLIER'].push(alert);
            });

            let html = '';
            let groupOrder = ['TODAY', 'YESTERDAY', 'EARLIER'];
            if(terminalAlertSort === 'asc') groupOrder.reverse();

            groupOrder.forEach(label => {
                if(groups[label].length > 0) {
                    html += `<div class="date-group-header">${label}</div>`;
                    groups[label].forEach(alert => {
                        const sev = (alert.severity || 'INFO').toUpperCase();
                        const theme = sev === 'CRITICAL' ? 'critical' : (sev === 'WARNING' ? 'warning' : (sev === 'EVACUATION' ? 'evac' : (sev === 'SYSTEM' ? 'system' : 'safe')));
                        const icon = sev === 'CRITICAL' ? 'octagon-alert' : (sev === 'WARNING' ? 'triangle-alert' : (sev === 'EVACUATION' ? 'map-pin' : (sev === 'SYSTEM' ? 'settings' : 'shield-check')));
                        const trendIcon = (alert.water_level && alert.water_level > 5) ? '<span class="trend-icon" style="color:#e74c3c;">↗</span>' : (alert.water_level ? '<span class="trend-icon" style="color:#2ecc71;">↘</span>' : '');
                        
                        html += `
                            <div class="history-item-prof alert-theme-${theme} animate__animated animate__fadeIn" onclick="window.toggleAlertDetails(this)">
                                <div class="alert-header-prof">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="${sev==='CRITICAL'?'alert-pulse':''}" style="color: ${getSevColor(sev)};">
                                            <i data-lucide="${icon}" style="width: 20px; height: 20px;"></i>
                                        </div>
                                        <span class="severity-pill pill-${theme}">${sev}</span>
                                        ${trendIcon}
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 13px; font-weight: 700; color: #fff;">${new Date(alert.timestamp).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
                                        <div style="font-size: 10px; opacity: 0.5;">${new Date(alert.timestamp).toLocaleDateString()}</div>
                                    </div>
                                </div>
                                <div style="font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 8px;">${alert.message.split('.')[0]}.</div>
                                <div style="display: flex; gap: 12px; align-items: center; font-size: 11px; opacity: 0.6;">
                                    <span style="display: flex; align-items: center; gap: 4px;"><i data-lucide="map-pin" style="width:12px;"></i> ${alert.location || 'Terminal Area'}</span>
                                    <span style="display: flex; align-items: center; gap: 4px;"><i data-lucide="cpu" style="width:12px;"></i> ${alert.alert_type || 'System'}</span>
                                </div>

                                <div class="alert-details-panel">
                                    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; font-size: 13px; line-height: 1.6; color: rgba(255,255,255,0.8);">
                                        <div style="margin-bottom: 10px;">${alert.message}</div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                                            <div>
                                                <div class="ctrl-label" style="margin-bottom: 5px;">Source Analysis</div>
                                                <div class="intelligence-tag">${alert.alert_type === 'IoT' ? 'SENSOR-DELTA' : 'OFFICIAL-COMMS'}</div>
                                            </div>
                                            <div>
                                                <div class="ctrl-label" style="margin-bottom: 5px;">Water Level</div>
                                                <div style="font-weight: 700; color: #fff;">${alert.water_level || '--'} ft</div>
                                            </div>
                                        </div>
                                        <div style="margin-top: 15px;">
                                            <div class="ctrl-label" style="margin-bottom: 8px;">Protocol Action</div>
                                            <div style="padding: 10px; background: rgba(255,255,255,0.05); border-radius: 8px; border-left: 3px solid ${getSevColor(sev)}; font-size: 12px;">
                                                ${getSuggestedAction(sev)}
                                            </div>
                                        </div>
                                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                                            <button class="btn-terminal" style="width: 100%; border-radius: 8px; padding: 8px;" onclick="event.stopPropagation(); window.showSection('section-map');">
                                                <i data-lucide="map" style="width:14px;"></i> View Hotspot
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
            });
            container.innerHTML = html;
            lucide.createIcons();
        }

        function getSevColor(sev) {
            const map = { 'CRITICAL': '#e74c3c', 'WARNING': '#f1c40f', 'SAFE': '#2ecc71', 'EVACUATION': '#3b82f6', 'SYSTEM': '#a855f7' };
            return map[sev] || '#fff';
        }

        function getSuggestedAction(sev) {
            const map = {
                'CRITICAL': 'IMMEDIATE EVACUATION. Move to designated high ground or shelter now. Do not delay.',
                'WARNING': 'INCREASED VIGILANCE. Prepare emergency kits. Monitor local comms channels for updates.',
                'SAFE': 'CONTINUE MONITORING. No immediate threats detected. Maintain standard preparedness.',
                'EVACUATION': 'PROCEED TO SHELTER. Official evacuation routes are active. Follow map markers.',
                'SYSTEM': 'TERMINAL UPDATE. Operational broadcast. No immediate weather action required.'
            };
            return map[sev] || 'Standard safety procedures remain in effect.';
        }

        window.toggleAlertSort = function() {
            terminalAlertSort = terminalAlertSort === 'desc' ? 'asc' : 'desc';
            const btn = document.getElementById('alertSortBtn');
            const icon = btn.querySelector('i');
            const text = btn.querySelector('span');
            
            if(terminalAlertSort === 'desc') {
                text.textContent = 'Newest First';
                icon.setAttribute('data-lucide', 'arrow-down-narrow-wide');
            } else {
                text.textContent = 'Oldest First';
                icon.setAttribute('data-lucide', 'arrow-up-narrow-wide');
            }
            if(window.lucide) lucide.createIcons();
            window.renderAlertTerminal();
        }

        window.filterAlertTerminal = function() {
            window.renderAlertTerminal();
        }

        window.toggleAlertDetails = function(el) {
            const all = document.querySelectorAll('.history-item-prof');
            all.forEach(item => { if(item !== el) item.classList.remove('expanded'); });
            el.classList.toggle('expanded');
        }

        function filterEvacuationPoints() {
            const input = document.getElementById('evacSearch');
            const filter = input.value.toUpperCase();
            const statusFilter = document.getElementById('evacFilter').value;
            const container = document.getElementById('evacuation-list-container');
            const items = container.querySelectorAll('.list-item, .card'); // Broaden to find both old/new styles

            items.forEach(item => {
                const text = item.innerText || item.textContent;
                const matchesSearch = text.toUpperCase().indexOf(filter) > -1;
                let matchesStatus = true;
                if(statusFilter !== 'All') {
                   const statusText = item.querySelector('.safety-badge')?.innerText || '';
                   matchesStatus = statusText.includes(statusFilter);
                }
                item.style.display = (matchesSearch && matchesStatus) ? "" : "none";
            });
        }

        function filterContacts() {
            const input = document.getElementById('contactSearch');
            const filter = input.value.toUpperCase();
            const container = document.getElementById('contacts-list-container');
            const items = container.querySelectorAll('.contact-card');

            items.forEach(item => {
                const text = item.innerText || item.textContent;
                item.style.display = (text.toUpperCase().indexOf(filter) > -1) ? "" : "none";
            });
        }
    </script>
    <!-- Custom Confirmation Modal -->
    <!-- Custom Confirmation Modal -->
    <div id="customConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#1e2029; border:2px solid #f1c40f; padding:30px; border-radius:15px; width:90%; max-width:450px; box-shadow:0 0 40px rgba(241,196,15,0.4); animation:fadeInUp 0.3s ease;">
            <div style="font-size:48px; text-align:center; margin-bottom:15px;">⚠️</div>
            <h3 style="color:#f1c40f; margin:0 0 15px 0; text-align:center; font-size:20px;">Confirm Action</h3>
            <p id="confirmMessage" style="color:rgba(255,255,255,0.9); text-align:center; font-size:15px; line-height:1.6; margin-bottom:25px;">Are you sure?</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" id="confirmCancelBtn" style="padding:12px 24px; background:transparent; border:1px solid rgba(255,255,255,0.3); color:#fff; font-weight:600; border-radius:8px; cursor:pointer; font-size:14px;">Cancel</button>
                <button type="button" id="confirmOkBtn" style="padding:12px 24px; background:#f1c40f; border:none; color:#13141b; font-weight:700; border-radius:8px; cursor:pointer; font-size:14px;">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div class="glass" style="background:#1e2029; padding:30px; border-radius:20px; width:90%; max-width:500px; border:1px solid #c054ff; box-shadow:0 0 50px rgba(192, 84, 255, 0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="color:#c054ff; font-size:22px;">👤 Edit Profile</h3>
                <button onclick="document.getElementById('editProfileModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            
            <form id="profileUpdateForm" onsubmit="window.submitProfileUpdate(event)">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:8px; color:#94a3b8; font-size:14px;">Full Name</label>
                    <input type="text" name="name" id="editName" value="<?php echo $name; ?>" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #2d2f39; background:#13141b; color:#fff;">
                </div>
                
                <div style="margin-bottom:25px;">
                    <label style="display:block; margin-bottom:8px; color:#94a3b8; font-size:14px;">Residential Area (Affects Alerts)</label>
                    <select name="location" id="editLocation" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #2d2f39; background:#13141b; color:#fff;">
                        <option value="Central City">Central City</option>
                        <option value="North District">North District</option>
                        <option value="South Reservoir">South Reservoir</option>
                        <option value="West Bank">West Bank</option>
                        <option value="East Valley">East Valley</option>
                        <option value="Churakullam">Churakullam</option>
                        <option value="Kakkikavala">Kakkikavala</option>
                        <option value="Nellimala">Nellimala</option>
                    </select>
                    <div id="sensorNotice" style="margin-top:8px; font-size:12px; color:#f1c40f; display:none; padding:10px; background:rgba(241,196,15,0.1); border-radius:8px; border:1px solid rgba(241,196,15,0.2);">
                        ℹ️ <strong>Heads up!</strong> Sensors for this area are not yet active. Automated implementation will be available soon.
                    </div>
                </div>
                
                <script>
                    window.checkSensorSupport = function(loc) {
                        const supported = ['Churakullam', 'Kakkikavala', 'Nellimala'];
                        const notice = document.getElementById('sensorNotice');
                        if (notice) {
                            notice.style.display = (supported.includes(loc) || !loc) ? 'none' : 'block';
                        }
                    };
                    
                    document.getElementById('editLocation').addEventListener('change', function() {
                        window.checkSensorSupport(this.value);
                    });
                </script>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="padding:10px 20px; background:transparent; border:1px solid #2d2f39; color:#fff; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="saveProfileBtn" style="padding:10px 25px; background:linear-gradient(135deg, #c054ff 0%, #6d28d9 100%); border:none; color:#fff; border-radius:8px; font-weight:600; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dashboard Sync Engine & Emergency Logic -->
    <script>
    (function() {
        let lastKnownLevel = 0;
        let audioCtx = null;
        // Global Sync Guard & Registry
        if (!window.aquaSafeSyncActive) {
            window.aquaSafeSyncActive = true;
            window.aquaSafeSyncRegistry = {
                alerts: null,
                safety: null,
                health: null,
                requests: null
            };
            
            window.SyncManager = {
                abort: function(key) {
                    if (window.aquaSafeSyncRegistry[key]) {
                        window.aquaSafeSyncRegistry[key].abort();
                        window.aquaSafeSyncRegistry[key] = null;
                    }
                },
                getSignal: function(key) {
                    this.abort(key);
                    window.aquaSafeSyncRegistry[key] = new AbortController();
                    return window.aquaSafeSyncRegistry[key].signal;
                }
            };
            console.log("[AquaSafe] SyncManager Initialized.");
        }
        
        window.rawAlertRegistry = [];
        window.terminalAlertSort = 'desc';
        let lastProcessedAlertId = 0;

        // --- WEB AUDIO SIREN ---
        function playAlertSiren(severity = 'INFO') {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume();
                
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                
                // Different sounds for different severities
                if (severity === 'DANGER' || severity === 'CRITICAL') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(440, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.4);
                    osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.8);
                } else {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(523.25, audioCtx.currentTime); // C5
                    osc.frequency.linearRampToValueAtTime(659.25, audioCtx.currentTime + 0.2); // E5
                }
                
                gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 1.0);
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                osc.start();
                osc.stop(audioCtx.currentTime + 1.0);
            } catch(e) { console.warn('Audio play failed', e); }
        }

        // --- GEOLOCATION ---
        window.shareLocation = function() {
            if (!navigator.geolocation) {
                return Swal.fire('Error', 'Geolocation not supported', 'error');
            }
            
            Swal.fire({
                title: 'Sharing Location...',
                text: 'Acquiring precise emergency coordinates',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            navigator.geolocation.getCurrentPosition(async pos => {
                const { latitude, longitude } = pos.coords;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'save');
                    formData.append('lat', latitude);
                    formData.append('lng', longitude);

                    const res = await fetch('manage_emergency_signals.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Signal Transmitted',
                            html: `
                                <div style="text-align:center;">
                                    <div style="font-size:40px; margin-bottom:15px;">📡</div>
                                    <div style="font-size:14px; opacity:0.8; line-height:1.6;">
                                        Coordinates: <code style="color:var(--primary);">${latitude.toFixed(5)}, ${longitude.toFixed(5)}</code><br>
                                        Your location has been broadcast to the Admin Command Center.
                                    </div>
                                </div>
                            `,
                            icon: 'success',
                            background: '#1e2029',
                            color: '#fff',
                            confirmButtonColor: 'var(--primary)'
                        });
                    } else {
                        Swal.fire('Broadcast Failed', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Network Error', 'Could not reach emergency servers.', 'error');
                }
            }, err => {
                Swal.fire('Search Failed', 'Please enable location permissions and try again.', 'error');
            }, { enableHighAccuracy: true });
        };

        // --- EMERGENCY HELP MODAL ---
        window.openHelpModal = function() {
            Swal.fire({
                title: '🆘 Request Help',
                html: `
                    <div style="text-align:left; font-size:14px; margin-bottom:15px; opacity:0.8;">Describe your situation for rescue teams:</div>
                    <input id="swalHelpTitle" class="swal2-input" style="background:#13141b; border:1px solid #2d2f39; color:#fff; font-size:14px; margin:0 0 10px 0; width:100%; box-sizing:border-box;" placeholder="Problem (e.g. Trapped by Water)">
                    <textarea id="swalHelpDetails" class="swal2-textarea" style="background:#13141b; border:1px solid #2d2f39; color:#fff; font-size:14px; margin:0; width:100%; box-sizing:border-box; height:100px;" placeholder="Details (Address, People needing help...)"></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Request',
                confirmButtonColor: '#e74c3c',
                background: '#1e2029',
                color: '#fff',
                focusConfirm: false,
                preConfirm: () => {
                    const title = document.getElementById('swalHelpTitle').value;
                    const details = document.getElementById('swalHelpDetails').value;
                    if (!title || !details) {
                        Swal.showValidationMessage('Please fill both fields');
                    }
                    return { title, details };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.submitManualHelp(result.value.title, result.value.details);
                }
            });
        };

        window.submitManualHelp = async function(title, details) {
            Swal.fire({ title: 'Sending...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            const formData = new FormData();
            formData.append('action', 'submit');
            formData.append('title', title);
            formData.append('details', details);
            
            try {
                const res = await fetch('manage_helpdesk.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire('Request Sent!', 'Your rescue ticket has been logged.', 'success');
                    if (window.loadMyRequests) window.loadMyRequests();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Failed', 'Network connection issue.', 'error');
            }
        };

        const dashboardElements = {
            hero: {
                level: 'heroLevel',
                location: 'heroLocation',
                trend: 'heroTrend',
                updated: 'heroUpdated',
                badge: 'heroBadge',
                guidance: 'heroGuidance'
            },
            alerts: 'adminAlertContent',
            intelligence: 'intelligenceList',
            evac: {
                name: 'evacName',
                loc: 'evacLoc',
                status: 'evacStatus',
                button: 'evacButton'
            },
            timeline: 'iqTimeline',
            health: 'sensorHealth'
        };

        const guidanceMsgs = {
            'SAFE': "No immediate flood risk detected. AquaSafe is monitoring real-time IoT sensors in your area. Stay informed and keep your emergency kit ready.",
            'WARNING': "Water levels are rising at a cautionary rate. Please stay alert, keep your mobile devices charged, and be prepared to move to higher ground if levels continue to increase.",
            'CRITICAL': "CRITICAL: Severe flood risk detected! Evacuate to the nearest shelter immediately. Higher ground is advised. Reach out for help via the Emergency Action Panel if needed."
        };

        // --- ALERT POLLING ENGINE ---
        async function fetchUserAlerts() {
            // Guard against overlaps using centralized registry
            const signal = window.SyncManager.getSignal('alerts');

            try {
                const res = await fetch('get_user_alerts.php?_t=' + Date.now(), { 
                    signal: signal 
                });
                if (res.status === 304) return; // Not modified
                if (!res.ok) throw new Error('Network response not ok');
                
                const data = await res.json();
                if (data.status === 'success') {
                    rawAlertRegistry = data.data;
                    
                    // 🛑 RELIABLE ALARM TRIGGER LOGIC 🛑
                    // We check the HIGHEST severity of any active alert (e.g. within the last hour or resolved=0)
                    // For AquaSafe, alerts[0] is the latest. If the latest alert is actively dangerous, we trigger.
                    if (rawAlertRegistry.length > 0) {
                        const latestAlert = rawAlertRegistry[0];
                        const sev = (latestAlert.severity || 'INFO').toUpperCase();
                        
                        if (sev === 'CRITICAL' || sev === 'WARNING') {
                            // Only show popup once per unique alert ID to avoid spamming the user every 10 seconds
                            if (lastProcessedAlertId !== latestAlert.id) {
                                playAlertSiren(sev);
                                if (window.Swal) {
                                    Swal.fire({
                                        toast: true, position: 'top-end', icon: 'warning',
                                        title: `${sev} ALERT DETECTED`,
                                        text: latestAlert.message.substring(0, 50) + '...',
                                        showConfirmButton: false, timer: 7000, timerProgressBar: true
                                    });
                                }
                                lastProcessedAlertId = latestAlert.id;
                            }
                        } else {
                            // If severity is SAFE/INFO, reset the tracker so next warnings trigger instantly
                            lastProcessedAlertId = 0;
                        }
                    }

                    renderAlertTerminal();
                    updateAlertBadge(rawAlertRegistry.length);
                    updateLatestAlertHero(rawAlertRegistry[0]);
                }
            } catch (e) {
                if (e.name !== 'AbortError') console.error("Alert Polling Error:", e);
            } finally {
                // Recursive Polling: schedule next run ONLY after current one finishes
                // 10 second interval for Alerts
                if (!window.aquaSafeSyncRegistry.alerts?.signal.aborted) {
                    setTimeout(fetchUserAlerts, 10000);
                }
            }
        }

        function updateAlertBadge(count) {
            const badge = document.getElementById('alertBadge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        function updateLatestAlertHero(alert) {
            const container = document.getElementById('adminAlertContent');
            if (!container) return;

            if (alert) {
                const sev = (alert.severity || 'INFO').toUpperCase();
                const isCritical = (sev === 'CRITICAL' || sev === 'DANGER');
                
                container.innerHTML = `
                    <div class="alert-item animate__animated animate__pulse" style="border-left: 4px solid ${isCritical ? '#e74c3c' : '#f1c40f'}; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span class="safety-badge badge-${isCritical ? 'danger' : 'warning'}" style="font-size: 10px;">${alert.alert_type} BROADCAST</span>
                            <span style="font-size: 11px; opacity: 0.5; font-weight: 600;">${new Date(alert.timestamp).toLocaleTimeString()}</span>
                        </div>
                        <div style="font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 5px;">${alert.severity} Level Alert</div>
                        <div style="font-size: 13px; opacity: 0.8; line-height: 1.5;">${alert.message}</div>
                    </div>
                `;
            } else {
                container.innerHTML = `<div style="text-align: center; padding: 30px; opacity: 0.3; font-size: 13px; font-weight: 500;">No active system broadcasts.</div>`;
            }
        }

        // --- SAFETY DATA SYNC ENGINE ---
        async function updateSafetyDashboard() {
            const signal = window.SyncManager.getSignal('safety');

            const updEl = document.getElementById(dashboardElements.hero.updated);
            const guidanceEl = document.getElementById(dashboardElements.hero.guidance);
            
            try {
                if(updEl) updEl.textContent = 'Syncing...';
                
                const res = await fetch('get_user_safety_data.php?_t=' + Date.now(), {
                    signal: signal
                });
                if (res.status === 304) {
                    if(updEl) updEl.textContent = 'Updated: ' + new Date().toLocaleTimeString();
                    return;
                }
                if (!res.ok) throw new Error(`API returned ${res.status}`);
                
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message || 'API error');

                const { iot, nearestEvac, iqHistory, location, iot_history } = data;

                // Update Hero Location
                const heroLocEl = document.getElementById(dashboardElements.hero.location);
                if (heroLocEl) {
                   heroLocEl.innerHTML = `<i data-lucide="map-pin" style="width: 14px; vertical-align: middle;"></i> ${location || window.userLocation || 'System Wide'}`;
                }

                // 1. Update Hero Card
                if (iot && iot.level !== undefined) {
                    const el = document.getElementById(dashboardElements.hero.level);
                    if(el) el.textContent = iot.level + ' ft';
                    
                    const trendEl = document.getElementById(dashboardElements.hero.trend);
                    if(trendEl) {
                        trendEl.textContent = (iot.trend === 'Rising' ? '▲ ' : iot.trend === 'Falling' ? '▼ ' : '● ') + (iot.trend || 'Stable');
                        trendEl.style.color = iot.trend === 'Rising' ? 'var(--danger)' : 'var(--safe)';
                    }

                    if(updEl) updEl.textContent = 'Updated: ' + new Date(iot.timestamp).toLocaleTimeString();

                    const badgeEl = document.getElementById(dashboardElements.hero.badge);
                    const status = (iot.status || 'SAFE').toUpperCase();
                    if(badgeEl) {
                        badgeEl.className = 'safety-badge ' + (status === 'SAFE' ? 'badge-safe' : (status === 'WARNING' ? 'badge-warning' : 'badge-danger'));
                        badgeEl.innerHTML = `<i data-lucide="${status === 'SAFE' ? 'shield-check' : 'alert-triangle'}"></i> <span>Safety Status: ${status}</span>`;
                    }

                    if(guidanceEl) guidanceEl.textContent = guidanceMsgs[status] || guidanceMsgs['SAFE'];
                    lastKnownLevel = iot.level;
                }

                // 2. Intelligence Feed
                const intelContainer = document.getElementById(dashboardElements.intelligence);
                if (intelContainer) {
                    const currentHistory = iot_history || [];
                    if (currentHistory.length > 0) {
                        intelContainer.innerHTML = currentHistory.slice(0, 3).map(h => `
                            <div class="intel-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600;">${h.level} ft - ${h.location}</div>
                                    <div style="font-size: 11px; opacity: 0.5;">${new Date(h.timestamp).toLocaleTimeString()}</div>
                                </div>
                                <span class="safety-badge ${h.status === 'SAFE' ? 'badge-safe' : 'badge-danger'}" style="font-size: 9px; padding: 2px 8px;">${h.status}</span>
                            </div>
                        `).join('');
                    }
                }

                // 3. Evacuation
                const evacNameEl = document.getElementById(dashboardElements.evac.name);
                if (evacNameEl && nearestEvac) {
                    const locEl = document.getElementById(dashboardElements.evac.loc);
                    const statEl = document.getElementById(dashboardElements.evac.status);
                    const btnEl = document.getElementById(dashboardElements.evac.button);
                    evacNameEl.textContent = nearestEvac.name;
                    if(locEl) locEl.textContent = nearestEvac.location;
                    if(statEl) {
                        statEl.textContent = nearestEvac.status;
                        statEl.className = 'safety-badge ' + (nearestEvac.status === 'Open' ? 'badge-safe' : 'badge-danger');
                    }
                    if(btnEl) btnEl.href = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(nearestEvac.location)}`;
                }

                // 4. Timeline
                const timelineContainer = document.getElementById(dashboardElements.timeline);
                if (timelineContainer && iqHistory) {
                    timelineContainer.innerHTML = iqHistory.slice(0, 4).map(h => {
                        const status = (h.severity || 'SAFE').toUpperCase();
                        return `
                        <div class="timeline-item">
                            <div class="item-icon"><i data-lucide="${status === 'SAFE' ? 'check' : 'alert-circle'}"></i></div>
                            <div style="flex: 1;">
                                <div style="font-size: 12px; font-weight: 700; display: flex; justify-content: space-between;">
                                    <span>${status} Event</span>
                                    <span style="font-size: 10px; opacity: 0.5;">${new Date(h.timestamp).toLocaleTimeString()}</span>
                                </div>
                                <div style="font-size: 11px; opacity: 0.7; margin-top: 2px;">${h.message}</div>
                            </div>
                        </div>
                    `;}).join('');
                }

                if (window.lucide) lucide.createIcons();

            } catch(e) {
                if (e.name !== 'AbortError') {
                    console.warn('Dashboard sync failed:', e);
                    if(updEl) updEl.textContent = 'Sync Failed';
                }
            } finally {
                // Recursive Polling: schedule next run ONLY after current one finishes
                // 10 second interval for Safety Data
                if (!window.aquaSafeSyncRegistry.safety?.signal.aborted) {
                    setTimeout(updateSafetyDashboard, 10000);
                }
            }
        }

        // --- DASHBOARD SYNC ENGINE (OPTIMIZED) ---
        // --- DASHBOARD SYNC INITIATION (STAGGERED) ---
        (function initSync() {
            // Prevent duplicate boot sequences
            if (window.aquaSafeSyncBooted) return;
            window.aquaSafeSyncBooted = true;

            console.log("[SyncManager] Initializing staggered boot sequence...");
            
            // 1. Alerts (High Priority) - 0 delay
            setTimeout(() => {
                console.log("[SyncManager] Booting Alerts...");
                fetchUserAlerts();
            }, 0);

            // 2. Safety Data (IoT) - 300ms delay
            setTimeout(() => {
                console.log("[SyncManager] Booting Safety Dashboard...");
                updateSafetyDashboard();
            }, 300);

            // 3. User Requests / Health (Lower Priority) - 600ms delay
            setTimeout(() => {
                if (typeof loadMyRequests === 'function') {
                    console.log("[SyncManager] Booting Requests...");
                    loadMyRequests();
                }
            }, 5000);
        })();

        // Global manual refresh (Used by sync buttons)
        window.refreshSafetyDashboard = function() {
            console.log("[SyncManager] Manual force-sync requested.");
            // Signal abort for existing tasks; they will re-schedule themselves on call
            window.SyncManager.abort('safety');
            window.SyncManager.abort('alerts');
            
            // Immediate trigger (Recursive chains will reset from here)
            updateSafetyDashboard();
            setTimeout(() => fetchUserAlerts(), 500);
        };

        window.fetchUserAlerts = fetchUserAlerts; // For historical compatibility
    })();
    </script>
</body>
</html>
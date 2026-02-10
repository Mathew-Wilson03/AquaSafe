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
            /* backdrop-filter: blur(12px); Removed for solid professional look */
            /* -webkit-backdrop-filter: blur(12px); */
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
        
        /* Custom scrollbar for sidebar */
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

        .sidebar-footer a {
            color: var(--accent) !important;
            margin-bottom: 0;
        }
        
        .sidebar-footer a:hover {
            background: rgba(255,141,133,0.1);
            color: var(--accent) !important;
        }

        .sidebar-footer { 
            border-top: 1px solid rgba(255,255,255,0.1); 
            padding-top: 20px; 
        }

        /* --- Icon Animations --- */
        @keyframes spin-slow { 100% { transform: rotate(360deg); } }
        @keyframes bounce-gentle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
        @keyframes pulse-soft { 0% { opacity: 0.8; transform: scale(1); } 50% { opacity: 1; transform: scale(1.1); } 100% { opacity: 0.8; transform: scale(1); } }

        /* Apply to Lucide Icons specifically */
        .sidebar-menu a i, .sidebar-footer a i {
            transition: transform 0.3s ease;
        }

        .sidebar-menu a:hover i {
            animation: bounce-gentle 1s infinite;
            color: var(--primary);
        }

        .sidebar-header i {
            animation: spin-slow 10s linear infinite;
        }

        .card-icon {
            transition: transform 0.3s ease;
        }
        
        .list-item:hover .card-icon, .card:hover .card-icon {
            transform: scale(1.2) rotate(5deg);
        }
        
        /* Specific Icon Tweaks */
        i[data-lucide="alert-triangle"] { animation: pulse-soft 2s infinite; color: var(--warning); }
        i[data-lucide="alert-octagon"] { animation: pulse-soft 1s infinite; color: var(--danger); }
        i[data-lucide="settings"]:hover { animation: spin-slow 4s linear infinite; }

        /* Main Content */
        .main { flex: 1; display: flex; flex-direction: column; gap: 24px; }

        .header { 
            padding: 20px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .header-left h1 { 
            font-size: 24px; 
            font-weight: 700; 
            background: linear-gradient(to right, #fff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-right { display:flex; align-items:center; gap:20px; }
        
        .last-updated { 
            background: rgba(0,0,0,0.4); 
            padding: 10px 16px; 
            border-radius: 50px; 
            font-size: 13px; 
            color: var(--primary); 
            border: 1px solid rgba(74,181,196,0.3); 
            display:flex; 
            align-items:center; 
            gap:8px;
            font-weight: 600;
        }

        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: #032023;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }

        /* Content Sections */
        .content-section { display: none; width: 100%; }
        .content-section.active { display: block; animation: fadeInUp 0.5s ease both; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
        .grid.full { grid-template-columns: 1fr; }

        .card { 
            padding: 24px; 
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .card h3 { 
            margin-bottom: 20px; 
            font-size: 18px; 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
        }

        /* Status Pills */
        .status-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .status-pill { 
            padding: 12px 10px; 
            border-radius: 12px; 
            text-align: center; 
            font-size: 14px;
            font-weight: 700; 
            transition: all 0.3s ease;
            opacity: 0.5;
            cursor: default;
        }
        .status-pill.active { 
            opacity: 1; 
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .safe { background: rgba(46,204,113,0.1); color: var(--safe); border: 2px solid rgba(46,204,113,0.2); }
        .warning { background: rgba(241,196,15,0.1); color: var(--warning); border: 2px solid rgba(241,196,15,0.2); }
        .danger { background: rgba(231,76,60,0.1); color: var(--danger); border: 2px solid rgba(231,76,60,0.2); }

        /* Lists */
        .list-container { display: flex; flex-direction: column; gap: 12px; }
        .list-item { 
            padding: 16px; 
            border-radius: 14px; 
            background: rgba(0,0,0,0.25); 
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-item:hover {
            background: rgba(255,255,255,0.05);
            transform: translateY(-2px);
            border-color: rgba(74,181,196,0.2);
        }

        .small-btn { 
            padding: 8px 16px; 
            border-radius: 10px; 
            background: rgba(74,181,196,0.15); 
            color: var(--primary); 
            border: 1px solid rgba(74,181,196,0.2); 
            text-decoration: none; 
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .small-btn:hover { background: var(--primary); color: #032023; }

        /* Forms */
        .help-form { display: flex; flex-direction: column; gap: 12px; }
        .help-form input, .help-form textarea { 
            width: 100%; 
            padding: 14px; 
            border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.1); 
            background: rgba(0,0,0,0.3); 
            color: #fff; 
            font-family: inherit;
            transition: all 0.3s;
        }
        .help-form input:focus, .help-form textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(0,0,0,0.4);
        }
        .help-form button { 
            background: var(--primary); 
            color: #032023; 
            padding: 14px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-weight: 700; 
            transition: all 0.3s;
        }
        .help-form button:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(74,181,196,0.3); }

        /* Map and Charts */
        #map { height: 400px; border-radius: 16px; margin-top: 10px; z-index: 10; }
        .chart-container { position: relative; height: 300px; width: 100%; }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Floating Weather Widget */
        .weather-widget {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            background: rgba(74,181,196,0.1);
            border-radius: 20px;
            border: 1px solid rgba(74,181,196,0.2);
            margin-bottom: 20px;
        }

        @media (max-width: 1100px) { 
            .container { flex-direction: column; padding: 15px; }
            .sidebar { 
                position: fixed;
                left: -320px;
                top: 0;
                height: 100vh;
                z-index: 2000;
                width: 280px;
                transition: left 0.3s ease;
                border-radius: 0 20px 20px 0;
                overflow-y: auto; /* Allow scrolling if menu is long */
                max-height: 100vh;
            }
            .sidebar.active { left: 0; }
            .main { gap: 15px; }
            .grid { grid-template-columns: 1fr; gap: 15px; }
            .mobile-toggle { display: block !important; }
            .header { padding: 15px; flex-wrap: wrap; gap: 15px; }
            .header-right { gap: 10px; width: 100%; justify-content: space-between; }
            .user-profile span { display: none; }
            .last-updated { flex: 1; justify-content: center; }
        }

        @media (max-width: 600px) {
            .status-row { grid-template-columns: 1fr; }
            .weather-widget { flex-direction: column; text-align: center; gap: 10px; }
            .weather-widget div:last-child { margin-left: 0; text-align: center; width: 100%; display: flex; justify-content: space-around; }
            .header-left h1 { 
                font-size: 24px; 
                font-weight: 700; 
                background: linear-gradient(to right, #fff, var(--primary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                display: flex; align-items: center; gap: 10px;
            }
            .header-left h1::before {
                content: '👤';
                -webkit-text-fill-color: initial;
                color: #fff;
                font-size: 30px;
            }
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
            z-index: 1999;
        }

        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>
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
                    <span id="alertBadge" style="display:none; position:absolute; top:8px; right:8px; background:#e74c3c; color:#fff; border-radius:50%; width:10px; height:10px; box-shadow:0 0 5px #e74c3c;"></span>
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
            <div class="header glass animate__animated animate__fadeInDown">
                <div class="header-left" style="display: flex; align-items: center;">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        ☰
                    </button>
                    <h1>User Dashboard</h1>
                </div>
                <div class="header-right">
                    <!-- Notification Permission Button -->
                    <button id="enableAlertsBtn" onclick="requestNotificationPermission()" style="display:none; padding:8px 16px; background:rgba(231,76,60,0.2); border:1px solid #e74c3c; color:#e74c3c; border-radius:30px; cursor:pointer; font-size:12px; font-weight:600; align-items:center; gap:6px; transition:all 0.3s;">
                        🔔 Enable Alerts
                    </button>

                    <div id="live-clock" class="last-updated">
                        🕒 <span>00:00:00</span>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                        <div style="text-align:left">
                            <strong style="display:block; font-size:14px;"><?php echo $name; ?></strong>
                            <span style="font-size:11px; color:rgba(255,255,255,0.6);"><?php echo $email; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <!-- Weather Widget -->
                <div class="weather-widget glass animate__animated animate__fadeIn">
                    <span style="font-size: 32px;">🌤️</span>
                    <div>
                        <div style="font-size: 18px; font-weight: 700;">28°C</div>
                        <div style="font-size: 12px; color: rgba(255,255,255,0.6);">Partly Cloudy — Riverbend Area</div>
                    </div>
                    <div style="margin-left: auto; text-align: right;">
                        <div style="font-size: 11px; color: var(--primary);">Humidity: 65%</div>
                        <div style="font-size: 11px; color: var(--primary);">Precipitation: 10%</div>
                    </div>
                </div>

                <div class="grid">
                    <div id="section-flood" class="content-section">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <!-- Status Card -->
                            <div class="card glass" style="padding: 25px; display:flex; flex-direction:column; justify-content:center;">
                                <script>
                                    (function() {
                                        const loc = window.userLocation || 'Central City';
                                        const statusMap = {
                                            'Central City': { color: 'var(--safe)', text: 'Normal', icon: '🛡️', msg: 'Levels safe.' },
                                            'North District': { color: '#f1c40f', text: 'Moderate', icon: '⚠️', msg: 'Levels elevated.' },
                                            'South Reservoir': { color: 'var(--danger)', text: 'CRITICAL', icon: '🚨', msg: 'IMMEDIATE ACTION.' },
                                            'West Bank': { color: 'var(--safe)', text: 'Normal', icon: '✅', msg: 'No risk.' },
                                            'East Valley': { color: '#e67e22', text: 'Warning', icon: '🌧️', msg: 'Heavy rain.' }
                                        };
                                        const s = statusMap[loc] || statusMap['Central City'];
                                        
                                        document.write(`
                                            <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
                                                <div style="font-size: 48px; animation: pulse 2s infinite;">${s.icon}</div>
                                                <div>
                                                    <h2 style="color:${s.color}; font-size: 28px; margin:0;">${s.text}</h2>
                                                    <div style="font-size:14px; opacity:0.7;">Zone: ${loc}</div>
                                                </div>
                                            </div>
                                            <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:15px; margin-bottom:15px;">
                                                <div style="font-size:12px; opacity:0.6; margin-bottom:5px;">Current Water Level</div>
                                                <div style="display:flex; justify-content:space-between; align-items:end;">
                                                    <div style="font-size:32px; font-weight:bold; color:#fff;">${loc === 'South Reservoir' ? '18.2 ft' : (loc === 'East Valley' ? '10.8 ft' : '3.2 ft')}</div>
                                                    <div style="font-size:14px; color:${s.color}; padding-bottom:5px;">${loc === 'South Reservoir' ? '▲ Rising' : '● Stable'}</div>
                                                </div>
                                            </div>
                                            <p style="font-size:13px; color:rgba(255,255,255,0.7); line-height:1.4; margin:0;">${s.msg}</p>
                                        `);
                                    })();
                                </script>
                            </div>

                            <!-- Mini Chart & Updates -->
                            <div style="display:flex; flex-direction:column; gap:20px;">
                                <div class="card glass" style="padding: 20px; flex:1;">
                                    <h4 style="margin-bottom:15px; font-size:16px; color:var(--accent);">🌊 24h Trend</h4>
                                    <div style="height:120px; width:100%;">
                                        <canvas id="miniFloodChart"></canvas>
                                    </div>
                                </div>
                                <div class="card glass" style="padding: 20px; flex:1;">
                                    <h4 style="margin-bottom:10px; font-size:16px; color:#fff;">📅 Updates</h4>
                                    <div style="font-size:13px; color:rgba(255,255,255,0.6); display:flex; align-items:center; gap:10px;">
                                        <span>📢</span> System monitoring active.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="section-evac" class="content-section">
                        <div class="card glass">
                                <h3>📍 Evacuation Points</h3>
                                
                                <!-- Search & Filter -->
                                <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                                    <input type="text" id="evacSearch" onkeyup="filterEvacuationPoints()" placeholder="🔍 Search location..." 
                                           style="flex:1; padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white;">
                                    
                                    <select id="evacFilter" onchange="filterEvacuationPoints()" 
                                            style="padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; cursor:pointer;">
                                        <option value="All" style="background: #1e2029; color: white;">All Status</option>
                                        <option value="Open" style="background: #1e2029; color: white;">Available</option>
                                        <option value="Full" style="background: #1e2029; color: white;">Full</option>
                                        <option value="Closed" style="background: #1e2029; color: white;">Closed</option>
                                    </select>
                                </div>

                                <div class="list-container" id="evacuation-list-container">
                                    <div style="text-align:center; padding:20px; color:rgba(255,255,255,0.5);">Loading points...</div>
                                </div>
                        </div>
                    </div>

                    <div id="section-alerts" class="content-section">
                        <div class="card glass">
                            <h3>🚨 System Alerts</h3>
                            <div id="userAlertsList" class="list-container">
                                <div style="text-align:center; padding: 20px; opacity: 0.6;">Loading latest alerts...</div>
                            </div>
                        </div>
                    </div>

                    <div id="section-contacts" class="content-section">
                        <div class="card glass">
                            <h3>📞 Emergency Contacts</h3>
                            
                            <!-- Search Bar -->
                            <div style="margin-bottom:15px;">
                                <input type="text" id="contactSearch" onkeyup="filterContacts()" placeholder="🔍 Search contacts..." 
                                       style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white;">
                            </div>

                            <div class="list-container" id="contacts-list-container">
                                <div class="list-item">
                                    <div>
                                        <strong>Panchayat Office</strong>
                                        <div style="font-size:12px; color:rgba(255,255,255,0.6);">General Assistance</div>
                                    </div>
                                    <a class="small-btn" href="tel:01234567890">Call Now</a>
                                </div>
                                <div class="list-item">
                                    <div>
                                        <strong>Disaster Control Room</strong>
                                        <div style="font-size:12px; color:rgba(255,255,255,0.6);">24/7 Helpline</div>
                                    </div>
                                    <a class="small-btn" href="tel:09876543210">Call Now</a>
                                </div>
                                <div class="list-item">
                                    <div>
                                        <strong>Medical Emergency</strong>
                                        <div style="font-size:12px; color:rgba(255,255,255,0.6);">Ambulance (108/102)</div>
                                    </div>
                                    <a class="small-btn" href="tel:108">Call Now</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div id="section-help" class="content-section">
                        <div class="card glass">
                            <h3>🆘 Help Desk</h3>
                            <div id="helpdesk-status-msg"></div>
                            
                            <form class="help-form" id="helpRequestForm" method="POST" onsubmit="window.submitHelpRequest(event)">
                                <input type="text" name="title" id="helpTitle" placeholder="Short title (e.g. Rising water level)" required>
                                <textarea name="details" id="helpDetails" rows="3" placeholder="Describe your request or situation..." required></textarea>
                                <button type="submit" id="helpSubmitBtn"><i data-lucide="send" style="width:16px; display:inline-block; vertical-align:middle; margin-right:6px;"></i> Send Request</button>
                            </form>

                            <h4 style="margin: 25px 0 10px; font-size: 14px; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="history" style="width:16px;"></i> My Recent Requests
                            </h4>
                            <div class="list-container" id="my-requests-list">
                                <div style="text-align:center; padding:20px; color:rgba(255,255,255,0.4); font-size:13px;">Fetching your requests...</div>
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
                            <h3><i data-lucide="settings"></i> Account Settings</h3>
                            <div class="list-container">
                                <div class="list-item">
                                    <span>Profile Information</span>
                                    <button class="small-btn" id="editProfileBtn" style="position:relative; z-index:10;">Edit</button>
                                </div>
                                <div class="list-item">
                                    <span>Notification Preferences</span>
                                    <button class="small-btn" id="manageNotificationsBtn" style="position:relative; z-index:10;">Manage</button>
                                </div>
                                <div class="list-item">
                                    <span>Security & Password</span>
                                    <button class="small-btn" id="updatePasswordBtn" style="position:relative; z-index:10;">Update</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // --- ULTIMATE DIAGNOSTICS ---
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error("Global Error:", msg, "at", url, ":", lineNo);
            // alert("❌ SYSTEM ERROR: " + msg + "\nLine: " + lineNo);
            return false;
        };

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
            window.showConfirm("Are you sure you want to CANCEL/DELETE this request?", async function() {
                const formData = new FormData();
                formData.append('action', 'delete'); // Correct action 'delete'
                formData.append('id', id);

                try {
                    const res = await fetch('manage_helpdesk.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if(json.status === 'success') {
                        window.showStatusMsg("Request cancelled.", "var(--safe)");
                        window.loadMyRequests();
                    } else {
                        alert("Failed: " + (json.message || "Action not supported"));
                    }
                } catch(e) {
                    alert("Network Error");
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

                            const isPending = req.status === 'Pending';
                            const deleteBtn = isPending ? `
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

        window.markHelpdeskAsRead = function() {
            // Mark all current requests with replies as "seen"
            fetchWithTimeout('manage_helpdesk.php?action=fetch_user', { timeout: 10000 })
                .then(res => res.json())
                .then(json => {
                    if(json.status === 'success' && json.data) {
                        const repliedIds = json.data
                            .filter(req => req.admin_reply)
                            .map(req => req.id);
                        localStorage.setItem('seenHelpdeskReplies', JSON.stringify(repliedIds));
                        
                        // Hide badge
                        const badge = document.getElementById('helpdeskNotificationBadge');
                        if(badge) badge.style.display = 'none';
                    }
                })
                .catch(err => console.log('Mark as read failed:', err));
        };

        document.addEventListener('DOMContentLoaded', function(){
            // Initialize Lucide Icons
            lucide.createIcons();

            // Section Navigation
            const links = document.querySelectorAll('.sidebar-menu a');
            const sections = document.querySelectorAll('.content-section');
            
            function showSection(id){
                sections.forEach(s => {
                    s.classList.toggle('active', s.id === id);
                    if(s.id === id) {
                    if(s.id === id) {
                        // Trigger specific initializations logic with tiny delay to allow CSS transitions
                        setTimeout(() => {
                            if(id === 'section-water') initChart();
                            if(id === 'section-flood') initMiniChart();
                            if(id === 'section-map') initMap();
                            if(id === 'section-help') {
                                loadMyRequests();
                                markHelpdeskAsRead(); 
                            }
                        }, 50);
                    }
                    }
                });
                links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + id));
                try{ history.replaceState(null, '', '#' + id); } catch(e){}
            }

            links.forEach(link => {
                link.addEventListener('click', function(e){
                    e.preventDefault();
                    const target = this.getAttribute('href').replace('#','');
                    showSection(target);
                    if (window.innerWidth <= 1100) {
                        toggleSidebar();
                    }
                });
            });

            // Initialize first section
            showSection('section-flood');

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

            // Live Clock
            function updateClock() {
                const now = new Date();
                const clock = document.querySelector('#live-clock span');
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

            function initMiniChart() {
                const ctxCanvas = document.getElementById('miniFloodChart');
                if(!ctxCanvas) return;
                
                if(miniChartInstance) {
                    miniChartInstance.destroy();
                    miniChartInstance = null;
                }

                const location = window.userLocation || 'Central City';
                // Simplified trends
                const trends = {
                    'Central City': [3.1, 3.2, 3.2, 3.3, 3.2, 3.1],
                    'South Reservoir': [17.5, 17.8, 18.0, 18.1, 18.2, 18.3], 
                    'East Valley': [9.0, 9.5, 9.8, 10.2, 10.5, 10.8],
                    'West Bank': [2.8, 2.9, 3.0, 3.1, 3.1, 3.2],
                    'North District': [5.0, 5.2, 5.4, 5.5, 5.8, 6.0]
                };
                const data = trends[location] || trends['Central City'];
                const color = location === 'South Reservoir' ? '#e74c3c' : (location === 'East Valley' ? '#e67e22' : '#00e5ff');

                // Ensure parent dimensions are set
                ctxCanvas.style.width = '100%';
                ctxCanvas.style.height = '100%';

                miniChartInstance = new Chart(ctxCanvas, {
                    type: 'line',
                    data: {
                        labels: ['1', '2', '3', '4', '5', '6'],
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
                        animation: { duration: 0 }, // Instant render for reliability
                        plugins: { legend: { display: false }, tooltip: { enabled: false } },
                        scales: { x: { display: false }, y: { display: false } }
                    }
                });
            }

            function initChart() {
                const ctxCanvas = document.getElementById('waterLevelChart');
                if(!ctxCanvas) return;
                
                // Ensure visibility
                if(chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                // Cyber Theme Colors
                const colorPrimary = '#c054ff'; 
                const colorAccent = '#00e5ff'; 

                // Dynamic Data based on Location
                const location = window.userLocation || 'Central City';

                // Define data profiles
                const dataProfiles = {
                    'Central City': { data: [3.2, 3.4, 3.1, 3.5, 3.3, 3.4, 3.2], label: 'Safe' },
                    'North District': { data: [4.5, 4.8, 5.2, 5.0, 5.5, 5.8, 6.0], label: 'Moderate' },
                    'South Reservoir': { data: [12.5, 13.0, 14.2, 13.8, 15.5, 17.0, 18.2], label: 'Critical' }, // High water
                    'West Bank': { data: [2.1, 2.3, 2.2, 2.4, 2.8, 3.0, 3.1], label: 'Safe' },
                    'East Valley': { data: [8.5, 8.8, 9.2, 9.5, 10.1, 10.5, 10.8], label: 'Warning' }
                };

                const profile = dataProfiles[location] || dataProfiles['Central City'];
                
                // Update Card Title if possible
                const chartTitle = document.querySelector('#section-water h3');
                if(chartTitle) chartTitle.innerHTML = `🌊 Water Levels - ${location} <span style="font-size:12px; opacity:0.7; margin-left:10px; color:#fff;">(${profile.label})</span>`;

                // Force layout
                ctxCanvas.style.width = '100%';
                ctxCanvas.style.height = '100%';

                chartInstance = new Chart(ctxCanvas, {
                    type: 'line',
                    data: {
                        labels: ['12 AM', '4 AM', '8 AM', '12 PM', '4 PM', '8 PM', 'Now'],
                        datasets: [{
                            label: `Water Level (${location})`,
                            data: profile.data,
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
                        animation: { duration: 0 }, // Instant render to avoid invisible charts
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
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } },
                                beginAtZero: false
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit' } }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
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

            // Fetch Evacuation Points (Dynamic)
            function loadEvacuationPoints() {
                fetch('fetch_evacuation_points.php')
                    .then(r => r.json())
                    .then(res => {
                        const container = document.getElementById('evacuation-list-container');
                        if(res.status === 'success' && res.data.length > 0) {
                            let html = '';
                            res.data.forEach(pt => {
                                const statusColor = pt.status === 'Available' ? 'var(--safe)' : (pt.status === 'Full' ? 'var(--danger)' : 'var(--warning)');
                                const statusLabel = pt.status === 'Available' ? 'Open' : pt.status;
                                
                                html += `
                                <div class="list-item">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <strong style="color: #fff;">${pt.name}</strong>
                                            <span style="font-size:10px; padding:2px 8px; border-radius:10px; border:1px solid ${statusColor}; color:${statusColor};">${statusLabel}</span>
                                        </div>
                                        <div style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 4px;">
                                            Location: ${pt.location} • Capacity: ${pt.current_occupancy || 0}/${pt.capacity}
                                        </div>
                                    </div>
                                    <a class="small-btn" href="https://www.google.com/maps/search/?api=1&query=${pt.query}" target="_blank">Directions</a>
                                </div>`;
                            });
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<div style="padding:20px; text-align:center; opacity:0.6;">No safe points found nearby.</div>';
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
                });
            }
        }
        document.addEventListener('click', resumeAudioContext);
        document.addEventListener('keydown', resumeAudioContext);
        document.addEventListener('touchstart', resumeAudioContext);

        function playSystemBeep() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume(); // Try one last time just in case
            }
            
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(523.25, audioCtx.currentTime); // C5 (High Beep)
            oscillator.frequency.exponentialRampToValueAtTime(1046.5, audioCtx.currentTime + 0.1); // Ramp to C6
            
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
        
        async function fetchUserAlerts() {
            const container = document.getElementById('userAlertsList');
            if(!container) return;
            
            try {
                // Targeted Broadcast Logic + Cache Buster
                const userLocParam = window.userLocation ? `&user_location=${encodeURIComponent(window.userLocation)}` : '';
                const cacheBuster = `&_t=${Date.now()}`;
                
                const res = await fetch(`manage_alerts.php?action=fetch_all${userLocParam}${cacheBuster}`);
                const json = await res.json();
                
                if(json.status === 'success') {
                    const alerts = json.data || [];
                    
                    // --- NEW ALERT DETECTION ---
                    if(alerts.length > 0) {
                        const latestAlert = alerts[0];
                        // SAFE DATE PARSING (Handle SQL format for Safari/Mobile)
                        const safeTimeStr = latestAlert.timestamp.replace(" ", "T"); 
                        const currentLatestTime = new Date(safeTimeStr).getTime();
                        
                        const lastSeenTime = localStorage.getItem('lastSeenAlertTime');
                        
                        // DEBUG LOGS (Check Console)
                        // console.log("Latest Alert Time:", currentLatestTime, "Stored Last Seen:", lastSeenTime);

                        // If we have a stored time, and the new alert is newer
                        if(lastSeenTime && currentLatestTime > parseInt(lastSeenTime)) {
                            console.log("!!! NEW ALERT DETECTED !!! NOTIFYING USER...");
                            
                            // Show Badge
                            const badge = document.getElementById('alertBadge');
                            if(badge) badge.style.display = 'flex';
                            
                            // PLAY SOUND ALWAYS (Regardless of tab)
                            try {
                                playSystemBeep();
                                console.log("System beep triggered");
                            } catch (e) {
                                console.error("Beep failed", e);
                            }
                            
                            // Show browser notification if possible
                            if("Notification" in window && Notification.permission === "granted") {
                                new Notification("New AquaSafe Alert", { body: latestAlert.message, icon: '../assets/logo.png' });
                            }
                            
                            // Update LocalStorage AFTER notifying (so it doesn't loop forever, 
                            // BUT we want it to loop if they reload? No, once notified is enough for that specific alert instance)
                            // We update lastSeenTime ONLY when they acknowledge it or we've notified them?
                            // Logic: If sound played, we can update it? 
                            // User Request: "I should hear sound when new messages are received"
                            // If we update it immediately, it won't play on next poll. That is correct behavior (play ONCE per alert).
                            localStorage.setItem('lastSeenAlertTime', currentLatestTime);
                        }
                        
                        // If this is the *very first* run (no localStorage), just set it without noise
                        // This prevents blasting old alerts on first login.
                        if(!lastSeenTime) {
                            localStorage.setItem('lastSeenAlertTime', currentLatestTime);
                        }
                    }
                    // ---------------------------

                    if(alerts.length > 0) {
                        let html = '';
                        alerts.forEach(alert => {
                            const isCrit = alert.severity === 'Critical';
                            const borderColor = isCrit ? '#e74c3c' : (alert.severity === 'Warning' ? '#f1c40f' : '#4ab5c4');
                            const bgColor = isCrit ? 'rgba(231, 76, 60, 0.1)' : 'rgba(255,255,255,0.05)';
                            
                            html += `
                                <div class="list-item" style="border-left: 4px solid ${borderColor}; background: ${bgColor}; align-items: flex-start; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; justify-content: space-between; width: 100%;">
                                        <strong style="color: ${borderColor};">${alert.severity}</strong>
                                        <span style="font-size: 11px; opacity: 0.6;">${new Date(alert.timestamp).toLocaleTimeString()}</span>
                                    </div>
                                    <div style="font-size: 14px; line-height: 1.4;">${alert.message}</div>
                                    <div style="font-size: 11px; opacity: 0.5; display: flex; gap: 5px; align-items: center;">
                                        <i data-lucide="map-pin" style="width: 12px;"></i> ${alert.location || 'System Wide'}
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                        lucide.createIcons();
                    } else {
                        container.innerHTML = '<div style="text-align:center; padding:30px; opacity:0.5;">No active alerts at the moment. Stay safe! 🛡️</div>';
                    }
                }
            } catch(e) {
                console.error("Alert Fetch Error:", e);
                container.innerHTML = '<div style="text-align:center; color:#e74c3c;">Failed to load alerts.</div>';
            }
        }
        
        // Initial Fetch
        fetchUserAlerts();
        setInterval(fetchUserAlerts, 5000); // Poll every 5s for testing

        // --- NEW FILTERING LOGIC ---
        function filterEvacuationPoints() {
            const input = document.getElementById('evacSearch');
            const filter = input.value.toUpperCase();
            const statusFilter = document.getElementById('evacFilter').value;
            const container = document.getElementById('evacuation-list-container');
            const items = container.getElementsByClassName('list-item');

            for (let i = 0; i < items.length; i++) {
                const text = items[i].innerText || items[i].textContent;
                const statusSpan = items[i].querySelector('span[style*="border-radius"]');
                const statusText = statusSpan ? statusSpan.innerText : '';

                const matchesSearch = text.toUpperCase().indexOf(filter) > -1;
                const matchesStatus = statusFilter === 'All' || statusText.includes(statusFilter);

                if (matchesSearch && matchesStatus) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
        }

        function filterContacts() {
            const input = document.getElementById('contactSearch');
            const filter = input.value.toUpperCase();
            const container = document.getElementById('contacts-list-container');
            const items = container.getElementsByClassName('list-item');

            for (let i = 0; i < items.length; i++) {
                const text = items[i].innerText || items[i].textContent;
                if (text.toUpperCase().indexOf(filter) > -1) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
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
                    </select>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="document.getElementById('editProfileModal').style.display='none'" style="padding:10px 20px; background:transparent; border:1px solid #2d2f39; color:#fff; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="saveProfileBtn" style="padding:10px 25px; background:linear-gradient(135deg, #c054ff 0%, #6d28d9 100%); border:none; color:#fff; border-radius:8px; font-weight:600; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
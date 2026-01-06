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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: #ffffff;
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            font-weight: 700;
            color: #4ab5c4;
            padding-left: 10px;
        }

        .sidebar-header svg {
            width: 32px;
            height: 32px;
            stroke: #4ab5c4;
            filter: drop-shadow(0 0 5px rgba(74, 181, 196, 0.5));
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
            background: rgba(74, 181, 196, 0.1);
            color: #4ab5c4;
            border-color: rgba(74, 181, 196, 0.2);
            box-shadow: 0 0 20px rgba(74, 181, 196, 0.1);
            transform: translateX(5px);
        }

        .sidebar-logout {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
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
            transition: all 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
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
            }
            .sidebar.active { left: 0; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .status-row { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
            .header { padding: 15px 20px; }
            .header-left h1 { font-size: 20px; }
            .mobile-toggle { display: block !important; }
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
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M12 2v8m0 4v8M2 12h8m4 0h8M4 4l5.66 5.66M14.34 4l5.66 5.66M4 20l5.66-5.66M14.34 20l5.66-5.66"/>
                </svg>
                AquaSafe
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" class="nav-link active" onclick="switchTab('dashboard', this)">📊 Dashboard</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('sensors', this)">📡 Sensors</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('alerts', this)">🚨 Alerts</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('map', this)">🗺️ Map</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('evacuation', this)">📍 Evacuation Points</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('reports', this)">📊 Reports</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('helpdesk', this)">🆘 Help Desk</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('notifications', this)">🔔 Notifications</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('users', this)">👥 Manage Users</a></li>
                <li><a href="#" class="nav-link" onclick="switchTab('settings', this)">⚙️ Settings</a></li>
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
                            <h3>📈 Real-time Water Levels</h3>
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
                        <div class="status-card">
                            <div class="status-label">Overall Safety</div>
                            <div class="status-value safe-text">98%</div>
                        </div>
                        <div class="status-card">
                            <div class="status-label">Active Alerts</div>
                            <div class="status-value warning-text">2 New</div>
                        </div>
                        <div class="status-card">
                            <div class="status-label">Critical Zones</div>
                            <div class="status-value danger-text">1 Zone</div>
                        </div>
                        <div class="status-card">
                            <div class="status-label">System Health</div>
                            <div class="status-value info-text">Optimal</div>
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
                        <button style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: background 0.3s;" onclick="openAddModal()">+ Add Point</button>
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
                        <input type="hidden" id="pointId">
                        <div class="form-group">
                            <label>Location Name</label>
                            <input type="text" id="pointName" required placeholder="e.g. City Hall">
                        </div>
                        <div class="form-group">
                            <label>Area / Address</label>
                            <input type="text" id="pointLocation" required placeholder="e.g. Downtown">
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex:1;">
                                <label>Latitude</label>
                                <input type="number" step="any" id="pointLat" placeholder="10.8505">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Longitude</label>
                                <input type="number" step="any" id="pointLng" placeholder="76.2711">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Capacity (Persons)</label>
                            <input type="number" id="pointCapacity" required min="1">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="pointStatus">
                                <option value="Available">Available</option>
                                <option value="Full">Full</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assigned Sensor</label>
                            <input type="text" id="pointSensor" placeholder="e.g. SNS-001">
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
                         <div class="status-value warning-text">14</div>
                     </div>
                     <div class="status-card">
                         <div class="status-label">Flood Events</div>
                         <div class="status-value danger-text">3</div>
                     </div>
                     <div class="status-card">
                         <div class="status-label">Safe Recoveries</div>
                         <div class="status-value safe-text">98%</div>
                     </div>
                </div>

                <!-- Charts Area -->
                <div class="dashboard-grid">
                     <div class="card">
                        <h3>📊 Water Level Trends</h3>
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

                <!-- Generated Reports Table -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                         <h3>📑 Recent Generated Reports</h3>
                         <button style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(74, 181, 196, 0.3);">
                             Export All (CSV)
                         </button>
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
                                <td style="padding: 15px; text-align: right; border-radius: 0 10px 10px 0;"><a href="#" style="color: #4ab5c4; text-decoration: none; font-weight: 500;">Download PDF</a></td>
                            </tr>
                            <tr style="background: rgba(255,255,255,0.03);">
                                <td style="padding: 15px; border-radius: 10px 0 0 10px;">Dec 18, 2025</td>
                                <td style="padding: 15px;">Critical Incident Log - North Zone</td>
                                <td style="padding: 15px;"><span style="background: rgba(241, 196, 15, 0.15); color: #f1c40f; padding: 4px 10px; border-radius: 20px; font-size: 13px;">Review Needed</span></td>
                                <td style="padding: 15px; text-align: right; border-radius: 0 10px 10px 0;"><a href="#" style="color: #4ab5c4; text-decoration: none; font-weight: 500;">Download PDF</a></td>
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
                        <div>
                             <span style="background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 12px; font-size: 13px; margin-right: 10px;">Pending: 3</span>
                             <span style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; padding: 5px 10px; border-radius: 12px; font-size: 13px;">Resolved: 12</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <!-- Request Item -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border-left: 4px solid #f1c40f;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong>User: john.doe@example.com</strong>
                                <span style="font-size: 12px; opacity: 0.6;">10 mins ago</span>
                            </div>
                            <p style="margin: 10px 0; opacity: 0.8; font-size: 14px;">"I am not receiving SMS alerts for the North Zone even though I subscribed."</p>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button style="padding: 6px 12px; background: #2ecc71; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 12px;">Mark Resolved</button>
                                <button style="padding: 6px 12px; background: rgba(255,255,255,0.1); border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 12px;">Reply</button>
                                <span style="font-size: 12px; color: #f1c40f; margin-left: auto;">Status: Open</span>
                            </div>
                        </div>

                         <!-- Request Item -->
                         <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border-left: 4px solid #4ab5c4;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong>User: sarah.m@example.com</strong>
                                <span style="font-size: 12px; opacity: 0.6;">1 hour ago</span>
                            </div>
                            <p style="margin: 10px 0; opacity: 0.8; font-size: 14px;">"Can I request a new evacuation point near the City Center?"</p>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button style="padding: 6px 12px; background: #2ecc71; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 12px;">Mark Resolved</button>
                                <button style="padding: 6px 12px; background: rgba(255,255,255,0.1); border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 12px;">Reply</button>
                                <span style="font-size: 12px; color: #4ab5c4; margin-left: auto;">Status: Reviewed</span>
                            </div>
                        </div>
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
                                    <td style="padding: 15px;"><?php echo htmlspecialchars((string)$u['name']); ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars((string)$u['email']); ?></td>
                                    <td style="padding: 15px;">
                                        <span class="<?php echo ($u['user_role'] === 'admin' || $u['user_role'] === 'administrator') ? 'danger-text' : 'info-text'; ?>" style="font-weight: 600;">
                                            <?php 
                                                $display_role = !empty($u['user_role']) ? ucfirst($u['user_role']) : 'Not Set';
                                                if ($display_role === 'Administrator') echo 'Administrator';
                                                else echo $display_role;
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: right;">
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
                        <label class="switch" style="position: relative; display: inline-block; width: 60px; height: 34px;">
                            <input type="checkbox" checked style="opacity: 0; width: 0; height: 0;">
                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.2); transition: .4s; border-radius: 34px;"></span>
                            <span style="position: absolute; content: ''; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; transform: translateX(26px); background: #2ecc71;"></span>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- Thresholds -->
                        <div style="padding: 20px; background: rgba(255,255,255,0.05); border-radius: 12px;">
                            <h4 style="margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Severity Thresholds</h4>
                            
                            <div style="margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <label>Warning Level</label>
                                    <span style="color: #f1c40f;">75%</span>
                                </div>
                                <input type="range" min="0" max="100" value="75" style="width: 100%; height: 6px; background: #ddd; border-radius: 5px; outline: none; opacity: 0.7;">
                            </div>
                             <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <label>Critical Level</label>
                                    <span style="color: #e74c3c;">90%</span>
                                </div>
                                <input type="range" min="0" max="100" value="90" style="width: 100%; height: 6px; background: #ddd; border-radius: 5px; outline: none; opacity: 0.7;">
                            </div>
                        </div>

                        <!-- Channels -->
                        <div style="padding: 20px; background: rgba(255,255,255,0.05); border-radius: 12px;">
                            <h4 style="margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Delivery Channels</h4>
                            
                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" checked id="sms_ch" style="width: 18px; height: 18px;">
                                <label for="sms_ch">SMS Alerts</label>
                            </div>
                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" checked id="email_ch" style="width: 18px; height: 18px;">
                                <label for="email_ch">Email Notifications</label>
                            </div>
                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="app_ch" style="width: 18px; height: 18px;">
                                <label for="app_ch">In-App Push</label>
                            </div>
                             <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="public_ch" style="width: 18px; height: 18px;">
                                <label for="public_ch">Public Sirens</label>
                            </div>
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
        // 1. GLOBAL STORE & DIAGNOSTICS
        var allEvacPoints = {}; 
        var log = console.log;
        log("AquaSafe Admin JS Loading...");

        // 2. GLOBAL EVACUATION FUNCTIONS (Explicit window assignment)
        window.openAddModal = function() {
            log("Opening Add Modal");
            const modal = document.getElementById('evacuationModal');
            if(!modal) return alert("System Error: Modal Overlay not found in DOM!");
            
            document.getElementById('modalTitle').innerText = 'Add Evacuation Point';
            document.getElementById('pointId').value = ''; 
            document.getElementById('evacuationForm').reset();
            modal.classList.add('active');
        };

        window.openEditModal = function(id) {
            log("openEditModal called for ID:", id);
            alert("Diagnostic: openEditModal triggered for ID " + id);
            
            const pt = allEvacPoints[id] || allEvacPoints[String(id)] || allEvacPoints[parseInt(id)];

            if(!pt) {
                log("Lookup failed for ID:", id, "Cache content:", allEvacPoints);
                return alert("Critical Error: Point data not found in browser memory for #" + id);
            }

            log("Editing Point:", pt.name);
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
                    fetchEvacuationPoints();
                    if(typeof refreshMapMarkers === 'function') refreshMapMarkers();
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
                    fetchEvacuationPoints();
                    if(typeof refreshMapMarkers === 'function') refreshMapMarkers();
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
                        card.style.cssText = "background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);";
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
                                <button onclick="window.openEditModal('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; cursor: pointer; font-weight: 500;">Edit</button>
                                <button onclick="window.deletePoint('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 8px; cursor: pointer; font-weight: 500;">Remove</button>
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

        // Simplified global click tracking (Optional but helpful)
        document.addEventListener('click', function(e) {
            log("Global Interaction:", e.target.tagName, e.target.className);
        });


        // -----------------------------------------------------

        window.onerror = function(msg, url, line) {
            log("FATAL ERROR: " + msg + " (Line: " + line + ")");
            alert("JS Error: " + msg + " at " + line);
            return false;
        };

        // 1. Diagnostics
        window.pingJS = function() {
            log("Ping triggered!");
            alert("Diagnostic Alert: JavaScript is WORKING!");
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
                    alert('Role updated successfully!');
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

        // 3. Navigation Logic (Enhanced)
        window.switchTab = function(tabId, element) {
            log("Switching to " + tabId);
            document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            if (window.innerWidth <= 1024) toggleSidebar();

            const titles = {
                'dashboard': 'Admin Dashboard',
                'sensors': 'Sensor Management',
                'alerts': 'System Alerts',
                'map': 'Live Map',
                'evacuation': 'Evacuation Management',
                'reports': 'Reports & Analytics',
                'helpdesk': 'Help Desk',
                'notifications': 'Notification Control',
                'users': 'User Management',
                'settings': 'System Settings'
            };
            const titleEl = document.getElementById('pageTitle');
            if (titleEl) titleEl.innerText = titles[tabId] || 'Admin Dashboard';

            if(tabId === 'map') initMap();
            if(tabId === 'reports') renderReportCharts();
            if(tabId === 'evacuation') {
                fetchEvacuationPoints();
                // Ensure map logic is ready if needed, or refresh markers just in case
                refreshMapMarkers(); 
            }
        };

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

        window.refreshMapMarkers = function() {
            if(!map) return;
            
            // Fetch points for map
            fetch('manage_evacuation.php?action=fetch_all')
                .then(res => res.json())
                .then(res => {
                    if(res.data) {
                        // Clear existing
                        for(let id in markersObj) {
                            map.removeLayer(markersObj[id]);
                        }
                        markersObj = {};

                        res.data.forEach(p => {
                            // Default lat/lng if missing (fallback for old dummy data)
                             // Since our DB schema has lat/long but maybe empty, let's fake it if needed or skip
                             // Actually user dashboard requirement implies we need location.
                             // For now, let's map location names to coordinates or use dummy offsets if 0,0
                            let lat = parseFloat(p.latitude);
                            let lng = parseFloat(p.longitude);

                            // Check if valid coords (not 0,0 and not NaN)
                            // If 0,0 try parsing location if it has comma
                            if((!lat && !lng) || (lat === 0 && lng === 0)) {
                                if(p.location && p.location.includes(',')) {
                                     const parts = p.location.split(',');
                                     if(parts.length === 2 && !isNaN(parts[0])) {
                                         lat = parseFloat(parts[0]);
                                         lng = parseFloat(parts[1]);
                                     }
                                }
                            }

                            // If still invalid, skip or put in default center
                            if(isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) {
                                // console.warn("Skipping point with invalid coords:", p.name);
                                return; 
                            }

                            const color = p.status === 'Available' ? '#2ecc71' : (p.status === 'Full' ? '#e74c3c' : '#f1c40f');
                            const marker = L.circleMarker([lat, lng], {
                                color: color,
                                fillColor: color,
                                fillOpacity: 0.8,
                                radius: 10
                            }).addTo(map).bindPopup(`<b>${p.name}</b><br>Status: ${p.status}<br>Cap: ${p.capacity}`);
                            
                            markersObj[p.id] = marker;
                        });
                    }
                });
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
            // Simulate data change based on range
            const labels = range === '24h' ? ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'] : (range === '7d' ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] : ['Week 1', 'Week 2', 'Week 3', 'Week 4']);
            const data1 = range === '24h' ? [30, 35, 40, 45, 42, 38] : [40, 55, 45, 60, 65, 50, 55];

            floodChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Average Water Level (cm)',
                        data: data1,
                        borderColor: '#4ab5c4',
                        backgroundColor: 'rgba(74, 181, 196, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            alertChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: ['Safe', 'Warning', 'Critical'],
                    datasets: [{
                        label: 'Alerts',
                        data: range === '24h' ? [120, 15, 5] : [500, 80, 20],
                        backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        };

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

        // 8. Sidebar Toggle
        window.toggleSidebar = function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        };
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        // 9. Charts on Dashboard (Safe Init)
        try {
            const chartCanvas = document.getElementById('waterLevelChart');
            if (chartCanvas && typeof Chart !== 'undefined') {
                const ctx = chartCanvas.getContext('2d');
                const waterChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['10:00', '10:05', '10:10', '10:15', '10:20', '10:25'],
                        datasets: [{
                            label: 'Water Level (cm)',
                            data: [45, 48, 52, 50, 55, 58],
                            borderColor: '#4ab5c4',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: false
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                setInterval(() => {
                    if (!waterChart) return;
                    const lastVal = waterChart.data.datasets[0].data[waterChart.data.datasets[0].data.length - 1];
                    let newVal = Math.max(30, Math.min(90, lastVal + (Math.random() - 0.5) * 8));
                    waterChart.data.labels.push(new Date().toLocaleTimeString());
                    waterChart.data.datasets[0].data.push(newVal);
                    if (waterChart.data.labels.length > 10) {
                        waterChart.data.labels.shift();
                        waterChart.data.datasets[0].data.shift();
                    }
                    waterChart.update('none'); 
                }, 2000);
            }
        } catch (e) { log("Chart Error: " + e.message); }

        // Tab persistence
        window.addEventListener('load', () => {
             const hash = window.location.hash.replace('#', '');
             if(hash) {
                 const targetLink = document.querySelector(`.nav-link[onclick*="'${hash}'"]`);
                 if(targetLink) switchTab(hash, targetLink);
             }
        });

        log("READY.");
        alert("AquaSafe Admin System: LOADED SUCCESSFULLY!");
    </script>
</body>
</html>
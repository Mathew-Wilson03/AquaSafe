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

        /* Responsive */
        @media (max-width: 1024px) {
            .container { flex-direction: column; padding: 10px; }
            .sidebar { width: 100%; height: auto; border-radius: 16px; margin-bottom: 20px; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .status-row { grid-template-columns: 1fr 1fr; }
        }
        /* Tab Logic */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .content-section.active {
            display: block;
        }
    </style>
</head>
<body>
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
                    <h1 id="pageTitle">Admin Dashboard</h1>
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
                 <div class="card" style="height: 600px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <h3>🗺️ Global Sensor Map</h3>
                    <p style="opacity: 0.6; margin-bottom: 20px;">Full-screen interactive map view would load here.</p>
                    <div class="map-container" style="width: 100%; height: 100%;">
                         <div class="map-grid"></div>
                            <div class="map-pin safe" style="top: 40%; left: 50%;"></div>
                            <div class="map-pin danger" style="top: 20%; left: 30%;"></div>
                            <div class="map-pin warning" style="top: 70%; left: 80%;"></div>
                    </div>
                </div>
            </div>

             <!-- Evacuation Section -->
            <div id="evacuation" class="content-section">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>📍 Evacuation Points</h3>
                        <button style="padding: 10px 20px; background: #4ab5c4; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#3d9fab'" onmouseout="this.style.background='#4ab5c4'">+ Add Point</button>
                    </div>
                    <p style="opacity: 0.7; margin-bottom: 20px;">Admins can manage and update evacuation points dynamically based on flood severity.</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- Evacuation Point 1 -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <strong style="font-size: 18px;">Central Community Hall</strong>
                                <span class="safe-text" style="font-size: 14px; background: rgba(46, 204, 113, 0.1); padding: 4px 10px; border-radius: 20px;">Available</span>
                            </div>
                            <div style="margin-bottom: 10px; font-size: 14px; opacity: 0.8;">
                                <div>Capacity: <strong>120 / 500</strong></div>
                                <div>Assigned Sensor: <strong>SNS-001 (North Tank)</strong></div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button style="flex: 1; padding: 8px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 6px; cursor: pointer;">Edit</button>
                                <button style="flex: 1; padding: 8px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 6px; cursor: pointer;">Remove</button>
                            </div>
                        </div>

                        <!-- Evacuation Point 2 -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <strong style="font-size: 18px;">North High School</strong>
                                <span class="danger-text" style="font-size: 14px; background: rgba(231, 76, 60, 0.1); padding: 4px 10px; border-radius: 20px;">Full</span>
                            </div>
                            <div style="margin-bottom: 10px; font-size: 14px; opacity: 0.8;">
                                <div>Capacity: <strong>298 / 300</strong></div>
                                <div>Assigned Sensor: <strong>SNS-002 (South Reservoir)</strong></div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button style="flex: 1; padding: 8px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 6px; cursor: pointer;">Edit</button>
                                <button style="flex: 1; padding: 8px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 6px; cursor: pointer;">Remove</button>
                            </div>
                        </div>
                         <!-- Evacuation Point 3 -->
                        <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); border-style: dashed; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                           <div style="text-align: center; opacity: 0.6;">
                                <div style="font-size: 24px;">+</div>
                                <div>Add New Location</div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports" class="content-section">
                <div class="dashboard-grid">
                     <div class="card">
                        <h3>📊 Daily Flood Reports</h3>
                        <p style="opacity: 0.7; font-size: 14px; margin-bottom: 15px;">Water level trends over the last 24 hours.</p>
                        <div class="chart-container">
                            <canvas id="floodTrendChart"></canvas>
                        </div>
                     </div>
                     <div class="card">
                        <h3>🚨 Alert Frequency</h3>
                        <p style="opacity: 0.7; font-size: 14px; margin-bottom: 15px;">Alerts triggered by zone severity.</p>
                        <div class="chart-container">
                            <canvas id="alertFreqChart"></canvas>
                        </div>
                     </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                         <h3>📑 Generated Reports</h3>
                         <button style="padding: 8px 16px; background: #4ab5c4; border: none; border-radius: 6px; color: white;">Export All (PDF)</button>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; color: rgba(255,255,255,0.8);">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th style="padding: 12px; text-align: left;">Date</th>
                                <th style="padding: 12px; text-align: left;">Report Type</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px;">Dec 19, 2025</td>
                                <td style="padding: 12px;">Daily Summary</td>
                                <td style="padding: 12px;"><span class="safe-text">Completed</span></td>
                                <td style="padding: 12px; text-align: right;"><a href="#" style="color: #4ab5c4; text-decoration: none;">Download</a></td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px;">Dec 18, 2025</td>
                                <td style="padding: 12px;">Critical Event Log</td>
                                <td style="padding: 12px;"><span class="warning-text">Flagged</span></td>
                                <td style="padding: 12px; text-align: right;"><a href="#" style="color: #4ab5c4; text-decoration: none;">Download</a></td>
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

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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

             <!-- Settings Section -->
            <div id="settings" class="content-section">
                 <div class="dashboard-grid">
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
        log = console.log; // Maintain log if needed elsewhere
        
        log("AquaSafe Admin JS Loading...");

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

        // 3. Navigation Logic
        window.switchTab = function(tabId, element) {
            log("Switching to " + tabId);
            document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

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
            if(tabId === 'reports') renderReportCharts();
        };

        // 4. Clock
        function updateTime() {
            const clockEl = document.getElementById('clock');
            if(clockEl) {
                const now = new Date();
                clockEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateTime, 1000);
        updateTime();

        // 5. Global Event Listener for Role Buttons
        document.addEventListener('click', function(e) {
            if(e.target.classList.contains('role-update-btn')) {
                const uid = e.target.getAttribute('data-id');
                const role = e.target.getAttribute('data-role');
                log("Button click captured by delegation: ID=" + uid + ", Role=" + role);
                window.updateRole(uid, role);
            }
        });

        // 6. Charts (Safe Init)
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

        window.renderReportCharts = function() {
            log("Rendering report charts...");
            // (Minimal Chart logic here to prevent bloat)
        };

        // Tab persistence on reload
        window.addEventListener('load', () => {
            const hash = window.location.hash.replace('#', '');
            if(hash) {
                const targetLink = document.querySelector(`.nav-link[onclick*="'${hash}'"]`);
                if(targetLink) switchTab(hash, targetLink);
            }
        });

        log("READY.");
    </script>
</body>
</html>
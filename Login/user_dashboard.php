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
            --primary: #4ab5c4;
            --primary-dark: #3a9aa8;
            --accent: #ff8d85;
            --bg-dark: #0f2027;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --safe: #2ecc71;
            --warning: #f1c40f;
            --danger: #e74c3c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Outfit', sans-serif; 
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); 
            color: #fff; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container { display: flex; gap: 24px; padding: 24px; max-width: 1600px; margin: 0 auto; }

        /* Glassmorphism Utility */
        .glass {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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
            .header-left h1 { font-size: 18px; }
            .card { padding: 15px; }
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
                <a href="#section-flood" class="active" data-target="section-flood"><i data-lucide="activity"></i> Flood Status</a>
                <a href="#section-evac" data-target="section-evac"><i data-lucide="map-pin"></i> Evacuation</a>
                <a href="#section-contacts" data-target="section-contacts"><i data-lucide="phone"></i> Contacts</a>
                <a href="#section-alerts" data-target="section-alerts"><i data-lucide="bell"></i> Alerts</a>
                <a href="#section-help" data-target="section-help" id="helpDeskLink">
                    <i data-lucide="help-circle"></i> Help Desk
                    <span id="helpdeskNotificationBadge" style="display:none; position:absolute; top:8px; right:8px; background:#e74c3c; color:#fff; border-radius:50%; width:20px; height:20px; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(231,76,60,0.5);">0</span>
                </a>
                <a href="#section-safety" data-target="section-safety"><i data-lucide="shield-check"></i> Safety Tips</a>
                <a href="#section-water" data-target="section-water"><i data-lucide="droplets"></i> Water Levels</a>
                <a href="#section-map" data-target="section-map"><i data-lucide="map"></i> Map View</a>
                <a href="#section-settings" data-target="section-settings"><i data-lucide="settings"></i> Settings</a>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php"><i data-lucide="log-out"></i> Sign Out</a>
            </div>
        </div>

        <div class="main">
            <div class="header glass animate__animated animate__fadeInDown">
                <div class="header-left" style="display: flex; align-items: center;">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i data-lucide="menu"></i>
                    </button>
                    <h1>User Dashboard</h1>
                </div>
                <div class="header-right">
                    <div id="live-clock" class="last-updated">
                        <i data-lucide="clock"></i> <span>00:00:00</span>
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
                    <i data-lucide="cloud-sun" style="color: var(--primary); width: 32px; height: 32px;"></i>
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
                        <div class="card glass">
                            <h3><i data-lucide="activity"></i> Flood Status</h3>
                            <div class="status-row">
                                <div class="status-pill safe <?php echo $flood_status==='Safe' ? 'active' : ''; ?>">Safe</div>
                                <div class="status-pill warning <?php echo $flood_status==='Warning' ? 'active' : ''; ?>">Warning</div>
                                <div class="status-pill danger <?php echo $flood_status==='Danger' ? 'active' : ''; ?>">Danger</div>
                            </div>
                            <div style="padding: 20px; background: rgba(0,0,0,0.2); border-radius: 14px; margin-bottom: 20px;">
                                <p style="color:#dfeff0; line-height: 1.6;"><?php echo $flood_explanation; ?></p>
                            </div>
                            <div style="font-size:13px; color:rgba(255,255,255,0.5); display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="calendar" style="width: 14px;"></i> Last checked: <?php echo $last_checked; ?>
                            </div>
                        </div>
                    </div>

                    <div id="section-evac" class="content-section">
                        <div class="card glass">
                                <h3><i data-lucide="map-pin"></i> Evacuation Points</h3>
                                <div class="list-container" id="evacuation-list-container">
                                    <div style="text-align:center; padding:20px; color:rgba(255,255,255,0.5);">Loading points...</div>
                                </div>
                        </div>
                    </div>

                    <div id="section-alerts" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="bell"></i> System Alerts</h3>
                            <div id="userAlertsList" class="list-container">
                                <div style="text-align:center; padding: 20px; opacity: 0.6;">Loading latest alerts...</div>
                            </div>
                        </div>
                    </div>

                    <div id="section-contacts" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="phone"></i> Emergency Contacts</h3>
                            <div class="list-container">
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
                            <h3><i data-lucide="help-circle"></i> Help Desk</h3>
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
                            <h3><i data-lucide="shield-check"></i> Safety Guidelines</h3>
                            <div class="list-container">
                                <div class="list-item">
                                    <div style="display:flex; gap:12px;">
                                        <i data-lucide="arrow-up-circle" style="color:var(--primary);"></i>
                                        <span>Move to higher ground immediately if water rises.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div style="display:flex; gap:12px;">
                                        <i data-lucide="users" style="color:var(--primary);"></i>
                                        <span>Follow instructions from local authorities.</span>
                                    </div>
                                </div>
                                <div class="list-item">
                                    <div style="display:flex; gap:12px;">
                                        <i data-lucide="alert-triangle" style="color:var(--accent);"></i>
                                        <span>Avoid walking or driving through floodwater.</span>
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
                        // Trigger specific initializations
                        if(id === 'section-water') initChart();
                        if(id === 'section-map') initMap();
                        if(id === 'section-help') {
                            loadMyRequests();
                            markHelpdeskAsRead(); // Clear notification badge
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
                    alert('📝 Profile Editor\n\nThis feature allows you to update your name, email, and other profile information.\n\n(Feature coming soon!)');
                });
            }

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
            function initChart() {
                const ctx = document.getElementById('waterLevelChart');
                if(!ctx || chartInstance) return;

                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['12 AM', '2 AM', '4 AM', '6 AM', '8 AM', '10 AM', 'Current'],
                        datasets: [{
                            label: 'Water Level (m)',
                            data: [1.2, 1.3, 1.5, 2.1, 2.8, 3.2, 3.5],
                            borderColor: '#4ab5c4',
                            backgroundColor: 'rgba(74, 181, 196, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#4ab5c4',
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: 'rgba(255,255,255,0.5)' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: 'rgba(255,255,255,0.5)' }
                            }
                        }
                    }
                });
            }

            // Map Initialization
            let mapInstance = null;
            function initMap() {
                if (mapInstance) return;
                
                // Set default view (centered around a generic location for now)
                mapInstance = L.map('map').setView([10.8505, 76.2711], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapInstance);

                // Add sample markers for evacuation points
                const markerIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: "<div style='background-color:#4ab5c4; width:12px; height:12px; border:2px solid #fff; border-radius:50%;'></div>",
                    iconSize: [12, 12],
                    iconAnchor: [6, 6]
                });

                L.marker([10.8550, 76.2750], {icon: markerIcon}).addTo(mapInstance).bindPopup("<b>Community Hall</b><br>Safe Zone");
                L.marker([10.8450, 76.2650], {icon: markerIcon}).addTo(mapInstance).bindPopup("<b>Town School</b><br>Warning Zone");
                
                // Force map to recalculate size after being shown
                setTimeout(() => mapInstance.invalidateSize(), 100);
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
        });
    </script>
    
        <script>
        // Existing script logic...

        // Fetch Alerts for User
        async function fetchUserAlerts() {
            const container = document.getElementById('userAlertsList');
            if(!container) return;
            
            try {
                // Targeted Broadcast Logic
                const userLocParam = window.userLocation ? `&user_location=${encodeURIComponent(window.userLocation)}` : '';
                const res = await fetch(`manage_alerts.php?action=fetch_all${userLocParam}`);
                const json = await res.json();
                
                if(json.status === 'success') {
                    const alerts = json.data || [];
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
                        lucide.createIcons(); // Refresh icons
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
        setInterval(fetchUserAlerts, 30000); // Poll every 30s
    </script>
    <!-- Custom Confirmation Modal -->
    <div id="customConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:2px solid #f1c40f; padding:30px; border-radius:15px; width:90%; max-width:450px; box-shadow:0 0 40px rgba(241,196,15,0.4); animation:fadeInUp 0.3s ease;">
            <div style="font-size:48px; text-align:center; margin-bottom:15px;">⚠️</div>
            <h3 style="color:#f1c40f; margin:0 0 15px 0; text-align:center; font-size:20px;">Confirm Action</h3>
            <p id="confirmMessage" style="color:rgba(255,255,255,0.9); text-align:center; font-size:15px; line-height:1.6; margin-bottom:25px;">Are you sure?</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" id="confirmCancelBtn" style="padding:12px 24px; background:transparent; border:1px solid rgba(255,255,255,0.3); color:#fff; font-weight:600; border-radius:8px; cursor:pointer; font-size:14px;">Cancel</button>
                <button type="button" id="confirmOkBtn" style="padding:12px 24px; background:#f1c40f; border:none; color:#032023; font-weight:700; border-radius:8px; cursor:pointer; font-size:14px;">Confirm</button>
            </div>
        </div>
    </div>
</body>
</html>
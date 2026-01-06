<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["name"] ?? "User");
$email = htmlspecialchars($_SESSION["email"] ?? "");

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

$alerts = [
    ['severity'=>'Warning','text'=>'River level rising near checkpoint A','time'=>date('Y-m-d H:i')],
    ['severity'=>'Info','text'=>'Routine sensor calibration scheduled','time'=>date('Y-m-d H:i', strtotime('-2 hours'))]
];

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
            gap: 10px;
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
            }
            .sidebar.active { left: 0; }
            .main { gap: 15px; }
            .grid { grid-template-columns: 1fr; gap: 15px; }
            .mobile-toggle { display: block !important; }
            .header { padding: 15px; }
            .header-right { gap: 10px; }
            .user-profile span { display: none; }
        }

        @media (max-width: 600px) {
            .status-row { grid-template-columns: 1fr; }
            .weather-widget { flex-direction: column; text-align: center; }
            .weather-widget div:last-child { margin-left: 0; text-align: center; }
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
                <i data-lucide="waves"></i> AquaSafe
            </div>
            <div class="sidebar-menu">
                <a href="#section-flood" class="active" data-target="section-flood"><i data-lucide="activity"></i> Flood Status</a>
                <a href="#section-evac" data-target="section-evac"><i data-lucide="map-pin"></i> Evacuation</a>
                <a href="#section-contacts" data-target="section-contacts"><i data-lucide="phone"></i> Contacts</a>
                <a href="#section-alerts" data-target="section-alerts"><i data-lucide="bell"></i> Alerts</a>
                <a href="#section-help" data-target="section-help"><i data-lucide="help-circle"></i> Help Desk</a>
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

                    <div id="section-alerts" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="bell"></i> Recent Alerts</h3>
                            <div class="list-container">
                                <?php foreach($alerts as $a): ?>
                                <div class="list-item" style="border-left: 4px solid <?php echo $a['severity'] === 'Warning' ? 'var(--warning)' : 'var(--primary)'; ?>">
                                    <div>
                                        <strong>[<?php echo $a['severity']; ?>]</strong> <?php echo htmlspecialchars($a['text']); ?>
                                        <div style="font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px;"><?php echo $a['time']; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div id="section-help" class="content-section">
                        <div class="card glass">
                            <h3><i data-lucide="help-circle"></i> Help Desk</h3>
                            <?php if(isset($_GET['help_sent'])): ?>
                                <div class="animate__animated animate__headShake" style="padding:12px; border-radius:10px; background:<?php echo $_GET['help_sent'] == '1' ? 'rgba(46,204,113,0.1)' : 'rgba(231,76,60,0.1)'; ?>; color:<?php echo $_GET['help_sent'] == '1' ? 'var(--safe)' : 'var(--danger)'; ?>; margin-bottom:15px; border:1px solid currentColor;">
                                    <?php echo $_GET['help_sent'] == '1' ? 'Request sent successfully. responders notified.' : 'Failed to send request. Please try again.'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form class="help-form" method="post" action="send_help.php">
                                <input type="text" name="title" placeholder="Short title (e.g. Rising water level)" required>
                                <textarea name="details" rows="3" placeholder="Describe your request or situation..." required></textarea>
                                <button type="submit"><i data-lucide="send" style="width:16px; display:inline-block; vertical-align:middle; margin-right:6px;"></i> Send Request</button>
                            </form>

                            <?php if(!empty($help_requests)): ?>
                                <h4 style="margin: 20px 0 10px; font-size: 14px; color: rgba(255,255,255,0.6);">Your Recent Requests</h4>
                                <div class="list-container">
                                    <?php foreach(array_slice($help_requests,0,3) as $hr): ?>
                                        <div class="list-item" style="flex-direction:column; align-items:flex-start; gap:8px;">
                                            <div style="width:100%; display:flex; justify-content:space-between;">
                                                <strong><?php echo htmlspecialchars($hr['title'] ?? ''); ?></strong>
                                                <span class="small-pill" style="font-size:10px; padding:2px 8px; border-radius:20px; background:var(--primary); color:#032023;"><?php echo htmlspecialchars($hr['status'] ?? 'Pending'); ?></span>
                                            </div>
                                            <p style="font-size:12px; color:rgba(255,255,255,0.7);"><?php echo nl2br(htmlspecialchars($hr['details'] ?? '')); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
                                    <button class="small-btn">Edit</button>
                                </div>
                                <div class="list-item">
                                    <span>Notification Preferences</span>
                                    <button class="small-btn">Manage</button>
                                </div>
                                <div class="list-item">
                                    <span>Security & Password</span>
                                    <button class="small-btn">Update</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
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
        });
    </script>
    
    </body>
</html>
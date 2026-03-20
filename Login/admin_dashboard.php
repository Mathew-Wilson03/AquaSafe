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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
        
        .card, .status-card, .status-widget { animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
        .status-widget:nth-child(1) { animation-delay: 0.1s; }
        .status-widget:nth-child(2) { animation-delay: 0.15s; }
        .status-widget:nth-child(3) { animation-delay: 0.2s; }
        .status-widget:nth-child(4) { animation-delay: 0.25s; }
        .status-widget:nth-child(5) { animation-delay: 0.3s; }
        .status-card:nth-child(1) { animation-delay: 0.4s; }
        .status-card:nth-child(2) { animation-delay: 0.5s; }

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
            transform: translateX(-50%); /* Removed translateY(10px) to keep it in final position */
            background: rgba(19, 20, 27, 0.95); /* Slightly darker/solid background */
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px; /* Slightly smaller to fit better */
            white-space: nowrap;
            opacity: 1; /* Always visible */
            pointer-events: none;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 10; font-weight: 600;
        }

        .map-pin:hover .pin-tooltip {
            transform: translateX(-50%) scale(1.05); /* Just a subtle scale on hover */
            border-color: rgba(74, 181, 196, 0.8);
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
            .status-widgets-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .device-cards-grid { grid-template-columns: 1fr; }

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

        /* Emergency Marker Pulse Animation (Stabilized) */
        @keyframes MarkerPulse {
            0% { 
                filter: drop-shadow(0 0 5px rgba(255, 0, 0, 0.8));
            }
            50% { 
                filter: drop-shadow(0 0 20px rgba(255, 0, 0, 1));
            }
            100% { 
                filter: drop-shadow(0 0 5px rgba(255, 0, 0, 0.8));
            }
        }
        .emergency-pulse-marker {
            animation: MarkerPulse 2s infinite ease-in-out;
            cursor: pointer !important;
            pointer-events: auto !important;
            outline: none;
            transform-origin: center;
        }
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
        /* Widgets Grid */
        .status-widgets-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .status-widget {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .status-widget i { font-size: 24px; color: #4ab5c4; }
        .status-widget .widget-value { font-size: 22px; font-weight: 700; color: #fff; }
        .status-widget .widget-label { font-size: 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px; }

        /* Device Cards */
        .device-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        .device-card {
            background: rgba(30, 32, 41, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .device-card:hover { transform: translateY(-10px); border-color: rgba(74, 181, 196, 0.4); box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .device-status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .status-active { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .status-offline { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
        .device-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .device-icon-wrapper { width: 50px; height: 50px; background: rgba(74, 181, 196, 0.1); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .device-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .stat-item { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
        .stat-label { font-size: 11px; opacity: 0.5; margin-bottom: 5px; }
        .stat-value { font-size: 16px; font-weight: 600; color: #fff; }
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
    <!-- AquaSafe Priority Handlers Bridge -->
    <script>
        (function() {
            // --- SYNC ENGINE (Priority) ---
            window.aquaSafeSyncRegistry = window.aquaSafeSyncRegistry || {};
            window.SyncManager = {
                getSignal(key) {
                    if (window.aquaSafeSyncRegistry[key]) window.aquaSafeSyncRegistry[key].abort();
                    window.aquaSafeSyncRegistry[key] = new AbortController();
                    return window.aquaSafeSyncRegistry[key].signal;
                },
                abort(key) {
                    if (window.aquaSafeSyncRegistry[key]) window.aquaSafeSyncRegistry[key].abort();
                }
            };

            // --- NAVIGATION ENGINE (Priority) ---
            window.switchTab = function(tabId, element) {
                console.log("[AquaSafe] Navigation Attempt:", tabId);
                try {
                    // 1. Sections
                    const sections = document.querySelectorAll('.content-section');
                    if (sections.length === 0) { console.warn("No content sections found!"); }
                    sections.forEach(s => s.classList.remove('active'));
                    
                    const target = document.getElementById(tabId);
                    if (target) {
                        target.classList.add('active');
                    } else {
                        console.error("Navigation Target Not Found:", tabId);
                        return;
                    }
                    
                    // 2. Links
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    let activeLink = element || document.getElementById('nav-' + tabId);
                    if (activeLink) activeLink.classList.add('active');

                    // 3. UI Updates
                    window.scrollTo({ top: 0, behavior: 'auto' });
                    const titleEl = document.getElementById('pageTitle');
                    if (titleEl) {
                        const titles = { 'dashboard': 'Admin Dashboard', 'sensors': 'Sensors', 'alerts': 'Alerts', 'map': 'Live Map', 'reports': 'Reports', 'helpdesk': 'Help Desk', 'users': 'User Management', 'system_settings': 'Settings' };
                        titleEl.innerText = titles[tabId] || 'Admin Dashboard';
                    }

                    // 4. Mobile
                    if (window.innerWidth <= 1024 && typeof window.toggleSidebar === 'function') {
                        const sidebar = document.querySelector('.sidebar');
                        if (sidebar && sidebar.classList.contains('active')) window.toggleSidebar();
                    }

                    // 5. Data Refresh (Passive)
                    if (tabId === 'map' && typeof window.initMap === 'function') window.initMap();
                    if (tabId === 'reports' && typeof window.renderReportCharts === 'function') window.renderReportCharts();
                    if (tabId === 'evacuation' && typeof window.fetchEvacuationPoints === 'function') window.fetchEvacuationPoints();
                    if (typeof window.refreshAdminDashboard === 'function') {
                         console.log("[AquaSafe] Triggering background refresh for:", tabId);
                         window.refreshAdminDashboard(); // Trigger full refresh
                    }
                } catch(e) { console.error("switchTab Crisis:", e); }
            };

            window.startPolling = function(rate) {
                console.log("[AquaSafe] Sync Polling initialized at " + rate + "s");
                if (window.refreshAdminDashboard) window.refreshAdminDashboard();
            };

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
                } catch(e) { console.error("Export Error:", e); }
            };
            window.aquaSafeDownloadPDF = function(name, btn) {
                const old = btn ? btn.innerHTML : "Download";
                if(btn) btn.innerText = "⏳ Preparing...";
                setTimeout(() => {
                    try {
                        const pdfContent = "%PDF-1.4\n1 0 obj\<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj\<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj\<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer\<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";
                        const blob = new Blob([pdfContent], { type: 'application/pdf' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = "AquaSafe_Report.pdf";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    } catch(e) { console.error("PDF Error:", e); }
                    if(btn) btn.innerHTML = old;
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
                <li><a href="#" id="nav-notifications" class="nav-link" onclick="switchTab('iq_intelligence', this)">🔔 Notifications</a></li>
                <?php if($user_email === SUPER_ADMIN_EMAIL): ?>
                <li><a href="#" id="nav-users" class="nav-link" onclick="switchTab('users', this)">👥 Manage Users</a></li>
                <?php endif; ?>
                <li><a href="#" id="nav-system_settings" class="nav-link" onclick="switchTab('system_settings', this)">⚙️ Settings</a></li>
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
                    <!-- Dashboard Informative Widgets -->
                    <div class="status-widgets-grid">
                        <div class="status-widget">
                            <i data-lucide="bell"></i>
                            <div class="widget-value" id="widget-alerts-today">0</div>
                            <div class="widget-label">Alerts Today</div>
                        </div>
                        <div class="status-widget">
                            <i data-lucide="alert-triangle"></i>
                            <div class="widget-value" id="widget-flood-risk">---</div>
                            <div class="widget-label">Flood Risk</div>
                        </div>
                        <div class="status-widget" id="widget-latest-alert-container">
                            <i data-lucide="message-square"></i>
                            <div class="widget-value" id="widget-latest-msg" style="font-size: 13px; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">---</div>
                            <div class="widget-label" id="widget-latest-location">Latest Alert</div>
                        </div>
                        <div class="status-widget">
                            <i data-lucide="map-pin"></i>
                            <div class="widget-value" id="widget-evac-count">0</div>
                            <div class="widget-label">Evac Points</div>
                        </div>
                        <div class="status-widget">
                            <i data-lucide="waves"></i>
                            <div class="widget-value" id="widget-daily-peak">0.00 ft</div>
                            <div class="widget-label">Daily Peak</div>
                        </div>
                    </div>

                    <!-- Device Status Cards -->
                    <div class="device-cards-grid">
                        <!-- Sender Card -->
                        <div class="device-card" id="device-sender">
                            <div class="device-status-badge status-offline" id="sender-status-badge">Offline</div>
                            <div class="device-header">
                                <div class="device-icon-wrapper">
                                    <i data-lucide="radio" style="color: #4ab5c4;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 18px;">Sender Device</h3>
                                    <div style="font-size: 13px; color: #4ab5c4; font-weight: 600; margin-bottom: 2px;" id="sender-location">---</div>
                                    <span style="font-size: 12px; opacity: 0.5;" id="sender-id-text">ID: SNS-001</span>
                                </div>
                            </div>
                            <div class="device-stats">
                                <div class="stat-item">
                                    <div class="stat-label">Water Level</div>
                                    <div class="stat-value" id="sender-water-level">-- ft</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Last Ping</div>
                                    <div class="stat-value" id="sender-last-ping">Never</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Daily Peak</div>
                                    <div class="stat-value" id="sender-peak">-- ft</div>
                                </div>
                                <div class="stat-item" style="grid-column: span 2; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 5px; padding-top: 10px;">
                                    <div class="stat-label">Last Data Link</div>
                                    <div class="stat-value" id="sender-timestamp" style="font-size: 13px; color: #4ab5c4;">Waiting for data...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Receiver Card -->
                        <div class="device-card" id="device-receiver">
                            <div class="device-status-badge status-offline" id="receiver-status-badge">Offline</div>
                            <div class="device-header">
                                <div class="device-icon-wrapper">
                                    <i data-lucide="hard-drive" style="color: #4ab5c4;"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 18px;">Receiver Device</h3>
                                    <div style="font-size: 13px; color: #4ab5c4; font-weight: 600; margin-bottom: 2px;" id="receiver-location">---</div>
                                    <span style="font-size: 12px; opacity: 0.5;" id="receiver-id-text">ID: REC-001</span>
                                </div>
                            </div>
                            <div class="device-stats">
                                <div class="stat-item">
                                    <div class="stat-label">Packet Status</div>
                                    <div class="stat-value" id="receiver-packet">N/A</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Last Ping</div>
                                    <div class="stat-value" id="receiver-last-ping">Never</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Uptime</div>
                                    <div class="stat-value" id="receiver-uptime">-- hrs</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Sync Health</div>
                                    <div class="stat-value" id="receiver-health">--</div>
                                </div>
                                <div class="stat-item" style="grid-column: span 2; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 5px; padding-top: 10px;">
                                    <div class="stat-label">Last Communication</div>
                                    <div class="stat-value" id="receiver-timestamp" style="font-size: 13px; color: #4ab5c4;">Waiting for data...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Alerts Summary moved to Notifications Centre -->


                    <!-- Dashboard Real-time Poller -->
                    <script>
                    (function() {
                        const riskColors = { 'SAFE': '#2ecc71', 'WARNING': '#f1c40f', 'CRITICAL': '#e74c3c', 'DANGER': '#e74c3c' };

                        window.pollDashboardData = async function() {
                            const signal = window.SyncManager.getSignal('stats');

                            try {
                                // 1. Poll Device Status
                                const devRes = await fetch('get_device_status.php?_t=' + Date.now(), { signal: signal });
                                // ... rest of devRes handling ...
                                const devJson = await devRes.json();
                                if (devJson.status === 'success') {
                                    devJson.devices.forEach(dev => {
                                        const type = dev.sensor_id === 'SNS-001' ? 'sender' : 'receiver';
                                        const badge = document.getElementById(type + '-status-badge');
                                        const ping = document.getElementById(type + '-last-ping');
                                        
                                        if (badge) {
                                            badge.className = 'device-status-badge ' + (dev.is_online ? 'status-active' : 'status-offline');
                                            badge.textContent = dev.is_online ? 'Active' : 'Offline';
                                        }
                                        if (ping) {
                                            const ts = dev.last_ping || dev.timestamp || '';
                                            ping.textContent = ts ? new Date(ts.replace(' ', 'T')).toLocaleTimeString() : 'Never';
                                        }
                                        
                                        const locEl = document.getElementById(type + '-location');
                                        if (locEl) locEl.textContent = '📍 ' + (dev.location || 'Unknown');
                                        
                                        const tsEl = document.getElementById(type + '-timestamp');
                                        if (tsEl) tsEl.textContent = dev.last_updated;

                                        if (type === 'sender') {
                                            const levelEl = document.getElementById('sender-water-level');
                                            const peakEl = document.getElementById('sender-peak');
                                            if (levelEl) levelEl.textContent = parseFloat(dev.water_level).toFixed(2) + ' ft';
                                            if (peakEl) peakEl.textContent = parseFloat(dev.water_level).toFixed(2) + ' ft'; 
                                        } else {
                                            const pkt = document.getElementById('receiver-packet');
                                            if(pkt) pkt.textContent = dev.is_online ? 'Decoding' : 'None';
                                            const up = document.getElementById('receiver-uptime');
                                            if(up) up.textContent = dev.is_online ? '12.5 hrs' : '0 hrs';
                                            const hlth = document.getElementById('receiver-health');
                                            if(hlth) hlth.textContent = dev.is_online ? 'Excellent' : 'Offline';
                                        }
                                    });
                                }

                                // 2. Poll Widgets Stats
                                const statsRes = await fetch('get_dashboard_stats.php?_t=' + Date.now(), { signal: signal });
                                const statsJson = await statsRes.json();
                                if (statsJson.status === 'success') {
                                    const d = statsJson.data;
                                    const alertCount = document.getElementById('widget-alerts-today');
                                    if(alertCount) alertCount.textContent = d.total_alerts_today;
                                    
                                    const riskEl = document.getElementById('widget-flood-risk');
                                    if(riskEl) {
                                        riskEl.textContent = d.flood_risk_level;
                                        riskEl.style.color = riskColors[d.flood_risk_level] || '#fff';
                                    }
                                    
                                    const latest = d.latest_alert;
                                    const msgEl = document.getElementById('widget-latest-msg');
                                    if(msgEl) msgEl.textContent = latest.message;
                                    const locEl = document.getElementById('widget-latest-location');
                                    if(locEl) locEl.textContent = '📍 ' + (latest.location || 'System');
                                    const cont = document.getElementById('widget-latest-alert-container');
                                    if(cont) cont.title = 'Location: ' + (latest.location || 'General');
                                    
                                    const evacEl = document.getElementById('widget-evac-count');
                                    if(evacEl) evacEl.textContent = d.evacuation_points_count;
                                    const peakEl = document.getElementById('widget-daily-peak');
                                    if(peakEl) peakEl.textContent = parseFloat(d.daily_peak).toFixed(2) + ' ft';
                                }

                                if (window.lucide) lucide.createIcons();
                                
                            } catch(e) {
                                if (e.name !== 'AbortError') console.error("[AquaSafe] Poller Error:", e);
                            } finally {
                                // Recursive schedule: 20s
                                if (!window.aquaSafeSyncRegistry.stats?.signal.aborted) {
                                    setTimeout(pollDashboardData, 20000);
                                }
                            }
                        }

                        // pollDashboardData() now managed by AdminSyncEngine
                    })();
                    </script>


                </div>
            </div>

            <!-- Sensors Section -->
            <div id="sensors" class="content-section">
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
                        <div>
                            <h3 style="margin: 0;">📡 Sensor Management</h3>
                            <p style="font-size: 12px; opacity: 0.5; margin-top: 5px;">Linked to real-time IoT network</p>
                        </div>
                        <div style="display:flex; gap:12px; align-items: center;">
                            <div style="position: relative;">
                                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; opacity: 0.4;"></i>
                                <input type="text" id="sensorSearch" onkeyup="filterSensors()" placeholder="Search ID, Location or Role..." 
                                    style="padding:10px 15px 10px 35px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); color:white; outline:none; font-family:inherit; min-width: 250px;">
                            </div>
                            
                            <select id="roleFilter" onchange="filterSensors()" 
                                style="padding:10px 15px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); color:white; outline:none; font-family:inherit; cursor:pointer;">
                                <option value="All">All Roles</option>
                                <option value="Sender ESP">Sender ESP</option>
                                <option value="Receiver ESP">Receiver ESP</option>
                            </select>

                            <select id="sensorFilter" onchange="filterSensors()" 
                                style="padding:10px 15px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); color:white; outline:none; font-family:inherit; cursor:pointer;">
                                <option value="All">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Offline">Offline</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>

                            <button onclick="window.openSensorModal()" style="padding: 10px 18px; background: #4ab5c4; border: none; color: #032023; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                                <i data-lucide="plus" style="width: 16px;"></i> Add
                            </button>
                        </div>
                    </div>

                    <script>
                        function filterSensors() {
                            const filter = document.getElementById("sensorSearch").value.toUpperCase();
                            const statusFilter = document.getElementById("sensorFilter").value;
                            const roleFilter = document.getElementById("roleFilter").value;
                            const table = document.getElementById("sensorTable");
                            const tr = table.getElementsByTagName("tr");

                            for (let i = 1; i < tr.length; i++) {
                                const cells = tr[i].getElementsByTagName("td");
                                if (cells.length < 5) continue; // Now expect at least 6 cols

                                const textID = cells[0].textContent || "";
                                const textLoc = cells[1].textContent || "";
                                const textRole = cells[2].textContent || "";
                                const textSignal = cells[3].textContent || "";

                                const matchesSearch = (textID + textLoc + textRole).toUpperCase().includes(filter);
                                
                                // Role filtering
                                const matchesRole = (roleFilter === 'All' || textRole.includes(roleFilter));

                                // Signal/Status filtering
                                let matchesStatus = (statusFilter === 'All');
                                if (statusFilter === 'Active') matchesStatus = textSignal.includes('Good');
                                if (statusFilter === 'Offline') matchesStatus = textSignal.includes('Lost');
                                if (statusFilter === 'Maintenance') matchesStatus = textRole.includes('Maintenance') || textSignal.includes('Maintenance') || textSignal.includes('Implementing Soon');

                                tr[i].style.display = (matchesSearch && matchesRole && matchesStatus) ? "" : "none";
                            }
                        }
                    </script>
                    <div style="overflow-x:auto;">
                        <table id="sensorTable" style="width: 100%; border-collapse: collapse; margin-top: 10px; color: rgba(255,255,255,0.8);">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Sensor ID</th>
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Location</th>
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Role</th>
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Signal Status</th>
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Water Level</th>
                                    <th style="padding: 18px 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Last Ping</th>
                                </tr>
                            </thead>
                            <tbody id="sensorTableBody">
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; opacity: 0.5;">📡 Synchronizing sensor network...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <script>
                    (function() {
                        const signalColors = { 'Good': '#2ecc71', 'Weak': '#f1c40f', 'Lost': '#e74c3c' };

                        window.fetchSensorStatus = async function() {
                            const signal = window.SyncManager.getSignal('sensors');

                            try {
                                const res = await fetch('get_sensors_list.php?_t=' + Date.now(), { signal: signal });
                                const json = await res.json();
                                if (json.status !== 'success') return;

                                const tbody = document.getElementById("sensorTableBody");
                                const zoneEl = document.getElementById('criticalZoneCount');
                                if (!tbody && !zoneEl) return;

                                let html = "";
                                let criticalCount = 0;
                                
                                json.sensors.forEach(s => {
                                    const sigColor = signalColors[s.signal] || (s.signal === 'Implementing Soon' ? '#95a5a6' : '#fff');
                                    const isPlaceholder = (s.signal === 'Implementing Soon');
                                    
                                    // Health logic (match old fetchSensorStatus)
                                    if (s.signal === 'Lost' || s.signal.includes('Maintenance')) criticalCount++;

                                    html += `
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s; ${isPlaceholder ? 'opacity: 0.7;' : ''}" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 15px; font-weight: 600; color: #4ab5c4;">${s.id}</td>
                                            <td style="padding: 15px; font-size: 14px;">${s.location}</td>
                                            <td style="padding: 15px;"><span style="background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 6px; font-size: 12px;">${s.role}</span></td>
                                            <td style="padding: 15px;">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: ${sigColor}; box-shadow: 0 0 10px ${sigColor}44;"></div>
                                                    <span style="font-size: 13px; ${isPlaceholder ? 'background: rgba(149, 165, 166, 0.15); color: #95a5a6; padding: 2px 8px; border-radius: 4px; font-weight: 600;' : ''}">${s.signal}</span>
                                                </div>
                                            </td>
                                            <td style="padding: 15px; font-weight: 700; color: #fff;">${s.water_level}</td>
                                            <td style="padding: 15px; font-size: 13px; opacity: 0.7;">${s.last_ping}</td>
                                        </tr>
                                    `;
                                });

                                if (tbody) {
                                    tbody.innerHTML = html;
                                    if (typeof filterSensors === 'function') filterSensors();
                                }

                                // Update Dynamic Graph Area Selector
                                const areaDropdown = document.getElementById('areaSelector');
                                if(areaDropdown && json.sensors.length > 0) {
                                    const currentVal = areaDropdown.value;
                                    const uniqueAreas = [...new Set(['South Reservoir', ...json.sensors.map(s => s.location)])].sort();
                                    
                                    const existingOptions = Array.from(areaDropdown.options).map(o => o.value).sort();
                                    if(uniqueAreas.join('|') !== existingOptions.join('|')) {
                                        let optionsHtml = '';
                                        uniqueAreas.forEach(area => {
                                            optionsHtml += `<option value="${area}">${area}</option>`;
                                        });
                                        areaDropdown.innerHTML = optionsHtml;
                                        if (uniqueAreas.includes(currentVal)) areaDropdown.value = currentVal;
                                        else areaDropdown.value = 'South Reservoir';
                                    }

                                    // Init data for new areas in chartDataStore
                                    uniqueAreas.forEach(area => {
                                        if(!window.chartDataStore) window.chartDataStore = {};
                                        if(!chartDataStore[area]) {
                                            chartDataStore[area] = {
                                                labels: Array.from({length:6}, (_,i) => new Date(Date.now() - (5-i)*300000).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})),
                                                data: Array.from({length:6}, () => 40 + Math.random() * 20)
                                            };
                                        }
                                    });
                                }

                                // Update widgets (match old fetchSensorStatus)
                                if (zoneEl) {
                                    zoneEl.innerText = criticalCount + (criticalCount === 1 ? " Zone" : " Zones");
                                    
                                    const safetyPercent = json.sensors.length > 0 ? Math.round(((json.sensors.length - criticalCount) / json.sensors.length) * 100) : 100;
                                    const safetyEl = document.getElementById('overallSafetyValue');
                                    if (safetyEl) {
                                        safetyEl.innerText = safetyPercent + "%";
                                        safetyEl.className = 'status-value ' + (safetyPercent > 90 ? 'safe-text' : (safetyPercent > 70 ? 'warning-text' : 'danger-text'));
                                    }

                                    const healthEl = document.getElementById('systemHealthText');
                                    if (healthEl) {
                                        if (criticalCount === 0) {
                                            healthEl.innerText = "Optimal";
                                            healthEl.className = 'status-value safe-text';
                                        } else if (criticalCount < json.sensors.length / 2) {
                                            healthEl.innerText = "Degraded";
                                            healthEl.className = 'status-value warning-text';
                                        } else {
                                            healthEl.innerText = "Critical";
                                            healthEl.className = "status-value danger-text";
                                        }
                                    }
                                }
                            } catch(e) {
                                if (e.name !== 'AbortError') console.error("[AquaSafe] Sensor Table Poller Error:", e);
                            } finally {
                                // Recursive schedule: 30s
                                if (!window.aquaSafeSyncRegistry.sensors?.signal.aborted) {
                                    setTimeout(window.fetchSensorStatus, 30000);
                                }
                            }
                        }

                        // Initial pull
                        window.fetchSensorStatus();
                    })();
                    </script>
                </div>
            </div>

             <!-- Alerts Section -->
            <div id="alerts" class="content-section">
                <!-- Emergency Broadcast System (Relocated from Notifications) -->
                <div class="card" style="margin-bottom: 25px; border-left: 5px solid #e74c3c;">
                    <h4 style="color: #e74c3c; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <span>📢</span> Emergency Broadcast System
                    </h4>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; margin-bottom: 5px; font-weight: 600;">Broadcast Message</label>
                        <textarea id="alertMessage" placeholder="Enter emergency instructions to be sent immediately..." style="width: 100%; padding: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 12px; min-height: 120px; font-family: inherit; font-size: 14px; outline: none; transition: border-color 0.3s;" onfocus="this.style.borderColor='#e74c3c'"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Target Area</label>
                            <select id="alertArea" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                                <option value="All" style="background: #1e2029; color: white;">🌍 Entire System</option>
                                <option value="South Reservoir" style="background: #1e2029; color: white;">🌊 South Reservoir</option>
                                <option value="East Valley" style="background: #1e2029; color: white;">⛰️ East Valley</option>
                                <option value="North District" style="background: #1e2029; color: white;">🏗️ North District</option>
                                <option value="Central City" style="background: #1e2029; color: white;">🏙️ Central City</option>
                                <option value="Churakullam" style="background: #1e2029; color: white;">📍 Churakullam</option>
                                <option value="Kakkikavala" style="background: #1e2029; color: white;">📍 Kakkikavala</option>
                                <option value="Nellimala" style="background: #1e2029; color: white;">📍 Nellimala</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Severity Level</label>
                            <select id="alertSeverity" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px;">
                                <option value="Warning" style="background: #1e2029; color: white;">⚠️ Warning</option>
                                <option value="Critical" style="background: #1e2029; color: white;">🚨 Critical</option>
                                <option value="Evacuation" style="background: #1e2029; color: white;">🏃 Evacuation</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button onclick="sendBroadcast()" id="btnBroadcast" style="flex: 2; padding: 12px; background: #e74c3c; border: none; border-radius: 8px; color: white; font-weight: 700; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i data-lucide="send"></i> Send Broadcast
                        </button>
                        <button onclick="quickAlert('Warning')" style="flex: 1; padding: 12px; background: rgba(241, 196, 15, 0.2); border: 1px solid #f1c40f; border-radius: 8px; color: #f1c40f; font-weight: 600; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px;" title="Quick Warning">
                            ⚠️ Warning
                        </button>
                        <button onclick="quickAlert('Critical')" style="flex: 1; padding: 12px; background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; border-radius: 8px; color: #e74c3c; font-weight: 600; cursor: pointer; transition: background 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px;" title="Quick Critical">
                            🚨 Critical
                        </button>
                    </div>

                    <script>
                        function quickAlert(severity) {
                            document.getElementById('alertSeverity').value = severity;
                            const msg = severity === 'Warning' ? 'Attention: Water levels are rising. Please stay alert.' : 'CRITICAL DANGER: Evacuate immediately!';
                            document.getElementById('alertMessage').value = msg;
                        }
                    </script>
                    
                    <p style="font-size: 12px; opacity: 0.5; margin-top: 15px; text-align: center;">
                        <span>ℹ️</span> This will email all registered users and offline community contacts in the selected area via PHPMailer.
                    </p>
                </div>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>🚨 Action History</h3>
                        <div style="display: flex; gap: 10px;">
                            <select id="alertsLocationFilter" onchange="fetchSystemAlerts()" style="padding: 8px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 6px; font-family: inherit; outline: none; cursor: pointer;">
                                <option value="">All Locations</option>
                                <option value="South Reservoir">South Reservoir</option>
                                <option value="East Valley">East Valley</option>
                                <option value="North District">North District</option>
                                <option value="Central City">Central City</option>
                                <option value="Churakullam">Churakullam</option>
                                <option value="Kakkikavala">Kakkikavala</option>
                                <option value="Nellimala">Nellimala</option>
                            </select>
                            <button onclick="fetchSystemAlerts()" style="padding: 8px 15px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 6px; cursor: pointer;">Refresh</button>
                        </div>
                    </div>
                    <div id="alertsHistoryList" style="margin-top: 20px; max-height: 400px; overflow-y: auto; padding-right: 5px;">
                        <p style="opacity: 0.5; font-size: 14px; text-align: center; padding: 20px;">Loading alert history...</p>
                    </div>
                </div>
            </div>

             <!-- Map Section -->
            <div id="map" class="content-section">
                 <div class="card" style="height: 600px; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>🗺️ Live Sensor Network</h3>
                        <div style="display: flex; gap: 10px;">
                            <button onclick="mapSetView(9.5700, 77.0800, 11)" style="padding: 5px 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 4px; cursor: pointer;">Idukki</button>
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

                    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
                        <input type="text" id="evacSearch" onkeyup="filterEvacuation()" placeholder="Search Name or Area..." 
                            style="padding:10px 15px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); color:white; outline:none; font-family:inherit; flex:1;">
                        
                        <select id="evacFilter" onchange="filterEvacuation()" 
                            style="padding:10px 15px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(0,0,0,0.2); color:white; outline:none; font-family:inherit; cursor:pointer;">
                            <option value="All">All Status</option>
                            <option value="Available">Available</option>
                            <option value="Full">Full</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <script>
                        function filterEvacuation() {
                            const input = document.getElementById("evacSearch");
                            const filter = input.value.toUpperCase();
                            const statusFilter = document.getElementById("evacFilter").value;
                            const container = document.getElementById("evacuationList");
                            const cards = container.getElementsByClassName("evac-card"); // Need to ensure cards have this class

                            for (let i = 0; i < cards.length; i++) {
                                const title = cards[i].getAttribute('data-name');
                                const status = cards[i].getAttribute('data-status');
                                
                                const matchesSearch = title.toUpperCase().indexOf(filter) > -1;
                                const matchesStatus = statusFilter === 'All' || status === statusFilter;

                                if (matchesSearch && matchesStatus) {
                                    cards[i].style.display = "";
                                } else {
                                    cards[i].style.display = "none";
                                }
                            }
                        }
                    </script>
                    
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
                <!-- Filter Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px 25px; background: rgba(255,255,255,0.05); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                    <h2 style="font-size: 20px; font-weight: 600; color: #fff;">Analytics Overview</h2>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        
                        <!-- View Toggle -->
                        <div style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 3px; display: flex; border: 1px solid rgba(255,255,255,0.1);">
                            <button onclick="toggleReportView('charts')" id="btnViewCharts" style="padding: 6px 12px; border: none; background: #4ab5c4; color: white; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">📊 Visuals</button>
                            <button onclick="toggleReportView('list')" id="btnViewList" style="padding: 6px 12px; border: none; background: transparent; color: rgba(255,255,255,0.6); border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">📋 Data Log</button>
                        </div>
                        
                        <div style="width: 1px; height: 25px; background: rgba(255,255,255,0.1);"></div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 14px; opacity: 0.7;">Area:</span>
                            <select id="reportAreaSelector" onchange="renderReportCharts()" style="padding: 8px 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.2); outline: none;">
                                <option value="All">🌍 System Wide</option>
                                <option value="South Reservoir">🌊 South Reservoir</option>
                                <option value="East Valley">🏘️ East Valley</option>
                                <option value="Central City">🏙️ Central City</option>
                                <option value="North District">🏗️ North District</option>
                                <option value="Churakullam">📍 Churakullam</option>
                                <option value="Kakkikavala">📍 Kakkikavala</option>
                                <option value="Nellimala">📍 Nellimala</option>
                            </select>
                        </div>
                        
                        <div style="width: 1px; height: 25px; background: rgba(255,255,255,0.1);"></div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 14px; opacity: 0.7;">Range:</span>
                            <select id="reportTimeRange" onchange="renderReportCharts()" style="padding: 8px 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.2); outline: none;">
                                <option value="24h">Last 24 Hours</option>
                                <option value="7d">Last 7 Days</option>
                                <option value="30d">Last 30 Days</option>
                            </select>
                        </div>

                        <div style="width: 1px; height: 25px; background: rgba(255,255,255,0.1);"></div>

                        <!-- Export Button -->
                        <button onclick="exportReportData()" style="padding: 8px 16px; border: 1px solid #4ab5c4; background: rgba(74, 181, 196, 0.1); color: #4ab5c4; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                            <span>📥</span> Export CSV
                        </button>

                        <script>
                            window.exportReportData = function() {
                                const range = document.getElementById('reportTimeRange')?.value || '24h';
                                const area = document.getElementById('reportAreaSelector')?.value || 'All';
                                window.location.href = `export_report_csv.php?range=${range}&area=${area}`;
                            };
                        </script>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                     <div class="status-card" onclick="viewMetricDetails('alerts')" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                         <div class="status-label">Total Alerts</div>
                         <div id="statTotalAlerts" class="status-value warning-text">14</div>
                     </div>
                     <div class="status-card" onclick="viewMetricDetails('floods')" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                         <div class="status-label">Flood Events</div>
                         <div id="statFloodEvents" class="status-value danger-text">3</div>
                     </div>
                     <div class="status-card" onclick="viewMetricDetails('recovery')" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                         <div class="status-label">Safe Recoveries</div>
                         <div id="statSafeRecoveries" class="status-value safe-text">98%</div>
                     </div>
                </div>

                <!-- View: Charts -->
                <div id="reportChartsView" class="dashboard-grid">
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

                <!-- View: List (Initially Hidden) -->
                <div id="reportListView" class="card" style="margin-bottom: 25px; display: none;">
                    <h3>📋 Detailed Event History</h3>
                    
                    <!-- Search & Filter Controls -->
                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <input type="text" id="reportSearch" onkeyup="filterEventHistory()" placeholder="🔍 Search events..." 
                               style="flex: 1; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                        
                        <select id="reportFilter" onchange="filterEventHistory()" 
                                style="padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; cursor: pointer;">
                            <option value="All" style="background: #1e2029; color: white;">All Severities</option>
                            <option value="Critical" style="background: #1e2029; color: white;">🚨 Critical</option>
                            <option value="Warning" style="background: #1e2029; color: white;">⚠️ Warning</option>
                            <option value="Info" style="background: #1e2029; color: white;">ℹ️ Info</option>
                        </select>
                    </div>

                    <div style="overflow-x: auto; max-height: 400px; overflow-y: auto;">
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

                <!-- Script for Toggle -->
                <script>
                    function toggleReportView(view) {
                        const charts = document.getElementById('reportChartsView');
                        const list = document.getElementById('reportListView');
                        const btnC = document.getElementById('btnViewCharts');
                        const btnL = document.getElementById('btnViewList');
                        
                        if(view === 'charts') {
                            charts.style.display = 'grid';
                            list.style.display = 'none';
                            btnC.style.background = '#4ab5c4'; btnC.style.color = 'white';
                            btnL.style.background = 'transparent'; btnL.style.color = 'rgba(255,255,255,0.6)';
                        } else {
                            charts.style.display = 'none';
                            list.style.display = 'block';
                            btnL.style.background = '#4ab5c4'; btnL.style.color = 'white';
                            btnC.style.background = 'transparent'; btnC.style.color = 'rgba(255,255,255,0.6)';
                        }
                    }

                    function filterEventHistory() {
                        const input = document.getElementById('reportSearch');
                        const filter = input.value.toUpperCase();
                        const severityFilter = document.getElementById('reportFilter').value;
                        const table = document.getElementById('eventLogBody');
                        const tr = table.getElementsByTagName('tr');

                        for (let i = 0; i < tr.length; i++) {
                            const tdEvents = tr[i].getElementsByTagName("td")[1]; // Event Column
                            const tdLocation = tr[i].getElementsByTagName("td")[2]; // Location Column
                            const tdSeverity = tr[i].getElementsByTagName("td")[3]; // Severity Column
                            
                            if (tdEvents && tdLocation) {
                                const txtValue = (tdEvents.textContent || tdEvents.innerText) + " " + (tdLocation.textContent || tdLocation.innerText);
                                const severityValue = tdSeverity.textContent || tdSeverity.innerText;
                                
                                const matchesSearch = txtValue.toUpperCase().indexOf(filter) > -1;
                                const matchesFilter = severityFilter === 'All' || severityValue.includes(severityFilter);
                                
                                if (matchesSearch && matchesFilter) {
                                    tr[i].style.display = "";
                                } else {
                                    tr[i].style.display = "none";
                                }
                            }       
                        }
                    }
                </script>

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

            <!-- Help Desk Section (Rescue Management) -->
            <div id="helpdesk" class="content-section">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                        <h3>🆘 Rescue Request Management</h3>
                        <div id="helpdesk-stats" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="background: rgba(241, 196, 15, 0.1); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;" id="pendingCount">Pending: 0</span>
                            <span style="background: rgba(52, 152, 219, 0.1); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;" id="respondedCount">Responded: 0</span>
                            <span style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;" id="resolvedCount">Resolved: 0</span>
                            <span style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;" id="emergencyCount">Emergency: 0</span>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; align-items: center; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="flex: 1; min-width: 250px; position: relative;">
                            <span style="position: absolute; left: 12px; top: 12px; opacity: 0.5;">🔍</span>
                            <input type="text" id="helpdeskSearch" onkeyup="filterHelpdesk()" placeholder="Search by name, email, location..." 
                                style="width: 100%; padding: 12px 15px 12px 40px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; outline: none; font-family: inherit;">
                        </div>
                        
                        <select id="helpdeskStatusFilter" onchange="filterHelpdesk()" style="padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; outline: none; font-family: inherit; cursor: pointer; min-width: 140px;">
                            <option value="All">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Responded">Responded</option>
                            <option value="Resolved">Resolved</option>
                        </select>

                        <select id="helpdeskPriorityFilter" onchange="filterHelpdesk()" style="padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; outline: none; font-family: inherit; cursor: pointer; min-width: 140px;">
                            <option value="All">All Priorities</option>
                            <option value="Normal">Normal</option>
                            <option value="Emergency">Emergency🚨</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;" id="adminHelpdeskList">
                        <div style="text-align:center; padding:30px; opacity:0.5;">Loading rescue requests...</div>
                    </div>
                </div>
            </div>

            <!-- Help Desk Reply Modal -->
            <div class="modal-overlay" id="helpdeskReplyModal">
                <div class="modal" style="max-width: 600px;">
                    <h2 style="margin-bottom: 5px;">Respond to Request</h2>
                    <p style="opacity: 0.7; font-size: 14px; margin-bottom: 20px;" id="replyModalSubtitle">Sending response to User...</p>
                    
                    <input type="hidden" id="replyRequestId">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Quick Templates</label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;">
                            <button type="button" onclick="insertReplyTemplate('Help is on the way. Please remain at your location or proceed to the nearest evacuation point if instructed.')" style="padding: 6px 12px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s;">Help is on the way</button>
                            <button type="button" onclick="insertReplyTemplate('We have received your request. Could you please provide your exact coordinates or a nearby landmark?')" style="padding: 6px 12px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s;">Request Coordinates</button>
                            <button type="button" onclick="insertReplyTemplate('Medical teams have been dispatched to your designated area. Please prepare your emergency kit.')" style="padding: 6px 12px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s;">Medical Dispatch</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea id="replyMessageText" rows="5" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; outline: none; font-family: inherit; resize: vertical;" placeholder="Type your response here..."></textarea>
                    </div>

                    <div class="modal-actions" style="margin-top: 25px;">
                        <button type="button" class="btn-cancel" onclick="document.getElementById('helpdeskReplyModal').classList.remove('active')">Cancel</button>
                        <button type="button" class="btn-submit" onclick="submitHelpdeskReply()">Send Response</button>
                    </div>
                </div>
            </div>

            <!-- User Management Section -->
            <div id="users" class="content-section">
                <div class="card">
                    <?php if($user_email === SUPER_ADMIN_EMAIL): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>👥 User Management</h3>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <button onclick="window.openCensusModal()" style="padding: 8px 15px; background: rgba(74, 181, 196, 0.15); border: 1px solid #4ab5c4; color: #4ab5c4; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                📂 Upload Census Data
                            </button>

                            <div style="font-size: 14px; opacity: 0.7;">Total Users: <?php echo count($all_users); ?></div>
                        </div>
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
                                        <span class="<?php echo ($u['email'] === SUPER_ADMIN_EMAIL) ? 'safe-text' : (($u['user_role'] === 'admin' || $u['user_role'] === 'administrator') ? 'danger-text' : 'info-text'); ?>" style="font-weight: 600;">
                                            <?php 
                                                if ($u['email'] === SUPER_ADMIN_EMAIL) {
                                                    echo '👑 Super Admin';
                                                } else {
                                                    $display_role = !empty($u['user_role']) ? ucfirst($u['user_role']) : 'Not Set';
                                                    if ($display_role === 'Administrator') echo 'Administrator';
                                                    else echo $display_role;
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: right;" data-label="Action">
                                        <?php if($u['email'] === SUPER_ADMIN_EMAIL): ?>
                                             <span style="opacity: 0.5; font-size: 12px; font-weight: bold; color: #f1c40f;">Protected</span>
                                        <?php elseif($u['email'] !== $user_email): ?>
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
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px;">
                            <h3 style="color: #e74c3c; margin-bottom: 15px;">🚫 Access Restricted</h3>
                            <p style="font-size: 16px; opacity: 0.8;">Only superadmin has the permission to do this.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- IQ Intelligence - Notifications Section -->
            <div id="iq_intelligence" class="content-section">
                <div style="display: grid; grid-template-columns: 350px 1fr; gap: 25px;">
                    
                    <!-- Left Column: Settings and Controls -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="card" style="padding: 25px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                <div style="width: 32px; height: 32px; background: rgba(192, 84, 255, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="settings-2" style="width: 18px; color: #c054ff;"></i>
                                </div>
                                <h3 style="margin: 0; font-size: 18px;">Alert Settings</h3>
                            </div>
                            <p style="opacity: 0.6; font-size: 13px; margin-bottom: 25px;">Configure IoT severity thresholds and delivery channels.</p>

                            <!-- Severity Thresholds -->
                            <div style="margin-bottom: 30px;">
                                <h4 style="font-size: 14px; margin-bottom: 20px; color: rgba(255,255,255,0.9); display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="bar-chart-3" style="width: 14px;"></i>
                                    Severity Thresholds (ft)
                                </h4>
                                
                                <div style="margin-bottom: 25px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <label style="font-size: 13px; opacity: 0.8;">Safe Max / Warning Min</label>
                                        <span style="color: #2ecc71; font-weight: 700; font-size: 14px;" id="thresholdSafeVal">10.00 ft</span>
                                    </div>
                                    <input type="range" id="thresholdSafeSlider" min="0" max="25" step="0.1" value="10" 
                                        style="width: 100%; cursor: pointer;" 
                                        oninput="document.getElementById('thresholdSafeVal').innerText = parseFloat(this.value).toFixed(2) + ' ft'" 
                                        onchange="saveIQSettings()">
                                    <div style="display: flex; justify-content: space-between; font-size: 10px; opacity: 0.4; margin-top: 5px;">
                                        <span>0.00 ft</span>
                                        <span>25.00 ft</span>
                                    </div>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <label style="font-size: 13px; opacity: 0.8;">Warning Max / Critical Min</label>
                                        <span style="color: #e74c3c; font-weight: 700; font-size: 14px;" id="thresholdWarningVal">18.00 ft</span>
                                    </div>
                                    <input type="range" id="thresholdWarningSlider" min="0" max="25" step="0.1" value="18" 
                                        style="width: 100%; cursor: pointer;" 
                                        oninput="document.getElementById('thresholdWarningVal').innerText = parseFloat(this.value).toFixed(2) + ' ft'" 
                                        onchange="saveIQSettings()">
                                </div>
                            </div>

                            <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 25px 0;">

                            <!-- Delivery Channels -->
                            <div>
                                <h4 style="font-size: 14px; margin-bottom: 20px; color: rgba(255,255,255,0.9); display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="megaphone" style="width: 14px;"></i>
                                    Delivery Channels
                                </h4>
                                
                                <div style="display: flex; flex-direction: column; gap: 18px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i data-lucide="message-square" style="width: 16px; opacity: 0.6;"></i>
                                            <label style="font-size: 14px; cursor: pointer;" for="chan_sms">SMS Alerts</label>
                                        </div>
                                        <label class="switch small">
                                            <input type="checkbox" id="chan_sms" onchange="saveIQSettings()" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i data-lucide="mail" style="width: 16px; opacity: 0.6;"></i>
                                            <label style="font-size: 14px; cursor: pointer;" for="chan_email">Email Updates</label>
                                        </div>
                                        <label class="switch small">
                                            <input type="checkbox" id="chan_email" onchange="saveIQSettings()" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i data-lucide="smartphone" style="width: 16px; opacity: 0.6;"></i>
                                            <label style="font-size: 14px; cursor: pointer;" for="chan_push">Push Notifications</label>
                                        </div>
                                        <label class="switch small">
                                            <input type="checkbox" id="chan_push" onchange="saveIQSettings()" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <i data-lucide="volume-2" style="width: 16px; opacity: 0.6;"></i>
                                            <label style="font-size: 14px; cursor: pointer;" for="chan_siren">External Siren</label>
                                        </div>
                                        <label class="switch small">
                                            <input type="checkbox" id="chan_siren" onchange="saveIQSettings()">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Settings Column -->

                    <!-- Right Column: IoT Intelligence Feed -->
                    <div class="card" style="display: flex; flex-direction: column; overflow: hidden; height: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: rgba(0, 229, 255, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="activity" style="width: 18px; color: #00e5ff;"></i>
                                </div>
                                <h3 style="margin: 0; font-size: 18px;">IoT Intelligence Feed</h3>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button onclick="fetchIQFeed()" class="btn-refresh" style="padding: 8px 16px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.2); color: #4ab5c4; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s;">
                                    <i data-lucide="refresh-cw" style="width: 14px;"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <p style="opacity: 0.6; font-size: 13px; margin-bottom: 25px;">Live timeline of auto-detected water level events and severity changes.</p>

                        <div id="iqFeedTimeline" style="flex: 1; overflow-y: auto; padding-right: 10px; display: flex; flex-direction: column; gap: 12px; min-height: 400px;">
                            <div style="text-align: center; padding: 40px; opacity: 0.5;">
                                <div class="loading-spinner" style="width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.1); border-top-color: #4ab5c4; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 10px;"></div>
                                Loading intelligence feed...
                            </div>
                        </div>
                    </div> <!-- End Feed Card -->

                </div> <!-- End Grid -->
            </div> <!-- End Notifications Section -->


                <!-- Standardized Settings Section -->
                <div id="system_settings" class="content-section">
                    


                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                        
                        <!-- Main Settings Card -->
                        <div class="card">
                            <h3>⚙️ General Configuration</h3>
                            
                            <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-size: 14px; opacity: 0.8;">Notification Email</label>
                                    <input type="email" id="adminEmail" value="admin@aquasafe.com" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-size: 14px; opacity: 0.8;">Dashboard Refresh Rate</label>
                                    <input type="number" id="refreshRate" value="30" style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                                </div>
                            </div>

                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                                <h3 style="font-size: 16px; margin-bottom: 15px;">System Preferences</h3>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 500;">Maintenance Mode</div>
                                        <div style="font-size: 12px; opacity: 0.5;">Suspend user access for updates</div>
                                    </div>
                                    <label class="switch small">
                                        <input type="checkbox" id="maintMode">
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <button onclick="saveSettings()" id="btnSaveSettings" style="padding: 12px 25px; background: #4ab5c4; border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer; float: right;">
                                    Save Changes
                                </button>
                                <div style="clear: both;"></div>
                            </div>
                        </div>

                        <!-- Sidebar Cards -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- Profile -->
                            <div class="card" style="text-align: center;">
                                <div style="width: 60px; height: 60px; background: #4ab5c4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin: 0 auto 15px; color: #0f2027;">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <h3 style="margin-bottom: 5px; font-size: 18px;"><?php echo htmlspecialchars($user_name); ?></h3>
                                <div style="font-size: 13px; opacity: 0.7;">
                                    <?php echo ($user_email === SUPER_ADMIN_EMAIL) ? 'Super Administrator' : 'Administrator'; ?>
                                </div>
                            </div>

                            <!-- System Health -->
                            <div class="card">
                                <h3>🖥️ System Health</h3>
                                <div style="margin-top: 15px; display: grid; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <span style="opacity: 0.7;">Status</span>
                                        <span style="color: #2ecc71;">● Online</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <span style="opacity: 0.7;">Database</span>
                                        <span>Connected</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                        <span style="opacity: 0.7;">Last Backup</span>
                                        <span>Today, 04:00 AM</span>
                                    </div>
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
            const { timeout = 15000, signal } = options;
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);
            
            // Link external signal if provided
            if (signal) {
                signal.addEventListener('abort', () => controller.abort(), { once: true });
            }

            try {
                const response = await fetch(resource, { ...options, signal: controller.signal });
                clearTimeout(id);
                return response;
            } catch (err) {
                clearTimeout(id);
                throw err;
            }
        }

        // GLOBAL UNLOCK: Verify audio context is ready on ANY interaction
        function resumeAudioContext() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume().then(() => {
                    console.log("Audio Context Resumed by Interaction");
                    if (pendingAlarmData && !isAlarmPlaying) {
                        playEmergencyAlarm(pendingAlarmData.message);
                    }
                });
            }
        }
        document.addEventListener('click', resumeAudioContext);
        document.addEventListener('keydown', resumeAudioContext);
        document.addEventListener('touchstart', resumeAudioContext);

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
            const signal = window.SyncManager.getSignal('helpdesk');

            const listEl = document.getElementById('adminHelpdeskList');
            if(!listEl) return;

            if(listEl.innerHTML.includes('Loading') || listEl.innerHTML.includes('found') || listEl.innerHTML === '') {
                listEl.innerHTML = '<div style="text-align:center; padding:30px; opacity:0.8; color:var(--info);">📡 Fetching user requests... (Please wait)</div>';
            }

            try {
                const res = await fetchWithTimeout('manage_helpdesk.php?action=fetch_all&_t=' + Date.now(), { 
                    timeout: 15000,
                    signal: signal 
                });
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
                        listEl.innerHTML = '<div style="text-align:center; padding:30px; opacity:0.5;">No active helpdesk requests.</div>';
                    }

                    // Update UI Counters
                    const pCount = document.getElementById('pendingCount');
                    const rCount = document.getElementById('resolvedCount');
                    if(pCount) pCount.innerText = 'Pending: ' + pending;
                    if(rCount) rCount.innerText = 'Resolved: ' + resolved;

                    const hBadge = document.getElementById('helpdeskBadge');
                    if (hBadge) {
                        if (pending > 0 && !document.getElementById('helpdesk').classList.contains('active')) {
                            hBadge.innerText = pending;
                            hBadge.style.display = 'block';
                        } else {
                            hBadge.style.display = 'none';
                        }
                    }
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error("Helpdesk Poll Error:", err);
                    listEl.innerHTML = `<div style="text-align:center; padding:30px; color:#e74c3c;">Failed to load requests.</div>`;
                }
            } finally {
                // Recursive schedule: 60s
                if (window.aquaSafeSyncRegistry && !window.aquaSafeSyncRegistry.helpdesk?.signal.aborted) {
                    setTimeout(window.fetchHelpdeskRequests, 60000);
                }
            }
        };

        // Consolidating logic
        // MOVED TO PRIORITY BRIDGE AT BODY START

        // --- EMERGENCY BROADCAST SYSTEM ---
        window.sendBroadcast = async function() {
            const area = document.getElementById('alertArea').value;
            const severity = document.getElementById('alertSeverity').value;
            const message = document.getElementById('alertMessage').value;
            
            if(!message) { 
                if(window.showNotification) window.showNotification("Please enter a message!", "warning");
                else alert("Please enter a message!"); 
                return; 
            }
            
            const confirmMsg = `⚠️ CONFIRM BROADCAST\nAre you sure you want to send a ${severity} alert to ${area}? This will email all registered users AND offline community contacts.`;
            
            const triggerBroadcast = async () => {
                const btn = document.getElementById('btnBroadcast');
                const originalText = btn.innerText;
                btn.innerText = "Sending Emails...";
                btn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'broadcast_alert');
                formData.append('area', area);
                formData.append('severity', severity);
                formData.append('message', message);

                try {
                    const res = await fetch('manage_community.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    if(window.showNotification) window.showNotification(json.message, json.status === 'success' ? 'success' : 'error');
                    else alert(json.message);
                } catch(e) {
                    if(window.showNotification) window.showNotification("Failed to send broadcast.", "error");
                    else alert("Failed to send broadcast.");
                    console.error(e);
                }
                
                btn.innerText = originalText;
                btn.disabled = false;
            };

            if(window.showConfirm) {
                window.showConfirm(confirmMsg, triggerBroadcast);
            } else {
                if(confirm(confirmMsg)) triggerBroadcast();
            }
        };

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

            // Validation
            const name = document.getElementById('pointName').value.trim();
            const location = document.getElementById('pointLocation').value.trim();
            const capacity = document.getElementById('pointCapacity').value;
            const sensor = document.getElementById('pointSensor').value.trim();
            const lat = document.getElementById('pointLat').value;
            const lng = document.getElementById('pointLng').value;

            if(!name || !location || !capacity || !sensor || !lat || !lng) {
                alert("Please fill ALL fields. Assigned Sensor is mandatory.");
                return;
            }

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

        window.getDirections = function(id) {
            const pt = allEvacPoints[id];
            if(!pt) return;

            let destination = "";
            if(parseFloat(pt.latitude) !== 0 && parseFloat(pt.longitude) !== 0) {
                destination = pt.latitude + "," + pt.longitude;
            } else {
                destination = encodeURIComponent(pt.name + ", " + pt.location + ", Idukki, Kerala");
            }

            const url = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
            window.open(url, '_blank');
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
                        card.className = "evac-card"; // Hook for Search
                        card.setAttribute('data-name', (pt.name + " " + pt.location).toLowerCase()); // Search Index
                        card.setAttribute('data-status', pt.status); // Hook for Filter
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
                            <div style="margin-top: 15px;">
                                <button onclick="window.getDirections('${pt.id}')" style="width: 100%; padding: 12px; background: rgba(74, 181, 196, 0.1); border: 1px solid rgba(74, 181, 196, 0.3); color: #4ab5c4; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; position: relative; z-index: 11;">
                                    <span>📍</span> Get Directions
                                </button>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="window.openEditModal('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; cursor: pointer; font-weight: 500; position: relative; z-index: 11;">Edit</button>
                                    <button onclick="window.deletePoint('${pt.id}')" style="flex: 1; padding: 10px; background: rgba(231, 76, 60, 0.1); border: 1px solid rgba(231, 76, 60, 0.3); color: #e74c3c; border-radius: 8px; cursor: pointer; font-weight: 500; position: relative; z-index: 11;">Remove</button>
                                </div>
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

        // --- EMERGENCY ALARM SYSTEM ---
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        let alarmInterval = null;
        let isAlarmPlaying = false;
        let pendingAlarmData = null;

        function playEmergencyAlarm(statusText, alertId = null, shouldSiren = true) {
            // 1. Visual Popup (SweetAlert2) - Independent of Siren
            if(window.Swal) {
                const currentAlertId = alertId || localStorage.getItem('adminLastAlertId');
                if (window.adminLastSwalId !== currentAlertId) {
                    window.adminLastSwalId = currentAlertId;
                    const isCrit = (statusText || '').toUpperCase().includes('CRITICAL');
                    Swal.fire({
                        title: `🚨 EMERGENCY ALERT!`,
                        text: statusText,
                        icon: isCrit ? 'error' : 'warning',
                        confirmButtonText: 'I UNDERSTAND',
                        background: '#1e2029',
                        color: '#fff',
                        confirmButtonColor: '#e74c3c',
                        backdrop: `rgba(231, 76, 60, 0.4)`
                    });
                }
            }

            // 2. Audible Siren Logic
            if (!shouldSiren || isAlarmPlaying) return;

            if (audioCtx.state === 'suspended') {
                console.warn("Audio Context Suspended. Alarm will start on next interaction.");
                pendingAlarmData = { message: statusText };
                // Still show UI banner
                const banner = document.getElementById('alarmBanner');
                if(banner) {
                    banner.style.display = 'flex';
                    document.getElementById('alarmStatus').textContent = statusText;
                }
                return;
            }

            isAlarmPlaying = true;
            pendingAlarmData = null;
            
            // Show UI
            const banner = document.getElementById('alarmBanner');
            if(banner) {
                banner.style.display = 'flex';
                document.getElementById('alarmStatus').textContent = statusText;
            }

            if (audioCtx.state === 'suspended') audioCtx.resume();

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

        function playSystemBeep() {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume(); 
            }
            
            const oscillator = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); 
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);

            oscillator.connect(gain);
            gain.connect(audioCtx.destination);

            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.5);
        }

        window.stopEmergencyAlarm = function() {
            isAlarmPlaying = false;
            pendingAlarmData = null; // Clear any pending data
            if(alarmInterval) clearInterval(alarmInterval);
            alarmInterval = null;
            
            const banner = document.getElementById('alarmBanner');
            if(banner) banner.style.display = 'none';

            // Mark highest seen alert ID as "silenced"
            const latestId = localStorage.getItem('adminLastAlertId');
            if(latestId) localStorage.setItem('adminSilencedAlertId', latestId);
        }

        window.fetchSystemAlerts = async function() {
            const signal = window.SyncManager.getSignal('alerts');

            const container = document.getElementById('alertsHistoryList');
            const dashboardContainer = document.getElementById('dashboardRecentAlerts');
            const countEl = document.getElementById('activeAlertCount');
            const badgeEl = document.getElementById('alertBadge');
            const filterEl = document.getElementById('alertsLocationFilter');
            if(!container && !dashboardContainer) return;

            try {
                const locQuery = filterEl && filterEl.value ? `&user_location=${encodeURIComponent(filterEl.value)}` : '';
                const res = await fetch('manage_alerts.php?action=fetch_all' + locQuery, { signal: signal });
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
                            localStorage.setItem('seenAlertCount', alerts.length);
                            badgeEl.style.display = 'none';
                        } else {
                            const newAlerts = alerts.length - lastSeenCount;
                            if (newAlerts > 0) {
                                badgeEl.innerText = newAlerts;
                                badgeEl.style.display = 'block';
                            } else {
                                badgeEl.style.display = 'none';
                            }
                        }
                        
                        // PLAY NOTIFICATION BEEP for any new alert (if siren not playing)
                        const latestAlert = alerts[0];
                        const lastBeepedId = parseInt(localStorage.getItem('adminLastBeepedId') || '0');
                        if (latestAlert && parseInt(latestAlert.id) > lastBeepedId) {
                            if (!isAlarmPlaying) playSystemBeep();
                            localStorage.setItem('adminLastBeepedId', latestAlert.id);
                        }
                    }

                    // 🚨 EMERGENCY ALARM TRIGGER (Scan all new alerts) 🚨
                    const lastSeenCountValue = parseInt(localStorage.getItem('seenAlertCount') || '0');
                    const silencedId = localStorage.getItem('adminSilencedAlertId');
                    let maxNewId = parseInt(localStorage.getItem('adminLastAlertId') || '0');
                    
                    alerts.slice(0, Math.max(0, alerts.length - lastSeenCountValue)).forEach(alert => {
                        const id = parseInt(alert.id);
                        const severityUpper = (alert.severity || '').toUpperCase();
                        if ((severityUpper === 'CRITICAL' || severityUpper === 'WARNING') && id > parseInt(silencedId || '0')) {
                            if (id > maxNewId) {
                                maxNewId = id;
                                localStorage.setItem('adminLastAlertId', id);
                            }
                            const shouldSiren = (alert.alert_type === 'IoT');
                            playEmergencyAlarm(alert.severity + ": " + alert.message, alert.id, shouldSiren);
                        }
                    });
                    
                    // Update UI Lists (Full History & Dashboard Summary)
                    if(container) {
                        let html = '';
                        alerts.forEach(alert => {
                            const borderCol = alert.severity === 'Critical' ? '#e74c3c' : (alert.severity === 'Warning' ? '#f1c40f' : '#4ab5c4');
                            html += `
                                <div style="background: rgba(255,255,255,0.02); border-left: 4px solid ${borderCol}; padding: 15px; margin-bottom: 15px; border-radius: 0 8px 8px 0; display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <strong style="color: ${borderCol};">${alert.severity} Alert</strong>
                                        <p style="font-size: 14px; margin-top: 5px; opacity: 0.8;">${alert.message}</p>
                                        <div style="font-size: 12px; opacity: 0.5; margin-top: 5px;">📍 ${alert.location || 'System Wide'} • ${new Date(alert.timestamp).toLocaleTimeString()}</div>
                                    </div>
                                    <button onclick="deleteAlert(${alert.id})" style="background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.4);" title="Delete Alert">×</button>
                                </div>`;
                        });
                        container.innerHTML = html || '<p style="text-align:center; opacity:0.5;">No active alerts.</p>';
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
                if (err.name !== 'AbortError') console.error("Alert Fetch Error:", err);
            } finally {
                // Recursive schedule: 30s
                if (!window.aquaSafeSyncRegistry.alerts?.signal.aborted) {
                    setTimeout(window.fetchSystemAlerts, 30000);
                }
            }
        };

        // 🚨 IoT INTELLIGENCE FEED LOGIC 🚨
        window.fetchIoTFeed = async function() {
            if (!window.adminSyncAborts) window.adminSyncAborts = {};
            if (window.adminSyncAborts.iq) window.adminSyncAborts.iq.abort();
            window.adminSyncAborts.iq = new AbortController();

            const container = document.getElementById('iotFeedTimeline');
            if (!container) return;

            const filterEl = document.getElementById('feedTypeFilter');
            const type = filterEl ? filterEl.value : 'all';

            try {
                const res = await fetch(`get_notifications.php?type=${encodeURIComponent(type)}&limit=50`, { signal: window.adminSyncAborts.iq.signal });
                const json = await res.json();
                
                if (json.status === 'success') {
                    const notifications = json.data || [];
                    
                    if (notifications.length === 0) {
                        container.innerHTML = `<p style="text-align: center; color: rgba(255,255,255,0.5); padding: 30px;">No events found in the intelligence feed.</p>`;
                        return;
                    }

                    let feedHtml = '';
                    notifications.forEach(notif => {
                        // Determine Icon & Color
                        let icon = 'ℹ️';
                        let colorClass = 'info';
                        let bgAlpha = '0.05';

                        if (notif.type === 'flood') icon = '🌊';
                        if (notif.type === 'device') icon = '🔌';
                        if (notif.type === 'emergency') {
                            icon = '🆘';
                            colorClass = '#e74c3c';
                            bgAlpha = '0.2';
                        } else {
                            switch (notif.severity) {
                                case 'safe': colorClass = '#2ecc71'; bgAlpha = '0.1'; break;
                                case 'warning': colorClass = '#f1c40f'; bgAlpha = '0.1'; break;
                                case 'danger': 
                                case 'critical': colorClass = '#e74c3c'; bgAlpha = '0.15'; break;
                                case 'info': colorClass = '#3498db'; bgAlpha = '0.1'; break;
                            }
                        }

                        let levelBadge = notif.water_level !== null ? 
                            `<span style="background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-left: auto;">${notif.water_level} ft</span>` : '';

                        feedHtml += `
                            <div style="background: rgba(${hexToRgb(colorClass)}, ${bgAlpha}); border-left: 4px solid ${colorClass}; margin-bottom: 12px; padding: 15px; border-radius: 8px; display: flex; align-items: flex-start; gap: 12px; animation: slideInRight 0.3s ease-out;">
                                <div style="font-size: 24px;">${icon}</div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                        <div style="font-weight: 600; font-size: 14px; text-transform: uppercase; color: ${colorClass}; letter-spacing: 0.5px;">${notif.location}</div>
                                        <div style="font-size: 12px; opacity: 0.6;">${notif.formatted_time}</div>
                                    </div>
                                    <div style="font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.4; display: flex; align-items: center;">
                                        ${notif.message}
                                        ${levelBadge}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    container.innerHTML = feedHtml;
                } else {
                    container.innerHTML = `<p style="text-align: center; color: #e74c3c; padding: 30px;">Failed to load feed: ${json.message}</p>`;
                }
            } catch (err) {
                console.error("Failed to fetch IoT feed:", err);
            }
        };

        // Helper to convert hex to RGB for rgba styling
        function hexToRgb(hex) {
            var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
            hex = hex.replace(shorthandRegex, function(m, r, g, b) {
                return r + r + g + g + b + b;
            });
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? 
                parseInt(result[1], 16) + ', ' + parseInt(result[2], 16) + ', ' + parseInt(result[3], 16) : '255, 255, 255';
        }

        // window.fetchSensorStatus consolidated into sensors section definition above

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

        // 3. Global Event Delegation for Role Buttons
        document.addEventListener('click', function(e) {
            if(e.target && e.target.classList.contains('role-update-btn')) {
                e.preventDefault();
                const uid = e.target.getAttribute('data-id');
                const role = e.target.getAttribute('data-role');
                if(uid && role) {
                    window.updateRole(uid, role);
                }
            }
        });

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
                
                map = L.map('leaflet-map').setView([9.5700, 77.0800], 11);
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
                const res = await fetch(`get_map_data.php?t=${Date.now()}`);
                const json = await res.json();

                if(json.status === 'success') {
                    const markerGroup = [];
                    const riskColors = { 'Safe': '#2ecc71', 'Warning': '#f1c40f', 'Danger': '#e74c3c' };
                    
                    // Track IDs present in this update to remove dead markers later
                    const currentIds = new Set();

                    // 1. Render Sensors
                    if(json.sensors) {
                        json.sensors.forEach(s => {
                            const color = riskColors[s.status] || '#2ecc71';
                            currentIds.add(s.id);
                            
                            const popupContent = `
                                <div style="font-family: 'Outfit', sans-serif; min-width: 160px; color: white;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                        <strong style="font-size:14px; color:#4ab5c4;">${s.id}</strong>
                                        <span style="background:${color}22; color:${color}; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; border: 1px solid ${color}44;">${s.status}</span>
                                    </div>
                                    <div style="font-size:13px; opacity:0.8; margin-bottom:10px;">
                                        📍 ${s.location}
                                    </div>
                                    <div style="font-size:18px; font-weight:700; color:#fff; margin-bottom:8px;">
                                        ${s.level} <span style="font-size:12px; font-weight:400; opacity:0.6;">ft depth</span>
                                    </div>
                                    <div style="font-size:11px; opacity:0.4; border-top:1px solid rgba(255,255,255,0.1); padding-top:6px; margin-top:4px;">
                                        Updated: ${s.updated}
                                    </div>
                                </div>
                            `;

                            if (markersObj[s.id]) {
                                // Update existing
                                markersObj[s.id].setLatLng([s.lat, s.lng]);
                                markersObj[s.id].setStyle({ color: color, fillColor: color, radius: s.status !== 'Safe' ? 14 : 10 });
                                markersObj[s.id].getPopup().setContent(popupContent);
                            } else {
                                // Create new
                                markersObj[s.id] = L.circleMarker([s.lat, s.lng], {
                                    color: color,
                                    fillColor: color,
                                    fillOpacity: 0.8,
                                    radius: s.status !== 'Safe' ? 14 : 10,
                                    weight: 2
                                }).addTo(map).bindPopup(popupContent);
                            }
                            markerGroup.push([s.lat, s.lng]);
                        });
                    }

                    if(json.emergency_signals) {
                        json.emergency_signals.forEach(sig => {
                            const isAck = sig.status === 'Acknowledged';
                            const sigId = 'emergency_' + sig.email;
                            currentIds.add(sigId);

                            const popupContent = `
                                <div style="font-family: 'Outfit', sans-serif; min-width: 200px; color: white; text-align:center;">
                                    <div style="font-size:30px; margin-bottom:10px;">${isAck ? '✅' : '🆘'}</div>
                                    <strong style="color:${isAck ? '#2ecc71' : '#ff4d4d'}; font-size:16px;">${isAck ? 'SOS ACKNOWLEDGED' : 'EMERGENCY SIGNAL'}</strong>
                                    <div style="font-size:13px; margin: 10px 0; opacity:0.8;">
                                        User: ${sig.email}<br>
                                        Sent: ${sig.time}
                                    </div>
                                    <div style="background:${isAck ? 'rgba(46, 204, 113, 0.1)' : 'rgba(255,77,77,0.1)'}; padding:10px; border-radius:8px; border:1px solid ${isAck ? 'rgba(46, 204, 113, 0.3)' : 'rgba(255,77,77,0.3)'}; font-size:11px; color:${isAck ? '#2ecc71' : '#ff4d4d'}; font-weight:700;">
                                        ${isAck ? 'HELP IS ON THE WAY' : 'DISPATCH RESCUE TEAM IMMEDIATELY'}
                                    </div>
                                    <div style="margin-top:10px; display:flex; gap:5px;">
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=${sig.lat},${sig.lng}" target="_blank" style="flex:1; background:#4ab5c4; color:white; padding:8px; border-radius:6px; text-decoration:none; font-size:11px; font-weight:700;">Navigate</a>
                                        ${!isAck ? `<button onclick="window.acknowledgeSOS('${sig.email}')" style="flex:1; background:#ff4d4d; color:white; padding:8px; border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer;">Acknowledge</button>` : ''}
                                    </div>
                                </div>
                            `;

                            if (markersObj[sigId]) {
                                markersObj[sigId].setLatLng([sig.lat, sig.lng]);
                                markersObj[sigId].setStyle({ color: isAck ? '#2ecc71' : '#ff0000', fillColor: isAck ? '#2ecc71' : '#ff0000' });
                                markersObj[sigId].getPopup().setContent(popupContent);
                            } else {
                                markersObj[sigId] = L.circleMarker([sig.lat, sig.lng], {
                                    color: isAck ? '#2ecc71' : '#ff0000',
                                    fillColor: isAck ? '#2ecc71' : '#ff0000',
                                    fillOpacity: 0.9,
                                    radius: 16,
                                    weight: 3,
                                    className: isAck ? '' : 'emergency-pulse-marker',
                                    interactive: true
                                }).addTo(map).bindPopup(popupContent, {
                                    closeButton: true,
                                    autoPan: true
                                });
                            }
                            markerGroup.push([sig.lat, sig.lng]);
                        });
                    }

                    // 3. Remove markers that are no longer in the response
                    for (let id in markersObj) {
                        if (!currentIds.has(id)) {
                            map.removeLayer(markersObj[id]);
                            delete markersObj[id];
                        }
                    }

                    // Only fit bounds if markers exist and it's the first load or map wasn't visible
                    // if (markerGroup.length > 0) map.fitBounds(L.latLngBounds(markerGroup), { padding: [50, 50] });
                }
            } catch (e) {
                console.log("Map Marker Refresh Error:", e);
            }
        };

        window.acknowledgeSOS = async function(email) {
            if(!confirm(`Acknowledge SOS from ${email} and notify user?`)) return;

            const formData = new FormData();
            formData.append('action', 'acknowledge');
            formData.append('email', email);

            try {
                const res = await fetch('manage_emergency_signals.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    window.showNotification("SOS Acknowledged!", 'success');
                    if(typeof refreshMapMarkers === 'function') refreshMapMarkers();
                } else {
                    alert("Error: " + json.message);
                }
            } catch(err) {
                console.error(err);
                alert("Failed to acknowledge emergency.");
            }
        };

        window.mapSetView = function(lat, lng, zoom = 7) {
            if(map) map.setView([lat, lng], zoom);
        };

        // 5. Reports Logic (Chart.js)
        let floodChart, alertChart;

        window.renderReportCharts = function() {
            const timeRange = document.getElementById('reportTimeRange').value;
            const ctx1 = document.getElementById('floodTrendChart');
            const ctx2 = document.getElementById('alertFreqChart');
            
            // --- 1. Dynamic Stats Simulation ---
            const stats = {
                '24h': { alerts: 14, floods: 3, recovery: '98%' },
                '7d': { alerts: 45, floods: 8, recovery: '95%' },
                '30d': { alerts: 128, floods: 21, recovery: '92%' }
            };
            
            const currentStats = stats[timeRange] || stats['24h']; // Safety fallback
            
            const elAlerts = document.getElementById('statTotalAlerts');
            const elFloods = document.getElementById('statFloodEvents');
            const elRecovery = document.getElementById('statSafeRecoveries');
            
            if(elAlerts) elAlerts.innerHTML = currentStats.alerts;
            if(elFloods) elFloods.innerHTML = currentStats.floods;
            if(elRecovery) elRecovery.innerHTML = currentStats.recovery;

            // --- 2. Chart Re-rendering (Existing Logic) ---
            if(!ctx1 || !ctx2) return;

            if(floodChart) floodChart.destroy();
            if(alertChart) alertChart.destroy();

            // Simulate Data based on Time Range
            let labels, dataPoints;
            if(timeRange === '24h') {
                labels = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '23:59'];
                dataPoints = [35, 38, 45, 50, 48, 42, 40];
            } else if(timeRange === '7d') {
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                dataPoints = [40, 42, 55, 60, 58, 50, 45];
            } else {
                labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                dataPoints = [30, 45, 65, 40];
            }

            floodChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Avg Water Level (ft)',
                        data: dataPoints,
                        borderColor: '#4ab5c4',
                        backgroundColor: 'rgba(74, 181, 196, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa' } },
                        x: { grid: { display: false }, ticks: { color: '#aaa' } }
                    }
                }
            });

            alertChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Alert Frequency',
                        data: dataPoints.map(d => Math.floor(d / 10)), // Mock data derivation
                        backgroundColor: '#e74c3c',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa' } },
                        x: { grid: { display: false }, ticks: { color: '#aaa' } }
                    }
                }
            });
        };
        window.renderReportCharts = async function() {
            const ctx1 = document.getElementById('floodTrendChart');
            const ctx2 = document.getElementById('alertFreqChart');
            if (!ctx1 || !ctx2) return;

            const range = document.getElementById('reportTimeRange')?.value || '24h';
            const area = document.getElementById('reportAreaSelector')?.value || 'All';
            
            try {
                // 1. Fetch Summary Stats & Event Logs
                const statsRes = await fetch(`get_report_stats.php?range=${range}&area=${area}`);
                const statsJson = await statsRes.json();
                
                if (statsJson.status === 'success') {
                    const s = statsJson.stats;
                    if(document.getElementById('statTotalAlerts')) {
                        document.getElementById('statTotalAlerts').innerText = s.total_alerts;
                        document.getElementById('statFloodEvents').innerText = s.flood_events;
                        document.getElementById('statSafeRecoveries').innerText = s.safe_recovery;
                    }

                    // Update Event Log Table
                    const eventBody = document.getElementById('eventLogBody');
                    if (eventBody) {
                        eventBody.innerHTML = statsJson.logs.length > 0 ? 
                            statsJson.logs.map(e => `
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 12px; font-size: 13px; opacity: 0.8;">${e.time}</td>
                                    <td style="padding: 12px; font-weight: 500;">${e.event}</td>
                                    <td style="padding: 12px; font-size: 13px;">📍 ${e.location}</td>
                                    <td style="padding: 12px;"><span class="${e.severity === 'CRITICAL' ? 'danger-text' : (e.severity === 'WARNING' ? 'warning-text' : 'safe-text')}" style="padding: 4px 8px; background: rgba(255,255,255,0.05); border-radius: 4px; font-size: 11px; font-weight: 600;">${e.severity}</span></td>
                                    <td style="padding: 12px;"><span style="color: #4ab5c4; font-size: 12px;">● ${e.status}</span></td>
                                </tr>
                            `).join('') : 
                            '<tr><td colspan="5" style="padding: 40px; text-align: center; opacity: 0.5;">No events found for this period.</td></tr>';
                    }
                }

                // 2. Fetch Historical Trends (Charts)
                const trendRes = await fetch(`get_historical_data.php?range=${range}&area=${area}`);
                const trendJson = await trendRes.json();

                if (trendJson.status === 'success') {
                    if (floodChart) floodChart.destroy();
                    if (alertChart) alertChart.destroy();

                    const labels = trendJson.labels;
                    const waterLevels = trendJson.waterLevels;
                    const alertCounts = trendJson.alertCounts;

                    // Chart Title Update
                    const chartTitle = document.querySelector('#reports h3');
                    if(chartTitle) chartTitle.innerText = `📊 Water Level Trends (${area === 'All' ? 'System Average' : area})`;

                    floodChart = new Chart(ctx1, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Water Level (ft)',
                                data: waterLevels,
                                borderColor: '#4ab5c4',
                                backgroundGradient: true,
                                backgroundColor: 'rgba(74, 181, 196, 0.1)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointBackgroundColor: '#4ab5c4'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa' }, beginAtZero: true },
                                x: { grid: { display: false }, ticks: { color: '#aaa' } }
                            }
                        }
                    });

                    alertChart = new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'System Alerts',
                                data: alertCounts,
                                backgroundColor: 'rgba(231, 76, 60, 0.4)',
                                borderColor: '#e74c3c',
                                borderWidth: 1,
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#aaa', stepSize: 1 }, beginAtZero: true },
                                x: { grid: { display: false }, ticks: { color: '#aaa' } }
                            }
                        }
                    });
                }
            } catch (err) {
                console.error("Report Fetch Error:", err);
            }
        };

        // REMOVED DUPLICATE/DEAD LOGIC


        // REMOVED LOGIC - MOVED TO TOP OF SCRIPT (Global Definition)


        // REMOVED DUPLICATE LOGIC - NOW CONSOLIDATED ABOVE


        window.viewMetricDetails = function(metric) {
            const area = document.getElementById('reportAreaSelector') ? document.getElementById('reportAreaSelector').value : 'All';
            const range = document.getElementById('reportTimeRange') ? document.getElementById('reportTimeRange').value : '24h';

            if(metric === 'alerts') {
                switchTab('alerts');
                // Simulate "Drill Down" by injecting historical data
                const container = document.querySelector('#alerts .card > div');
                if(container) {
                    const isSpecificArea = area !== 'All';
                    const areaTitle = isSpecificArea ? area : 'System Wide';
                    
                    // Filter counts based on area
                    let count = 14;
                    if(area === 'South Reservoir') count = 9;
                    if(area === 'Central City') count = 2;
                    if(range === '7d') count = Math.round(count * 3.5);

                    container.innerHTML = `
                        <div style="background:rgba(74,181,196,0.1); border:1px solid #4ab5c4; color:#4ab5c4; padding:10px; border-radius:8px; margin-bottom:15px; font-size:13px; display:flex; align-items:center; justify-content:space-between;">
                            <span>📊 Viewing <strong>${areaTitle} Log (${range})</strong> (${count} Events)</span>
                            <button onclick="fetchSystemAlerts()" style="background:none; border:none; color:white; opacity:0.7; cursor:pointer; text-decoration:underline;">Switch to Live View</button>
                        </div>
                    `;
                    // Generate mock alerts
                    let html = '';
                    const types = ['Sensor Disconnect', 'Water Level Warning', 'Pump Failure', 'Network Latency'];
                    let locs = ['South Reservoir', 'North Dam', 'River A-2', 'Canal Zone'];
                    
                    // If specific area, force all locs to match
                    if(isSpecificArea) locs = [area, area, area, area];
                    
                    for(let i=0; i<count; i++) {
                        const isCrit = i < (isSpecificArea && area==='South Reservoir' ? 5 : 2); 
                        const severity = isCrit ? 'Critical' : 'Warning';
                        const color = isCrit ? '#e74c3c' : '#f1c40f';
                        const time = new Date(Date.now() - i * 3600000).toLocaleTimeString();
                        
                        html += `
                             <div style="background: ${color}15; border-left: 4px solid ${color}; padding: 15px; margin-bottom: 10px; border-radius: 0 8px 8px 0; display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <strong style="color: ${color};">${severity} Alert</strong>
                                    <p style="font-size: 14px; margin-top: 5px; opacity: 0.8;">${types[i%4]} detected at ${locs[i%4]}</p>
                                    <div style="font-size: 12px; opacity: 0.5; margin-top: 5px;">${time} • Historical Record</div>
                                </div>
                            </div>
                        `;
                    }
                    container.innerHTML += html;
                }
            } 
            else if(metric === 'floods') {
                switchTab('map');
                setTimeout(() => {
                    const msg = area !== 'All' ? `🚨 ${area}: Active Flood Event` : "🚨 Displaying 3 Active Flood Events";
                    // Zoom logic...
                    if(map) map.setView([10.8505, 76.2711], 10);
                    // Show Overlay
                    const overlay = document.createElement('div');
                    overlay.style.cssText = "position:absolute; top:80px; left:50%; transform:translateX(-50%); background:rgba(231,76,60,0.9); color:white; padding:10px 20px; border-radius:30px; z-index:1000; font-weight:600; box-shadow:0 5px 20px rgba(0,0,0,0.3); pointer-events:none; animation: fadeInDown 0.5s;";
                    overlay.innerHTML = msg;
                    document.getElementById('leaflet-map').appendChild(overlay);
                    setTimeout(() => overlay.remove(), 4000);
                }, 500);
            }
            else if(metric === 'recovery') {
                switchTab('evacuation');
                setTimeout(() => {
                    // Filter for Available
                    const select = document.getElementById('evacFilter');
                    const search = document.getElementById('evacSearch');
                    
                    if(select) { select.value = 'Available'; }
                    if(search && area !== 'All') { search.value = area; } // Pre-fill search with area
                    
                    if(typeof filterEvacuation === 'function') filterEvacuation();
                    
                    // Show Stats Banner
                    const list = document.getElementById('evacuationList');
                    if(list) {
                        const banner = document.createElement('div');
                        banner.style.cssText = "grid-column: 1 / -1; background:rgba(46, 204, 113, 0.15); border:1px solid #2ecc71; color:#2ecc71; padding:15px; border-radius:12px; margin-bottom:10px; text-align:center; font-weight:600;";
                        banner.innerHTML = "✅ Safety Status: 98% of Evacuation Capacity is Open & Available";
                        list.prepend(banner);
                    }
                }, 300);
            }
        };

         // 7. Clock & Utilities
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            const clockEl = document.getElementById('clock');
            if(clockEl) clockEl.innerText = timeString;

            const sClock = document.getElementById('settingsClock');
            if(sClock) sClock.innerText = timeString;
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
                        // Skip IoT-live areas (these are updated via monitorFloodAlerts)
                        if(['Churakullam', 'Kakkikavala', 'Nellimala'].includes(area)) continue;

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


        // --- IQ INTELLIGENCE CENTER LOGIC (Refactored) ---
        window.fetchIQSettings = async function() {
            try {
                const res = await fetch('get_notification_settings.php');
                const json = await res.json();
                if (json.status === 'success') {
                    const data = json.data;
                    // Update Sliders
                    const safeSlider = document.getElementById('thresholdSafeSlider');
                    const warnSlider = document.getElementById('thresholdWarningSlider');
                    if (safeSlider) {
                        safeSlider.value = data.threshold_safe_max;
                        document.getElementById('thresholdSafeVal').innerText = parseFloat(data.threshold_safe_max).toFixed(2) + ' ft';
                    }
                    if (warnSlider) {
                        warnSlider.value = data.threshold_warning_max;
                        document.getElementById('thresholdWarningVal').innerText = parseFloat(data.threshold_warning_max).toFixed(2) + ' ft';
                    }
                    // Update Channels
                    if (document.getElementById('chan_sms')) document.getElementById('chan_sms').checked = data.channel_sms == '1';
                    if (document.getElementById('chan_email')) document.getElementById('chan_email').checked = data.channel_email == '1';
                    if (document.getElementById('chan_push')) document.getElementById('chan_push').checked = data.channel_push == '1';
                    if (document.getElementById('chan_siren')) document.getElementById('chan_siren').checked = data.channel_siren == '1';
                }
            } catch (err) { console.error("IQ Settings Fetch Error:", err); }
        };

        window.saveIQSettings = async function() {
            const formData = new FormData();
            formData.append('threshold_safe_max', document.getElementById('thresholdSafeSlider').value);
            formData.append('threshold_warning_max', document.getElementById('thresholdWarningSlider').value);
            formData.append('channel_sms', document.getElementById('chan_sms').checked ? '1' : '0');
            formData.append('channel_email', document.getElementById('chan_email').checked ? '1' : '0');
            formData.append('channel_push', document.getElementById('chan_push').checked ? '1' : '0');
            formData.append('channel_siren', document.getElementById('chan_siren').checked ? '1' : '0');

            try {
                const res = await fetch('save_settings.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.status === 'success') {
                    console.log("[IQ Center] Settings persisted.");
                    // Optional: show a small toast if it was a manual change
                }
            } catch (err) { console.error("IQ Settings Save Error:", err); }
        };

        window.fetchIQFeed = async function() {
            if (!window.adminSyncAborts) window.adminSyncAborts = {};
            if (window.adminSyncAborts.iq) window.adminSyncAborts.iq.abort();
            window.adminSyncAborts.iq = new AbortController();

            try {
                const res = await fetch('get_notification_feed.php?limit=20', { signal: window.adminSyncAborts.iq.signal });
                const json = await res.json();
                const container = document.getElementById('iqFeedTimeline');
                if (!container) return;

                if (json.status === 'success' && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(item => {
                        const sevClass = item.severity.toLowerCase();
                        const icon = sevClass === 'critical' ? 'alert-triangle' : (sevClass === 'warning' ? 'bell' : 'shield-check');
                        const color = sevClass === 'critical' ? '#e74c3c' : (sevClass === 'warning' ? '#f1c40f' : '#2ecc71');
                        
                        html += `
                            <div class="iq-event-card" style="background: rgba(255,255,255,0.03); border-left: 4px solid ${color}; padding: 15px; border-radius: 0 12px 12px 0; display: flex; gap: 15px; align-items: start; transition: transform 0.2s;">
                                <div style="margin-top: 3px; color: ${color};">
                                    <i data-lucide="${icon}" style="width: 18px;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <span style="font-size: 11px; font-weight: 700; color: ${color}; text-transform: uppercase; letter-spacing: 0.5px;">${item.severity}</span>
                                        <span style="font-size: 11px; opacity: 0.4;">${new Date(item.timestamp.replace(' ', 'T')).toLocaleTimeString()}</span>
                                    </div>
                                    <h4 style="font-size: 14px; margin-bottom: 2px; color: #fff;">📍 ${item.location}</h4>
                                    <p style="font-size: 13px; opacity: 0.7; line-height: 1.4;">${item.message}</p>
                                    <div style="margin-top: 8px; font-size: 12px; font-weight: 600; color: #4ab5c4;">
                                        🌊 Level: ${parseFloat(item.water_level).toFixed(2)} ft
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    if (window.lucide) lucide.createIcons();
                } else if (json.status === 'success') {
                    container.innerHTML = `<div style="text-align:center; padding:40px; opacity:0.3;">No recent intelligence events.</div>`;
                }
            } catch (err) { console.error("IQ Feed Fetch Error:", err); }
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
                    formData.append('action', 'broadcast_alert'); // Changed from 'broadcast' to match manage_community.php
                    formData.append('message', message);
                    formData.append('severity', severity);
                    formData.append('area', area); // Changed from 'location' to match manage_community.php
                    
                    try {
                        const res = await fetch('manage_community.php', { method: 'POST', body: formData });
                        const json = await res.json();
                        // manage_community returns 'success': boolean
                        if(json.success || json.status === 'success') {
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

        // Admin Intelligence Center & Helpers
        fetchIQSettings();
        
        // --- ADMIN DASHBOARD SYNC ENGINE (CENTRALIZED) ---
        if (!window.aquaSafeSyncActive) {
            window.aquaSafeSyncActive = true;
            if (!window.aquaSafeSyncRegistry) window.aquaSafeSyncRegistry = {};

            // Staggered Boot Sequence
            (function runStaggered() {
                if (window.aquaSafeSyncBooted) return;
                window.aquaSafeSyncBooted = true;

                console.log("[SyncManager] Initializing staggered boot sequence...");
                
                // 1. Alerts (Critical) - 1.0s delay
                setTimeout(() => fetchSystemAlerts(), 1000);
                
                // 2. Flood Data (IoT) - 3.0s delay
                setTimeout(() => monitorFloodAlerts(), 3000);
                
                // 3. Stats & Sensors - 5.0s delay
                setTimeout(() => {
                    pollDashboardData();
                    fetchSensorStatus();
                }, 5000);

                // 4. Intelligence & Support - 8.0s delay
                setTimeout(() => {
                    if (typeof fetchIoTFeed === 'function') fetchIoTFeed();
                    fetchHelpdeskRequests();
                }, 8000);
            })();
        }

        // Global Update Time (UI only)
        updateTime();
        setInterval(updateTime, 1000);

        // Global manual refresh
        window.refreshAdminDashboard = function() {
            console.log("[SyncManager] Manual refresh triggered.");
            // Abort all in registry
            if (window.SyncManager) {
                Object.keys(window.SyncManager.controllers || {}).forEach(key => window.SyncManager.abort(key));
            }
            
            // Immediate re-triggers (Recursive chains will reset)
            if (typeof fetchSystemAlerts === 'function') fetchSystemAlerts();
            if (typeof monitorFloodAlerts === 'function') setTimeout(monitorFloodAlerts, 200);
            if (typeof pollDashboardData === 'function') setTimeout(pollDashboardData, 400);
            if (typeof fetchSensorStatus === 'function') setTimeout(fetchSensorStatus, 600);
            if (typeof fetchHelpdeskRequests === 'function') setTimeout(fetchHelpdeskRequests, 800);
            if (typeof fetchIoTFeed === 'function') setTimeout(fetchIoTFeed, 1000);
        };

        // Global Export Diagnostic
        console.log("[AquaSafe] Export Definitions Check:", {
            csv: typeof window.aquaSafeExportCSV,
            pdf: typeof window.aquaSafeDownloadPDF
        });
        
        log("AquaSafe Admin System: LOADED SUCCESSFULLY!");
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

    <script>
        // --- SETTINGS LOGIC ---
        window.saveSettings = async function() {
            const email = document.getElementById('adminEmail').value;
            const refresh = document.getElementById('refreshRate').value;
            const btn = document.getElementById('btnSaveSettings');
            
            if(!email || !refresh) {
                window.showNotification("Please fill all fields.", 'warning');
                return;
            }

            const originalText = btn.innerText;
            btn.innerText = "Saving...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('email', email);
            formData.append('refresh', refresh);

            try {
                const res = await fetch('manage_settings.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.success) {
                    window.showNotification(json.message, 'success');
                    if (typeof window.startPolling === 'function') window.startPolling(parseInt(refresh));
                } else {
                    window.showNotification("Error: " + json.message, 'error');
                }
            } catch(e) {
                console.error(e);
                window.showNotification("Failed to save settings.", 'error');
            }
            btn.innerText = originalText;
            btn.disabled = false;
        };

        window.fetchSettings = async function() {
            try {
                const res = await fetch('manage_settings.php');
                const json = await res.json();
                if(json.success) {
                    const s = json.data;
                    if(document.getElementById('adminEmail')) document.getElementById('adminEmail').value = s.admin_email || '';
                    if(document.getElementById('refreshRate')) {
                        const rate = s.refresh_rate || '30';
                        document.getElementById('refreshRate').value = rate;
                        if (typeof window.startPolling === 'function') window.startPolling(parseInt(rate));
                    }
                }
            } catch(e) { console.error("Fetch Settings Error", e); }
        };
        
        // Initialize
        fetchSettings();

        // --- IOT FLOOD MONITOR — Live Chart Update ---
        let lastIoTAlertId = 0;
        let lastSeenEmergencyId = 0;

        window.monitorFloodAlerts = async function() {
            const signal = window.SyncManager.getSignal('flood');
            
            try {
                const res  = await fetch('get_flood_data.php?_t=' + Date.now(), { signal: signal });
                const json = await res.json();

                if (json.status !== 'success') return;

                const history = json.history || [];
                const latest  = json.latest;
                if (!latest) return;
                // ... rest of monitorFloodAlerts logic ...
                const latestId = parseInt(latest.id);

                if (history.length > 0) {
                    const labels = history.map(r => {
                        const ts = r.timestamp || r.created_at.replace(' ', 'T');
                        const d = new Date(ts.includes('T') ? ts : ts.replace(' ', 'T'));
                        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    });
                    const dataPoints = history.map(r => parseFloat(r.level));
                    const pointColors = history.map(r =>
                        r.status === 'CRITICAL' ? '#e74c3c' :
                        r.status === 'WARNING'  ? '#f1c40f' : '#2ecc71'
                    );

                    if (typeof floodChart !== 'undefined' && floodChart) {
                        floodChart.data.labels = labels;
                        floodChart.data.datasets[0].data = dataPoints;
                        floodChart.data.datasets[0].pointBackgroundColor = pointColors;
                        floodChart.update('none');
                    }
                }

                if (latestId > lastIoTAlertId) {
                    if (lastIoTAlertId !== 0 && latest.status === 'CRITICAL') {
                        window.showNotification('🚨 CRITICAL FLOOD ALERT: ' + latest.level + ' ft', 'error');
                    }
                    lastIoTAlertId = latestId;
                }
            } catch(e) {
                if (e.name !== 'AbortError') console.warn('IoT Poll Error:', e);
            } finally {
                // Recursive schedule: 15s
                if (!window.aquaSafeSyncRegistry.flood?.signal.aborted) {
                    setTimeout(monitorFloodAlerts, 15000);
                }
            }
        }

        // Initialize Lucide Icons on Load
        if(window.lucide) lucide.createIcons();

        // Start Polling (Managed by AdminSyncEngine)
        // monitorFloodAlerts();
        // setInterval(monitorFloodAlerts, 5000);


        // --- CENSUS UPLOAD LOGIC ---
        window.openCensusModal = function() {
            const modal = document.getElementById('censusModal');
            if(modal) {
                modal.style.display = 'flex';
                document.getElementById('censusFile').value = ''; // Reset
            } else {
                alert("Error: Modal not found");
            }
        };

        window.uploadCensusData = async function() {
            const fileInput = document.getElementById('censusFile');
            const file = fileInput.files[0];
            const btn = document.getElementById('btnUploadCensus');

            if(!file) {
                window.showNotification("Please select a CSV file first.", 'warning');
                return;
            }

            const originalText = btn.innerText;
            btn.innerText = "⏳ Processing...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'upload_csv');
            formData.append('censusFile', file);

            try {
                const res = await fetch('manage_community.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.success) {
                    let msg = `Processed: ${json.data.processed}\nNew: ${json.data.new_contacts}\nExisting: ${json.data.existing_users}\nErrors: ${json.data.errors}`;
                    window.showNotification("Upload Successful!", 'success');
                    alert(msg); // Detailed stats
                    document.getElementById('censusModal').style.display = 'none';
                    // Optional: Refresh user stats
                    if(document.getElementById('users').classList.contains('active')) location.reload(); 
                } else {
                    window.showNotification("Upload Failed: " + json.message, 'error');
                }
            } catch(e) {
                console.error("Upload Error:", e);
                window.showNotification("Network Error during upload.", 'error');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        };

        // --- SENSOR MANAGEMENT ---
        window.openSensorModal = function() {
            document.getElementById('sensorModal').style.display = 'flex';
            document.getElementById('sensorForm').reset();
        };

        window.saveSensor = async function(e) {
            e.preventDefault();
            const sid = document.getElementById('newSensorId').value.trim();
            const sloc = document.getElementById('newSensorLoc').value.trim();
            
            if(!sid || !sloc) {
                alert("Please fill all fields.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('sensor_id', sid);
            formData.append('location_name', sloc);

            try {
                const res = await fetch('manage_sensors.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.status === 'success') {
                    window.showNotification("Sensor Added Successfully!", 'success');
                    document.getElementById('sensorModal').style.display = 'none';
                    if(typeof fetchSensorStatus === 'function') fetchSensorStatus(); 
                } else {
                    alert("Error: " + json.message);
                }
            } catch(err) {
                console.error(err);
                alert("Failed to add sensor.");
            }
        };

    </script>


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

    <!-- Upload Census Modal -->
    <div id="censusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:1px solid #4ab5c4; padding:30px; border-radius:16px; width:90%; max-width:500px; box-shadow:0 0 30px rgba(74,181,196,0.3); animation:fadeInUp 0.3s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="color:#4ab5c4; margin:0; display:flex; align-items:center; gap:10px;"><i data-lucide="upload-cloud"></i> Upload Census Data</h3>
                <button onclick="document.getElementById('censusModal').style.display='none'" style="background:none; border:none; color:white; font-size:20px; cursor:pointer;">&times;</button>
            </div>
            
            <p style="color:rgba(255,255,255,0.7); font-size:14px; margin-bottom:20px; line-height:1.5;">
                Upload a CSV file containing resident details (Address, Lat/Lon, Demographics). The system will automatically migrate the database and update records.
            </p>

            <a href="manage_community.php?action=export_csv" style="display:block; margin-bottom:25px; padding:15px; background:rgba(74, 181, 196, 0.1); border:1px dashed #4ab5c4; border-radius:8px; text-decoration:none; color:#4ab5c4; font-size:13px; text-align:center; transition:all 0.3s;">
                <i data-lucide="download" style="vertical-align:middle; margin-right:5px;"></i> Download Extended CSV Template
            </a>

            <div style="margin-bottom:25px;">
                <label for="censusFile" style="display:block; margin-bottom:10px; font-size:14px;">Select CSV File</label>
                <input type="file" id="censusFile" accept=".csv" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white;">
            </div>

            <button onclick="uploadCensusData()" id="btnUploadCensus" style="width:100%; padding:12px; background:linear-gradient(135deg, #4ab5c4 0%, #2980b9 100%); border:none; border-radius:8px; color:white; font-weight:700; cursor:pointer; font-size:15px; box-shadow:0 4px 15px rgba(74, 181, 196, 0.4);">
                Start Upload
            </button>
        </div>
    </div>

    <!-- Add Sensor Modal -->
    <div id="sensorModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
        <div style="background:#0f2027; border:1px solid #4ab5c4; padding:30px; border-radius:16px; width:90%; max-width:450px; box-shadow:0 0 30px rgba(74,181,196,0.3); animation:fadeInUp 0.3s ease;">
            <h3 style="color:#4ab5c4; margin:0 0 20px 0;">📡 Add New IoT Sensor</h3>
            
            <form id="sensorForm" onsubmit="window.saveSensor(event)">
                <div class="form-group">
                    <label>Sensor ID (Unique Hardware ID)</label>
                    <input type="text" id="newSensorId" placeholder="e.g., SENS-005" required>
                </div>
                <div class="form-group">
                    <label>Location Name</label>
                    <input type="text" id="newSensorLoc" placeholder="e.g., East Canal" required>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" onclick="document.getElementById('sensorModal').style.display='none'" style="padding:10px 20px; background:transparent; border:1px solid rgba(255,255,255,0.2); color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:#4ab5c4; border:none; color:#032023; font-weight:700; border-radius:8px; cursor:pointer;">Add Sensor</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>```
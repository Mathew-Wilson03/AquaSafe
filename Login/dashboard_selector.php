<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["name"] ?? "User");
$email = htmlspecialchars($_SESSION["email"] ?? "");
$user_role = htmlspecialchars($_SESSION["user_role"] ?? "user");
$role_lower = strtolower(trim((string)$user_role));
$is_admin = in_array($role_lower, ['administrator', 'admin', 'superadmin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Selector - AquaSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            min-height: 100vh;
        }
        .selector-wrapper {
            width: 100%;
            max-width: 800px;
            padding: 20px;
        }
        .selector-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .brand p {
            color: #888;
            font-size: 13px;
        }
        .welcome {
            text-align: center;
            margin-bottom: 30px;
        }
        .welcome h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .welcome p {
            color: #666;
        }
        .dashboards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .dashboard-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        .dashboard-card:hover:not(.disabled) {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
        .dashboard-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f8f8;
        }
        .dashboard-card.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: #e0e0e0;
        }
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .admin-card .card-icon {
            color: #f77f00;
        }
        .user-card .card-icon {
            color: #667eea;
        }
        .dashboard-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .session-info {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: left;
        }
        .session-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .session-info strong {
            color: #333;
        }
        .access-status {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
        }
        .access-granted {
            background: #d4edda;
            color: #155724;
        }
        .access-denied {
            background: #f8d7da;
            color: #721c24;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .selector-container {
                padding: 30px 20px;
            }
            .dashboards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="selector-wrapper">
        <div class="selector-container">
            <div class="brand">
                <h1>AquaSafe</h1>
                <p>Water monitoring & safety dashboard</p>
            </div>

            <div class="welcome">
                <h2>Welcome back, <?php echo $name; ?>!</h2>
                <p>Please select your dashboard below</p>
            </div>

            <div class="dashboards-grid">
                <div class="dashboard-card admin-card <?php echo $is_admin ? '' : 'disabled'; ?>" <?php echo $is_admin ? 'onclick="window.location.href=\'admin_dashboard.php\'"' : ''; ?>>
                    <div class="card-icon">🔐</div>
                    <h3>Admin Dashboard</h3>
                    <div class="session-info">
                        <p><strong>Name:</strong> <?php echo $name; ?></p>
                        <p><strong>Email:</strong> <?php echo $email; ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst($user_role); ?></p>
                        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    </div>
                    <div class="access-status <?php echo $is_admin ? 'access-granted' : 'access-denied'; ?>">
                        <?php echo $is_admin ? 'Access Granted' : 'Access Denied'; ?>
                    </div>
                </div>

                <div class="dashboard-card user-card" onclick="window.location.href='user_dashboard.php'">
                    <div class="card-icon">👤</div>
                    <h3>User Dashboard</h3>
                    <div class="session-info">
                        <p><strong>Name:</strong> <?php echo $name; ?></p>
                        <p><strong>Email:</strong> <?php echo $email; ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst($user_role); ?></p>
                        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    </div>
                    <div class="access-status access-granted">Access Granted</div>
                </div>
            </div>

            <div class="footer">
                <a href="logout.php">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>

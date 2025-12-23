<?php
// google_signin_landing.php
// Landing page shown when Google sign-in button is clicked
session_start();
require_once 'config.php';

// Check if id_token is provided
if (!isset($_POST['id_token']) || empty($_POST['id_token'])) {
    header('Location: login.php');
    exit;
}

$id_token = $_POST['id_token'];
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing in with Google - AquaSafe</title>
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
        .landing-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .landing-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .brand {
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
        .loading {
            margin: 20px 0;
        }
        .loading p {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 480px) {
            .landing-container {
                padding: 30px 20px;
            }
            .brand h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="landing-wrapper">
        <div class="landing-container">
            <div class="brand">
                <h1>AquaSafe</h1>
                <p>Water monitoring & safety dashboard</p>
            </div>
            <div class="loading">
                <p>Signing in with Google...</p>
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <form id="googleForm" method="POST" action="google_login_process.php" style="display: none;">
        <input type="hidden" name="id_token" value="<?php echo htmlspecialchars($id_token); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
    </form>

    <script>
        // Automatically submit the form after a short delay to show the landing page
        setTimeout(function() {
            document.getElementById('googleForm').submit();
        }, 2000); // 2 seconds delay
    </script>
</body>
</html>

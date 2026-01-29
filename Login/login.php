<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSafe Login</title>
    <?php require_once 'config.php'; ?>
    <meta name="google-signin-client_id" content="<?php echo GOOGLE_CLIENT_ID; ?>">
    <style>
        :root {
            --primary: #4ab5c4;
            --primary-dark: #3a97a5;
            --secondary: #2c3e50;
            --glass: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.2);
            --text-light: #f5f7fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #001f3f; /* Deep Navy to match Vanta */
            min-height: 100vh;
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 20px 0;
        }

        /* Login Card */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px; /* Increased width */
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px; /* Reduced padding */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 25px; /* Reduced margin */
        }

        .header-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 18px;
            font-weight: 700;
        }

        .header-logo img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .header-title {
            font-size: 24px;
            font-weight: 300;
            color: white;
            letter-spacing: 0.5px;
        }

        .role-badge {
            display: inline-block;
            background: rgba(74, 181, 196, 0.15);
            color: #4ab5c4;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 600;
            border: 1px solid rgba(74, 181, 196, 0.3);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
        }

        input[type="email"], 
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px; /* Matches Google button */
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 14px;
            color: white;
            transition: all 0.3s ease;
            height: 46px; /* Explicit height to match */
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 181, 196, 0.15);
        }

        .submit-btn {
            width: 100%;
            height: 46px; /* Match input height */
            background: linear-gradient(135deg, #4ab5c4 0%, #3a97a5 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(58, 151, 165, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(58, 151, 165, 0.4);
            filter: brightness(1.1);
        }

        /* Divider */
        .divider-wrapper {
            display: flex;
            align-items: center;
            margin: 20px 0;
            gap: 12px;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider-text {
            color: rgba(255, 255, 255, 0.3);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Google Button */
        .google-btn-custom {
            width: 100%;
            height: 46px; /* Match input height */
            padding: 0 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.95);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .google-btn-custom:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .google-btn-custom img {
            width: 18px; /* Slightly smaller icon */
            height: 18px;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 13px;
            margin: 0 8px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 20;
        }

        .back-btn:hover {
            color: white;
            transform: translateX(-5px);
        }
        /* Responsive Adjustments */
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 15px;
            }
            
            .login-container {
                padding: 25px 20px;
                border-radius: 15px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .header-logo {
                font-size: 16px;
            }

            .back-btn {
                top: 20px;
                left: 20px;
                font-size: 14px;
            }
            
            .submit-btn, .google-btn-custom, input {
                height: 44px;
                font-size: 14px;
            }

            .footer-links {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .footer-links span {
                display: none;
            }
        }
    </style>

    <script src="https://accounts.google.com/gsi/client" async defer onload="gsiLoaded()"></script>
    <!-- SweetAlert2 Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- VANTA 3D BACKGROUND -->
    <div id="vanta-bg" style="position:fixed; width:100%; height:100%; top:0; left:0; z-index:-1;"></div>

    <!-- THREE.JS & VANTA.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanta/0.5.24/vanta.waves.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            VANTA.WAVES({
                el: "#vanta-bg",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 1.00,
                color: 0x112240,       /* Deep Navy */
                shininess: 35.00,      /* Glossy Water */
                waveHeight: 20.00,     /* Visible Swell */
                waveSpeed: 0.75,       /* Gentle Motion */
                zoom: 0.85             /* Showing more waves */
            })
        });
    </script>

    <?php
    // Fetch Public Alerts Logic
    $alert_js = "";
    if(isset($link)){
        // Modified query: Fetch top 5 recent alerts
        $sql_alert = "SELECT * FROM sensor_alerts ORDER BY timestamp DESC LIMIT 5";
        $res_alert = mysqli_query($link, $sql_alert);
        
        if($res_alert && mysqli_num_rows($res_alert) > 0){
            $alerts_html = '<div style="text-align:left; max-height:300px; overflow-y:auto; padding-right:5px;">';
            
            while($row = mysqli_fetch_assoc($res_alert)){
                $sev = $row['severity'];
                $msg = htmlspecialchars($row['message']);
                $loc = htmlspecialchars($row['location']);
                $time = date('H:i', strtotime($row['timestamp']));
                
                // Color coding
                $border = '#4ab5c4';
                $bg = 'rgba(74, 181, 196, 0.1)';
                $icon_char = 'ℹ️';
                
                if(stripos($sev, 'Critical') !== false) { 
                    $border = '#e74c3c'; 
                    $bg = 'rgba(231, 76, 60, 0.15)';
                    $icon_char = '🚨';
                }
                elseif(stripos($sev, 'Warning') !== false) { 
                    $border = '#f1c40f'; 
                    $bg = 'rgba(241, 196, 15, 0.15)'; 
                    $icon_char = '⚠️';
                }
                
                $alerts_html .= "
                    <div style='margin-bottom:10px; border-left:4px solid $border; background:$bg; padding:10px; border-radius:4px;'>
                        <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;'>
                            <strong style='color:$border; font-size:14px;'>$icon_char " . strtoupper($sev) . "</strong>
                            <span style='font-size:11px; opacity:0.6;'>$time</span>
                        </div>
                        <div style='font-size:13px; margin-bottom:5px;'>$msg</div>
                        <div style='font-size:11px; opacity:0.7;'><i style='font-style:normal'>📍</i> $loc</div>
                    </div>
                ";
            }
            $alerts_html .= '</div>';
            
            // Escape for JS
            $alerts_html_js = json_encode($alerts_html); // Safe output
            
            $alert_js = "
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Active System Alerts',
                        html: $alerts_html_js,
                        icon: 'info',
                        confirmButtonText: 'Understood',
                        confirmButtonColor: '#4ab5c4',
                        background: '#0f2027',
                        color: '#fff',
                        backdrop: `rgba(0,0,0,0.8)`,
                        width: '500px'
                    });
                });
            ";
        }
    }
    ?>

    <?php if($alert_js) echo "<script>$alert_js</script>"; ?>

    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Home
    </a>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="header">
                <div class="header-logo">
                    <img src="../assets/logo.png" alt="AquaSafe Logo">
                    AquaSafe
                </div>
                
                <?php
                $role_title = "Welcome Back";
                if (isset($_GET['role'])) {
                    $role = $_GET['role'];
                    if ($role === 'admin') $role_title = "Admin Portal";
                    if ($role === 'user') $role_title = "Resident Portal";
                }
                ?>
                
                <div class="header-title"><?php echo $role_title; ?></div>
                
                <?php if(isset($_GET['role']) && ($_GET['role'] == 'admin' || $_GET['role'] == 'user')): ?>
                    <!-- <div class="role-badge"><?php echo ucfirst($_GET['role']); ?> Access</div> -->
                <?php endif; ?>
            <?php if(isset($_GET['signup']) && $_GET['signup'] == 'success'): ?>
                <div style="background: rgba(39, 174, 96, 0.2); border: 1px solid rgba(39, 174, 96, 0.4); color: #2ecc71; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    Account created successfully! Please sign in.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div style="background: rgba(39, 174, 96, 0.2); border: 1px solid rgba(39, 174, 96, 0.4); color: #2ecc71; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    Password reset successfully! You can now login.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.4); color: #ff8d85; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            </div>

            <div class="google-button-wrapper">
                <button type="button" id="googleSignBtn" class="google-btn-custom">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
                    <span>Continue with Google</span>
                </button>
            </div>

            <div class="divider-wrapper">
                <div class="divider-line"></div>
                <div class="divider-text">OR CONTINUE WITH</div>
                <div class="divider-line"></div>
            </div>

            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" placeholder="example@aquasafe.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button class="submit-btn" type="submit" name="login_btn">Access Dashboard</button>
            </form>

            <div class="footer-links">
                <a href="forgot_password.php">Forgot Password?</a>
                <span style="color: rgba(255,255,255,0.2);">|</span>
                <?php $current_role = (!empty($_GET['role'])) ? htmlspecialchars($_GET['role']) : 'user'; ?>
                <a href="signup.php?role=<?php echo $current_role; ?>">Create Account</a>
            </div>
        </div>
    </div>

    <script>
        // Google Sign-In Logic (OAuth2 Popup Flow)
        function gsiLoaded() {
            if (!window.google || !google.accounts) return;

            // Initialize the Token Client for the Popup UX
            const client = google.accounts.oauth2.initTokenClient({
                client_id: '<?php echo GOOGLE_CLIENT_ID; ?>',
                scope: 'email profile',
                callback: (tokenResponse) => {
                    if (tokenResponse && tokenResponse.access_token) {
                        handleAuthSuccess(tokenResponse.access_token);
                    }
                },
            });

            document.getElementById('googleSignBtn').addEventListener('click', function(e) {
                e.preventDefault();
                client.requestAccessToken();
            });
        }

        window.addEventListener('DOMContentLoaded', function() {
            if (window.google && google.accounts) gsiLoaded();
        });

        function handleAuthSuccess(accessToken) {
            // Determine role context if possible, or let backend decide
            var role = '<?php echo isset($_GET['role']) ? htmlspecialchars($_GET['role']) : "user"; ?>';

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'google_login_process.php';

            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'access_token';
            tokenInput.value = accessToken;
            form.appendChild(tokenInput);
            
            var roleInput = document.createElement('input');
            roleInput.type = 'hidden';
            roleInput.name = 'role';
            roleInput.value = role;
            form.appendChild(roleInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

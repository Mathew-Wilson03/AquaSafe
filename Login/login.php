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
            background: #0f2027; /* Fallback */
            background: linear-gradient(to bottom, #0f2027, #203a43, #2c5364);
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

        /* Animated Waves Background */
        .waves-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50vh;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 88.7'%3E%3Cpath d='M800 56.9c-155.5 0-204.9-50-405.5-49.9-200 0-250 49.9-394.5 49.9v31.8h800v-.2-31.6z' fill='%23ffffff'/%3E%3C/svg%3E");
            background-repeat: repeat-x;
            background-repeat: repeat-x;
            background-size: 50% auto;
            opacity: 0.1;
            transform-origin: center bottom;
            will-change: transform;
            backface-visibility: hidden;
        }

        .wave:nth-child(1) {
            bottom: -5px;
            animation: moveWave 20s linear infinite;
            opacity: 0.1;
            animation-duration: 12s;
        }
        
        .wave:nth-child(2) {
            bottom: -10px;
            animation: moveWave 15s linear infinite;
            opacity: 0.05;
            animation-duration: 8s;
            background-position: 250px 0;
        }
        
        .wave:nth-child(3) {
            bottom: -15px;
            animation: moveWave 10s linear infinite;
            opacity: 0.08;
            animation-duration: 6s;
            background-position: 500px 0;
        }

        @keyframes moveWave {
            0% { transform: translateX(0); }
            50% { transform: translateX(-25%); }
            100% { transform: translateX(-50%); }
        }

        /* Particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            will-change: transform;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            20% { opacity: 1; }
            80% { opacity: 1; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        /* Login Card */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px; /* Reduced from 480px for a tigher app feel */
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
            gap: 10px;
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 18px; /* Slightly smaller */
            font-weight: 700;
        }

        .header-logo svg {
            width: 28px;
            height: 28px;
            stroke: var(--primary);
            filter: drop-shadow(0 0 8px rgba(74, 181, 196, 0.4));
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
</head>
<body>
    <!-- Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Animated Waves -->
    <div class="waves-container">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v8m0 4v8M2 12h8m4 0h8M4 4l5.66 5.66M14.34 4l5.66 5.66M4 20l5.66-5.66M14.34 20l5.66-5.66"/>
                    </svg>
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
        // Particle Animation
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                const size = Math.random() * 5 + 2;
                p.style.width = `${size}px`;
                p.style.height = `${size}px`;
                p.style.left = `${Math.random() * 100}%`;
                p.style.animationDelay = `${Math.random() * 10}s`;
                container.appendChild(p);
            }
        });

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - AquaSafe</title>
    <?php require_once 'config.php'; ?>
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
            padding: 20px 0;
        }

        /* Signup Card */
        .signup-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 600px; /* Increased width */
            padding: 20px;
        }

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

        @media (max-width: 600px) {
            .signup-wrapper { max-width: 100%; padding: 15px; }
            .back-btn { top: 20px; left: 20px; font-size: 14px; }
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
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
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 14px;
            color: white;
            transition: all 0.3s ease;
            height: 46px; 
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 181, 196, 0.15);
        }

        .submit-btn {
            width: 100%;
            height: 46px;
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

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #ff8d85;
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }
        
        .role-notice {
            font-size: 12px;
            color: rgba(74, 181, 196, 0.8);
            text-align: center;
            margin-top: -15px;
            margin-bottom: 15px;
        }
        @media (max-width: 480px) {
            .signup-wrapper { padding: 15px; }
            .signup-container { padding: 25px 20px; border-radius: 15px; }
            .header-title { font-size: 20px; }
            .submit-btn, input { height: 44px; font-size: 14px; }
            .role-notice { font-size: 11px; }
        }

        /* Password Strength Styles */
        .password-requirements {
            margin-top: 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            list-style: none;
            padding-left: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            opacity: 0;
            visibility: hidden;
            height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .password-requirements.show {
            margin-top: 10px;
            opacity: 1;
            visibility: visible;
            height: auto;
        }
        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        .requirement.met {
            color: var(--primary);
        }
        .requirement.met i {
            transform: scale(1.2);
        }
        .strength-meter {
            height: 0;
            background: rgba(255, 255, 255, 0.1);
            margin-top: 0;
            border-radius: 2px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .strength-meter.show {
            height: 4px;
            margin-top: 10px;
            opacity: 1;
            visibility: visible;
        }
        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
    </style>
    <script src="https://accounts.google.com/gsi/client" async defer onload="gsiLoaded()"></script>
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

    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Home
    </a>

    <div class="signup-wrapper">
        <div class="signup-container">
            <div class="header">
                <div class="header-logo">
                    <img src="../assets/logo.png" alt="AquaSafe Logo">
                    AquaSafe
                </div>
                <div class="header-title">Create Account</div>
            </div>

            <?php
            $role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : 'user';
            $role_display = ($role === 'admin') ? 'Administrator' : 'Resident';
            ?>
            
            <div class="role-notice">Registering as <?php echo $role_display; ?></div>

            <!-- GSI Button -->
            <div class="google-button-wrapper" style="margin-bottom: 20px;">
                <button type="button" id="googleSignBtn" class="google-btn-custom" style="width: 100%; height: 46px; padding: 0 20px; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; background: rgba(255, 255, 255, 0.95); cursor: pointer; font-size: 14px; font-weight: 600; color: #333; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease;">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" style="width: 18px; height: 18px;">
                    <span>Sign up with Google</span>
                </button>
            </div>

            <div class="divider-wrapper" style="display: flex; align-items: center; margin: 20px 0; gap: 12px;">
                <div class="divider-line" style="flex: 1; height: 1px; background: rgba(255, 255, 255, 0.1);"></div>
                <div class="divider-text" style="color: rgba(255, 255, 255, 0.3); font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">OR REGISTER WITH EMAIL</div>
                <div class="divider-line" style="flex: 1; height: 1px; background: rgba(255, 255, 255, 0.1);"></div>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="signup_process.php" method="POST">
                <input type="hidden" name="role" value="<?php echo $role; ?>">
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" placeholder="example@aquasafe.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <div class="strength-meter">
                        <div id="strengthBar" class="strength-bar"></div>
                    </div>
                    <ul class="password-requirements">
                        <li id="reqLength" class="requirement"><span>○</span> 8+ Chars</li>
                        <li id="reqUpper" class="requirement"><span>○</span> Uppercase</li>
                        <li id="reqLower" class="requirement"><span>○</span> Lowercase</li>
                        <li id="reqNumber" class="requirement"><span>○</span> Number</li>
                        <li id="reqSpecial" class="requirement"><span>○</span> Special</li>
                    </ul>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Residential Area</label>
                    <select name="location" style="width: 100%; padding: 12px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; font-size: 14px; color: white; height: 46px; outline: none;">
                        <option value="Central City" style="color:black;">Central City</option>
                        <option value="North District" style="color:black;">North District</option>
                        <option value="South Reservoir" style="color:black;">South Reservoir</option>
                        <option value="West Bank" style="color:black;">West Bank</option>
                        <option value="East Valley" style="color:black;">East Valley</option>
                    </select>
                </div>
                <button class="submit-btn" type="submit" name="signup_btn">Sign Up</button>
            </form>

            <div class="footer-links">
                <a href="login.php?role=<?php echo $role; ?>">Already have an account? Sign In</a>
            </div>
        </div>
    </div>

    <script>
        // Password Validation Logic
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('strengthBar');
            const strengthMeter = document.querySelector('.strength-meter');
            const requirementsList = document.querySelector('.password-requirements');
            
            const requirements = {
                length: { el: document.getElementById('reqLength'), regex: /.{8,}/ },
                upper: { el: document.getElementById('reqUpper'), regex: /[A-Z]/ },
                lower: { el: document.getElementById('reqLower'), regex: /[a-z]/ },
                number: { el: document.getElementById('reqNumber'), regex: /[0-9]/ },
                special: { el: document.getElementById('reqSpecial'), regex: /[@$!%*?&]/ }
            };

            const showRequirements = () => {
                strengthMeter.classList.add('show');
                requirementsList.classList.add('show');
            };

            const hideRequirements = () => {
                if (passwordInput.value.length === 0) {
                    strengthMeter.classList.remove('show');
                    requirementsList.classList.remove('show');
                }
            };

            if(passwordInput) {
                passwordInput.addEventListener('focus', showRequirements);
                passwordInput.addEventListener('blur', hideRequirements);

                passwordInput.addEventListener('input', () => {
                    const val = passwordInput.value;
                    if (val.length > 0) showRequirements();
                    
                    let metCount = 0;

                    Object.keys(requirements).forEach(key => {
                        const req = requirements[key];
                        if (req.regex.test(val)) {
                            req.el.classList.add('met');
                            req.el.querySelector('span').innerText = '●';
                            metCount++;
                        } else {
                            req.el.classList.remove('met');
                            req.el.querySelector('span').innerText = '○';
                        }
                    });

                    // Update Bar
                    const width = (metCount / 5) * 100;
                    strengthBar.style.width = width + '%';
                    
                    if (metCount <= 2) strengthBar.style.background = '#e74c3c';
                    else if (metCount <= 4) strengthBar.style.background = '#f1c40f';
                    else strengthBar.style.background = '#2ecc71';
                });
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
                // Request access token (triggers the account chooser popup)
                client.requestAccessToken();
            });
        }

        window.addEventListener('DOMContentLoaded', function() {
            if (window.google && google.accounts) gsiLoaded();
        });

        function handleAuthSuccess(accessToken) {
            var role = '<?php echo $role; ?>'; // Pass the current context role

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'google_login_process.php';

            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'access_token'; // Sending Access Token now
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

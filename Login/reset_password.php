<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - AquaSafe</title>
    <?php 
    session_start();
    require_once 'config.php'; 
    
    $token = "";
    $error = "";
    $showForm = false;

    // Detect table
    $table = 'users'; 
    try { $r = mysqli_query($link, "SHOW TABLES LIKE 'user'"); if ($r && mysqli_num_rows($r) > 0) $table = 'user'; } catch (Throwable $e) {}

    if(isset($_SESSION['reset_verified_email'])){
        $showForm = true;
        // The process script will use the session email
    } elseif(isset($_GET['token'])){
        $token = trim($_GET['token']);
        
        // Validate Token
        $sql = "SELECT id FROM `$table` WHERE reset_token = ? AND reset_expiry > NOW()";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                $showForm = true;
            } else {
                $error = "Invalid or expired reset link.";
            }
        }
    } else {
        $error = "Access denied. Please verify your code first.";
    }
    ?>
    <style>
        :root {
            --primary: #4ab5c4;
            --primary-dark: #3a97a5;
            --secondary: #2c3e50;
            --glass: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.2);
            --text-light: #f5f7fa;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #001f3f; /* Deep Navy to match Vanta */
            height: 100vh; color: var(--text-light); display: flex; justify-content: center; align-items: center; position: relative; overflow: hidden;
        }
        .wrapper { position: relative; z-index: 10; width: 100%; max-width: 500px; padding: 20px; }
        .container { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .header { text-align: center; margin-bottom: 25px; }
        .header-logo { display: inline-flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px; color: var(--primary); font-size: 18px; font-weight: 700; }
        .header-logo svg { width: 32px; height: 32px; stroke: var(--primary); filter: drop-shadow(0 0 8px rgba(74, 181, 196, 0.4)); }
        .header-title { font-size: 22px; font-weight: 300; color: white; letter-spacing: 0.5px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: rgba(255, 255, 255, 0.7); font-size: 13px; font-weight: 500; }
        input[type="password"] { width: 100%; padding: 12px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; font-size: 14px; color: white; height: 46px; }
        input:focus { outline: none; background: rgba(255, 255, 255, 0.1); border-color: var(--primary); }
        .submit-btn { width: 100%; height: 46px; background: linear-gradient(135deg, #4ab5c4 0%, #3a97a5 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; }
        .submit-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

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

    <div class="wrapper">
        <div class="container">
            <div class="header">
                 <div class="header-logo">
                    <img src="../assets/logo.png" alt="AquaSafe Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    AquaSafe
                </div>
                <div class="header-title">Set New Password</div>
            </div>

            <?php if($showForm): ?>
            <form action="reset_password_process.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" id="password" placeholder="New Password" required>
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
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button class="submit-btn" type="submit" name="reset_btn">Update Password</button>
            </form>
            <?php else: ?>
                <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.4); color: #ff8d85; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                    <br><br>
                    <a href="forgot_password.php" style="color:white; text-decoration: underline;">Request a new link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
     <script>
        document.addEventListener('DOMContentLoaded', () => {
             // Password Validation Logic
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
    </script>
</body>
</html>

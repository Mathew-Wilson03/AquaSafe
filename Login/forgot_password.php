<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AquaSafe</title>
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
            overflow-y: auto;
            padding: 20px 0;
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
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 18px;
            font-weight: 700;
        }

        .header-logo img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        /* Card */
        .wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px; /* Increased to 500px */
            padding: 20px;
        }

        .container {
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

        .header-title {
            font-size: 22px;
            font-weight: 300;
            color: white;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
             font-size: 13px;
             color: rgba(255,255,255,0.6);
             line-height: 1.5;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
        }

        input[type="email"] {
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: white;
        }

        /* Back Button Top Left */
        .back-btn-top {
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

        .back-btn-top:hover {
            color: white;
            transform: translateX(-5px);
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

    <!-- Back Button -->
    <a href="login.php" class="back-btn-top">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Login
    </a>

    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="header-logo">
                    <img src="../assets/logo.png" alt="AquaSafe Logo">
                    AquaSafe
                </div>
                <div class="header-title">Forgot Password?</div>
                <div class="header-subtitle">Enter your email address to receive a verification code to reset your password.</div>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.4); color: #ff8d85; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="send_otp.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" placeholder="example@aquasafe.com" required>
                </div>
                <button class="submit-btn" type="submit" name="send_otp_btn">Send Verification Code</button>
            </form>

            <a href="login.php" class="back-link">Remember your password? Login</a>
        </div>
    </div>
</body>
</html>

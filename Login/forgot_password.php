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
            background-size: 50% auto;
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

        /* Card */
        .wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
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
    <!-- Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Animated Waves -->
    <div class="waves-container">
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

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

    <script>
        // Particle Animation
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('particles');
            const particleCount = 15;
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
    </script>
</body>
</html>

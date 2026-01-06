<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - AquaSafe</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f2027; background: linear-gradient(to bottom, #0f2027, #203a43, #2c5364);
            min-height: 100vh; color: var(--text-light);
            display: flex; justify-content: center; align-items: center; position: relative; 
            overflow-x: hidden;
            overflow-y: auto;
            padding: 20px 0;
        }
        
        .waves-container { position: absolute; bottom: 0; left: 0; width: 100%; height: 50vh; z-index: 1; overflow: hidden; pointer-events: none; }
        .wave { position: absolute; bottom: 0; left: 0; width: 200%; height: 100%; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 88.7'%3E%3Cpath d='M800 56.9c-155.5 0-204.9-50-405.5-49.9-200 0-250 49.9-394.5 49.9v31.8h800v-.2-31.6z' fill='%23ffffff'/%3E%3C/svg%3E"); background-repeat: repeat-x; background-size: 50% auto; opacity: 0.1; transform-origin: center bottom; }
        .wave:nth-child(1) { bottom: -5px; animation: moveWave 20s linear infinite; opacity: 0.1; animation-duration: 12s; }
        .wave:nth-child(2) { bottom: -10px; animation: moveWave 15s linear infinite; opacity: 0.05; animation-duration: 8s; background-position: 250px 0; }
        @keyframes moveWave { 0% { transform: translateX(0); } 50% { transform: translateX(-25%); } 100% { transform: translateX(-50%); } }
        
        .particles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .particle { position: absolute; background: rgba(255, 255, 255, 0.5); border-radius: 50%; animation: float 15s infinite linear; }
        @keyframes float { 0% { transform: translateY(100vh) scale(0); opacity: 0; } 100% { transform: translateY(-20vh) scale(1); opacity: 0; } }

        .wrapper { position: relative; z-index: 10; width: 100%; max-width: 400px; padding: 20px; }
        .container { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .header { text-align: center; margin-bottom: 25px; }
        .header-logo { display: inline-flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px; color: var(--primary); font-size: 18px; font-weight: 700; }
        .header-logo svg { width: 32px; height: 32px; stroke: var(--primary); filter: drop-shadow(0 0 8px rgba(74, 181, 196, 0.4)); }
        .header-title { font-size: 22px; font-weight: 300; color: white; letter-spacing: 0.5px; margin-bottom: 10px; }
        .header-subtitle { font-size: 13px; color: rgba(255,255,255,0.6); line-height: 1.5; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: rgba(255, 255, 255, 0.7); font-size: 13px; font-weight: 500; }
        input[type="text"] { width: 100%; padding: 12px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; font-size: 20px; color: white; height: 50px; text-align: center; letter-spacing: 5px; font-family: monospace;}
        input:focus { outline: none; background: rgba(255, 255, 255, 0.1); border-color: var(--primary); }
        
        .submit-btn { width: 100%; height: 46px; background: linear-gradient(135deg, #4ab5c4 0%, #3a97a5 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s ease; }
        .submit-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
        
        .alert-demo {
            background: rgba(243, 156, 18, 0.1); 
            border: 1px solid rgba(243, 156, 18, 0.3); 
            color: #f1c40f; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 12px; 
            text-align: center;
        }
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .wrapper { padding: 15px; }
            .container { padding: 25px 20px; }
            .header-title { font-size: 20px; }
            input[type="text"] { font-size: 16px; letter-spacing: 3px; height: 44px; }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    <div class="waves-container"><div class="wave"></div><div class="wave"></div></div>

    <div class="wrapper">
        <div class="container">
            <div class="header">
                 <div class="header-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v8m0 4v8M2 12h8m4 0h8M4 4l5.66 5.66M14.34 4l5.66 5.66M4 20l5.66-5.66M14.34 20l5.66-5.66"/>
                    </svg>
                    AquaSafe
                </div>
                <div class="header-title">Verify Code</div>
                <div class="header-subtitle">We've sent a 6-digit verification code to <span style="color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($_GET['email'] ?? 'your email'); ?></span>.</div>
            </div>

            <?php if(isset($_GET['sent'])): ?>
                <div style="background: rgba(39, 174, 96, 0.2); border: 1px solid rgba(39, 174, 96, 0.4); color: #2ecc71; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    Code sent successfully! Please check your inbox.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid rgba(231, 76, 60, 0.4); color: #ff8d85; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="verify_otp_process.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                <div class="form-group">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="otp" placeholder="123456" maxlength="6" required pattern="[0-9]*" autocomplete="off" autofocus>
                </div>
                <button class="submit-btn" type="submit" name="verify_btn">Verify Code</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <form action="send_otp.php" method="POST">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                    <button type="submit" name="send_otp_btn" style="background: none; border: none; color: rgba(255,255,255,0.5); font-size: 13px; text-decoration: underline; cursor: pointer; padding: 0;">Did not receive the code? Resend Code</button>
                </form>
            </div>
        </div>
    </div>
     <script>
        document.addEventListener('DOMContentLoaded', () => {
             const container = document.getElementById('particles');
             for (let i = 0; i < 15; i++) {
                 const p = document.createElement('div'); p.classList.add('particle');
                 p.style.left = `${Math.random() * 100}%`; p.style.width = p.style.height = `${Math.random() * 5 + 2}px`;
                 container.appendChild(p);
             }
        });
    </script>
</body>
</html>

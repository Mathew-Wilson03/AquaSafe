<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSafe - Smart Water Monitoring</title>
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
            background-color: #001f3f; /* Fallback Color */
            min-height: 100vh;
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 40px 0;
        }

        /* Vanta Background is handled by #vanta-bg inline styles */

        /* ... Wave keyframes remain same ... */

        /* Main Card - PREMIUM GLASSMORPHISM */
        .container {
            width: 1100px;
            max-width: 90%;
            min-height: 600px;
            display: flex;
            z-index: 10;
            /* Ultra-Clean Glass Effect */
            background: rgba(16, 30, 50, 0.25); /* More transparent to show Vanta */
            backdrop-filter: blur(20px);         /* Strong frost */
            -webkit-backdrop-filter: blur(20px);
            
            /* Diamond-Cut Border */
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 32px;
            
            /* Soft Deep Shadow */
            box-shadow: 
                0 40px 80px -20px rgba(0, 0, 0, 0.6), 
                inset 0 0 0 1px rgba(255, 255, 255, 0.05);
            
            overflow: hidden;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(60px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Left Content */
        .hero-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-row {
            display: flex;
            align-items: center;
            justify-content: center; /* Center horizontally */
            gap: 20px;
            margin-bottom: 20px;
        }

        .header-logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        h1 {
            font-size: 40px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0;
            background: linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: left; /* Keep text left aligned relative to itself, but block is centered */
        }

        .subtitle {
            font-size: 18px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 auto 40px auto; /* Center block */
            max-width: 500px; /* Limit width for reading */
            text-align: center; /* Center text */
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            justify-items: center; /* Center the button */
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 16px;
            display: block;
        }

        .card h3 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .card p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.4;
        }

        .card-arrow {
            position: absolute;
            bottom: 20px;
            right: 20px;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
            color: var(--primary);
        }

        .card:hover .card-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        /* Right Visual */
        .visual-section {
            width: 400px;
            /* Removed background to blend with glass container */
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .water-circle {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: #4ab5c4;
            position: relative;
            box-shadow: 0 0 50px rgba(74, 181, 196, 0.3);
            animation: pulse 4s infinite ease-in-out;
        }

        .water-circle::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border-radius: 50%;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            animation: spin 30s linear infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 50px rgba(74, 181, 196, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 80px rgba(74, 181, 196, 0.5); }
            100% { transform: scale(1); box-shadow: 0 0 50px rgba(74, 181, 196, 0.3); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .stat-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            color: #2c3e50;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .stat-1 { top: 30%; right: 40px; }
        .stat-2 { bottom: 30%; left: 40px; }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                width: 95%;
                margin: 20px auto;
            }
            .visual-section {
                width: 100%;
                height: 180px;
            }
            .hero-section {
                padding: 30px 20px;
                text-align: center;
                align-items: center;
            }
            h1 { font-size: 28px; }
            .subtitle { margin: 0 auto 30px; }
            .brand-badge { margin: 0 auto 20px; }
        }

        @media (max-width: 480px) {
            .hero-section { padding: 25px 15px; }
            h1 { font-size: 24px; }
            .card { padding: 20px 15px; }
        }

        @media (max-width: 600px) {
            .action-grid {
                grid-template-columns: 1fr;
            }
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

    <!-- Main Content -->
    <div class="container">
        <div class="hero-section">
            <div class="header-row">
                <img src="../assets/logo.png" alt="AquaSafe Logo" class="header-logo-img">
                <h1>Smart Water<br>Monitoring</h1>
            </div>
            
            <p class="subtitle" style="font-style: italic;">
                Real-time tracking of water quality, safety levels, and environmental data. Secure access for residents and administrators.
            </p>

            <div class="action-grid">
                <a href="login.php" class="access-portal-btn">
                    <div class="btn-glow"></div>
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lock-icon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="unlock-icon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                    </div>
                    <div class="text-content">
                        <span class="btn-title">Access Dashboard</span>
                        <span class="btn-subtitle">Secure Gateway</span>
                    </div>
                    <div class="arrow-box">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </div>
                </a>
            </div>

    <style>
        /* NEW ACCESS BUTTON STYLES */
        .access-portal-btn {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
            background: linear-gradient(90deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 25px;
            border-radius: 16px;
            text-decoration: none;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            max-width: 400px;
        }

        .access-portal-btn:hover {
            background: linear-gradient(90deg, rgba(74, 181, 196, 0.15), rgba(255,255,255,0.05));
            border-color: rgba(74, 181, 196, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .btn-glow {
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: skewX(-20deg);
            transition: 0.5s;
        }

        .access-portal-btn:hover .btn-glow {
            left: 150%;
            transition: 0.7s ease-in-out;
        }

        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            color: #4ab5c4;
            position: relative;
            transition: 0.3s;
        }

        .access-portal-btn:hover .icon-box {
            background: #4ab5c4;
            color: #0f2027;
            box-shadow: 0 0 15px rgba(74, 181, 196, 0.6);
        }

        .lock-icon { display: block; transition: 0.3s; }
        .unlock-icon { display: none; transition: 0.3s; }

        .access-portal-btn:hover .lock-icon { display: none; }
        .access-portal-btn:hover .unlock-icon { display: block; }

        .text-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .btn-title {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .btn-subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
        }

        .arrow-box {
            color: rgba(255,255,255,0.3);
            transition: 0.3s;
        }

        .access-portal-btn:hover .arrow-box {
            color: #4ab5c4;
            transform: translateX(5px);
        }
    </style>
        </div>

        <div class="visual-section">
        <div class="visual-section">
            <div class="flood-gauge-container">
                <div class="gauge-glass">
                    <div class="marker m-safe">SAFE</div>
                    <div class="marker m-warn">WARNING</div>
                    <div class="marker m-crit">CRITICAL</div>
                    
                    <div class="water-column">
                        <div class="wave-surface w1"></div>
                        <div class="wave-surface w2"></div>
                    </div>
                    
                    <div class="glare"></div>
                </div>
            </div>
            
            <!-- Dynamic Badges -->
            <div class="badge-container">
                <div class="dynamic-badge b-safe">
                    <span class="pulsing-dot" style="background: #2ecc71"></span> Status: SAFE
                </div>
                <div class="dynamic-badge b-warn">
                    <span class="pulsing-dot" style="background: #f1c40f"></span> Status: WARNING
                </div>
                <div class="dynamic-badge b-crit">
                    <span class="pulsing-dot" style="background: #e74c3c"></span> Status: CRITICAL
                </div>
            </div>
        </div>
    </div>

    <style>
        /* FLOOD GAUGE VISUAL */
        .visual-section {
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-direction: column; /* Stack gauge and badge */
        }

        .flood-gauge-container {
            width: 200px;
            height: 420px;
            position: relative;
            filter: drop-shadow(0 30px 40px rgba(0,0,0,0.5));
            margin-bottom: 30px;
        }

        .badge-container {
            position: relative;
            height: 40px;
            width: 200px;
            display: flex;
            justify-content: center;
        }

        .dynamic-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            backdrop-filter: blur(5px);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.5s ease;
        }

        /* Animation Loop Configuration (12s Cycle) */
        /* 0-3s: SAFE | 4-7s: WARN | 8-11s: CRIT */
        
        .b-safe { animation: badgeFade 12s infinite; animation-delay: 0s; color: #2ecc71; }
        .b-warn { animation: badgeFade 12s infinite; animation-delay: 4s; color: #f1c40f; }
        .b-crit { animation: badgeFade 12s infinite; animation-delay: 8s; color: #e74c3c; box-shadow: 0 0 15px rgba(231, 76, 60, 0.3); }

        @keyframes badgeFade {
            0% { opacity: 0; transform: translateY(10px); }
            5% { opacity: 1; transform: translateY(0); } /* Appears quickly */
            25% { opacity: 1; transform: translateY(0); } /* Stays */
            30% { opacity: 0; transform: translateY(-10px); } /* Fades out */
            100% { opacity: 0; transform: translateY(-10px); }
        }

        .gauge-glass {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01));
            border: 3px solid rgba(255, 255, 255, 0.15);
            border-right: 3px solid rgba(255, 255, 255, 0.05);
            border-bottom: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 100px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(6px);
            box-shadow: inset 0 0 60px rgba(0,0,0,0.6);
        }

        /* Markers */
        .marker {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            border-top: 2px dashed rgba(255, 255, 255, 0.2);
            text-align: right;
            padding-top: 4px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            z-index: 10;
        }
        .m-safe { bottom: 30%; color: #2ecc71; border-color: rgba(46, 204, 113, 0.3); }
        .m-warn { bottom: 60%; color: #f1c40f; border-color: rgba(241, 196, 15, 0.3); }
        .m-crit { bottom: 85%; color: #e74c3c; border-color: #e74c3c; animation: flashLine 2s infinite; }

        @keyframes flashLine {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; text-shadow: 0 0 10px #e74c3c; }
        }

        /* Water Column - LOOPED ANIMATION */
        .water-column {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 0%; /* Start empty */
            background: linear-gradient(to top, #09151e 0%, #173242 40%, #1f4e5f 70%, #2980b9 100%);
            border-radius: 0 0 100px 100px;
            z-index: 1;
            box-shadow: 0 0 40px rgba(41, 128, 185, 0.5);
            animation: waterCycle 12s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        @keyframes waterCycle {
            0% { height: 10%; }      /* Start Low */
            5% { height: 30%; }      /* Rise to SAFE */
            30% { height: 35%; }     /* Hold Safe (Simulate fluctuation) */
            
            35% { height: 60%; }     /* Rise to WARNING */
            60% { height: 65%; }     /* Hold Warning */
            
            65% { height: 90%; }     /* Rise to CRITICAL */
            90% { height: 92%; }     /* Hold Critical */
            
            95% { height: 10%; }     /* Drain/Reset */
            100% { height: 10%; }
        }

        /* NEW WAVE ANIMATION (Horizontal Flow) */
        .wave-surface {
            position: absolute;
            top: -10px;
            left: 0;
            width: 200%;
            height: 25px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' fill='%232980b9' opacity='0.8'/%3E%3C/svg%3E");
            background-size: 50% 100%;
            background-repeat: repeat-x;
            transform: rotate(180deg);
            opacity: 1;
        }

        .w1 {
            top: -20px;
            animation: moveWaveHorizontal 4s linear infinite;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' fill='%233498db' opacity='1'/%3E%3C/svg%3E");
            opacity: 1;
            z-index: 2;
        }

        .w2 {
            top: -30px;
            width: 200%;
            animation: moveWaveHorizontal 7s linear infinite reverse;
            opacity: 0.4;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' fill='%2391dcfc' opacity='0.5'/%3E%3C/svg%3E");
            z-index: 1;
        }

        @keyframes moveWaveHorizontal {
            0% { transform: translateX(0) scaleY(-1); }
            100% { transform: translateX(-50%) scaleY(-1); }
        }

        /* Glass Glare & Bubbles */
        .glare {
            position: absolute;
            top: 5%;
            right: 25%;
            width: 20px;
            height: 100px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.4), rgba(255,255,255,0));
            border-radius: 99px;
            filter: blur(2px);
            z-index: 20;
        }

        /* Pulsing Dot Styles */
        .pulsing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
            animation: dotPulse 2s infinite;
        }

        @keyframes dotPulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>

    <script>
        // Simple JS to create floating particles
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                
                // Random size
                const size = Math.random() * 5 + 2;
                p.style.width = `${size}px`;
                p.style.height = `${size}px`;
                
                // Random position
                p.style.left = `${Math.random() * 100}%`;
                
                // Random delay
                p.style.animationDelay = `${Math.random() * 10}s`;
                
                container.appendChild(p);
            }
        });
    </script>
</body>
</html>

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
            padding: 40px 0;
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

        /* Main Card */
        .container {
            width: 1000px;
            max-width: 90%;
            display: flex;
            z-index: 10;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left Content */
        .hero-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(74, 181, 196, 0.2);
            border: 1px solid rgba(74, 181, 196, 0.3);
            padding: 6px 16px;
            border-radius: 50px;
            width: fit-content;
            margin-bottom: 24px;
            color: #4ab5c4;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        h1 {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 18px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            max-width: 400px;
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
            background: linear-gradient(135deg, rgba(74, 181, 196, 0.2) 0%, rgba(74, 181, 196, 0.05) 100%);
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
    <!-- Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Animated Waves -->
    <div class="waves-container">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="hero-section">
            <div class="brand-badge">
                <span class="pulse-dot">●</span> System Operational
            </div>
            
            <h1>Smart Water<br>Monitoring</h1>
            <p class="subtitle">
                Real-time tracking of water quality, safety levels, and environmental data. Secure access for residents and administrators.
            </p>

            <div class="action-grid">
                <a href="login.php" class="card" style="text-align: center;">
                    <span class="card-icon">🔐</span>
                    <h3>Login to Dashboard</h3>
                    <p>Access your account to view data.</p>
                    <svg class="card-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="visual-section">
            <div class="water-circle"></div>
            <div class="stat-badge stat-1">
                <span style="color: #27ae60">●</span> 98% Purity
            </div>
            <div class="stat-badge stat-2">
                <span style="color: #2980b9">●</span> Live Sync
            </div>
        </div>
    </div>

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

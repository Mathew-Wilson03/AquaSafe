<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSafe - Water Monitoring System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
            color: #333;
        }

        /* Header & Navigation */
        header {
            background: linear-gradient(135deg, #5ec8d8 0%, #4ab5c4 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 40px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
            font-size: 15px;
        }

        .nav-menu a:hover {
            opacity: 0.8;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #5ec8d8 0%, #4ab5c4 100%);
            padding: 80px 30px;
            text-align: center;
            color: white;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .hero p {
            font-size: 22px;
            margin-bottom: 50px;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .btn {
            padding: 14px 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-admin {
            background: #333;
            color: white;
        }

        .btn-admin:hover {
            background: #555;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .btn-user {
            background: #333;
            color: white;
        }

        .btn-user:hover {
            background: #555;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Features Section */
        .features {
            max-width: 1200px;
            margin: -60px auto 80px;
            padding: 0 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            position: relative;
            z-index: 10;
        }

        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #5ec8d8 0%, #4ab5c4 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }

        .feature-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .feature-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }

        footer p {
            font-size: 14px;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 18px;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .nav-menu {
                gap: 20px;
                font-size: 14px;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">
                <img src="assets/logo.png" alt="AquaSafe Logo" class="logo-img">
                AquaSafe
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="Login/login.php">Login</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Real Time Water Level Monitoring and Early Warning System</h1>
            <p>Powered by IoT & LoRaWAN</p>
            
            <div class="hero-buttons">
                <a href="Login/login.php" class="btn btn-user">Login</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Real Time Monitoring</h3>
            <p>Monitor water levels and system status in real-time with live data updates and analytics.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⚠️</div>
            <h3>Alerts</h3>
            <p>Receive instant alerts and notifications when water levels exceed critical thresholds.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📍</div>
            <h3>Locations</h3>
            <p>Track and manage multiple monitoring locations with precise GPS coordinates and mapping.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">✓</div>
            <h3>Safety</h3>
            <p>Ensure community safety with early warning systems and reliable data for disaster prevention.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 AquaSafe - Water Monitoring System. All rights reserved.</p>
    </footer>
</body>
</html>

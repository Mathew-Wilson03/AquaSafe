<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Simulation</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        .btn { background: #4ab5c4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Passwor Reset Simulation</h2>
        <p>Since this is a local environment, no real email was sent.</p>
        <p>Here is your <strong>Reset Link</strong> (click to proceed):</p>
        <a href="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" class="btn">Reset Password</a>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .google-btn {
            background-color: #4285f4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .google-btn:hover {
            background-color: #357abd;
        }
        .user-profile {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: 10px;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="login-container" x>
        <?php
        session_start();
        if (isset($_SESSION['user_data'])) {
            $userInfo = $_SESSION['user_data'];
        ?>
            <div class="user-profile">
                <img src="<?php echo $userInfo['picture']; ?>" alt="Profile" class="user-avatar">
                <h3>Welcome, <?php echo $userInfo['full_name']; ?></h3>
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        <?php
        } else {
        ?>
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <button type="submit" class="google-btn">Login with Google</button>
            </form>
        <?php
        }
        ?>
    </div>
</body>
</html>

<?php
include ("connection.php");
session_start();
$msg = '';

if(isset($_POST['submit'])){
    // Sanitize email input
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Do not sanitize the password; it breaks special characters

    // 1. Fetch the user by email ONLY
    $result = mysqli_query($conn, "SELECT * FROM `login_table` WHERE email = '$email'");

    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        
        // 2. Use password_verify to check the typed password against the hashed one in DB
        if(password_verify($password, $row['password'])){
            
            // 3. Password is correct! Set sessions based on user_type
            if($row['user_type'] == 'user'){
                $_SESSION['user'] = $row['email'];
                header('location:user.php');
            } else {
                $_SESSION['admin'] = $row['email'];
                header('location:admin.php');
            }
            exit();
            
        } else {
            // Password did not match the hash
            $msg = "Incorrect password!";
        }
    } else {
        // Email not found in database
        $msg = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Faculty of Computing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ebsu-dark: #050a02;
            --ebsu-glow: #30e403;
            --ebsu-white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        /* Match index.php Hero Background */
        body {
            height: 100vh;
            background: 
                radial-gradient(circle at center, rgba(48, 228, 3, 0.12) 0%, transparent 70%),
                radial-gradient(circle at center, #0a1504 0%, var(--ebsu-dark) 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Header Area */
        header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 8%;
        }

        .logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none;}
        .logo-box img { height: 45px; filter: drop-shadow(0 0 5px var(--ebsu-glow)); }
        .logo-text h2 { font-size: 14px; letter-spacing: 1px; line-height: 1.3; color: white; text-align: left; }

        /* Login Container Styling */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            text-align: center;
        }

        .regcaption h2 { color: white; font-weight: 800; font-size: 24px; margin-bottom: 5px; }
        .msg { color: #ff6b6b; font-size: 13px; font-weight: 600; margin-bottom: 15px; }

        /* Form Controls */
        .form_group { margin-bottom: 20px; }
        .form_group input {
            width: 100%;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            outline: none;
            transition: 0.3s;
        }

        .form_group input:focus { border-color: var(--ebsu-glow); background: rgba(255, 255, 255, 0.1); }

        .form_btn button {
            width: 100%;
            padding: 14px;
            background: var(--ebsu-glow);
            color: var(--ebsu-dark);
            border: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0 20px rgba(48, 228, 3, 0.3);
            transition: 0.3s;
        }

        .form_btn button:hover { transform: translateY(-3px); box-shadow: 0 0 30px rgba(48, 228, 3, 0.5); }

        .signin_caption { margin-top: 20px; }
        .signin_caption p { color: rgba(255,255,255,0.6); font-size: 13px; }
        .signin_caption a { color: var(--ebsu-glow); text-decoration: none; font-weight: 600; }

        /* Footer */
        footer {
            padding: 20px;
            text-align: center;
            background: rgba(0,0,0,0.3);
        }
        .main_footer { color: rgba(255,255,255,0.5); font-size: 11px; }
        .main_footer strong { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo-box">
            <img src="asset/images/NACOSLOGO.png" alt="EBSU Logo">
            <div class="logo-text">
                <h2>FACULTY OF COMPUTING<br>EBONYI STATE UNIVERSITYE</h2>
            </div>
        </a>
    </header>

    <div class="login-wrapper">
        <div class="container">
            <div class="regcaption">
                <h2>Login Now</h2>
                <p class="msg"><?= $msg ?></p>
            </div>
            <form action="" method="POST">
                <div class="form_group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="form_group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form_btn">
                    <button type="submit" name="submit">Login to Portal</button>
                </div>
                <div class="signin_caption">
                    <p>New here? <a href="register.php">Create Account</a></p>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="main_footer">
            <div>
                Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> 
                <span class="footer_divider">|</span> 
                Powered by <strong>NACOS President</strong>
            </div>
            <div style="margin-top: 10px; font-size: 11px; color: #888; text-transform: uppercase;">
                Official Student Management & Clearance Portal
            </div>
        </div>
    </footer>

</body>
</html>
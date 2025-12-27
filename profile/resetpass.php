<?php
// 1. Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Include the PHPMailer files (Manual Install)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 3. Database Connection (Step up one folder to reach the root)
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";
$step = 1; 

// Step 1: Generate and Send Code using PHPMailer
if (isset($_POST['send_code'])) {
    $code = rand(100000, 999999);
    
    // Update the database with the temporary code
    mysqli_query($conn, "UPDATE login_table SET reset_code = '$code' WHERE email = '$email'");
    
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '002egba@gmail.com'; //  GMAIL HERE
        $mail->Password   = ' xmyegpuviwwyuf';     // Gmail 16-CHAR APP PASSWORD HERE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply@ebsu-computing.edu.ng', 'Faculty of Computing');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body    = "Hello, <br><br>Your verification code for resetting your password is: <b>$code</b><br><br>If you did not request this, please ignore this email.";

        $mail->send();
        $msg = "<p style='color:green; font-weight:bold;'>A secure code has been sent to your email!</p>";
        $step = 2;
    } catch (Exception $e) {
        // This prevents the "unsafe" system warnings from showing on the UI
        $msg = "<p style='color:red; font-weight:bold;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
        $step = 1;
    }
}

// Step 2: Verify Code and Update Password
if (isset($_POST['update_password'])) {
    $user_code = mysqli_real_escape_string($conn, $_POST['verify_code']);
    $new_pass = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_pass = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    $check_query = mysqli_query($conn, "SELECT reset_code FROM login_table WHERE email = '$email'");
    $row = mysqli_fetch_assoc($check_query);

    if ($row['reset_code'] !== $user_code) {
        $msg = "<p style='color:red; font-weight:bold;'>Invalid verification code!</p>";
        $step = 2;
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "<p style='color:red; font-weight:bold;'>Passwords do not match!</p>";
        $step = 2;
    } else {
        // Update password and clear the reset code
        mysqli_query($conn, "UPDATE login_table SET password = '$new_pass', reset_code = NULL WHERE email = '$email'");
        
        // SUCCESS REDIRECT LOGIC
        echo "<script>
                alert('Password updated successfully! You will now be redirected to the login page.');
                window.location.href='../login.php';
              </script>";
        exit();
    }
}

// Fetch user data for the sidebar and top nav
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_image = !empty($user['profile_pic']) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .reset-container { max-width: 450px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #30e403; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 13px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-reset { width: 100%; padding: 12px; background: #30e403; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 14px; transition: 0.3s; }
        .btn-reset:hover { background: #28c902; }
        
        /* Sidebar Styling Consistency */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 13px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($fullname) ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU</h4></div>       
            </div>
            <div class="logout_btn"><a href="../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../user.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu active" id="profile">
                        <li><a href="update.php">Update Profile</a></li>
                        <li><a href="resetpass.php" style="color:#30e403;">Change Password</a></li>
                    </ul>
                </li>
                </ul>
        </aside>

        <div class="body_div">
            <div class="reset-container">
                <h3 style="text-align: center; color: #0026ff; margin-bottom: 20px;">Secure Password Reset</h3>
                <?= $msg ?>

                <?php if ($step == 1): ?>
                <form action="" method="POST">
                    <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
                        Click below to receive a reset code at: <br><strong><?= $email ?></strong>
                    </p>
                    <button type="submit" name="send_code" class="btn-reset">SEND VERIFICATION CODE</button>
                </form>
                <?php else: ?>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Enter 6-Digit Code:</label>
                        <input type="text" name="verify_code" maxlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>New Password:</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password" class="btn-reset">UPDATE PASSWORD</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
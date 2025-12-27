<?php
// 1. Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Include the PHPMailer files (Manual Install)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 3. Database Connection
include('../connection.php'); 
session_start();

// Security Check
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['admin'];
$msg = "";
$step = 1; 

// Step 1: Generate and Send Code using PHPMailer
if (isset($_POST['send_code'])) {
    $code = rand(100000, 999999);
    
    // Update the database with the temporary code
    mysqli_query($conn, "UPDATE login_table SET reset_code = '$code' WHERE email = '$email'");
    
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '002egba@gmail.com'; 
        $mail->Password   = 'mlkvhsbvznjcwpdl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- RECIPIENTS ---
        $mail->setFrom('noreply@ebsu-computing.edu.ng', 'Faculty of Computing');
        $mail->addAddress($email);

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body    = "Hello, <br><br>Your verification code for resetting your password is: <b>$code</b><br><br>If you did not request this, please ignore this email.";

        $mail->send();
        $msg = "<p style='color:green; font-weight:bold;'>A secure code has been sent to your email!</p>";
        $step = 2;
    } catch (Exception $e) {
        $msg = "<p style='color:red; font-weight:bold;'>Connection failed. <br> Error: {$mail->ErrorInfo}</p>";
        $step = 1;
    }
}

// Step 2: Verify Code and Update Password (WITH HASHING)
if (isset($_POST['update_password'])) {
    $user_code = mysqli_real_escape_string($conn, $_POST['verify_code']);
    $new_pass = $_POST['new_password']; // Don't escape yet, we hash it
    $confirm_pass = $_POST['confirm_password'];

    $check_query = mysqli_query($conn, "SELECT reset_code FROM login_table WHERE email = '$email'");
    $row = mysqli_fetch_assoc($check_query);

    if ($row['reset_code'] !== $user_code) {
        $msg = "<p style='color:red; font-weight:bold;'>Invalid verification code!</p>";
        $step = 2;
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "<p style='color:red; font-weight:bold;'>Passwords do not match!</p>";
        $step = 2;
    } else {
        // --- SECURITY FIX: HASH THE PASSWORD ---
        // This ensures compatibility with password_verify() in login.php
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE login_table SET password = '$hashed_password', reset_code = NULL WHERE email = '$email'";
        mysqli_query($conn, $update_query);
        
        echo "<script>
                alert('Password updated and secured successfully!');
                window.location.href='../login.php';
              </script>";
        exit();
    }
}

$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_image = !empty($user['profile_pic']) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .reset-container { max-width: 450px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #30e403; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 11px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-reset { width: 100%; padding: 12px; background: #30e403; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 14px; transition: 0.3s; }
        .btn-reset:hover { background: #28c902; }
        
        .has_dropdown::after { content: '▼'; float: right; font-size: 9px; color: rgba(255,255,255,0.7); margin-top: 2px; }
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        
        /* PROFESSIONAL FOOTER STYLING */
        .main_footer {
            background: #fdfdfd;
            margin-top: 40px;
            padding: 25px 0;
            text-align: center;
            border-top: 1px solid #e0e4e8;
            color: #888;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .main_footer strong {
            color: #0e5001;
            font-weight: 600;
        }
        .footer_divider {
            margin: 0 10px;
            color: #ccc;
        }
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
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU </h4></div>       
            </div>
            <div class="logout_btn"><a href="../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu active" id="profile">
                        <li><a href="admin_update_profile.php">Update Profile</a></li>
                        <li><a href="#" style="color:#30e403;">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admission')">ACCEPTANCE FEE</button>
                    <ul class="submenu" id="admission">
                        <li><a href="verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admission')">ADMISSION LETTER</button>
                    <ul class="submenu" id="admission">
                        <li><a href="admin/offer_letter.php">Upload Credentials</a></li>
                        <li><a href="#">Print Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="verify_credentials.php">Upload Credentials</a></li>
                        <li><a href="verify_olevel.php">O'level Verification</a></li>
                        <li><a href="confirm_dues.php">Pay Faculty Dues</a></li>
                        <li><a href="#">Four File Clearance</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="approve_dept.php">Pay Departmental Dues</a></li>
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

            <footer class="main_footer">
                <div>
                    Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> 
                    <span class="footer_divider">|</span> 
                    Powered by <strong>NACOS President</strong>
                </div>
                <div style="margin-top: 5px; font-size: 10px; color: #bbb; text-transform: uppercase;">
                    Official Student Management & Clearance Portal
                </div>
            </footer>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
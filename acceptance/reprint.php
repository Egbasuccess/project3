<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];

// 1. Fetch data for logic and sidebar UI
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);

$fullname = $user['fullname'] ?? $email;
$student_upload = $user['acceptance_receipt'] ?? ""; 
$verified_file = $user['verified_receipt'] ?? "";   
$user_image = !empty($user['profile_pic']) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// Path for the file the admin uploaded
$file_path = "../asset/uploads/verified_receipts/" . $verified_file;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reprint Receipt - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Consistent Sidebar Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; display: block; text-decoration: none;
        }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* Main Content UI */
        .reprint-card { max-width: 600px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; border-top: 5px solid #30e403; }
        .status-icon { font-size: 50px; margin-bottom: 20px; }
        .btn-download { display: block; width: 100%; padding: 15px; background: #0026ff; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; margin-top: 20px; }
        .btn-upload { display: block; width: 100%; padding: 15px; background: #30e403; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" alt="Profile" style="width:100%; height:100%; border-radius: 50%; object-fit: cover;"></div>
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
                    <ul class="submenu" id="profile">
                        <li><a href="../profile/update.php">Update Profile</a></li>
                        <li><a href="../profile/resetpass.php">Change Password</a></li>
                    </ul>
                </li>

                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">ACCEPTANCE FEE</button>
                    <ul class="submenu active" id="acceptance">
                        <?php if (empty($student_upload)): ?>
                            <li><a href="uploadacceptance.php">Upload Remita Receipt</a></li>
                        <?php endif; ?>
                        <li><a href="reprint.php" style="color:#30e403;">Reprint Original Receipt</a></li>
                    </ul>
                </li>

                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">ADMISSION LETTER</button>
                    <ul class="submenu" id="admission">
                        <li><a href="#">Upload Credentials</a></li>
                        <li><a href="#">Print Admission Letter</a></li>
                    </ul>
                </li>

                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="#">Upload Credentials</a></li>
                        <li><a href="#">O'level Verification</a></li>
                        <li><a href="#">Pay Faculty Dues</a></li>
                        <li><a href="#">Four File Clearance</a></li>
                    </ul>
                </li>

                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="#">Pay Departmental Dues</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <div class="reprint-card">
                <?php if (empty($student_upload)) : ?>
                    <div class="status-icon">❌</div>
                    <h3>Remita Receipt Missing</h3>
                    <p style="color:#666;">You haven't uploaded your Acceptance Remita receipt yet.</p>
                    <a href="uploadacceptance.php" class="btn-upload">GO TO UPLOAD PAGE</a>

                <?php elseif (!empty($student_upload) && empty($verified_file)) : ?>
                    <div class="status-icon">⏳</div>
                    <h3 style="color: #f39c12;">Awaiting Verification</h3>
                    <p style="color:#666;">Your receipt has been submitted. Please wait for the Admin to verify and upload your original receipt.</p>
                    <a href="../user.php" class="btn-upload" style="background:#999;">BACK TO DASHBOARD</a>

                <?php else : ?>
                    <div class="status-icon">✅</div>
                    <h3 style="color: #30e403;">Official Receipt Ready</h3>
                    <p style="color:#666;">Your payment has been verified. You can now download your original receipt.</p>
                    <a href="<?= $file_path ?>" class="btn-download" download>DOWNLOAD ORIGINAL RECEIPT (PDF)</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            const menu = document.getElementById(id);
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
// 1. Connection and Security
include('../connection.php'); 
session_start();

// Ensure it's a student (user) accessing this
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];

// 2. FETCH DATA for the Logged-in Student
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);

$fullname = $user['fullname'] ?? "Student";
$admission_letter = $user['admission_letter'] ?? "";
$admission_status = $user['admission_status'] ?? "Pending";

// Handle Profile Image for consistency
$user_image = !empty($user['profile_pic']) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Maintain sidebar consistency */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; display: block; text-decoration: none;
        }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* Document Display Area */
        .letter_container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 800px;
            margin: 20px auto;
        }
        .letter_preview {
            width: 100%;
            height: 500px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .print_btn {
            background: #0e5001;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }
        .print_btn:hover { background: #30e403; }
        
        .status_box {
            padding: 20px;
            border-radius: 5px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" style="width:100%; height:100%; object-fit: cover;"></div>
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
                <li class="menu_item"><a href="../user.php" class="dash_link">Dashboard</a></li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">Profile Details</button>
                    <ul class="submenu" id="profile">
                        <li><a href="admin_update_profile.php">Update Profile</a></li>
                        <li><a href="admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">ADMISSION LETTER</button>
                    <ul class="submenu active" id="admission">
                        <li><a href="upload_credentials.php">Upload Credentials</a></li>
                        <li><a href="#" style="color:#30e403;">Print Admission Letter</a></li>
                    </ul>
                </li>
                </ul>
        </aside>

        <div class="body_div">
            <h3 style="color: #0e5001; border-bottom: 2px solid #0e5001; margin-bottom: 20px; padding-bottom: 5px;">OFFICIAL ADMISSION LETTER</h3>

            <div class="letter_container">
                <?php if (!empty($admission_letter)): ?>
                    <p style="margin-bottom: 15px; font-weight: bold; color: #333;">Congratulations! Your Admission Letter has been issued.</p>
                    
                    <iframe src="../asset/uploads/final_admission_letters/<?= $admission_letter ?>" class="letter_preview"></iframe>
                    
                    <br>
                    
                    <a href="../asset/uploads/final_admission_letters/<?= $admission_letter ?>" download class="print_btn">
                        📥 DOWNLOAD & PRINT ADMISSION LETTER
                    </a>

                <?php else: ?>
                    <div class="status_box">
                        <h4>Awaiting Review</h4>
                        <p>Your documents are currently being verified by the Faculty Admin. Once approved, your official admission letter will appear here for download.</p>
                    </div>
                <?php endif; ?>
            </div>

            <footer class="main_footer" style="text-align: center; margin-top: 50px; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #eee;">
                Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> | Powered by <strong>NACOS President</strong>
            </footer>
        </div>
    </div>

    <script>
        // Maintained toggle script
        function toggleSubmenu(id) {
            document.querySelectorAll('.submenu').forEach(sub => {
                if(sub.id !== id) sub.classList.remove('active');
            });
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
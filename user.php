<?php
include('connection.php');
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user'];

$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);

$fullname = $user['fullname'] ?? $email;
$user_image = (!empty($user['profile_pic'])) ? "asset/images/profiles/" . $user['profile_pic'] : "asset/images/user_icon.png";

/**
 * CLEARANCE PROGRESS LOGIC
 */
$acceptance_status = (!empty($user['verified_receipt'])) ? "Completed" : "Pending";
$admission_status = (!empty($user['admission_letter'])) ? "Completed" : "Pending";
$faculty_status = (isset($user['faculty_status']) && $user['faculty_status'] == 'Cleared') ? "Completed" : "Pending";
$dept_status = (isset($user['dept_status']) && $user['dept_status'] == 'Cleared') ? "Completed" : "Pending";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Faculty of Computing</title>
    <link href="asset/css/user.css" rel="stylesheet">
    <style>
        /* Sidebar Menu Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; transition: 0.3s;
            display: block; text-decoration: none;
        }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; transition: 0.3s; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* PROGRESS TRACKER STYLING */
        .progress-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 20px; }
        .tracker-title { font-size: 18px; color: #0e5001; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .step-list { display: flex; justify-content: space-between; position: relative; list-style: none; padding: 0; }
        .step-item { flex: 1; text-align: center; position: relative; }
        .step-item:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 3px; background: #e0e0e0; z-index: 1; }
        .step-icon { width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; position: relative; z-index: 2; font-weight: bold; }
        .step-label { font-size: 12px; font-weight: bold; color: #666; }
        .step-status { font-size: 10px; text-transform: uppercase; display: block; margin-top: 5px; }

        .Completed .step-icon { background: #30e403; }
        .Completed .step-label { color: #0e5001; }
        .Completed .step-status { color: #30e403; }
        .Completed:not(:last-child)::after { background: #30e403; }
        .Pending .step-icon { background: #f39c12; }
        .Pending .step-status { color: #f39c12; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic">
                <img src="<?php echo $user_image; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div class="user_caption"><span><?php echo htmlspecialchars($fullname); ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU</h4></div>       
            </div>
            <div class="logout_btn">
                <a href="logout.php"><button>Logout</button></a>
            </div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item">
                    <a href="user.php" class="dash_link">DASHBOARD</a>
                </li>

                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">Profile Details</button>
                    <ul class="submenu" id="profile">
                        <li><a href="profile/update.php">Update Profile</a></li>
                        <li><a href="profile/resetpass.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="acceptance/uploadacceptance.php">Upload Remita Receipt</a></li>
                        <li><a href="acceptance/reprint.php">Reprint Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="admission/admission_letter.php">Upload Credentials</a></li>
                        <li><a href="acceptance/reprint.php">Print Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">Faculty Clearance</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="faculty/upload_credentials.php">Upload Credentials</a></li>
                        <li><a href="faculty/olevel_verification.php">O'level Verification</a></li>
                        <li><a href="faculty/faculty_dues.php">Pay Faculty Dues</a></li>
                        <li><a href="faculty/four_files.php">Four File Clearance</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('department')">Departmental Clearance</button>
                    <ul class="submenu" id="department">
                        <li><a href="#">Pay Departmental Dues</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2>Welcome, <?php echo htmlspecialchars($fullname); ?></h2>
            <p>Monitor your clearance progress below.</p>

            <div class="progress-container">
                <h3 class="tracker-title">Clearance Progress Tracker</h3>
                <ul class="step-list">
                    <li class="step-item <?php echo $acceptance_status; ?>">
                        <div class="step-icon"><?php echo ($acceptance_status == "Completed") ? "✓" : "1"; ?></div>
                        <span class="step-label">Acceptance Fee</span>
                        <span class="step-status"><?php echo $acceptance_status; ?></span>
                    </li>
                    <li class="step-item <?php echo $admission_status; ?>">
                        <div class="step-icon"><?php echo ($admission_status == "Completed") ? "✓" : "2"; ?></div>
                        <span class="step-label">Admission Letter</span>
                        <span class="step-status"><?php echo $admission_status; ?></span>
                    </li>
                    <li class="step-item <?php echo $faculty_status; ?>">
                        <div class="step-icon"><?php echo ($faculty_status == "Completed") ? "✓" : "3"; ?></div>
                        <span class="step-label">Faculty Clearance</span>
                        <span class="step-status"><?php echo $faculty_status; ?></span>
                    </li>
                    <li class="step-item <?php echo $dept_status; ?>">
                        <div class="step-icon"><?php echo ($dept_status == "Completed") ? "✓" : "4"; ?></div>
                        <span class="step-label">Departmental</span>
                        <span class="step-status"><?php echo $dept_status; ?></span>
                    </li>
                </ul>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #e8f5e9; border-left: 5px solid #30e403;">
                <p style="font-size: 14px; color: #2e7d32;"><strong>Note:</strong> Once a stage is marked as <span style="color:#30e403; font-weight:bold;">Completed</span>, the next stage will be activated for processing.</p>
            </div>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            const allSubmenus = document.querySelectorAll('.submenu');
            allSubmenus.forEach(menu => {
                if (menu.id !== id) menu.classList.remove('active');
            });

            const selectedMenu = document.getElementById(id);
            if (selectedMenu) {
                selectedMenu.classList.toggle('active');
            }
        }
    </script>
</body>
</html>
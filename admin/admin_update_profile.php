<?php
// 1. Corrected path to reach connection.php from the 'admin' folder
include('../connection.php'); 
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['admin'];
$msg = "";

// Handle Form Submission
if (isset($_POST['update_profile'])) {
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $lga = mysqli_real_escape_string($conn, $_POST['lga'] ?? '');
    $hometown = mysqli_real_escape_string($conn, $_POST['hometown'] ?? '');
    $p_address = mysqli_real_escape_string($conn, $_POST['p_address'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $c_address = mysqli_real_escape_string($conn, $_POST['c_address'] ?? '');
    $genotype = $_POST['genotype'] ?? '';
    $religion = mysqli_real_escape_string($conn, $_POST['religion'] ?? '');

    // Handle Image Upload
    $profile_pic_sqltxt = "";
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../asset/images/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = "profile_" . time() . "." . $file_ext; 
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $profile_pic_sqltxt = ", profile_pic='$file_name'";
        }
    }

    $update_query = "UPDATE login_table SET 
        gender='$gender', dob='$dob', state_of_origin='$state', 
        lga='$lga', hometown='$hometown', permanent_address='$p_address', 
        phone='$phone', contact_address='$c_address', genotype='$genotype', 
        religion='$religion' $profile_pic_sqltxt WHERE email='$email'";

    if (mysqli_query($conn, $update_query)) {
        $msg = "<p style='color:green; font-weight:bold;'>Profile and Picture Updated Successfully!</p>";
    }
}

// 2. FETCH DATA (IMPORTANT: This must run AFTER the update logic above)
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;

// Fixed image paths for the 'admin' subfolder
if (!empty($user['profile_pic'])) {
    $user_image = "../asset/images/profiles/" . $user['profile_pic'];
} else {
    $user_image = "../asset/images/user_icon.png";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; display: block; text-decoration: none;
        }
         .has_dropdown::after { 
            content: '▼'; 
            float: right; 
            font-size: 9px; 
            color: rgba(255,255,255,0.7); 
            margin-top: 2px;
        }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        
        .main_content_flex { display: flex; gap: 30px; align-items: flex-start; }
        .form_side { flex: 2; }
        .photo_side { flex: 1; background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #ddd; }
        .update-form-container { display: flex; flex-wrap: wrap; gap: 15px; }
        .form-column { flex: 1; min-width: 250px; }
        .form-group-update { margin-bottom: 12px; display: flex; flex-direction: column; }
        .form-group-update label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group-update input, .form-group-update select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .preview_img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #30e403; }

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
            <div class="profile_pic"><img src="<?= $user_image ?>" alt="Profile" style="width:100%; height:100%;  object-fit: cover;"></div>
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
                <li class="menu_item"><a href="../admin.php" class="dash_link">Dashboard</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('profile')">Profile Details</button>
                    <ul class="submenu active" id="profile">
                        <li><a href="admin_update_profile.php" style="color:#30e403;">Update Profile</a></li>
                        <li><a href="admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('acceptance')">ACCEPTANCE FEE</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="offer_letter.php">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="faculty_admin/verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="faculty_admin/verify_olevel.php">Approve O'level</a></li>
                        <li><a href="faculty_admin/confirm_fac_dues.php">Issue Faculty Receipt</a></li>
                        <li><a href="faculty_admin/confirm_fourfile.php">Confirm Four File Submission</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="approve_dept.php">Issue Dept. Dues Receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h3 style="color: #0026ff; border-bottom: 2px solid #0026ff; margin-bottom: 20px; padding-bottom: 5px;">ADMIN PERSONAL INFORMATION</h3>
            
            <?php echo $msg; ?>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="main_content_flex">
                    <div class="form_side">
                        <div class="form-group-update">
                            <label>Full Name:</label>
                            <input type="text" value="<?= htmlspecialchars($fullname) ?>" readonly style="background:#eee;">
                        </div>

                        <div class="form-group-update">
                            <label>Email Address:</label>
                            <input type="text" value="<?= htmlspecialchars($email) ?>" readonly style="background:#eee;">
                        </div>
                        
                        <div class="update-form-container">
                            <div class="form-column">
                                <div class="form-group-update">
                                    <label>Gender:</label>
                                    <select name="gender" required>
                                        <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                                <div class="form-group-update"><label>Date of Birth:</label><input type="date" name="dob" value="<?= $user['dob'] ?? '' ?>" required></div>
                                <div class="form-group-update"><label>State of Origin:</label><input type="text" name="state" value="<?= $user['state_of_origin'] ?? '' ?>" required></div>
                            </div>
                            <div class="form-column">
                                <div class="form-group-update"><label>LGA:</label><input type="text" name="lga" value="<?= $user['lga'] ?? '' ?>" required></div>
                                <div class="form-group-update"><label>Phone Number:</label><input type="text" name="phone" value="<?= $user['phone'] ?? '' ?>" required></div>
                                <div class="form-group-update"><label>Religion:</label><input type="text" name="religion" value="<?= $user['religion'] ?? '' ?>" required></div>
                            </div>
                        </div>
                    </div>

                    <div class="photo_side">
                        <img src="<?= $user_image ?>" class="preview_img" id="output">
                        <p style="font-size: 12px; margin-top: 10px;">Profile Picture Upload</p>
                        <input type="file" name="image" accept="image/*" onchange="document.getElementById('output').src = window.URL.createObjectURL(this.files[0])">
                        <br><br>
                        <button type="submit" name="update_profile" style="background:#30e403; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-weight:bold;">Update Profile</button>
                    </div>
                </div>
            </form>
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
            document.querySelectorAll('.submenu').forEach(sub => {
                if(sub.id !== id) sub.classList.remove('active');
            });
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
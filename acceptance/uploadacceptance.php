<?php
// 1. Database Connection (Step up one level to reach project root)
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// Handle Receipt Upload logic
if (isset($_POST['upload_receipt'])) {
    if (!empty($_FILES['receipt']['name'])) {
        $target_dir = "../asset/uploads/acceptance/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $allowed_ext = array("pdf", "jpg", "jpeg", "png");

        if (in_array($file_ext, $allowed_ext)) {
            $file_name = "acceptance_" . time() . "." . $file_ext; 
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
                $update = mysqli_query($conn, "UPDATE login_table SET acceptance_receipt = '$file_name' WHERE email = '$email'");
                if ($update) {
                    $msg = "<p style='color:green; font-weight:bold;'>Remita Receipt Uploaded Successfully!</p>";
                }
            } else {
                $msg = "<p style='color:red;'>Failed to move uploaded file.</p>";
            }
        } else {
            $msg = "<p style='color:red;'>Invalid file type. Only PDF, JPG, and PNG allowed.</p>";
        }
    } else {
        $msg = "<p style='color:red;'>Please select a file to upload.</p>";
    }
}

// Fetch user data for UI
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_image = !empty($user['profile_pic']) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
$has_uploaded = !empty($user['acceptance_receipt']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Acceptance Fee - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Sidebar Pattern Consistency */
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

        /* Content Styling */
        .upload-card { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #30e403; }
        .upload-box { border: 2px dashed #ccc; padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
        .btn-action { width: 100%; padding: 15px; background: #30e403; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .btn-download { background: #0026ff; text-decoration: none; display: block; text-align: center; margin-top: 20px; color: white; padding: 12px; border-radius: 4px;}
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px; }
        .status-success { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" alt="Profile" style="width:100%; height:100%; object-fit: cover;"></div>
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
                <li class="menu_item">
                    <a href="../user.php" class="dash_link">DASHBOARD</a>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../profile/update.php">Update Profile</a></li>
                        <li><a href="../profile/resetpass.php">Change Password</a></li>
                    </ul>
                </li>
                
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu active" id="acceptance">
                        <li><a href="#" style="color:#30e403;">Upload Remita Receipt</a></li>
                        <li><a href="../acceptance/reprint.php">Reprint Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="../admission/admission_letter.php">Upload Credentials</a></li>
                        <li><a href="../admission/download_admission.php">Print Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">Faculty Clearance</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="../faculty/upload_credentials.php">Upload Credentials</a></li>
                        <li><a href="../faculty/olevel_verification.php">O'level Verification</a></li>
                        <li><a href="../faculty/faculty_dues.php">Pay Faculty Dues</a></li>
                        <li><a href="../faculty/four_files.php">Get Four Files</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('department')">Departmental Clearance</button>
                    <ul class="submenu" id="department">
                        <li><a href="../department/dept_dues.php">Pay Departmental Dues</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('schoolfess')">School Fees</button>
                    <ul class="submenu" id="schoolfess">
                        <li><a href="../fees/schoolfee.php">Get Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu" id="payment">
                        <li><a href="../payment/medical_fee.php">Pay Medical Fee</a></li>
                        <li><a href="../payment/orientation_fee.php">Pay Orientation Fee</a></li>
                        <li><a href="../payment/etracking_fee.php">Pay E-tracking Fee</a></li>
                        <li><a href="../payment/olevel_original_receipt.php">O'level verification Original receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <div class="upload-card">
                <h3 style="color: #0026ff; border-bottom: 2px solid #0026ff; padding-bottom: 10px;">Acceptance Fee Receipt</h3>
                <p style="font-size: 13px; color: #666; margin: 15px 0;">Upload your Remita Payment Receipt to process your Original Acceptance Letter.</p>
                
                <?= $msg ?>

                <?php if ($has_uploaded): ?>
                    <div class="status-badge status-success">✓ Uploaded</div>
                    <p style="font-size: 14px;">Receipt has been successfully recorded.</p>
                    <a href="generate_receipt.php" class="btn-download">DOWNLOAD ORIGINAL RECEIPT (PDF)</a>
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                <?php else: ?>
                    <div class="status-badge status-pending">Status: Pending Upload</div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="upload-box">
                        <input type="file" name="receipt" accept=".pdf, .jpg, .jpeg, .png" required>
                        <p style="font-size: 11px; color: #888; margin-top: 10px;">PDF, JPG, or PNG (Max 2MB)</p>
                    </div>
                    <button type="submit" name="upload_receipt" class="btn-action">UPLOAD RECEIPT</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../asset/js/main.js"></script>
</body>
</html>
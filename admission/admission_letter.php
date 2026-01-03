<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// Check if user has already submitted
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// Logic for the Tracker: Step 2 Completion check
$is_submitted = !empty($user['admission_docs_status']); // You can add this column to DB
$is_approved = !empty($user['admission_letter']); // Admin uploaded the final letter

if (isset($_POST['submit_docs'])) {
    $target_dir = "../asset/uploads/admission_docs/" . $user['id'] . "/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $all_uploaded = true;
    $file_keys = [
        'jamb_result', 'jamb_admission', 'olevel_result', 'acceptance_receipt',
        'supplementary_fee', 'lg_id', 'post_utme', 'acceptance_admission',
        'admission_notification', 'birth_certificate'
    ];

    foreach ($file_keys as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $new_name = $key . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES[$key]['tmp_name'], $target_dir . $new_name);
        } else if ($key != 'supplementary_fee') { // Supplementary is optional
            $all_uploaded = false;
        }
    }

    if ($all_uploaded) {
        mysqli_query($conn, "UPDATE login_table SET admission_docs_status = 'Submitted' WHERE email = '$email'");
        $msg = "<p class='success-msg'>All documents submitted successfully! Please wait for verification.</p>";
        header("Refresh: 2");
    } else {
        $msg = "<p class='error-msg'>Please upload all required documents.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter Requirements</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; transition: 0.3s;
            display: block; text-decoration: none;
        }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 300px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .file-input-group { display: flex; flex-direction: column; gap: 5px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .file-input-group label { font-size: 11px; font-weight: bold; color: #333; }
        .success-msg { color: green; font-weight: bold; background: #d4edda; padding: 10px; border-radius: 5px; }
        .error-msg { color: red; font-weight: bold; }
        .status-box { padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" style="width:100%; height:100%; object-fit:cover;"></div>
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
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">Profile Details</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../profile/update.php">Update Profile</a></li>
                        <li><a href="../profile/resetpass.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="../acceptance/uploadacceptance.php">Upload Remita Receipt</a></li>
                        <li><a href="../acceptance/reprint.php">Reprint Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')" style="color:#30e403;">Admission Letter</button>
                    <ul class="submenu active" id="admission">
                        <li><a href="#" style="color:#30e403;">Upload Credentials</a></li>
                        <li><a href="download_admission.php">Print Admission Letter</a></li>
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
            <h2>Collection of Admission Letter Requirements</h2>
            <p style="color: #666; font-size: 13px;">Please upload scanned copies of the following 10 documents.</p>
            <?= $msg ?>

            <?php if ($is_approved): ?>
                <div class="status-box" style="background: #d4edda; border: 1px solid green;">
                    <h3 style="color: green;">✔ Verification Complete</h3>
                    <p>Your documents have been verified. You can now download your admission letter.</p>
                    <a href="download_admission.php" class="view-btn" style="background:green; color:white; padding:10px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:10px;">Download Admission Letter</a>
                </div>
            <?php elseif ($is_submitted): ?>
                <div class="status-box" style="background: #fff3cd; border: 1px solid #ffeeba;">
                    <h3 style="color: #856404;">⏳ Verification Pending</h3>
                    <p>Your documents are being reviewed by the admin. Please check back later for your Admission Letter.</p>
                </div>
            <?php else: ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="upload-grid">
                        <div class="file-input-group"><label>1. JAMB RESULT</label><input type="file" name="jamb_result" required></div>
                        <div class="file-input-group"><label>2. JAMB ADMISSION LETTER</label><input type="file" name="jamb_admission" required></div>
                        <div class="file-input-group"><label>3. O'LEVEL RESULT</label><input type="file" name="olevel_result" required></div>
                        <div class="file-input-group"><label>4. ACCEPTANCE FEES RECEIPT</label><input type="file" name="acceptance_receipt" required></div>
                        <div class="file-input-group"><label>5. SUPPLEMENTARY FEE (IF ANY)</label><input type="file" name="supplementary_fee"></div>
                        <div class="file-input-group"><label>6. L.G IDENTIFICATION</label><input type="file" name="lg_id" required></div>
                        <div class="file-input-group"><label>7. EBSU POST UTME</label><input type="file" name="post_utme" required></div>
                        <div class="file-input-group"><label>8. ACCEPTANCE OF ADMISSION</label><input type="file" name="acceptance_admission" required></div>
                        <div class="file-input-group"><label>9. ADMISSION NOTIFICATION</label><input type="file" name="admission_notification" required></div>
                        <div class="file-input-group"><label>10. BIRTH CERTIFICATE</label><input type="file" name="birth_certificate" required></div>
                    </div>
                    <button type="submit" name="submit_docs" style="margin-top:20px; width:100%; padding:15px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">SUBMIT ALL DOCUMENTS</button>
                </form>
            <?php endif; ?>
            <!-- FOOTER SECTION --> 
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
    <script src="../asset/js/main.js"></script>
</body>
</html>
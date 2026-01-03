<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// 1. Fetch User Data
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_id = $user['id'];
// Check if user has already submitted
$current_status = $user['faculty_docs_status'] ?? 'Not Submitted';
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// 2. Handle Upload
if (isset($_POST['submit_faculty_docs'])) {
    $target_dir = "../asset/uploads/faculty_docs/" . $user_id . "/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_keys = ['jamb_adm', 'jamb_res', 'olevel_print', 'ebsu_acc', 'ebsu_adm', 'lg_id', 'post_utme', 'supp_form', 'attestation', 'birth_cert'];

    $upload_ok = true;
    foreach ($file_keys as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            $new_name = $key . "_" . time() . "." . $ext;
            if(!move_uploaded_file($_FILES[$key]['tmp_name'], $target_dir . $new_name)){
                $upload_ok = false;
            }
        } elseif ($key != 'supp_form') { 
            $upload_ok = false;
        }
    }

    if ($upload_ok) {
        mysqli_query($conn, "UPDATE login_table SET faculty_docs_status = 'Submitted' WHERE id = '$user_id'");
        echo "<script>alert('Credentials Uploaded Successfully!'); window.location.href = 'upload_credentials.php';</script>";
        exit();
    } else {
        $msg = "<p style='color:red; font-weight:bold; background:#ffe6e6; padding:10px; border-radius:5px;'>Please upload all required documents.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Credentials - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Sidebar Menu Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* Upload Section Styling */
        .upload-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .input-box { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .input-box label { display: block; font-size: 10px; font-weight: bold; color: #0e5001; margin-bottom: 5px; }

        /* New Feature Styles */
        .success-banner { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; margin-bottom: 20px; }
        .btn-next { display: inline-block; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: 0.3s; margin-top: 15px; }
        .btn-enabled { background: #0e5001; color: white; }
        .btn-enabled:hover { background: #30e403; }
        .btn-disabled { background: #cccccc; color: #666666; cursor: not-allowed; pointer-events: none; }
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
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
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
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="../admission/admission_letter.php">Upload Credentials</a></li>
                        <li><a href="../admission/download_admission.php">Print Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">Faculty Clearance</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="#" style="color:#30e403;">Upload Credentials</a></li>
                        <li><a href="olevel_verification.php">O'level Verification</a></li>
                        <li><a href="faculty_dues.php" >Pay Faculty Dues</a></li>
                        <li><a href="four_files.php">Get Four Files</a></li>
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
            <h2>Step 1: Faculty Clearance Credentials</h2>
            <?= $msg ?>

            <?php if ($current_status == 'Submitted'): ?>
                <div class="success-banner">
                    <h3 style="margin-top:0;">✅ Documents Uploaded Successfully</h3>
                    <p>Your credentials have been received. <strong>Status: Awaiting verification from the Faculty Officer.</strong></p>
                    <p style="font-size: 12px;">You can now proceed to the next stage of your clearance.</p>
                </div>
                <a href="olevel_verification.php" class="btn-next btn-enabled">PROCEED TO O'LEVEL VERIFICATION &rarr;</a>

            <?php else: ?>
                <p>Ensure all documents are scanned clearly in PDF or Image format.</p>
                <div class="upload-card">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="input-box"><label>1. JAMB Admission Letter</label><input type="file" name="jamb_adm" required></div>
                            <div class="input-box"><label>2. Original JAMB Results</label><input type="file" name="jamb_res" required></div>
                            <div class="input-box"><label>3. Online O'Level Printout</label><input type="file" name="olevel_print" required></div>
                            <div class="input-box"><label>4. EBSU Acceptance Receipt</label><input type="file" name="ebsu_acc" required></div>
                            <div class="input-box"><label>5. EBSU Admission Letter</label><input type="file" name="ebsu_adm" required></div>
                            <div class="input-box"><label>6. L.G. Identification</label><input type="file" name="lg_id" required></div>
                            <div class="input-box"><label>7. Post UTME Remita Receipt</label><input type="file" name="post_utme" required></div>
                            <div class="input-box"><label>8. Supplementary Receipt (Optional)</label><input type="file" name="supp_form"></div>
                            <div class="input-box"><label>9. Letter of Attestation</label><input type="file" name="attestation" required></div>
                            <div class="input-box"><label>10. Birth Cert/Age Declaration</label><input type="file" name="birth_cert" required></div>
                        </div>
                        <button type="submit" name="submit_faculty_docs" style="width:100%; padding:15px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:20px;">UPLOAD DOCUMENTS</button>
                    </form>
                    
                    <a href="#" class="btn-next btn-disabled">NEXT: O'LEVEL VERIFICATION (Locked)</a>
                </div>
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
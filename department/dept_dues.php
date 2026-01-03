<?php
include('../connection.php'); 
session_start();

// Security: Check if student is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// 1. Fetch User Data from login_table
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_id = $user['id'];
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// 2. Logic for Departmental Status
$has_uploaded_teller = !empty($user['dept_dues_teller']);
$is_receipt_ready = !empty($user['original_dept_dues_receipt']); // Updated column name

// 3. Handle Departmental Teller Upload
if (isset($_POST['submit_dept_teller'])) {
    $target_dir = "../asset/uploads/dept_payments/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['teller_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['teller_file']['name'], PATHINFO_EXTENSION));
        // Allowed extensions
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        
        if (in_array($ext, $allowed)) {
            $new_name = "dept_teller_" . $user_id . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($_FILES['teller_file']['tmp_name'], $target_dir . $new_name)) {
                // Update the teller column and the status
                mysqli_query($conn, "UPDATE login_table SET dept_dues_teller = '$new_name', dept_dues_status = 'Pending Verification' WHERE id = '$user_id'");
                $msg = "<p style='color:green; font-weight:bold; background:#eaffea; padding:10px; border-radius:5px;'>Departmental Teller uploaded successfully! Awaiting Admin Verification.</p>";
                header("Refresh: 2");
            } else {
                $msg = "<p style='color:red;'>Upload failed. Please check folder permissions.</p>";
            }
        } else {
            $msg = "<p style='color:red;'>Invalid file type. Please upload an Image or PDF.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departmental Dues - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /*Sidebar Menu Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* UI Components */
        .dues-container { display: flex; gap: 20px; margin-top: 20px; }
        .payment-info { flex: 1; background: #f0f4f0; padding: 20px; border-left: 5px solid #0e5001; border-radius: 5px; }
        .upload-section { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .bank-details { font-size: 14px; line-height: 1.8; color: #333; }
        .bank-details b { color: #0e5001; }
        .btn-download { display: inline-block; padding: 12px 25px; background: #0e5001; color: #fff; text-decoration: none; font-weight: bold; border-radius: 5px; margin-top: 15px; transition: 0.3s; }
        .btn-download:hover { background: #30e403; color: #000; }
        .status-icon { font-size: 40px; margin-bottom: 10px; }
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
                    <ul class="submenu active" id="department">
                        <li><a href="#" style="color:#30e403;">Pay Departmental Dues</a></li>
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
            <h2 style="color: #0e5001;">Departmental Dues Payment</h2>
            <p>Verify your departmental standing by uploading your payment teller.</p>
            <?= $msg ?>

            <div class="dues-container">
                <div class="payment-info">
                    <h3>Account Details</h3>
                    <div class="bank-details">
                        <p><b>Bank:</b> EBSU Microfinance Bank</p>
                        <p><b>Account Name:</b> Computer Science Dept. (NACOS)</p>
                        <p><b>Account Number:</b> 3351006604</p>
                        <p><b>Amount:</b> ₦3,500</p>
                        <hr style="border:0; border-top:1px solid #ccc; margin:10px 0;">
                        <p style="font-size:12px; color:#666;">Note: Please use your Full Name or Registration Number as the payment reference.</p>
                    </div>
                </div>

                <div class="upload-section">
                    <?php if ($is_receipt_ready): ?>
                        <div style="text-align: center; padding: 10px;">
                            <div class="status-icon">✅</div>
                            <h3 style="color: green;">Receipt Available</h3>
                            <p>Admin has verified your payment. You can now download your official departmental receipt.</p>
                            <a href="../asset/uploads/official_receipts/<?= $user['original_dept_dues_receipt'] ?>" class="btn-download" download>Download Original Dept Receipt</a>
                            <br><br>
                            <a href="../faculty/four_files.php" style="color:#0e5001; font-weight:bold; text-decoration:none;">Proceed to Next Stage →</a>
                        </div>

                    <?php elseif ($has_uploaded_teller): ?>
                        <div style="text-align: center; padding: 10px;">
                            <div class="status-icon">⏳</div>
                            <h3 style="color: #f39c12;">Awaiting Verification</h3>
                            <p>Your teller has been received. Please check back later for your official departmental dues receipt once the Departmental Officer approves it.</p>
                        </div>

                    <?php else: ?>
                        <h3>Upload Teller</h3>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <label style="font-size: 11px; font-weight: bold; color: #555;">Attach Bank Teller / Transfer Receipt (Image or PDF)</label><br><br>
                            <input type="file" name="teller_file" required style="margin-bottom: 20px; border: 1px solid #ddd; padding: 10px; width: 95%;">
                            <button type="submit" name="submit_dept_teller" style="width:100%; padding:14px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">SUBMIT DEPARTMENTAL TELLER</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
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
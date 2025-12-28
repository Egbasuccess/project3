<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// Fetch User Data
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_id = $user['id'];
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// Logic for status
$has_uploaded_teller = !empty($user['faculty_dues_teller']);
$is_receipt_ready = !empty($user['faculty_dues_receipt']);

// Handle Teller Upload
if (isset($_POST['submit_teller'])) {
    $target_dir = "../asset/uploads/faculty_payments/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['teller_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['teller_file']['name'], PATHINFO_EXTENSION));
        $new_name = "teller_" . $user_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['teller_file']['tmp_name'], $target_dir . $new_name)) {
            mysqli_query($conn, "UPDATE login_table SET faculty_dues_teller = '$new_name', faculty_dues_status = 'Pending Verification' WHERE id = '$user_id'");
            $msg = "<p style='color:green; font-weight:bold;'>Bank Teller uploaded successfully! Awaiting Admin Verification.</p>";
            header("Refresh: 2");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dues - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Sidebar Menu Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* Dues Container Styling */
        .dues-container { display: flex; gap: 20px; margin-top: 20px; }
        .payment-info { flex: 1; background: #f9f9f9; padding: 20px; border-left: 5px solid #0e5001; border-radius: 5px; }
        .upload-section { flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .bank-details { font-size: 14px; line-height: 1.8; color: #333; }
        .bank-details b { color: #0e5001; }
        .status-pill { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .btn-download { display: inline-block; padding: 10px 20px; background: #30e403; color: #000; text-decoration: none; font-weight: bold; border-radius: 5px; margin-top: 10px; }
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
                        <li><a href="upload_credentials.php">Upload Credentials</a></li>
                        <li><a href="olevel_verification.php">O'level Verification</a></li>
                        <li><a href="#" style="color:#30e403;">Pay Faculty Dues</a></li>
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
            <h2>Step 3: Faculty Dues Payment</h2>
            <p>Pay your dues into the bank account below and upload your bank teller/transfer receipt.</p>
            <?= $msg ?>

            <div class="dues-container">
                <div class="payment-info">
                    <h3>Payment Details</h3>
                    <div class="bank-details">
                        <p><b>Bank Name:</b> United Bank for Africa (UBA)</p>
                        <p><b>Account Name:</b> EBSU Faculty of Computing Dues</p>
                        <p><b>Account Number:</b> 2039485721</p>
                        <p><b>Amount:</b> ₦3,500.00</p>
                        <p><b>Description:</b> Faculty Dues / Name / Registration Number</p>
                    </div>
                </div>

                <div class="upload-section">
                    <?php if ($is_receipt_ready): ?>
                        <div style="text-align: center; padding: 20px;">
                            <h3 style="color: green;">✔ Payment Verified</h3>
                            <p>Your official Faculty Receipt is now ready for download.</p>
                            <a href="../asset/uploads/official_receipts/<?= $user['faculty_dues_receipt'] ?>" class="btn-download" download>Download Original Receipt</a>
                            <br><br>
                            <a href="four_files.php" style="color:#0e5001; font-weight:bold;">Proceed to Next Stage →</a>
                        </div>
                    <?php elseif ($has_uploaded_teller): ?>
                        <div style="text-align: center; padding: 20px;">
                            <h3 style="color: #f39c12;">⏳ Verification Pending</h3>
                            <p>You have uploaded your teller. Please wait while the admin verifies your payment and issues your receipt.</p>
                        </div>
                    <?php else: ?>
                        <h3>Upload Bank Teller</h3>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <label style="font-size: 12px; font-weight: bold;">Select Scanned Teller (Image or PDF)</label><br><br>
                            <input type="file" name="teller_file" required style="margin-bottom: 20px;">
                            <button type="submit" name="submit_teller" style="width:100%; padding:12px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">SUBMIT TELLER</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
   <script src="../asset/js/main.js"></script>
</body>
</html>
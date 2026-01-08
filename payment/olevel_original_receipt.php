<?php
// Path: payment/olevel_original_receipt.php
include('../connection.php');
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

$user_email = $_SESSION['user'];
$msg = "";

// 1. Fetch User and Payment Data using JOIN for Olevel columns
$query = mysqli_query($conn, "SELECT login_table.id, login_table.fullname, login_table.profile_pic,
    fees_payments.olevel_teller, fees_payments.olevel_status,
    fees_payments.olevel_official_receipt
    FROM login_table
    LEFT JOIN fees_payments ON login_table.id = fees_payments.user_id
    WHERE login_table.email = '$user_email'");

$user = mysqli_fetch_assoc($query);
$user_id = $user['id'];

// 2. Handle Olevel Teller Upload
if (isset($_POST['upload_olevel_teller'])) {
    $target_dir = "../asset/uploads/olevel_tellers/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['olevel_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['olevel_file']['name'], PATHINFO_EXTENSION));
        $filename = "olevel_teller_" . $user_id . "_" . time() . "." . $ext;
       
        if (move_uploaded_file($_FILES['olevel_file']['tmp_name'], $target_dir . $filename)) {
            // Check if record exists in fees_payments
            $check = mysqli_query($conn, "SELECT * FROM fees_payments WHERE user_id = '$user_id'");
           
            if (mysqli_num_rows($check) > 0) {
                $sql = "UPDATE fees_payments SET olevel_teller = '$filename', olevel_status = 'Pending' WHERE user_id = '$user_id'";
            } else {
                $sql = "INSERT INTO fees_payments (user_id, olevel_teller, olevel_status) VALUES ('$user_id', '$filename', 'Pending')";
            }
           
            if (mysqli_query($conn, $sql)) {
                header("Location: olevel_original_receipt.php?success=1");
                exit();
            }
        }
    }
}

if(isset($_GET['success'])) {
    $msg = "<p style='color:green; padding:12px; background:#e8f5e9; border-left:5px solid #28a745; border-radius:4px; font-weight:bold; margin-bottom:20px;'>✔ O'level Verification Teller uploaded successfully! Admin will process your original receipt soon.</p>";
}

$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O'level Verification Receipt - User Portal</title>
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
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" alt="User" style="width:100%; height:100%; object-fit: cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($user['fullname']); ?></span></div>
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
                        <li><a href="fees/schoolfee.php">Get Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu active" id="payment">
                        <li><a href="medical_fee.php">Pay Medical Fee</a></li>
                        <li><a href="orientation_fee.php">Pay Orientation Fee</a></li>
                        <li><a href="etracking_fee.php">Pay E-tracking Fee</a></li>
                        <li><a href="#" style="color:#30e403;">O'level verification Original receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2 style="color: #0e5001;">O'level Verification Fee</h2>
            <p style="font-size: 13px; color: #777; margin-bottom: 20px;">Upload your bank teller to receive your official O'level verification receipt from the faculty.</p>

            <?= $msg ?>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; border-top: 4px solid #0e5001; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <h4 style="margin-bottom: 10px;">Step 1:</h4>
                    <div style="background: #fff9c4; padding: 15px; border-radius: 5px; font-size: 13px; margin-bottom: 20px; border: 1px dashed #fbc02d;">
                        <p><strong>Upload Your O'level verification fee Remita Receipt</p>
                    </div>

                    <?php if (empty($user['olevel_teller'])): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 5px;">UPLOAD REMITA RECIEPT (Image/PDF):</label>
                            <input type="file" name="olevel_file" required style="margin-bottom: 15px; font-size: 12px;">
                            <button type="submit" name="upload_olevel_teller" style="background:#0e5001; color:white; border:none; padding:12px; border-radius:4px; width:100%; cursor:pointer; font-weight:bold;">UPLOAD PAYMENT EVIDENCE</button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 15px; background: #e3f2fd; color: #1976d2; border-radius: 5px; font-weight: bold; font-size: 12px; border: 1px solid #bbdefb;">
                            STATUS: <?= strtoupper($user['olevel_status']) ?> <br>
                            <small style="font-weight: normal;">Your Remita Receipt has been submitted for review.</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; border-top: 4px solid #0e5001; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <h4 style="margin-bottom: 15px;">Official Receipt</h4>
                    
                    <?php if (!empty($user['olevel_official_receipt'])): ?>
                        <div style="text-align: center; background: #e8f5e9; border: 1px solid #28a745; padding: 20px; border-radius: 8px;">
                             <p style="color: #28a745; font-size: 13px; margin-bottom: 15px; font-weight: bold;">✔ Your Official Verification Receipt is ready.</p>
                             <a href="../asset/uploads/olevel_receipts/<?= $user['olevel_official_receipt'] ?>" download style="display: inline-block; background: #28a745; color: white; padding: 12px 25px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px;">DOWNLOAD ORIGINAL RECEIPT</a>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 10px; color: #bbb; border: 2px dashed #eee; border-radius: 8px;">
                            <p style="font-size: 12px;">The original receipt will be available for download here once the Admin verifies your payment teller.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <footer class="main_footer">
                <div>
                    Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> 
                    <span class="footer_divider">|</span> 
                    Powered by <strong>NACOS President</strong>
                </div>
            </footer>
        </div>
    </div>
           
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('active');
        }
    </script>
</body>
</html>
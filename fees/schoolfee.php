<?php
// Path: fees/schoolfee.php
include('../connection.php'); 
session_start();

// Security: Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$user_email = $_SESSION['user'];
$msg = "";

// 1. Fetch user and payment data using JOIN
// We link login_table.id to fees_payments.user_id
$query = mysqli_query($conn, "SELECT login_table.*, fees_payments.school_fee_remita, fees_payments.school_fee_status, fees_payments.school_fee_official 
    FROM login_table 
    LEFT JOIN fees_payments ON login_table.id = fees_payments.user_id 
    WHERE login_table.email = '$user_email'");

$user = mysqli_fetch_assoc($query);
$user_id = $user['id'];

// 2. Handle Remita Receipt Upload
if (isset($_POST['upload_remita'])) {
    $target_dir = "../asset/uploads/school_fees_remita/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['remita_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['remita_file']['name'], PATHINFO_EXTENSION));
        $filename = "remita_" . $user_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['remita_file']['tmp_name'], $target_dir . $filename)) {
            
            // Check if a payment record already exists for this user
            $check_record = mysqli_query($conn, "SELECT * FROM fees_payments WHERE user_id = '$user_id'");
            
            if (mysqli_num_rows($check_record) > 0) {
                // Update existing record
                $sql = "UPDATE fees_payments SET school_fee_remita = '$filename', school_fee_status = 'Pending' WHERE user_id = '$user_id'";
            } else {
                // Insert new record
                $sql = "INSERT INTO fees_payments (user_id, school_fee_remita, school_fee_status) VALUES ('$user_id', '$filename', 'Pending')";
            }
            
            if (mysqli_query($conn, $sql)) {
                // Refresh data after upload
                header("Location: schoolfee.php?success=1");
                exit();
            }
        }
    }
}

if(isset($_GET['success'])) {
    $msg = "<p style='color:green; padding:12px; background:#e8f5e9; border-left:5px solid #28a745; border-radius:4px; font-weight:bold; margin-bottom:20px;'>✔ Remita Receipt uploaded! Awaiting Admin verification.</p>";
}

// Re-branding Paths (Go up one level)
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Fees - User Portal</title>
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
                    <ul class="submenu active" id="faculty">
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
                        <li><a href="#" style="color:#30e403;">Get Original Receipt</a></li>
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
            <h2 style="color: #0e5001;">School Fees Verification</h2>
            <p style="font-size: 13px; color: #777; margin-bottom: 20px;">Upload your Remita Receipt to receive your Official University Receipt.</p>

            <?= $msg ?>

            

            <div class="status_container" style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; border-top: 4px solid #0e5001; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <h4 style="margin-bottom: 15px; color: #333;">1. Remita Submission</h4>
                    <?php if (empty($user['school_fee_remita'])): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Ensure your RRR number is clearly visible on the document.</p>
                            <input type="file" name="remita_file" required style="margin-bottom: 20px; font-size: 12px;">
                            <button type="submit" name="upload_remita" style="background:#0e5001; color:white; border:none; padding:12px; border-radius:4px; width:100%; cursor:pointer; font-weight:bold;">SUBMIT FOR VERIFICATION</button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; border: 2px dashed #d4edda; padding: 20px;">
                            <p style="color: #28a745; font-weight: bold;">✔ Remita Uploaded</p>
                            <a href="../asset/uploads/school_fees_remita/<?= $user['school_fee_remita'] ?>" target="_blank" style="color: #007bff; font-size: 12px; text-decoration: underline;">View Uploaded Document</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; border-top: 4px solid #0e5001; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <h4 style="margin-bottom: 15px; color: #333;">2. Verification Status</h4>
                    <?php 
                        $status = $user['school_fee_status'] ?? 'Not Started';
                        $status_color = ($status == 'Verified') ? '#d4edda' : '#fff3cd';
                        $text_color = ($status == 'Verified') ? '#155724' : '#856404';
                    ?>
                    <div style="background: <?= $status_color ?>; color: <?= $text_color ?>; padding: 15px; border-radius: 5px; text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 20px;">
                        <?= strtoupper($status) ?>
                    </div>

                    <?php if ($status == 'Verified' && !empty($user['school_fee_official'])): ?>
                        <a href="../asset/uploads/official_school_receipts/<?= $user['school_fee_official'] ?>" download style="display: block; text-align: center; background: #28a745; color: white; padding: 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px;">DOWNLOAD OFFICIAL RECEIPT</a>
                    <?php else: ?>
                        <p style="font-size: 11px; color: #888; text-align: center; line-height: 1.5;">Once the Admin verifies your Remita payment, your official University receipt will be available for download here.</p>
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
<?php
// Path: admin/verify_medicals.php
include('../connection.php'); 
session_start();

// Security: Redirect if not an admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php"); 
    exit();
}

$msg = "";

// --- HANDLE MEDICAL VERIFICATION & RECEIPT UPLOAD ---
if (isset($_POST['verify_medical'])) {
    $user_id = $_POST['user_id'];
    
    // Generate a Unique Medical Tracking ID
    $tracking_id = "MED-" . date("Y") . "-" . strtoupper(substr(md5(uniqid()), 0, 5));
    
    // Path: up 1 level from admin/ folder to asset/
    $target_dir = "../asset/uploads/medical_receipts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['medical_receipt_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['medical_receipt_file']['name'], PATHINFO_EXTENSION));
        $filename = "med_official_" . $user_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['medical_receipt_file']['tmp_name'], $target_dir . $filename)) {
            // Update the unified fees_payments table
            $sql = "UPDATE fees_payments SET 
                    medical_official_receipt = '$filename', 
                    medical_tracking_id = '$tracking_id',
                    medical_status = 'Verified' 
                    WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $sql)) {
                $msg = "<p style='color:green; padding:12px; background:#e8f5e9; border-left:5px solid #28a745; border-radius:4px; font-weight:bold; margin-bottom:20px;'>✔ Medical payment verified! Tracking ID: $tracking_id generated and Receipt uploaded.</p>";
            }
        }
    }
}

// Fetch all students who have uploaded medical tellers
$query = mysqli_query($conn, "SELECT login_table.id, login_table.fullname, login_table.email, 
    fees_payments.medical_teller, fees_payments.medical_status, fees_payments.medical_tracking_id, fees_payments.medical_official_receipt 
    FROM login_table 
    INNER JOIN fees_payments ON login_table.id = fees_payments.user_id 
    WHERE fees_payments.medical_teller IS NOT NULL 
    ORDER BY fees_payments.medical_status DESC");

// Fetch admin info for Top Nav branding
$admin_email = $_SESSION['admin'];
$admin_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'"));
$admin_image = (!empty($admin_data['profile_pic'])) ? "../asset/images/profiles/" . $admin_data['profile_pic'] : "../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Medical Fees - Admin</title>
    <link href="../asset/css/admin.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: #30e403; background: rgba(255,255,255,0.05); }
        
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th { background: #0e5001; color: white; padding: 12px; font-size: 11px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        .status_pill { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }

        .has_dropdown::after { 
            content: '▼'; 
            float: right; 
            font-size: 9px; 
            color: rgba(255,255,255,0.7); 
            margin-top: 2px;
        }

        /* FOOTER STYLING */
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
            <div class="profile_pic"><img src="<?= $admin_image ?>" alt="Admin" style="width:100%; height:100%; object-fit: cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($admin_data['fullname']); ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU (ADMIN)</h4></div>       
            </div>
            <div class="logout_btn"><a href="../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="admin/admin_update_profile.php">Update Profile</a></li>
                        <li><a href="admin/admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
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
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('schoolfess')">School Fees</button>
                    <ul class="submenu" id="schoolfess">
                        <li><a href="issue_schoolfee_receipt.php">Issue Sch. Fee Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu active" id="payment">
                        <li><a href="#" style="color:#30e403;">Verify Medical Fee</a></li>
                        <li><a href="verify_orientation.php">Verify Orientation Fee</a></li>
                        <li><a href="verify_etracking.php">verify E-tracking Fee</a></li>
                        <li><a href="olevel_original_receipt.php">O'level verification Original receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>
        
        <div class="body_div">
            <h2 style="color: #0e5001;">Verify Medical Fee Payments</h2>
            <p style="font-size: 13px; color: #777;">Review student bank tellers, generate Unique Medical Tracking IDs, and upload official receipts.</p>

            <?= $msg ?>

            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Medical Teller</th>
                        <th>Status / Tracking ID</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                            <small><?= $row['email'] ?></small>
                        </td>
                        <td>
                            <a href="../asset/uploads/medical_tellers/<?= $row['medical_teller'] ?>" target="_blank" style="color:#007bff; font-weight:bold; text-decoration:none;">
                                👁️ VIEW TELLER
                            </a>
                        </td>
                        <td>
                            <span class="status_pill" style="<?= ($row['medical_status'] == 'Verified') ? 'background:#d4edda; color:#155724;' : 'background:#fff3cd; color:#856404;' ?>">
                                <?= strtoupper($row['medical_status']) ?>
                            </span>
                            <?php if(!empty($row['medical_tracking_id'])): ?>
                                <br><small style="font-weight:bold; color:#1976d2;">ID: <?= $row['medical_tracking_id'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['medical_status'] != 'Verified'): ?>
                                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:5px;">
                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                    <input type="file" name="medical_receipt_file" required style="font-size:10px;">
                                    <button type="submit" name="verify_medical" style="background:#28a745; color:white; border:none; padding:5px 10px; border-radius:3px; cursor:pointer; font-size:10px; font-weight:bold;">VERIFY & ISSUE</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: bold; font-size: 11px;">✔ RECEIPT ISSUED</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

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

    </script>
</body>
</html>
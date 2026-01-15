<?php
// PATH LOGIC: Go up 2 levels to reach connection.php
include('../../connection.php'); 
session_start();

// Security Check: Redirect if not an admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../../login.php"); 
    exit();
}

$msg = "";

// --- HANDLE RECEIPT UPLOAD BY ADMIN ---
if (isset($_POST['upload_receipt'])) {
    $student_id = $_POST['student_id'];
    
    // Path to root asset folder: up 3 levels from current folder (faculty_admin > admin > project3)
    $target_dir = "../../asset/uploads/official_receipts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['receipt_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION));
        $filename = "official_fac_receipt_" . $student_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $target_dir . $filename)) {
            $sql = "UPDATE login_table SET 
                    faculty_dues_receipt = '$filename', 
                    faculty_dues_status = 'Verified' 
                    WHERE id = '$student_id'";
            
            if (mysqli_query($conn, $sql)) {
                $msg = "<p style='color:green; padding:12px; background:#e8f5e9; border-left:5px solid #28a745; border-radius:4px; font-weight:bold; margin-bottom:20px;'>✔ Official Receipt uploaded successfully!</p>";
            }
        }
    }
}

// Fetch all students who have uploaded a teller
$query = mysqli_query($conn, "SELECT id, fullname, email, faculty_dues_teller, faculty_dues_status, faculty_dues_receipt FROM login_table WHERE faculty_dues_teller IS NOT NULL ORDER BY id DESC");

// Fetch admin info for Top Nav branding
$admin_email = $_SESSION['admin'];
$admin_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'");
$admin_data = mysqli_fetch_assoc($admin_query);
$admin_name = $admin_data['fullname'] ?? "Admin";

// Admin Image Path: up 3 levels to reach asset/
$admin_image = (!empty($admin_data['profile_pic'])) ? "../../asset/images/profiles/" . $admin_data['profile_pic'] : "../../../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Faculty Receipt - Admin Portal</title>
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <style>
     /* Sidebar Styling */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { 
            width: 100%; text-align: left; background: none; border: none; 
            color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; 
            font-weight: bold; text-transform: uppercase; display: block; text-decoration: none;
            box-sizing: border-box;
        }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        
        .has_dropdown::after { 
            content: '▼'; 
            float: right; 
            font-size: 9px; 
            color: rgba(255,255,255,0.7); 
            margin-top: 2px;
        }

        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: #30e403; background: rgba(255,255,255,0.05); }
        
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
            <div class="user_caption"><span><?= htmlspecialchars($admin_name); ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="../../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU (ADMIN PORTAL)</h4></div>       
            </div>
            <div class="logout_btn"><a href="../../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../admin_update_profile.php">Update Profile</a></li>
                        <li><a href="../admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="../verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="../offer_letter.php">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="verify_credentials.php" >Verify Credentials</a></li>
                        <li><a href="verify_olevel.php">Approve O'level</a></li>
                        <li><a href="#" style="color:#30e403;">Issue Faculty Receipt</a></li>
                        <li><a href="confirm_fourfile.php">Confirm Four File Submission</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="../approve_dept.php">Issue Dept. Dues Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('schoolfess')">School Fees</button>
                    <ul class="submenu" id="schoolfess">
                        <li><a href="../issue_schoolfee_receipt.php">Issue Sch. Fee Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu" id="payment">
                        <li><a href="../verify_medicals.php">Verify Medical Fee</a></li>
                        <li><a href="../verify_orientation.php">Pay Orientation Fee</a></li>
                        <li><a href="../verify_etracking.php">Pay E-tracking Fee</a></li>
                    </ul>
                </li>
            </ul>
        </aside>
        
        <div class="body_div">
            <h2 style="color: #0e5001; margin-bottom: 5px;">Faculty Dues Verification</h2>
            <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Review tellers and upload official faculty receipts.</p>
            
            <?= $msg ?>

            <table style="width: 100%; border-collapse: collapse; background: white;">
                <thead>
                    <tr>
                        <th style="background:#0e5001; color:white; padding:12px; font-size:11px; text-align:left;">STUDENT NAME</th>
                        <th style="background:#0e5001; color:white; padding:12px; font-size:11px; text-align:left;">TELLER (PROOF)</th>
                        <th style="background:#0e5001; color:white; padding:12px; font-size:11px; text-align:left;">STATUS</th>
                        <th style="background:#0e5001; color:white; padding:12px; font-size:11px; text-align:left;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td style="padding:12px; border-bottom:1px solid #eee;">
                            <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                            <small style="color:#888;"><?= $row['email'] ?></small>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #eee;">
                            <a href="../../asset/uploads/faculty_payments/<?= $row['faculty_dues_teller'] ?>" target="_blank" style="color:#007bff; font-weight:bold; text-decoration:none; font-size:12px;">
                                👁️ VIEW TELLER
                            </a>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #eee;">
                            <span style="padding:4px 8px; border-radius:4px; font-size:10px; font-weight:bold; <?= ($row['faculty_dues_status'] == 'Verified') ? 'background:#d4edda; color:#155724;' : 'background:#fff3cd; color:#856404;' ?>">
                                <?= strtoupper($row['faculty_dues_status']) ?>
                            </span>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #eee;">
                            <?php if (empty($row['faculty_dues_receipt'])): ?>
                                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                    <input type="file" name="receipt_file" required style="font-size:10px;">
                                    <button type="submit" name="upload_receipt" style="background:#28a745; color:white; border:none; padding:5px 10px; border-radius:3px; cursor:pointer; font-size:10px; font-weight:bold;">UPLOAD</button>
                                </form>
                            <?php else: ?>
                                <span style="color: green; font-weight: bold; font-size:12px;">✔ ISSUED</span>
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

    <script src="../../asset/js/main.js"></script>
</body>
</html>
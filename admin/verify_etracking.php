<?php
// Path: admin/verify_etracking.php
include('../connection.php'); 
session_start();

// Security: Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php"); 
    exit();
}

$msg = "";

// 1. Handle Official E-tracking Receipt Upload by Admin
if (isset($_POST['upload_etracking_receipt'])) {
    $user_id = $_POST['user_id'];
    $target_dir = "../asset/uploads/etracking_receipts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['official_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['official_file']['name'], PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        
        if (in_array($ext, $allowed)) {
            $new_name = "OFFICIAL_TRACK_" . $user_id . "_" . time() . "." . $ext;
            
            if (move_uploaded_file($_FILES['official_file']['tmp_name'], $target_dir . $new_name)) {
                // Update fees_payments table with Approved status and the new file
                $sql = "UPDATE fees_payments SET etracking_official_receipt = '$new_name', etracking_status = 'Approved' WHERE user_id = '$user_id'";
                if (mysqli_query($conn, $sql)) {
                    $msg = "<p style='color:green; padding:10px; background:#d4edda; border-radius:5px; margin-bottom:15px;'>✔ E-tracking Receipt uploaded and status updated to Approved!</p>";
                }
            } else {
                $msg = "<p style='color:red;'>Error: Could not save the file.</p>";
            }
        } else {
            $msg = "<p style='color:red;'>Error: Invalid file format (JPG, PNG, or PDF only).</p>";
        }
    }
}

// 2. Fetch admin data for profile section
$admin_email = $_SESSION['admin'];
$admin_query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$admin_email'");
$admin = mysqli_fetch_assoc($admin_query);
$admin_image = (!empty($admin['profile_pic'])) ? "../asset/images/profiles/" . $admin['profile_pic'] : "../asset/images/user_icon.png";

// 3. Fetch students who have uploaded E-tracking tellers
$students = mysqli_query($conn, "SELECT lt.id, lt.fullname, lt.email, fp.etracking_teller, fp.etracking_official_receipt, fp.etracking_status 
    FROM login_table lt 
    JOIN fees_payments fp ON lt.id = fp.user_id 
    WHERE fp.etracking_teller IS NOT NULL 
    ORDER BY lt.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify E-tracking Fee - Admin Portal</title>
    <link href="../asset/css/admin.css" rel="stylesheet">
    <style>
        /* Maintain Sidebar Consistency */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 800px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: #30e403; background: rgba(255,255,255,0.05); }

        /* Table UI */
        .admin-table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-table th { background: #0e5001; color: white; padding: 15px; text-align: left; font-size: 12px; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        .btn-view { padding: 6px 12px; background: #222; color: #fff; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .upload-form { display: flex; align-items: center; gap: 10px; background: #f4f4f4; padding: 8px; border-radius: 5px; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .bg-pending { background: #fff3cd; color: #856404; }
        .bg-approved { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $admin_image ?>" style="width:100%; height:100%; object-fit:cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($admin['fullname']) ?></span></div>
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
                <li class="menu_item"><a href="admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">Profile Details</button>
                    <ul class="submenu" id="profile">
                        <li><a href="admin_update_profile.php">Update Profile</a></li>
                        <li><a href="admin_resetpass.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="offer_letter.php">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">Faculty Clearance</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="faculty_admin/verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="faculty_admin/verify_olevel.php">O'level Verification</a></li>
                        <li><a href="faculty_admin/confirm_fac_dues.php">Issue Faculty Receipt</a></li>
                        <li><a href="faculty_admin/confirm_fourfile.php">Confirm Four File Submission</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('department')">Departmental Clearance</button>
                    <ul class="submenu" id="department">
                        <li><a href="approve_dept.php">Issue Dept. Dues Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('schoolfess')">School Fees</button>
                    <ul class="submenu" id="schoolfess">
                        <li><a href="issue_schoolfee_receipt.php">Issue Sch. Fee Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu active" id="payment">
                        <li><a href="verify_medicals.php">Verify Medical Fee</a></li>
                        <li><a href="verify_orientation.php">Pay Orientation Fee</a></li>
                        <li><a href="verify_etracking.php" style="color:#30e403;">Pay E-tracking Fee</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2 style="color: #0e5001;">E-tracking Payment Verification</h2>
            <p style="font-size: 13px; color: #666;">Verify student payments for E-tracking and upload the official digital receipts.</p>
            
            <?= $msg ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Status</th>
                        <th>User Teller</th>
                        <th>Upload Official Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($students)): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                            <small style="color:#888;"><?= htmlspecialchars($row['email']) ?></small>
                        </td>
                        <td>
                            <?php if($row['etracking_status'] == 'Approved'): ?>
                                <span class="status-badge bg-approved">Approved</span>
                            <?php else: ?>
                                <span class="status-badge bg-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="../asset/uploads/etracking_tellers/<?= $row['etracking_teller'] ?>" target="_blank" class="btn-view">View Teller</a>
                        </td>
                        <td>
                            <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="file" name="official_file" required style="font-size:10px; width: 140px;">
                                <button type="submit" name="upload_etracking_receipt" style="background:#0e5001; color:white; border:none; padding:5px 10px; cursor:pointer; font-size:10px; border-radius:3px; font-weight:bold;">UPLOAD</button>
                            </form>
                            <?php if(!empty($row['etracking_official_receipt'])): ?>
                                <div style="margin-top:5px; font-size:10px; color:#28a745;">Current: <?= $row['etracking_official_receipt'] ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($students) == 0): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px; color: #999;">No E-tracking payment tellers found in the database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <footer class="main_footer" style="margin-top: 50px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <div style="font-size: 12px; color: #888;">
                    Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> 
                    <span class="footer_divider">|</span> 
                    Powered by <strong>NACOS President</strong>
                </div>
            </footer>
        </div>
    </div>

    <script src="../asset/js/main.js"></script>
    <script>
        function toggleSubmenu(id) {
            let menu = document.getElementById(id);
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>
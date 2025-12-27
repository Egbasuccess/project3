<?php
// 1. Corrected Path: Step up one level to find connection.php
include('../connection.php'); 
session_start();

// Security Check
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle the Admin Upload of Original Receipt
if (isset($_POST['upload_original'])) {
    $student_id = $_POST['student_id'];
    
    if (!empty($_FILES['original_file']['name'])) {
        // Path adjusted to step up to assets folder in root
        $target_dir = "../asset/uploads/verified_receipts/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['original_file']['name'], PATHINFO_EXTENSION));
        $file_name = "original_" . $student_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['original_file']['tmp_name'], $target_file)) {
            // Update the verified_receipt column for the specific student
            $update = mysqli_query($conn, "UPDATE login_table SET verified_receipt = '$file_name' WHERE id = '$student_id'");
            if ($update) {
                $msg = "<p style='color:green; font-weight:bold; padding:10px; background:#d4edda; border-radius:5px;'>Original Receipt Uploaded Successfully!</p>";
            }
        } else {
            $msg = "<p style='color:red;'>File upload failed.</p>";
        }
    }
}

// 3. Fetch students who have uploaded an acceptance receipt
$query = "SELECT id, fullname, email, acceptance_receipt, verified_receipt FROM login_table WHERE user_type = 'user' AND acceptance_receipt IS NOT NULL ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Admin Info
$admin_email = $_SESSION['admin'];
$admin_data_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'");
$admin_data = mysqli_fetch_assoc($admin_data_query);
$admin_name = $admin_data['fullname'] ?? "Admin";
$admin_image = !empty($admin_data['profile_pic']) ? "../asset/images/profiles/" . $admin_data['profile_pic'] : "../asset/images/admin_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify RRR - Admin Portal</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Sidebar Styling Consistency */
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
        
        /* THE FIX: Ensure the arrow appears on buttons with dropdowns */
        .has_dropdown::after { content: ' ▼'; float: right; font-size: 10px; color: rgba(255,255,255,0.5); }

        /* Table and Status Branding */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 15px; border: 1px solid #eee; text-align: left; font-size: 13px; }
        th { background-color: #0e5001; color: white; text-transform: uppercase; }
        .view-btn { background: #0026ff; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .status-verified { color: #155724; background: #d4edda; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .status-pending { color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        
        /*FOOTER STYLING */
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
            <div class="profile_pic"><img src="<?= $admin_image ?>" style="width:100%; height:100%; object-fit: cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($admin_name) ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING (ADMIN PORTAL)</h4></div>       
            </div>
            <div class="logout_btn"><a href="../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admin_profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="admin_profile">
                        <li><a href="admin_update_profile.php">Update Profile</a></li>
                        <li><a href="admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu active" id="acceptance">
                        <li><a href="#" style="color:#30e403;">Verify Acceptance RRR</a></li>
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
                        <li><a href="verify_credentials.php">Upload Credentials</a></li>
                        <li><a href="verify_olevel.php">O'level Verification</a></li>
                        <li><a href="confirm_dues.php">Pay Faculty Dues</a></li>
                        <li><a href="#">Four File Clearance</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="approve_dept.php">Pay Departmental Dues</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h3 style="border-bottom: 2px solid #0e5001; padding-bottom: 10px; color: #0e5001;">Acceptance Fee Verification Portal</h3>
            <br>
            <?= $msg ?>

            <table>
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Student Receipt (RRR)</th>
                        <th>Verification Status</th>
                        <th>Upload Official Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                                <span style="color: #666; font-size: 11px;"><?= $row['email'] ?></span>
                            </td>
                            <td>
                                <a href="../asset/uploads/acceptance/<?= $row['acceptance_receipt'] ?>" target="_blank" class="view-btn">VIEW RRR</a>
                            </td>
                            <td>
                                <?php if(!empty($row['verified_receipt'])): ?>
                                    <span class="status-verified">COMPLETED</span>
                                <?php else: ?>
                                    <span class="status-pending">AWAITING</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                    <div style="display: flex; gap: 5px;">
                                        <input type="file" name="original_file" accept=".pdf" required style="font-size: 11px; width: 150px;">
                                        <button type="submit" name="upload_original" style="background: #0e5001; color:white; border:none; padding: 5px 10px; cursor:pointer; font-size:11px; border-radius: 3px;">SUBMIT</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px; color: #999;">No student receipts found to verify.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    <script>
        function toggleSubmenu(id) {
            document.querySelectorAll('.submenu').forEach(sub => {
                if(sub.id !== id) sub.classList.remove('active');
            });
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
// 1. Connection and Security
include('../connection.php'); 
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php"); 
    exit();
}

$msg = "";

// 2. Handle Admin Upload of the Final Admission Letter
if (isset($_POST['upload_letter'])) {
    $student_id = $_POST['student_id'];
    
    if (!empty($_FILES['final_letter']['name'])) {
        $target_dir = "../asset/uploads/final_admission_letters/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['final_letter']['name'], PATHINFO_EXTENSION));
        $file_name = "admission_letter_" . $student_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['final_letter']['tmp_name'], $target_file)) {
            // Update database: Set status to Approved and save the file name
            $update = mysqli_query($conn, "UPDATE login_table SET admission_status = 'Approved', admission_letter = '$file_name' WHERE id = '$student_id'");
            if ($update) {
                $msg = "<p style='color:green; font-weight:bold; padding:10px; background:#d4edda; border-radius:5px;'>Admission Letter Issued Successfully!</p>";
            }
        } else {
            $msg = "<p style='color:red;'>File upload failed.</p>";
        }
    }
}

// 3. Fetch students who have submitted documents
$query = "SELECT id, fullname, email, admission_docs_status, admission_letter FROM login_table WHERE user_type = 'user' AND admission_docs_status = 'Submitted' ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Admin Info
$admin_email = $_SESSION['admin'];
$admin_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'"));
$admin_name = $admin_data['fullname'] ?? "Admin";
$admin_image = !empty($admin_data['profile_pic']) ? "../asset/images/profiles/" . $admin_data['profile_pic'] : "../asset/images/admin_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Admission Letter - Admin Portal</title>
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
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }

        /* Table and Branding */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 12px; }
        th { background-color: #0e5001; color: white; }
        .doc-link { color: #0026ff; text-decoration: none; font-weight: bold; display: block; margin-bottom: 2px; }
        .doc-link:hover { text-decoration: underline; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .issued { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }

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
                    <button class="menu_btn" onclick="toggleSubmenu('admin_profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="admin_profile">
                        <li><a href="admin_update_profile.php">Update Profile</a></li>
                        <li><a href="admin_change_password.php">Change Password</a></li>
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
                    <ul class="submenu active" id="admission">
                        <li><a href="#" style="color:#30e403;">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('approve_faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu" id="approve_faculty">
                        <li><a href="verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="verify_olevel.php">Verify O'level</a></li>
                        <li><a href="confirm_dues.php">Confirm Dues Payment</a></li>
                        <li><a href="confirm_dues.php">Confirm four file</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="approve_dept.php">Verify Departmental Dues</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h3 style="color: #0e5001;">Admission Letter Issuance (Step 2 Verification)</h3>
            <p style="font-size: 13px; color: #666;">Review the 10 documents submitted by students and upload their official EBSU Admission Letter.</p>
            <?= $msg ?>
            <!-- TABLE SECTION --> 
            <table>
                <thead>
                    <tr>
                        <th>Student Information</th>
                        <th>Uploaded Documents (Review)</th>
                        <th>Current Status</th>
                        <th>Action: Issue Admission Letter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                                <?= $row['email'] ?>
                            </td>
                            <td>
                                <div style="max-height: 150px; overflow-y: auto; padding: 5px; border: 1px solid #f0f0f0; background: #fafafa;">
                                    <?php 
                                    $doc_path = "../asset/uploads/admission_docs/" . $row['id'] . "/";
                                    $docs = ['jamb_result', 'jamb_admission', 'olevel_result', 'acceptance_receipt', 'supplementary_fee', 'lg_id', 'post_utme', 'acceptance_admission', 'admission_notification', 'birth_certificate'];
                                    
                                    foreach($docs as $doc):
                                        // Find any file starting with this key
                                        $files = glob($doc_path . $doc . "_*.*");
                                        if($files):
                                            $filename = basename($files[0]);
                                            echo "<a href='$files[0]' target='_blank' class='doc-link'>📄 ".strtoupper(str_replace('_', ' ', $doc))."</a>";
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($row['admission_letter'])): ?>
                                    <span class="status-badge issued">LETTER ISSUED</span>
                                <?php else: ?>
                                    <span class="status-badge pending">AWAITING REVIEW</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                    <input type="file" name="final_letter" accept=".pdf" required style="font-size: 11px; margin-bottom: 5px;"><br>
                                    <button type="submit" name="upload_letter" style="background: #0e5001; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px;">UPLOAD ADMISSION LETTER</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px; color: #999;">No students have submitted admission documents yet.</td></tr>
                    <?php endif; ?>
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
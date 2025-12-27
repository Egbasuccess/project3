<?php
include('connection.php');
session_start();

// Security Check: Redirect if not an admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// 1. Handle Approval/Rejection Logic
if (isset($_POST['action'])) {
    $student_id = $_POST['id'];
    $status = $_POST['action'];
    $remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? 'Processed by Admin');

    $update_query = "UPDATE login_table SET status = '$status', remark = '$remark' WHERE id = '$student_id'";
    mysqli_query($conn, $update_query);
}

// 2. Fetch Summary Stats - Strict Logic
$total_students_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_table WHERE user_type = 'user'");
$total_students = mysqli_fetch_assoc($total_students_query)['count'];

$acceptance_done_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_table WHERE user_type = 'user' AND (verified_receipt IS NOT NULL AND verified_receipt != '')");
$acceptance_done = mysqli_fetch_assoc($acceptance_done_query)['count'];

$admission_done_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_table WHERE user_type = 'user' AND (admission_letter IS NOT NULL AND admission_letter != '')");
$admission_done = mysqli_fetch_assoc($admission_done_query)['count'];

$faculty_done_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_table WHERE user_type = 'user' AND (status = 'Faculty Cleared' OR status = 'Approved')");
$faculty_done = mysqli_fetch_assoc($faculty_done_query)['count'];

$dept_done_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM login_table WHERE user_type = 'user' AND status = 'Approved'");
$dept_done = mysqli_fetch_assoc($dept_done_query)['count'];

// 3. Fetch all students
$students_result = mysqli_query($conn, "SELECT * FROM login_table WHERE user_type = 'user' ORDER BY id DESC");

// Fetch admin info
$admin_email = $_SESSION['admin'];
$admin_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'");
$admin_data = mysqli_fetch_assoc($admin_query);
$admin_name = $admin_data['fullname'] ?? "Admin";

// Logic for admin image path
if (!empty($admin_data['profile_pic'])) {
    $admin_image = "asset/images/profiles/" . $admin_data['profile_pic'];
} else {
    $admin_image = "asset/images/user_icon.png";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Faculty of Computing</title>
    <link href="asset/css/admin.css" rel="stylesheet">
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

        /* Admin Table & Summary Card Styling */
        .summary_grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-top: 4px solid #30e403; text-align: center; }
        .card h4 { font-size: 9px; color: #666; margin-bottom: 5px; text-transform: uppercase; font-weight: bold; }
        .card .val { font-size: 22px; font-weight: bold; color: #0e5001; }

        .prog_dots { display: flex; gap: 5px; justify-content: center; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #ddd; border: 1px solid #ccc; }
        .dot.done { background: #30e403; border-color: #0e5001; }

        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        th { background-color: #0e5001; color: white; padding: 12px; font-size: 11px; text-align: left; text-transform: uppercase; }
        td { padding: 12px; border: 1px solid #eee; font-size: 13px; }

        .level-badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; display: inline-block; text-transform: uppercase; }
        .lvl-0 { background: #f8d7da; color: #721c24; } 
        .lvl-1 { background: #fff3cd; color: #856404; } 
        .lvl-2 { background: #d1ecf1; color: #0c5460; } 
        .lvl-3 { background: #d4edda; color: #155724; }

        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .status-msg { color: #999; font-style: italic; font-size: 11px; }

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
                <div class="logo"><img src="asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU  (ADMIN PORTAL)</h4></div>       
            </div>
            <div class="logout_btn"><a href="logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="#" style="color:#30e403;" class="dash_link">DASHBOARD</a></li>
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
                        <li><a href="admin/verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="admin/offer_letter.php">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu" id="faculty">
                        <li><a href="admin/faculty_admin/verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="admin/faculty_admin/verify_olevel.php">Approve O'level</a></li>
                        <li><a href="admin/faculty_admin/confirm_fac_dues.php">Issue Faculty Receipt</a></li>
                        <li><a href="admin/faculty_admin/confirm_fourfile.php">Confirm Four File Submission</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
                    <ul class="submenu" id="dept">
                        <li><a href="admin/approve_dept.php">Issue Dept. Dues Receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>
        
        <div class="body_div">
            <div class="summary_grid">
                <div class="card"><h4>Students</h4><div class="val"><?= $total_students ?></div></div>
                <div class="card"><h4>Acceptance</h4><div class="val"><?= $acceptance_done ?></div></div>
                <div class="card"><h4>Admission</h4><div class="val"><?= $admission_done ?></div></div>
                <div class="card"><h4>Faculty</h4><div class="val"><?= $faculty_done ?></div></div>
                <div class="card"><h4>Dept</h4><div class="val"><?= $dept_done ?></div></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Progress</th>
                        <th>Clearance Level</th>
                        <th>Action (Final Status)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($students_result)): 
                        $curr_status = $row['status'] ?? 'Pending';
                        $has_acc = !empty($row['verified_receipt']);
                        $has_adm = !empty($row['admission_letter']);
                        $is_fac = ($curr_status == 'Faculty Cleared' || $curr_status == 'Approved');
                        $is_dept = ($curr_status == 'Approved');
                        $is_fully_completed = ($has_acc && $has_adm && $is_fac && $is_dept);

                        if ($is_dept) { $lvl_text = "Dept. Cleared"; $lvl_css = "lvl-3"; }
                        elseif ($is_fac) { $lvl_text = "Faculty Cleared"; $lvl_css = "lvl-2"; }
                        elseif ($has_adm) { $lvl_text = "At Faculty Level"; $lvl_css = "lvl-2"; }
                        elseif ($has_acc) { $lvl_text = "At Admission Level"; $lvl_css = "lvl-1"; }
                        else { $lvl_text = "Acceptance Pending"; $lvl_css = "lvl-0"; }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                        <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                        <td>
                            <div class="prog_dots">
                                <div class="dot <?= $has_acc ? 'done' : '' ?>" title="Acceptance Fee"></div>
                                <div class="dot <?= $has_adm ? 'done' : '' ?>" title="Admission Letter"></div>
                                <div class="dot <?= $is_fac ? 'done' : '' ?>" title="Faculty Clearance"></div>
                                <div class="dot <?= $is_dept ? 'done' : '' ?>" title="Dept Clearance"></div>
                            </div>
                        </td>
                        <td><span class="level-badge <?= $lvl_css ?>"><?= $lvl_text ?></span></td>
                        <td>
                            <?php if ($is_fully_completed): ?>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button name="action" value="Approved" class="btn-action btn-approve">APPROVE</button>
                                    <button name="action" value="Rejected" class="btn-action btn-reject">REJECT</button>
                                </form>
                            <?php else: ?>
                                <span class="status-msg">Clearance Incomplete</span>
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

    <script src="asset/js/main.js"></script>
</body>
</html>
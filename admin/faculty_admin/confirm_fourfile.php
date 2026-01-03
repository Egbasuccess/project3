<?php
include('../../connection.php');
session_start();

// Security Check: Redirect if not an admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../../login.php");
    exit();
}

// Fetch admin info
$admin_email = $_SESSION['admin'];
$admin_query = mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'");
$admin_data = mysqli_fetch_assoc($admin_query);
$admin_name = $admin_data['fullname'] ?? "Admin";
$admin_image = !empty($admin_data['profile_pic']) ? "../../asset/images/profiles/" . $admin_data['profile_pic'] : "../../asset/images/user_icon.png";

$message = "";

// 1. HANDLE CONFIRMATION ACTIONS
if (isset($_POST['update_status'])) {
    $sid = $_POST['student_id'];
    $action = $_POST['action'];

    if ($action == 'confirm_collection') {
        mysqli_query($conn, "UPDATE fourfile_clearance SET submission_status = 'Files Collected' WHERE user_id = '$sid'");
        $message = "Collection confirmed!";
    } elseif ($action == 'confirm_submission') {
        mysqli_query($conn, "UPDATE fourfile_clearance SET submission_status = 'Cleared' WHERE user_id = '$sid'");
        mysqli_query($conn, "UPDATE login_table SET final_faculty_clearance = 'Cleared', status = 'Faculty Cleared' WHERE id = '$sid'");
        $message = "Student Faculty Clearance Approved!";
    }
}

// 2. FETCH STUDENTS
$search = $_GET['search'] ?? '';
$query_str = "SELECT lt.id as user_id, lt.fullname, lt.email, ff.* FROM login_table lt 
              INNER JOIN fourfile_clearance ff ON lt.id = ff.user_id 
              WHERE ff.tracking_id IS NOT NULL AND lt.user_type = 'user'";

if (!empty($search)) {
    $query_str .= " AND (lt.fullname LIKE '%$search%' OR ff.tracking_id LIKE '%$search%')";
}
$query_str .= " ORDER BY lt.id DESC";
$students = mysqli_query($conn, $query_str);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Four Files Confirmation - Faculty Admin</title>
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; box-sizing: border-box; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .has_dropdown::after { content: '▼'; float: right; font-size: 9px; color: rgba(255,255,255,0.7); margin-top: 2px; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }

        /* Modal & Document Grid Styling */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); overflow-y: auto; padding-top: 50px; }
        .modal-content { background: #f4f7f6; margin: auto; padding: 30px; width: 90%; max-width: 1000px; border-radius: 12px; position: relative; }
        .close-modal { position: absolute; right: 20px; top: 15px; font-size: 30px; cursor: pointer; color: #333; }
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .doc-card { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center; }
        .doc-card p { font-size: 10px; font-weight: bold; margin-bottom: 10px; color: #555; height: 30px; overflow: hidden; }
        .doc-card a { display: block; background: #0e5001; color: white; text-decoration: none; padding: 8px; font-size: 10px; border-radius: 4px; }
        
        /* Table List Styling */
        .student-row { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 5px solid #0e5001; }
        .status-badge { float: right; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #eee; border: 1px solid #ddd; }
        .btn-confirm { background: #0e5001; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-collect { background: #1976d2; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-view { background: #555; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
    
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
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU (ADMIN)</h4></div>         
            </div>
            <div class="logout_btn"><a href="../../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../../admin.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../admin_update_profile.php">Update Profile</a></li>
                        <li><a href="../admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn has_dropdown" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="verify_olevel.php">Approve O'level</a></li>
                        <li><a href="confirm_fac_dues.php">Issue Faculty Receipt</a></li>
                        <li><a href="confirm_fourfile.php" style="color:#30e403;">Confirm Four File Submission</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2 style="color: #0e5001;">Four-Files Physical Confirmation</h2>

            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; border-left: 5px solid #28a745;"><?= $message ?></div>
            <?php endif; ?>

            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 25px;">
                <input type="text" name="search" placeholder="Search Tracking ID..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <button type="submit" class="btn-confirm">SEARCH</button>
            </form>

            <div class="student-list">
                <?php while ($row = mysqli_fetch_assoc($students)): 
                    $uid = $row['user_id'];
                    $all_docs = [
                        'JAMB Admission' => $row['jamb_admission'],
                        'EBSU Admission' => $row['admission_letter'],
                        'JAMB Result' => $row['jamb_result'],
                        'Birth Cert' => $row['birth_certificate'],
                        'School Fees' => $row['school_fees_receipt'],
                        'Dept Dues' => $row['dept_dues_receipt'],
                        'Faculty Dues' => $row['faculty_dues_receipt'],
                        'Acceptance Receipt' => $row['verified_receipt'],
                        'Olevel Cert' => $row['verified_olevel_cert'],
                        'Post UTME' => $row['post_utme_result'],
                        'Passport' => $row['passport_photo'],
                        'CRF Form' => $row['crf_form'],
                        'SIF Form' => $row['sif_form'],
                        'Medical Form' => $row['medical_form'],
                        'LGA Letter' => $row['lga_letter'],
                        'Undertaking' => $row['parent_undertaking']
                    ];
                ?>
                    <div class="student-row">
                        <span class="status-badge"><?= $row['submission_status'] ?? 'Pending' ?></span>
                        <h3 style="margin: 0;"><?= htmlspecialchars($row['fullname']) ?></h3>
                        <p style="font-size: 12px; color: #d81b60; font-weight: bold;">ID: <?= $row['tracking_id'] ?></p>

                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <button class="btn-view" onclick="openModal('modal-<?= $uid ?>')">View Uploaded Files</button>
                            
                            <?php if ($row['submission_status'] == 'Submitted' || empty($row['submission_status'])): ?>
                                <form method="POST"><input type="hidden" name="student_id" value="<?= $uid ?>"><button type="submit" name="update_status" value="confirm_collection" class="btn-collect">Confirm Collection</button><input type="hidden" name="action" value="confirm_collection"></form>
                            <?php endif; ?>

                            <?php if ($row['submission_status'] == 'Files Collected'): ?>
                                <form method="POST"><input type="hidden" name="student_id" value="<?= $uid ?>"><button type="submit" name="update_status" value="confirm_submission" class="btn-confirm">Final Approval</button><input type="hidden" name="action" value="confirm_submission"></form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="modal-<?= $uid ?>" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeModal('modal-<?= $uid ?>')">&times;</span>
                            <h3 style="color:#0e5001;">Document Verification: <?= htmlspecialchars($row['fullname']) ?></h3>
                            <div class="doc-grid">
                                <?php foreach ($all_docs as $label => $file): ?>
                                    <div class="doc-card">
                                        <p><?= $label ?></p>
                                        <?php if (!empty($file)): ?>
                                            <a href="../../asset/uploads/four_files/<?= $uid ?>/<?= $file ?>" target="_blank">VIEW FILE</a>
                                        <?php else: ?>
                                            <span style="color:red; font-size:9px;">NOT UPLOADED</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
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

    <script>
        function toggleSubmenu(id) { document.getElementById(id).classList.toggle('active'); }
        function openModal(id) { document.getElementById(id).style.display = "block"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }
        window.onclick = function(event) { if (event.target.className === 'modal') { event.target.style.display = "none"; } }
    </script>
</body>
</html>
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
        mysqli_query($conn, "UPDATE login_table SET four_files_status = 'Files Collected' WHERE id = '$sid'");
        $message = "Physical File Collection Confirmed!";
    } elseif ($action == 'confirm_submission') {
        mysqli_query($conn, "UPDATE login_table SET four_files_status = 'Completed', final_faculty_clearance = 'Cleared', status = 'Faculty Cleared' WHERE id = '$sid'");
        $message = "Physical File Submission Confirmed and Faculty Clearance Approved!";
    }
}

// 2. FETCH STUDENTS
$search = $_GET['search'] ?? '';
$query_str = "SELECT * FROM login_table WHERE four_files_track_id IS NOT NULL AND user_type = 'user'";
if (!empty($search)) {
    $query_str .= " AND (fullname LIKE '%$search%' OR four_files_track_id LIKE '%$search%' OR email LIKE '%$search%')";
}
$query_str .= " ORDER BY id DESC";
$students_result = mysqli_query($conn, $query_str);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Confirm Four Files</title>
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

        .student-row { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 5px solid #0e5001; }
        .status-badge { float: right; padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; background: #eee; border: 1px solid #ddd; }
        
        .btn-confirm { background: #0e5001; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-collect { background: #1976d2; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-view { background: #555; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; text-decoration: none; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); overflow-y: auto; }
        .modal-content { background: white; margin: 2% auto; padding: 25px; width: 90%; max-width: 1000px; border-radius: 8px; position: relative; }
        .close-modal { position: absolute; right: 20px; top: 10px; font-size: 30px; cursor: pointer; color: #333; }
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 20px; }
        .doc-card { border: 1px solid #eee; padding: 12px; border-radius: 6px; text-align: left; background: #fcfcfc; display: flex; flex-direction: column; justify-content: space-between; height: 100px; }
        .doc-card p { font-size: 10px; margin-bottom: 8px; line-height: 1.2; font-weight: bold; text-transform: uppercase; }
        .doc-card a { font-size: 9px; color: #ffffff; background: #0e5001; text-decoration: none; padding: 6px; text-align: center; border-radius: 3px; font-weight: bold; }
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
            <h2 style="color: #0e5001;">Faculty Officer: Four-Files Management</h2>
            <p style="font-size: 12px; color: #666; margin-bottom: 25px;">Track student physical submissions using their system-generated Tracking IDs.</p>

            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-size: 13px; border-left: 5px solid #28a745;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="search_container" style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <input type="text" name="search" class="search_input" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Search Tracking ID or Name..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-confirm">FILTER RECORDS</button>
            </form>

            <div class="student-list">
                <?php while ($row = mysqli_fetch_assoc($students_result)): 
                    $uid = $row['id'];
                    
                    // Aligned with user-side column names
                    $all_docs = [
                        '1. JAMB Admission Letter' => $row['jamb_admission'] ?? null,
                        '2. EBSU Admission Letter' => $row['admission_letter'] ?? null,
                        '3. Official School Fees Receipt' => $row['school_fees_receipt'] ?? null,
                        '4. JAMB Result Slip' => $row['jamb_slip'] ?? null,
                        '5. Year One Dept Dues' => $row['dept_dues_receipt'] ?? null,
                        '6. Verified O\'Level Result' => $row['verified_olevel_cert'] ?? null,
                        '7. Post-UTME Result' => $row['post_utme_result'] ?? null,
                        '8. Photocopy of Fees Receipt' => $row['school_fees_receipt'] ?? null,
                        '9. Acceptance Fee Receipt' => $row['verified_receipt'] ?? null,
                        '10. Birth Certificate' => $row['birth_cert'] ?? null,
                        '11. LGA Identification' => $row['lga_letter'] ?? null,
                        '12. Parent Undertaking' => $row['parent_undertaking'] ?? null,
                        '13. 4 Recent Passports' => $row['passport_photo'] ?? null,
                        '14. Year One Faculty Dues' => $row['faculty_dues_receipt'] ?? null,
                        '15. Course Reg Form (CRF)' => $row['crf_form'] ?? null,
                        '16. Student Info Form (SIF)' => $row['sif_form'] ?? null,
                        '17. Medical Exam Form' => $row['medical_form'] ?? null
                    ];
                ?>
                    <div class="student-row">
                        <span class="status-badge"><?= $row['four_files_status'] ?? 'ID Generated' ?></span>
                        <h3 style="margin: 0; font-size: 16px; color: #0e5001;"><?= htmlspecialchars($row['fullname']) ?></h3>
                        <p style="color: #666; font-size: 12px; margin: 5px 0;">
                            <strong>Tracking ID:</strong> <span style="color: #d81b60; font-weight: bold;"><?= $row['four_files_track_id'] ?></span>
                        </p>

                        <div style="display: flex; gap: 10px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <button class="btn-view" onclick="openModal('modal-<?= $uid ?>')">View Uploaded Docs (17)</button>
                            
                            <?php if (empty($row['four_files_status']) || $row['four_files_status'] == 'Submitted'): ?>
                                <form method="POST"><input type="hidden" name="student_id" value="<?= $uid ?>"><input type="hidden" name="action" value="confirm_collection"><button type="submit" name="update_status" class="btn-collect">Confirm Collection</button></form>
                            <?php endif; ?>

                            <?php if ($row['four_files_status'] == 'Submitted' || $row['four_files_status'] == 'Files Collected'): ?>
                                <form method="POST"><input type="hidden" name="student_id" value="<?= $uid ?>"><input type="hidden" name="action" value="confirm_submission"><button type="submit" name="update_status" class="btn-confirm" onclick="return confirm('Approve final submission?')">Final Approval</button></form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="modal-<?= $uid ?>" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeModal('modal-<?= $uid ?>')">&times;</span>
                            <h2 style="color:#0e5001; font-size: 20px;">Verification Checklist</h2>
                            <p style="font-size:12px; color:#333; margin-bottom: 20px;">Student: <strong><?= htmlspecialchars($row['fullname']) ?></strong></p>
                            
                            <div class="doc-grid">
                                <?php foreach ($all_docs as $label => $filename): ?>
                                    <div class="doc-card" style="<?= empty($filename) ? 'border-color: #ffcccc; background: #fffefe;' : 'border-color: #d4edda; background: #fafffa;' ?>">
                                        <p><?= $label ?></p>
                                        <?php if (!empty($filename)): ?>
                                            <a href="../../asset/uploads/four_files/<?= $uid ?>/<?= $filename ?>" target="_blank">VIEW DOCUMENT</a>
                                        <?php else: ?>
                                            <span style="color:red; font-size: 9px; font-weight: bold; text-align: center; border: 1px dashed red; padding: 4px; background: white;">NOT UPLOADED</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <footer class="main_footer">
                <div>Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong></div>
            </footer>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            document.getElementById(id).classList.toggle('active');
        }
        function openModal(id) {
            document.getElementById(id).style.display = "block";
            document.body.style.overflow = "hidden";
        }
        function closeModal(id) {
            document.getElementById(id).style.display = "none";
            document.body.style.overflow = "auto";
        }
    </script>
</body>
</html>
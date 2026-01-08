<?php
// Path: admin/faculty_admin/verify_credentials.php
include('../../connection.php'); 
session_start();

// Security: Redirect if not an admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../../login.php"); 
    exit();
}

$msg = "";

// --- HANDLE CREDENTIAL APPROVAL ---
if (isset($_POST['approve_docs'])) {
    $user_id = $_POST['user_id'];
    $sql = "UPDATE login_table SET faculty_docs_status = 'Verified' WHERE id = '$user_id'";
    if (mysqli_query($conn, $sql)) {
        $msg = "<p style='color:green; padding:12px; background:#e8f5e9; border-left:5px solid #28a745; border-radius:4px; font-weight:bold; margin-bottom:20px;'>✔ Student credentials verified successfully!</p>";
    }
}

// Fetch students who have submitted documents
$query = mysqli_query($conn, "SELECT id, fullname, email, faculty_docs_status FROM login_table WHERE faculty_docs_status != 'Not Submitted' ORDER BY faculty_docs_status DESC");

// Fetch admin info for Top Nav
$admin_email = $_SESSION['admin'];
$admin_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'"));
$admin_image = (!empty($admin_data['profile_pic'])) ? "../../asset/images/profiles/" . $admin_data['profile_pic'] : "../../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Credentials - Faculty Admin</title>
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <style>
        /* Sidebar Styling - Strictly Maintained */
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
        
        /* TABLE ORGANISATION CSS */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
        }
        thead { background: #0e5001; color: #fff; }
        th { padding: 15px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background: #f9f9f9; }

        /* Document Badge Styling */
        .doc_container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 8px;
        }
        .doc_link {
            display: block;
            background: #f1f3f5;
            color: #0e5001;
            padding: 6px 4px;
            text-align: center;
            border-radius: 4px;
            text-decoration: none;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #dce0e4;
            transition: all 0.3s;
        }
        .doc_link:hover {
            background: #0e5001;
            color: #fff;
            border-color: #0e5001;
        }
        .missing_tag {
            font-size: 9px;
            color: #d93025;
            background: #fff5f5;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #ffdad8;
            text-align: center;
        }

        .status_pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
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
                <div class="logo"><img src="../../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU (ADMIN)</h4></div>       
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
                        <li><a href="#" style="color:#30e403;">Verify Credentials</a></li>
                        <li><a href="verify_olevel.php">Approve O'level</a></li>
                        <li><a href="confirm_fac_dues.php">Issue Faculty Receipt</a></li>
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
            <h2 style="color: #0e5001; margin-bottom:5px;">Faculty Credential Verification</h2>
            <p style="font-size: 13px; color: #777;">Review all 10 uploaded documents per student and approve for clearance.</p>

            <?= $msg ?>

            <table>
                <thead>
                    <tr>
                        <th width="20%">Student Details</th>
                        <th width="50%">Uploaded Documents (View)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): 
                        $sid = $row['id'];
                        $doc_path = "../../asset/uploads/faculty_docs/$sid/";
                    ?>
                    <tr>
                        <td>
                            <strong style="color:#333; font-size:14px;"><?= htmlspecialchars($row['fullname']) ?></strong><br>
                            <small style="color:#777;"><?= $row['email'] ?></small>
                        </td>
                        <td>
                            <div class="doc_container">
                                <?php 
                                $docs = ['jamb_adm' => 'JAMB ADM', 'jamb_res' => 'JAMB RES', 'olevel_print' => 'O\'LEVEL', 'ebsu_acc' => 'EBSU ACC', 'ebsu_adm' => 'EBSU ADM', 'lg_id' => 'LG ID', 'post_utme' => 'POST UTME', 'supp_form' => 'SUPP', 'attestation' => 'ATTES.', 'birth_cert' => 'BIRTH'];
                                foreach($docs as $key => $label):
                                    $files = glob($doc_path . $key . "_*.*");
                                    if($files):
                                        $file_url = $files[0];
                                ?>
                                    <a href="<?= $file_url ?>" target="_blank" class="doc_link"><?= $label ?></a>
                                <?php else: ?>
                                    <?php if($key != 'supp_form'): ?>
                                        <div class="missing_tag">No <?= $label ?></div>
                                    <?php endif; ?>
                                <?php endif; endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <span class="status_pill" style="<?= ($row['faculty_docs_status'] == 'Verified') ? 'background:#d4edda; color:#155724;' : 'background:#fff3cd; color:#856404;' ?>">
                                <?= strtoupper($row['faculty_docs_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['faculty_docs_status'] == 'Submitted'): ?>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="approve_docs" style="background:#28a745; color:white; border:none; padding:10px 14px; border-radius:4px; cursor:pointer; font-size:11px; font-weight:bold; box-shadow: 0 2px 5px rgba(40,167,69,0.2);">APPROVE ALL</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: bold; font-size: 11px; display:flex; align-items:center;">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="margin-right:4px;">
                                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg> VERIFIED
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
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

    <script src="../../asset/js/main.js"></script>
    
</body>
</html>
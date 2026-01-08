<?php
// Path: admin/faculty_admin/verify_olevel.php
include('../../connection.php'); 
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../../login.php"); 
    exit();
}

$msg = "";

// --- HANDLE ACTIONS (APPROVE, REJECT, TRANSFER) ---
if (isset($_POST['action_type'])) {
    $uid = $_POST['user_id'];
    $action = $_POST['action_type'];
    
    if ($action == 'Approve') {
        $status = 'Verified';
    } elseif ($action == 'Reject') {
        $status = 'Rejected';
    } elseif ($action == 'Transfer') {
        $status = 'Transfered';
    }

    $update = mysqli_query($conn, "UPDATE login_table SET olevel_status = '$status' WHERE id = '$uid'");
    if ($update) {
        $msg = "<p style='color:green; padding:10px; background:#d4edda; border-radius:5px;'>User status updated to: $status</p>";
    }
}

// Fetch all students who have submitted O'Level
$query = mysqli_query($conn, "SELECT l.id, l.fullname, l.email, l.olevel_status, o.* FROM login_table l 
                              JOIN olevel_table o ON l.id = o.user_id 
                              WHERE l.olevel_status != 'Not Submitted' 
                              ORDER BY l.id DESC");

// Fetch admin info
$admin_email = $_SESSION['admin'];
$admin_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, profile_pic FROM login_table WHERE email = '$admin_email'"));
$admin_image = (!empty($admin_data['profile_pic'])) ? "../../asset/images/profiles/" . $admin_data['profile_pic'] : "../../asset/images/user_icon.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O'Level Verification - Admin</title>
    <link href="../../asset/css/admin.css" rel="stylesheet">
    <style>
        /* Shared Sidebar/Nav Branding */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 800px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: #30e403; }

        /* Table & Modal UI */
        .data-table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .data-table th { background: #0e5001; color: #fff; padding: 12px; font-size: 12px; text-align: left; }
        .data-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        .view-btn { background: #0e5001; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; }

        /* The Modal (Pop-up) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); overflow-y: auto; }
        .modal-content { background: #f4f4f4; margin: 5% auto; padding: 20px; width: 80%; max-width: 900px; border-radius: 8px; position: relative; }
        .close { position: absolute; right: 20px; top: 10px; font-size: 28px; font-weight: bold; cursor: pointer; }
        
        .result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        .info-box { background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .info-box h4 { margin-top: 0; color: #0e5001; border-bottom: 2px solid #0e5001; padding-bottom: 5px; }
        
        .mini-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
        .mini-table td, .mini-table th { padding: 5px; border-bottom: 1px solid #eee; }
        
        .action-area { margin-top: 20px; padding: 15px; background: #fff; border-radius: 5px; text-align: center; }
        .btn-act { padding: 10px 20px; border: none; border-radius: 4px; color: white; font-weight: bold; cursor: pointer; margin: 0 5px; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .btn-transfer { background: #ffc107; color: #000; }
        
        .status-pill { padding: 4px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $admin_image ?>" style="width:100%; height:100%; object-fit:cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($admin_data['fullname']) ?></span></div>
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
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../admin_update_profile.php">Update Profile</a></li>
                        <li><a href="../admin_change_password.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="../verify_rrr.php">Verify Acceptance RRR</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="../offer_letter.php">Offer Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">FACULTY CLEARANCE</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="verify_credentials.php">Verify Credentials</a></li>
                        <li><a href="#" style="color:#30e403;">Approve O'level</a></li>
                        <li><a href="confirm_fac_dues.php">Issue Faculty Receipt</a></li>
                        <li><a href="confirm_fourfile.php">Confirm Four File Submission</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('dept')">DEPARTMENTAL CLEARANCE</button>
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
                    <button class="menu_btn" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu" id="payment">
                        <li><a href="../verify_medicals.php">Verify Medical Fee</a></li>
                        <li><a href="../verify_orientation.php">Pay Orientation Fee</a></li>
                        <li><a href="../verify_etracking.php">Pay E-tracking Fee</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2>O'Level Verification Portal</h2>
            <p>Review student results, scratch card details, and update their clearance status.</p>
            <?= $msg ?>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?php 
                                $color = "#856404; background:#fff3cd;";
                                if($row['olevel_status'] == 'Verified') $color = "#155724; background:#d4edda;";
                                if($row['olevel_status'] == 'Rejected') $color = "#721c24; background:#f8d7da;";
                                if($row['olevel_status'] == 'Transfered') $color = "#0c5460; background:#d1ecf1;";
                            ?>
                            <span class="status-pill" style="<?= $color ?>"><?= $row['olevel_status'] ?></span>
                        </td>
                        <td>
                            <button class="view-btn" onclick='showResults(<?= json_encode($row) ?>)'>VIEW RESULTS</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <footer class="main_footer" style="margin-top:50px; text-align:center; font-size:12px; color:#888;">
                Copyright &copy; 2025 <strong>Faculty of Computing, EBSU</strong> | Powered by <strong>NACOS President</strong>
            </footer>
        </div>
    </div>

    <div id="resultModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="m_name" style="margin-bottom:5px;"></h3>
            <p id="m_email" style="font-size:12px; color:#666; margin-top:0;"></p>
            <hr>

            <div class="result-grid">
                <div class="info-box">
                    <h4>First Sitting Details</h4>
                    <p><strong>Type:</strong> <span id="s1_type"></span> | <strong>Year:</strong> <span id="s1_year"></span></p>
                    <p><strong>Exam No:</strong> <span id="s1_no"></span></p>
                    <p style="color:red; font-size:11px;"><strong>PIN:</strong> <span id="s1_pin"></span> | <strong>S/N:</strong> <span id="s1_sn"></span></p>
                    <table class="mini-table" id="s1_results_table">
                        <thead><tr><th>Subject</th><th>Grade</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="info-box" id="s2_container">
                    <h4>Second Sitting Details</h4>
                    <p><strong>Type:</strong> <span id="s2_type"></span> | <strong>Year:</strong> <span id="s2_year"></span></p>
                    <p><strong>Exam No:</strong> <span id="s2_no"></span></p>
                    <p style="color:red; font-size:11px;"><strong>PIN:</strong> <span id="s2_pin"></span> | <strong>S/N:</strong> <span id="s2_sn"></span></p>
                    <table class="mini-table" id="s2_results_table">
                        <thead><tr><th>Subject</th><th>Grade</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="action-area">
                <form method="POST">
                    <input type="hidden" name="user_id" id="m_uid">
                    <button type="submit" name="action_type" value="Approve" class="btn-act btn-approve">APPROVE RESULTS</button>
                    <button type="submit" name="action_type" value="Reject" class="btn-act btn-reject">REJECT RESULTS</button>
                    <button type="submit" name="action_type" value="Transfer" class="btn-act btn-transfer">TRANSFER TO ANOTHER FACULTY</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            document.getElementById(id).classList.toggle('active');
        }

        function showResults(data) {
            document.getElementById('m_name').innerText = data.fullname;
            document.getElementById('m_email').innerText = data.email;
            document.getElementById('m_uid').value = data.id;

            // First Sitting
            document.getElementById('s1_type').innerText = data.exam_type1;
            document.getElementById('s1_year').innerText = data.exam_year1;
            document.getElementById('s1_no').innerText = data.exam_no1;
            document.getElementById('s1_pin').innerText = data.card_pin1;
            document.getElementById('s1_sn').innerText = data.card_sn1;
            populateTable('s1_results_table', data.sitting1_results);

            // Second Sitting
            if(data.exam_type2) {
                document.getElementById('s2_container').style.display = "block";
                document.getElementById('s2_type').innerText = data.exam_type2;
                document.getElementById('s2_year').innerText = data.exam_year2;
                document.getElementById('s2_no').innerText = data.exam_no2;
                document.getElementById('s2_pin').innerText = data.card_pin2;
                document.getElementById('s2_sn').innerText = data.card_sn2;
                populateTable('s2_results_table', data.sitting2_results);
            } else {
                document.getElementById('s2_container').style.display = "none";
            }

            document.getElementById('resultModal').style.display = "block";
        }

        function populateTable(tableId, jsonData) {
            let tbody = document.getElementById(tableId).querySelector('tbody');
            tbody.innerHTML = "";
            let results = JSON.parse(jsonData);
            for (let sub in results) {
                if(sub && results[sub]) {
                    let row = `<tr><td>${sub}</td><td><strong>${results[sub]}</strong></td></tr>`;
                    tbody.innerHTML += row;
                }
            }
        }

        function closeModal() {
            document.getElementById('resultModal').style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('resultModal')) closeModal();
        }
    </script>
</body>
</html>
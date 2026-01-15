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
        $msg = "<div style='color:#155724; padding:15px; background:#d4edda; border:1px solid #c3e6cb; border-radius:5px; margin-bottom:20px;'>SUCCESS: User status updated to $status</div>";
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
        /* --- Sidebar & Layout Fixes --- */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 800px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: #30e403; background: rgba(255,255,255,0.05); }

        /* --- Organized Table CSS --- */
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px; }
        .data-table { width: 100%; border-collapse: collapse; overflow: hidden; }
        .data-table thead tr { background-color: #0e5001; color: #ffffff; text-align: left; font-weight: bold; }
        .data-table th, .data-table td { padding: 15px 12px; border-bottom: 1px solid #eee; }
        .data-table tbody tr:hover { background-color: #f9f9f9; }
        
        .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; display: inline-block; min-width: 80px; text-align: center; }
        .view-btn { background: #0e5001; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 11px; transition: 0.3s; font-weight: bold; }
        .view-btn:hover { background: #30e403; color: #000; }

        /* --- Refined Modal --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(3px); }
        .modal-content { background: #fff; margin: 3% auto; padding: 0; width: 90%; max-width: 850px; border-radius: 10px; overflow: hidden; animation: slideDown 0.4s ease; }
        @keyframes slideDown { from {transform: translateY(-50px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
        
        .modal-header { background: #0e5001; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 20px; max-height: 75vh; overflow-y: auto; }
        
        .result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-box { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #fcfcfc; }
        .info-box h4 { margin: 0 0 10px 0; color: #0e5001; border-bottom: 2px solid #30e403; display: inline-block; }
        .card-details { background: #fff3cd; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 13px; margin: 10px 0; border-left: 4px solid #ffc107; }

        .mini-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .mini-table th { background: #eee; padding: 8px; font-size: 11px; text-align: left; }
        .mini-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }

        .action-area { padding: 20px; background: #f4f4f4; border-top: 1px solid #ddd; text-align: center; }
        .btn-act { padding: 12px 25px; border: none; border-radius: 5px; color: white; font-weight: bold; cursor: pointer; margin: 5px; transition: 0.2s; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .btn-transfer { background: #ffc107; color: #000; }
        .btn-act:hover { opacity: 0.8; transform: translateY(-2px); }

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
                        <li><a href="verify_credentials"> Verify Credentials</a></li>
                        <li><a href="#" style="color:#30e403;">Approve O'level</a></li>
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
            <div class="table-container">
                <h2 style="color: #0e5001; margin-top:0;">O'Level Verification Portal</h2>
                <p style="color: #666; font-size: 14px;">Review student results, scratch card details, and update their clearance status.</p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                
                <?= $msg ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Verification Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?php 
                                    $style = "color:#856404; background:#fff3cd; border:1px solid #ffeeba;";
                                    if($row['olevel_status'] == 'Verified') $style = "color:#155724; background:#d4edda; border:1px solid #c3e6cb;";
                                    if($row['olevel_status'] == 'Rejected') $style = "color:#721c24; background:#f8d7da; border:1px solid #f5c6cb;";
                                    if($row['olevel_status'] == 'Transfered') $style = "color:#0c5460; background:#d1ecf1; border:1px solid #bee5eb;";
                                ?>
                                <span class="status-pill" style="<?= $style ?>"><?= $row['olevel_status'] ?></span>
                            </td>
                            <td style="text-align: center;">
                                <button class="view-btn" onclick='showResults(<?= json_encode($row) ?>)'>OPEN RECORD</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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

    <div id="resultModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="m_name" style="margin:0;"></h3>
                <span class="close" onclick="closeModal()" style="cursor:pointer; font-size:24px;">&times;</span>
            </div>
            
            <div class="modal-body">
                <p id="m_email" style="font-weight:bold; color:#555; margin-bottom:20px;"></p>
                
                <div class="result-grid">
                    <div class="info-box">
                        <h4>FIRST SITTING</h4>
                        <p style="font-size:13px; margin: 5px 0;"><strong>Exam:</strong> <span id="s1_type"></span> (<span id="s1_year"></span>)</p>
                        <p style="font-size:13px; margin: 5px 0;"><strong>Exam No:</strong> <span id="s1_no"></span></p>
                        
                        <div class="card-details">
                            PIN: <span id="s1_pin"></span><br>
                            S/N: <span id="s1_sn"></span>
                        </div>

                        <table class="mini-table" id="s1_results_table">
                            <thead><tr><th>SUBJECT</th><th>GRADE</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="info-box" id="s2_container">
                        <h4>SECOND SITTING</h4>
                        <p style="font-size:13px; margin: 5px 0;"><strong>Exam:</strong> <span id="s2_type"></span> (<span id="s2_year"></span>)</p>
                        <p style="font-size:13px; margin: 5px 0;"><strong>Exam No:</strong> <span id="s2_no"></span></p>
                        
                        <div class="card-details">
                            PIN: <span id="s2_pin"></span><br>
                            S/N: <span id="s2_sn"></span>
                        </div>

                        <table class="mini-table" id="s2_results_table">
                            <thead><tr><th>SUBJECT</th><th>GRADE</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="action-area">
                <form method="POST">
                    <input type="hidden" name="user_id" id="m_uid">
                    <button type="submit" name="action_type" value="Approve" class="btn-act btn-approve">APPROVE</button>
                    <button type="submit" name="action_type" value="Reject" class="btn-act btn-reject">REJECT</button>
                    <button type="submit" name="action_type" value="Transfer" class="btn-act btn-transfer">TRANSFER FACULTY</button>
                </form>
            </div>
        </div>
    </div>
    <script src="asset/js/main.js"></script>
    <script>

        function showResults(data) {
            document.getElementById('m_name').innerText = data.fullname;
            document.getElementById('m_email').innerText = "Email: " + data.email;
            document.getElementById('m_uid').value = data.id;

            // First Sitting
            document.getElementById('s1_type').innerText = data.exam_type1;
            document.getElementById('s1_year').innerText = data.exam_year1;
            document.getElementById('s1_no').innerText = data.exam_no1;
            document.getElementById('s1_pin').innerText = data.card_pin1;
            document.getElementById('s1_sn').innerText = data.card_sn1;
            populateTable('s1_results_table', data.sitting1_results);

            // Second Sitting Logic
            if(data.exam_type2 && data.exam_type2 !== "") {
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
            try {
                let results = JSON.parse(jsonData);
                for (let sub in results) {
                    if(sub && results[sub] && results[sub] !== "") {
                        let row = `<tr><td>${sub.replace(/_/g, ' ')}</td><td><b style="color:#0e5001;">${results[sub]}</b></td></tr>`;
                        tbody.innerHTML += row;
                    }
                }
            } catch(e) { console.error("Invalid JSON results"); }
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
<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);

$fullname = $user['fullname'] ?? $email;
$user_id = $user['id'];
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// --- FETCH DATA FROM NEW FOURFILE_CLEARANCE TABLE ---
$check_clearance = mysqli_query($conn, "SELECT * FROM fourfile_clearance WHERE user_id = '$user_id'");
if (mysqli_num_rows($check_clearance) == 0) {
    // Initialize record if it doesn't exist
    mysqli_query($conn, "INSERT INTO fourfile_clearance (user_id) VALUES ('$user_id')");
    $check_clearance = mysqli_query($conn, "SELECT * FROM fourfile_clearance WHERE user_id = '$user_id'");
}
$docs = mysqli_fetch_assoc($check_clearance);

// --- NEW FUNCTIONALITY: GENERATE UNIQUE TRACKING ID ---
if (empty($docs['tracking_id'])) {
    $new_track_id = "EBSU-" . strtoupper(substr(md5($user_id . time()), 0, 8));
    mysqli_query($conn, "UPDATE fourfile_clearance SET tracking_id = '$new_track_id' WHERE user_id = '$user_id'");
    $tracking_id = $new_track_id;
} else {
    $tracking_id = $docs['tracking_id'];
}

// HANDLE UPLOADS FOR MISSING ITEMS
if (isset($_POST['upload_missing_docs'])) {
    $target_dir = "../asset/uploads/four_files/" . $user_id . "/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $files_to_upload = [
        'jamb_adm' => 'jamb_admission',
        'ebsu_adm' => 'admission_letter', // Added EBSU Admission
        'jamb_res' => 'jamb_result',
        'birth_cert' => 'birth_certificate',
        'school_fees' => 'school_fees_receipt',
        'dept_dues' => 'dept_dues_receipt',
        'fac_dues' => 'faculty_dues_receipt', // Added Faculty Dues
        'acceptance' => 'verified_receipt',    // Added Acceptance Receipt
        'olevel_cert' => 'verified_olevel_cert',
        'post_utme' => 'post_utme_result',
        'passport' => 'passport_photo',
        'crf' => 'crf_form',
        'sif' => 'sif_form',
        'medical' => 'medical_form',
        'lga_id' => 'lga_letter',
        'undertaking' => 'parent_undertaking'
    ];

    foreach ($files_to_upload as $key => $column) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            $filename = $key . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $target_dir . $filename)) {
                mysqli_query($conn, "UPDATE fourfile_clearance SET $column = '$filename' WHERE user_id = '$user_id'");
            }
        }
    }
    header("Location: four_files.php?status=uploaded");
    exit();
}

// CHECKLIST DYNAMIC LOGIC
$has_jamb_adm = !empty($docs['jamb_admission']); 
$has_ebsu_adm = !empty($docs['admission_letter']);
$has_school_fees = !empty($docs['school_fees_receipt']);
$has_jamb_res = !empty($docs['jamb_result']); 
$has_dept_dues = !empty($docs['dept_dues_receipt']);
$has_olevel_v = !empty($docs['verified_olevel_cert']);
$has_post_utme = !empty($docs['post_utme_result']);
$has_acceptance = !empty($docs['verified_receipt']);
$has_birth_cert = !empty($docs['birth_certificate']); 
$has_lga = !empty($docs['lga_letter']);
$has_undertaking = !empty($docs['parent_undertaking']);
$has_passport = !empty($docs['passport_photo']);
$has_faculty_dues = !empty($docs['faculty_dues_receipt']);
$has_crf = !empty($docs['crf_form']);
$has_sif = !empty($docs['sif_form']);
$has_medical = !empty($docs['medical_form']);

// STAGE 1: CORE DOCUMENTS
$core_docs_uploaded = ($has_jamb_adm && $has_ebsu_adm && $has_school_fees && $has_jamb_res && $has_dept_dues && $has_olevel_v && $has_post_utme && $has_acceptance && $has_birth_cert && $has_lga && $has_undertaking && $has_passport && $has_faculty_dues);

// STAGE 2: FINAL FORMS
$final_forms_uploaded = ($has_crf && $has_sif && $has_medical);

if (isset($_POST['notify_submission'])) {
    mysqli_query($conn, "UPDATE fourfile_clearance SET submission_status = 'Submitted' WHERE user_id = '$user_id'");
    echo "<script>alert('Notification sent to Faculty Officer. Ensure physical files are submitted.'); window.location.href='four_files.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Four Files Clearance - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        .submenu li a:hover { color: white; background: rgba(48, 228, 3, 0.3); }
        .menu_btn::after { content: ' ▼'; float: right; font-size: 10px; }
        .instruction-card { background: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .checklist-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .requirement-item { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        .status-tick { margin-right: 10px; font-size: 16px; }
        .tick-yes { color: #28a745; font-weight: bold; }
        .tick-no { color: #dc3545; font-weight: bold; }
        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f4f7f6; padding: 20px; border-radius: 8px; }
        .upload-box { border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .upload-box label { display: block; font-size: 10px; font-weight: bold; color: #0e5001; margin-bottom: 5px; text-transform: uppercase; }
        .final-badge { text-align: center; padding: 30px; background: #e8f5e9; border: 2px dashed #2e7d32; border-radius: 10px; }
        .tracking-card { background: #e3f2fd; border: 2px solid #1976d2; padding: 20px; text-align: center; border-radius: 8px; margin-top: 20px; }
        .tracking-id { font-size: 24px; font-weight: bold; color: #0d47a1; letter-spacing: 2px; margin: 10px 0; }
        .secondary-upload { background: #fff; border: 1px solid #1976d2; padding: 15px; margin-top: 15px; border-radius: 5px; display: grid; grid-template-columns: 1fr; gap: 10px; text-align: left; }
        #printable_slip { display: none; }
        @media print {
            body * { visibility: hidden; }
            #printable_slip, #printable_slip * { visibility: visible; }
            #printable_slip { display: block !important; position: absolute; left: 0; top: 0; width: 100%; padding: 50px; border: none; color: #000; }
            .print-data { margin-bottom: 15px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        }
    </style>
</head>
<body>

    <div id="printable_slip">
        <div style="text-align: center; border-bottom: 3px solid #0e5001; margin-bottom: 30px; padding-bottom: 10px;">
            <h1 style="margin:0;">FACULTY OF COMPUTING, EBSU</h1>
            <p style="margin:5px 0; font-weight: bold;">Four-Files Tracking Identification Slip</p>
        </div>
        <div class="print-data"><strong>Full Name:</strong> <?= htmlspecialchars($fullname) ?></div>
        <div class="print-data"><strong>Email:</strong> <?= htmlspecialchars($email) ?></div>
        <div class="print-data"><strong>Faculty:</strong> Faculty of Computing</div>
        <div class="print-data" style="margin-top: 40px; text-align: center; border: 2px dashed #333; padding: 20px; background: #fafafa;">
            <span style="display: block; font-size: 14px; text-transform: uppercase;">Tracking ID:</span>
            <strong style="font-size: 30px; letter-spacing: 3px; color: #0e5001;"><?= $tracking_id ?></strong>
        </div>
        <div style="margin-top: 100px; font-size: 12px; text-align: center; color: #666;">
            Generated on: <?= date("F j, Y, g:i a") ?> | Present this slip to the Faculty Officer.
        </div>
    </div>

    <div class="top_nav">
        <div class="user_info">
            <div class="profile_pic"><img src="<?= $user_image ?>" style="width:100%; height:100%; object-fit:cover;"></div>
            <div class="user_caption"><span><?= htmlspecialchars($fullname) ?></span></div>
        </div>
        <div class="nav_elements">
            <div class="logo_section">
                <div class="logo"><img src="../asset/images/NACOSLOGO.png" alt="LOGO"></div>
                <div class="logo_caption"><h4>FACULTY OF COMPUTING, EBSU</h4></div>         
            </div>
            <div class="logout_btn"><a href="../logout.php"><button>Logout</button></a></div>
        </div>
    </div>

    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../user.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('profile')">PROFILE DETAILS</button>
                    <ul class="submenu" id="profile">
                        <li><a href="../profile/update.php">Update Profile</a></li>
                        <li><a href="../profile/resetpass.php">Change Password</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('acceptance')">Acceptance Fee</button>
                    <ul class="submenu" id="acceptance">
                        <li><a href="../acceptance/uploadacceptance.php">Upload Remita Receipt</a></li>
                        <li><a href="../acceptance/reprint.php">Reprint Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('admission')">Admission Letter</button>
                    <ul class="submenu" id="admission">
                        <li><a href="../admission/admission_letter.php">Upload Credentials</a></li>
                        <li><a href="../admission/download_admission.php">Print Admission Letter</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')">Faculty Clearance</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="upload_credentials.php" >Upload Credentials</a></li>
                        <li><a href="olevel_verification.php">O'level Verification</a></li>
                        <li><a href="faculty_dues.php" >Pay Faculty Dues</a></li>
                        <li><a href="#" style="color:#30e403;">Get Four Files</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('department')">Departmental Clearance</button>
                    <ul class="submenu" id="department">
                        <li><a href="../department/dept_dues.php">Pay Departmental Dues</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('schoolfess')">School Fees</button>
                    <ul class="submenu" id="schoolfess">
                        <li><a href="../fees/schoolfee.php">Get Original Receipt</a></li>
                    </ul>
                </li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('payment')">Payment</button>
                    <ul class="submenu" id="payment">
                        <li><a href="../payment/medical_fee.php">Pay Medical Fee</a></li>
                        <li><a href="../payment/orientation_fee.php">Pay Orientation Fee</a></li>
                        <li><a href="../payment/etracking_fee.php">Pay E-tracking Fee</a></li>
                        <li><a href="../payment/olevel_original_receipt.php">O'level verification Original receipt</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <?php if ($docs['submission_status'] == 'Cleared'): ?>
                <div class="final-badge">
                    <h1 style="color: #1b5e20;">🎉 CONGRATULATIONS!</h1>
                    <p>Your Faculty Clearance is fully Approved and Recorded.</p>
                    <a href="../user.php" style="background:#0e5001; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:15px;">Return to Dashboard</a>
                </div>
            <?php else: ?>
                <h2>Step 4: Student Four Files Submission</h2>
                <div class="instruction-card">
                    <strong>⚠️ ACTION REQUIRED:</strong> Visit the Faculty Officer to collect your <b>Physical Four Files</b>. Ensure all items below are present in the files before final submission.
                </div>

                <h3>Document Checklist</h3>
                <div class="checklist-container">
                    <div class="requirement-item"><span class="status-tick <?= $has_jamb_adm ? 'tick-yes' : 'tick-no' ?>"><?= $has_jamb_adm ? '✔' : '✘' ?></span> 1. JAMB Admission Letter</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_ebsu_adm ? 'tick-yes' : 'tick-no' ?>"><?= $has_ebsu_adm ? '✔' : '✘' ?></span> 2. EBSU Admission Letter</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_school_fees ? 'tick-yes' : 'tick-no' ?>"><?= $has_school_fees ? '✔' : '✘' ?></span> 3. Official School Fees Receipt</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_jamb_res ? 'tick-yes' : 'tick-no' ?>"><?= $has_jamb_res ? '✔' : '✘' ?></span> 4. JAMB Result Slip</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_dept_dues ? 'tick-yes' : 'tick-no' ?>"><?= $has_dept_dues ? '✔' : '✘' ?></span> 5. Year One Dept Dues Receipt</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_olevel_v ? 'tick-yes' : 'tick-no' ?>"><?= $has_olevel_v ? '✔' : '✘' ?></span> 6. Verified O'Level Result</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_post_utme ? 'tick-yes' : 'tick-no' ?>"><?= $has_post_utme ? '✔' : '✘' ?></span> 7. Post-UTME Result</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_school_fees ? 'tick-yes' : 'tick-no' ?>"><?= $has_school_fees ? '✔' : '✘' ?></span> 8. Photocopy of Fees Receipt</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_acceptance ? 'tick-yes' : 'tick-no' ?>"><?= $has_acceptance ? '✔' : '✘' ?></span> 9. Acceptance Fee Receipt</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_birth_cert ? 'tick-yes' : 'tick-no' ?>"><?= $has_birth_cert ? '✔' : '✘' ?></span> 10. Birth Certificate</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_lga ? 'tick-yes' : 'tick-no' ?>"><?= $has_lga ? '✔' : '✘' ?></span> 11. LGA Identification Letter</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_undertaking ? 'tick-yes' : 'tick-no' ?>"><?= $has_undertaking ? '✔' : '✘' ?></span> 12. Parent Undertaking Letter</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_passport ? 'tick-yes' : 'tick-no' ?>"><?= $has_passport ? '✔' : '✘' ?></span> 13. 4 Recent Passport Photos</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_faculty_dues ? 'tick-yes' : 'tick-no' ?>"><?= $has_faculty_dues ? '✔' : '✘' ?></span> 14. Year One Faculty Dues</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_crf ? 'tick-yes' : 'tick-no' ?>"><?= $has_crf ? '✔' : '✘' ?></span> 15. Course Reg Form (CRF)</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_sif ? 'tick-yes' : 'tick-no' ?>"><?= $has_sif ? '✔' : '✘' ?></span> 16. Student Info Form (SIF)</div>
                    <div class="requirement-item"><span class="status-tick <?= $has_medical ? 'tick-yes' : 'tick-no' ?>"><?= $has_medical ? '✔' : '✘' ?></span> 17. Medical Exam Form</div>
                </div>

                <?php if ($core_docs_uploaded): ?>
                    <div class="tracking-card">
                        <h3>✅ CORE DOCUMENTS VERIFIED</h3>
                        <p>Your unique Four Files Tracking ID:</p>
                        <div class="tracking-id"><?= $tracking_id ?></div>
                        
                        <div class="secondary-upload">
                            <p style="font-weight:bold; color:#1976d2; margin-bottom:10px;">Final Step: Upload Forms to Before Submitting your Four Files</p>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="upload-box"><label>15. Course Reg Form (CRF)</label><input type="file" name="crf" <?= $has_crf ? 'disabled' : 'required' ?>></div>
                                <div class="upload-box"><label>16. Student Info Form (SIF)</label><input type="file" name="sif" <?= $has_sif ? 'disabled' : 'required' ?>></div>
                                <div class="upload-box"><label>17. Medical Exam Form</label><input type="file" name="medical" <?= $has_medical ? 'disabled' : 'required' ?>></div>
                                <?php if (!$final_forms_uploaded): ?>
                                    <button type="submit" name="upload_missing_docs" style="width:100%; padding:10px; background:#1976d2; color:white; border:none; cursor:pointer; margin-top:10px;">UPLOAD FORMS</button>
                                <?php else: ?>
                                    <p style="color:green; font-weight:bold; margin-top:10px;">✔ Forms Uploaded Successfully</p>
                                <?php endif; ?>
                            </form>
                        </div>

                        <button onclick="window.print()" style="margin-top:15px; background:#1565c0; color:white; padding:12px 25px; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">🖨️ PRINT TRACKING SLIP</button>
                    </div>

                    <?php if ($final_forms_uploaded): ?>
                        <form method="POST" style="margin-top: 30px;">
                            <button type="submit" name="notify_submission" style="background:#0e5001; color:white; border:none; padding:15px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;">NOTIFY ADMIN OF PHYSICAL SUBMISSION</button>
                        </form>
                    <?php else: ?>
                        <div style="margin-top: 20px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 5px; text-align: center; font-weight: bold;">
                            Upload CRF, SIF, and Medical Form above to enable Admin Notification That You have Completed Faculty Clearance.
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <h3>Upload The Documents Below To Generate Your Four File Tracking ID</h3>
                    <p>Then Present The Tracking ID To The Faculty Officer To Collect Your Physical Four Files</p>
                    <form method="POST" enctype="multipart/form-data" class="upload-grid">
                        <div class="upload-box"><label>1. JAMB Admission Letter</label><input type="file" name="jamb_adm"></div>
                        <div class="upload-box"><label>2. EBSU Admission Letter</label><input type="file" name="ebsu_adm"></div>
                        <div class="upload-box"><label>3. School Fees Receipt</label><input type="file" name="school_fees"></div>
                        <div class="upload-box"><label>4. JAMB Result Slip</label><input type="file" name="jamb_res"></div>
                        <div class="upload-box"><label>5. Dept Dues Receipt</label><input type="file" name="dept_dues"></div>
                        <div class="upload-box"><label>6. Verified O'Level Result</label><input type="file" name="olevel_cert"></div>
                        <div class="upload-box"><label>7. Post-UTME Result</label><input type="file" name="post_utme"></div>
                        <div class="upload-box"><label>9. Acceptance Fee Receipt</label><input type="file" name="acceptance"></div>
                        <div class="upload-box"><label>10. Birth Certificate</label><input type="file" name="birth_cert"></div>
                        <div class="upload-box"><label>11. LGA Letter</label><input type="file" name="lga_id"></div>
                        <div class="upload-box"><label>12. Parent Undertaking</label><input type="file" name="undertaking"></div>
                        <div class="upload-box"><label>13. Passport Photo</label><input type="file" name="passport"></div>
                        <div class="upload-box"><label>14. Year One Faculty Dues</label><input type="file" name="fac_dues"></div>
                        <div style="grid-column: span 2;">
                            <button type="submit" name="upload_missing_docs" style="width:100%; padding:12px; background:#1b5e20; color:white; border:none; cursor:pointer; font-weight:bold;">UPLOAD DOCUMENTS</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
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
    <script src="../asset/js/main.js"></script>
    
</body>
</html>
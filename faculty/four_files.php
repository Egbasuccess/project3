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

// HANDLE UPLOADS FOR MISSING ITEMS
if (isset($_POST['upload_missing_docs'])) {
    $target_dir = "../asset/uploads/four_files/" . $user_id . "/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $files_to_upload = [
        'school_fees' => 'school_fees_receipt',
        'dept_dues' => 'dept_dues_receipt',
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
                mysqli_query($conn, "UPDATE login_table SET $column = '$filename' WHERE id = '$user_id'");
            }
        }
    }
    header("Location: four_files.php?status=uploaded");
}

// CHECKLIST DYNAMIC LOGIC (1-19)
$has_jamb_adm = !empty($user['admission_docs_status']) || !empty($user['faculty_docs_status']);
$has_ebsu_adm = !empty($user['admission_letter']);
$has_school_fees = !empty($user['school_fees_receipt']);
$has_jamb_res = !empty($user['faculty_docs_status']);
$has_dept_dues = !empty($user['dept_dues_receipt']);
$has_olevel_v = !empty($user['verified_olevel_cert']);
$has_post_utme = !empty($user['post_utme_result']);
$has_acceptance = !empty($user['verified_receipt']);
$has_birth_cert = !empty($user['faculty_docs_status']);
$has_lga = !empty($user['lga_letter']);
$has_undertaking = !empty($user['parent_undertaking']);
$has_passport = !empty($user['passport_photo']);
$has_faculty_dues = !empty($user['faculty_dues_receipt']);
$has_crf = !empty($user['crf_form']);
$has_sif = !empty($user['sif_form']);
$has_medical = !empty($user['medical_form']);

if (isset($_POST['notify_submission'])) {
    mysqli_query($conn, "UPDATE login_table SET four_files_status = 'Submitted' WHERE id = '$user_id'");
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
    </style>
</head>
<body>
    <div class="body_area">
        <aside class="main_layout">
            <ul class="menu_list">
                <li class="menu_item"><a href="../user.php" class="dash_link">DASHBOARD</a></li>
                <li class="menu_item">
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')" style="color:#30e403;">Faculty Clearance</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="upload_credentials.php">1. Upload Credentials</a></li>
                        <li><a href="olevel_verification.php">2. O'level Verification</a></li>
                        <li><a href="faculty_dues.php">3. Pay Faculty Dues</a></li>
                        <li><a href="four_files.php" style="color:#30e403;">4. Four File Clearance</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <?php if ($user['final_faculty_clearance'] == 'Cleared'): ?>
                <div class="final-badge">
                    <h1 style="color: #1b5e20;">🎉 CONGRATULATIONS!</h1>
                    <p>Your Faculty Clearance is fully Approved and Recorded.</p>
                    <a href="../user.php" style="background:#0e5001; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block; margin-top:15px;">Return to Dashboard</a>
                </div>
            <?php else: ?>
                <h2>Step 4: Student Four Files Submission</h2>
                <div class="instruction-card">
                    <strong>⚠️ ACTION REQUIRED:</strong> Visit the Faculty Officer to collect your <b>Physical Four Files</b>. Ensure all 19 items below are present in the files before final submission.
                </div>

                <h3>Document Checklist (1-19)</h3>
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

                <h3>Upload Missing Documents For Your E-Files Records</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-grid">
                    <div class="upload-box"><label>School Fees Receipt</label><input type="file" name="school_fees"></div>
                    <div class="upload-box"><label>Dept Dues Receipt</label><input type="file" name="dept_dues"></div>
                    <div class="upload-box"><label>Verified O'Level</label><input type="file" name="olevel_cert"></div>
                    <div class="upload-box"><label>Post-UTME Result</label><input type="file" name="post_utme"></div>
                    <div class="upload-box"><label>LGA Letter</label><input type="file" name="lga_id"></div>
                    <div class="upload-box"><label>Parent Undertaking</label><input type="file" name="undertaking"></div>
                    <div class="upload-box"><label>Passport Photo</label><input type="file" name="passport"></div>
                    <div class="upload-box"><label>CRF Form</label><input type="file" name="crf"></div>
                    <div class="upload-box"><label>SIF Form</label><input type="file" name="sif"></div>
                    <div class="upload-box"><label>Medical Form</label><input type="file" name="medical"></div>
                    <div style="grid-column: span 2;">
                        <button type="submit" name="upload_missing_docs" style="width:100%; padding:12px; background:#1b5e20; color:white; border:none; cursor:pointer; font-weight:bold;">UPLOAD DOCUMENTS</button>
                    </div>
                </form>

                <form method="POST" style="margin-top: 30px;">
                    <button type="submit" name="notify_submission" style="background:#0e5001; color:white; border:none; padding:15px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;">NOTIFY ADMIN OF PHYSICAL SUBMISSION</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>function toggleSubmenu(id) { document.getElementById(id).classList.toggle('active'); }</script>
</body>
</html>
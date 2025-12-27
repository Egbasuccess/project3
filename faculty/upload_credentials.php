<?php
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];
$msg = "";

// 1. Fetch User Data
$query = mysqli_query($conn, "SELECT * FROM login_table WHERE email = '$email'");
$user = mysqli_fetch_assoc($query);
$fullname = $user['fullname'] ?? $email;
$user_id = $user['id'];
// Check if user has already submitted
$current_status = $user['faculty_docs_status'] ?? 'Not Submitted';
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// 2. Handle Upload
if (isset($_POST['submit_faculty_docs'])) {
    $target_dir = "../asset/uploads/faculty_docs/" . $user_id . "/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_keys = ['jamb_adm', 'jamb_res', 'olevel_print', 'ebsu_acc', 'ebsu_adm', 'lg_id', 'post_utme', 'supp_form', 'attestation', 'birth_cert'];

    $upload_ok = true;
    foreach ($file_keys as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            $new_name = $key . "_" . time() . "." . $ext;
            if(!move_uploaded_file($_FILES[$key]['tmp_name'], $target_dir . $new_name)){
                $upload_ok = false;
            }
        } elseif ($key != 'supp_form') { 
            $upload_ok = false;
        }
    }

    if ($upload_ok) {
        mysqli_query($conn, "UPDATE login_table SET faculty_docs_status = 'Submitted' WHERE id = '$user_id'");
        echo "<script>alert('Credentials Uploaded Successfully!'); window.location.href = 'upload_credentials.php';</script>";
        exit();
    } else {
        $msg = "<p style='color:red; font-weight:bold; background:#ffe6e6; padding:10px; border-radius:5px;'>Please upload all required documents.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Credentials - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        /* Existing Styles */
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 500px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }
        
        .upload-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .input-box { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .input-box label { display: block; font-size: 10px; font-weight: bold; color: #0e5001; margin-bottom: 5px; }

        /* New Feature Styles */
        .success-banner { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; margin-bottom: 20px; }
        .btn-next { display: inline-block; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; transition: 0.3s; margin-top: 15px; }
        .btn-enabled { background: #0e5001; color: white; }
        .btn-enabled:hover { background: #30e403; }
        .btn-disabled { background: #cccccc; color: #666666; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>
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
                    <button class="menu_btn" onclick="toggleSubmenu('faculty')" style="color:#30e403;">Faculty Clearance</button>
                    <ul class="submenu active" id="faculty">
                        <li><a href="upload_credentials.php" style="color:#30e403;">1. Upload Credentials</a></li>
                        <li><a href="olevel_verification.php">2. O'level Verification</a></li>
                        <li><a href="faculty_dues.php">3. Pay Faculty Dues</a></li>
                        <li><a href="four_files.php">4. Four File Clearance</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2>Step 1: Faculty Clearance Credentials</h2>
            <?= $msg ?>

            <?php if ($current_status == 'Submitted'): ?>
                <div class="success-banner">
                    <h3 style="margin-top:0;">✅ Documents Uploaded Successfully</h3>
                    <p>Your credentials have been received. <strong>Status: Awaiting verification from the Faculty Officer.</strong></p>
                    <p style="font-size: 12px;">You can now proceed to the next stage of your clearance.</p>
                </div>
                <a href="olevel_verification.php" class="btn-next btn-enabled">PROCEED TO O'LEVEL VERIFICATION &rarr;</a>

            <?php else: ?>
                <p>Ensure all documents are scanned clearly in PDF or Image format.</p>
                <div class="upload-card">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="input-box"><label>1. JAMB Admission Letter</label><input type="file" name="jamb_adm" required></div>
                            <div class="input-box"><label>2. Original JAMB Results</label><input type="file" name="jamb_res" required></div>
                            <div class="input-box"><label>3. Online O'Level Printout</label><input type="file" name="olevel_print" required></div>
                            <div class="input-box"><label>4. EBSU Acceptance Receipt</label><input type="file" name="ebsu_acc" required></div>
                            <div class="input-box"><label>5. EBSU Admission Letter</label><input type="file" name="ebsu_adm" required></div>
                            <div class="input-box"><label>6. L.G. Identification</label><input type="file" name="lg_id" required></div>
                            <div class="input-box"><label>7. Post UTME Remita Receipt</label><input type="file" name="post_utme" required></div>
                            <div class="input-box"><label>8. Supplementary Receipt (Optional)</label><input type="file" name="supp_form"></div>
                            <div class="input-box"><label>9. Letter of Attestation</label><input type="file" name="attestation" required></div>
                            <div class="input-box"><label>10. Birth Cert/Age Declaration</label><input type="file" name="birth_cert" required></div>
                        </div>
                        <button type="submit" name="submit_faculty_docs" style="width:100%; padding:15px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:20px;">UPLOAD DOCUMENTS</button>
                    </form>
                    
                    <a href="#" class="btn-next btn-disabled">NEXT: O'LEVEL VERIFICATION (Locked)</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>function toggleSubmenu(id) { document.getElementById(id).classList.toggle('active'); }</script>
</body>
</html>
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
$user_id = $user['id'];
$fullname = $user['fullname'] ?? $email;
$olevel_status = $user['olevel_status'] ?? 'Not Submitted';
$user_image = (!empty($user['profile_pic'])) ? "../asset/images/profiles/" . $user['profile_pic'] : "../asset/images/user_icon.png";

// 2. Define Comprehensive Subject List (Nigeria O'Level Standard)
$subjects = [
    "Core" => ["Mathematics", "English Language", "Civic Education"],
    "Sciences" => ["Biology", "Chemistry", "Physics", "Agricultural Science", "Further Mathematics", "Computer Studies", "Health Education", "Animal Husbandry", "Fisheries"],
    "Arts & Humanities" => ["Literature in English", "Government", "History", "Christian Religious Studies", "Islamic Studies", "Geography", "Economics", "Visual Arts", "Music", "French", "Igbo", "Yoruba", "Hausa"],
    "Commercial" => ["Financial Accounting", "Commerce", "Office Practice", "Insurance", "Marketing", "Data Processing", "Book Keeping"]
];

// 3. Handle Form Submission
if (isset($_POST['submit_olevel'])) {
    $et1 = mysqli_real_escape_string($conn, $_POST['exam_type1']);
    $ec1 = mysqli_real_escape_string($conn, $_POST['exam_centre1']);
    $en1 = mysqli_real_escape_string($conn, $_POST['exam_no1']);
    $ey1 = mysqli_real_escape_string($conn, $_POST['exam_year1']);
    $cp1 = mysqli_real_escape_string($conn, $_POST['card_pin1']);
    $cs1 = mysqli_real_escape_string($conn, $_POST['card_sn1']);

    $et2 = mysqli_real_escape_string($conn, $_POST['exam_type2']);
    $ec2 = mysqli_real_escape_string($conn, $_POST['exam_centre2']);
    $en2 = mysqli_real_escape_string($conn, $_POST['exam_no2']);
    $ey2 = mysqli_real_escape_string($conn, $_POST['exam_year2']);
    $cp2 = mysqli_real_escape_string($conn, $_POST['card_pin2']);
    $cs2 = mysqli_real_escape_string($conn, $_POST['card_sn2']);

    $s1_results = json_encode(array_combine($_POST['s1_subj'], $_POST['s1_grade']));
    $s2_results = json_encode(array_combine($_POST['s2_subj'], $_POST['s2_grade']));

    $sql = "INSERT INTO olevel_table (user_id, exam_type1, exam_centre1, exam_no1, exam_year1, card_pin1, card_sn1, 
            exam_type2, exam_centre2, exam_no2, exam_year2, card_pin2, card_sn2, sitting1_results, sitting2_results) 
            VALUES ('$user_id', '$et1', '$ec1', '$en1', '$ey1', '$cp1', '$cs1', '$et2', '$ec2', '$en2', '$ey2', '$cp2', '$cs2', '$s1_results', '$s2_results')";

    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "UPDATE login_table SET olevel_status = 'Submitted' WHERE id = '$user_id'");
        echo "<script>alert('Results Submitted Successfully!'); window.location.href='olevel_verification.php';</script>";
        exit();
    } else {
        $msg = "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O'Level Verification - EBSU</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .menu_list { list-style: none; padding-top: 20px; }
        .menu_item { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu_btn, .dash_link { width: 100%; text-align: left; background: none; border: none; color: white; padding: 12px 15px; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; text-decoration: none; }
        .menu_btn:hover, .dash_link:hover { background: rgba(255,255,255,0.1); color: #30e403; }
        .submenu { background: rgba(0,0,0,0.2); max-height: 0; overflow: hidden; transition: max-height 0.3s; list-style: none; }
        .submenu.active { max-height: 800px; }
        .submenu li a { display: block; color: #ddd; padding: 10px 25px; text-decoration: none; font-size: 12px; }

        .olevel-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .sitting-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .header-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .header-row input, .header-row select { width: 100%; padding: 8px; border: 1px solid #ddd; font-size: 12px; }
        .header-row label { font-size: 10px; font-weight: bold; color: #0e5001; display: block; }
        
        .result-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .result-table th { background: #0e5001; color: #fff; font-size: 11px; padding: 8px; text-align: left; }
        .result-table td { border: 1px solid #eee; padding: 2px; }
        .result-table select { width: 100%; border: none; padding: 8px; font-size: 12px; outline: none; }
        
        .status-banner { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; text-align: center; }
        optgroup { font-weight: bold; color: #0e5001; background: #f9f9f9; }
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
                        <li><a href="upload_credentials.php">1. Upload Credentials</a></li>
                        <li><a href="olevel_verification.php" style="color:#30e403;">2. O'level Verification</a></li>
                        <li><a href="faculty_dues.php">3. Pay Faculty Dues</a></li>
                        <li><a href="four_files.php">4. Four File Clearance</a></li>
                    </ul>
                </li>
            </ul>
        </aside>

        <div class="body_div">
            <h2>Step 2: O'Level Results Verification</h2>
            <p>Select your subjects and grades as they appear on your result slip.</p>
            <?= $msg ?>

            <?php if ($olevel_status == 'Submitted'): ?>
                <div class="status-banner">
                    <h3>✅ O'Level Results Submitted</h3>
                    <p>Your records are being cross-referenced with the examination body.</p>
                    <a href="faculty_dues.php" class="dash_link" style="background:#0e5001; display:inline-block; margin:20px auto; padding:10px 30px;">Proceed to Step 3 &rarr;</a>
                </div>
            <?php else: ?>
                <form method="POST" class="olevel-card">
                    <div class="sitting-grid">
                        <div style="border: 1px solid #eee; padding: 15px; border-radius: 5px;">
                            <h4 style="color:#0e5001; margin-top:0;">First Sitting Header</h4>
                            <div class="header-row">
                                <div><label>Exam Type</label>
                                    <select name="exam_type1" required>
                                        <option>WAEC (SSCE)</option>
                                        <option>WAEC GCE</option>
                                        <option>NECO (SSCE)</option>
                                        <option>NECO GCE</option>
                                        <option>NABTEB</option>
                                    </select>
                                </div>
                                <div><label>Exam Year</label><input type="text" name="exam_year1" required></div>
                            </div>
                            <div class="header-row">
                                <div><label>Exam Center</label><input type="text" name="exam_centre1" required></div>
                                <div><label>Exam No</label><input type="text" name="exam_no1" required></div>
                            </div>
                            <div class="header-row">
                                <div><label>Card PIN</label><input type="text" name="card_pin1" required></div>
                                <div><label>Card S/N</label><input type="text" name="card_sn1" required></div>
                            </div>
                        </div>

                        <div style="border: 1px solid #eee; padding: 15px; border-radius: 5px;">
                            <h4 style="color:#0e5001; margin-top:0;">Second Sitting (Optional)</h4>
                            <div class="header-row">
                                <div><label>Exam Type</label>
                                    <select name="exam_type2">
                                        <option value="">None</option>
                                        <option>WAEC (SSCE)</option>
                                        <option>WAEC GCE</option>
                                        <option>NECO (SSCE)</option>
                                        <option>NECO GCE</option>
                                        <option>NABTEB</option>
                                    </select>
                                </div>
                                <div><label>Exam Year</label><input type="text" name="exam_year2"></div>
                            </div>
                            <div class="header-row">
                                <div><label>Exam Center</label><input type="text" name="exam_centre2"></div>
                                <div><label>Exam No</label><input type="text" name="exam_no2"></div>
                            </div>
                            <div class="header-row">
                                <div><label>Card PIN</label><input type="text" name="card_pin2"></div>
                                <div><label>Card S/N</label><input type="text" name="card_sn2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="sitting-grid">
                        <table class="result-table">
                            <thead><tr><th>1st Sitting Subject</th><th>Grade</th></tr></thead>
                            <tbody>
                                <?php for($i=1; $i<=9; $i++): ?>
                                <tr>
                                    <td>
                                        <select name="s1_subj[]">
                                            <option value="">Select Subject</option>
                                            <?php foreach($subjects as $group => $list): ?>
                                                <optgroup label="<?= $group ?>">
                                                    <?php foreach($list as $sub): ?><option value="<?= $sub ?>"><?= $sub ?></option><?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="s1_grade[]">
                                            <option value="">Grade</option>
                                            <?php foreach(['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'] as $g): ?><option><?= $g ?></option><?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>

                        <table class="result-table">
                            <thead><tr><th>2nd Sitting Subject</th><th>Grade</th></tr></thead>
                            <tbody>
                                <?php for($i=1; $i<=9; $i++): ?>
                                <tr>
                                    <td>
                                        <select name="s2_subj[]">
                                            <option value="">Select Subject</option>
                                            <?php foreach($subjects as $group => $list): ?>
                                                <optgroup label="<?= $group ?>">
                                                    <?php foreach($list as $sub): ?><option value="<?= $sub ?>"><?= $sub ?></option><?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="s2_grade[]">
                                            <option value="">Grade</option>
                                            <?php foreach(['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9'] as $g): ?><option><?= $g ?></option><?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" name="submit_olevel" style="width:100%; padding:15px; background:#0e5001; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-top:20px;">SUBMIT RESULTS FOR VERIFICATION</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            document.getElementById(id).classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
// 1. Connection and Session
include('../connection.php'); 
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); 
    exit();
}

$email = $_SESSION['user'];

// 2. Fetch the "Verified Receipt" uploaded by the Admin
$query = mysqli_query($conn, "SELECT verified_receipt, fullname FROM login_table WHERE email = '$email'");
$row = mysqli_fetch_assoc($query);

$verified_file = $row['verified_receipt'];
$fullname = $row['fullname'];

// 3. Define the path where Admin uploads are stored
// Note: You should have a folder for admin-processed receipts
$file_path = "../asset/uploads/verified_receipts/" . $verified_file;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Receipt - Faculty of Computing</title>
    <link href="../asset/css/user.css" rel="stylesheet">
    <style>
        .message-card {
            max-width: 500px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .waiting-icon { font-size: 50px; color: #f39c12; margin-bottom: 20px; }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #30e403;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body style="background: #f4f7f6;">

    <div class="message-card">
        <?php 
        // 4. Check if the file exists and is not empty
        if (!empty($verified_file) && file_exists($file_path)) {
            // If file exists, force download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="Original_Acceptance_Receipt_'.str_replace(' ', '_', $fullname).'.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            // 5. If not yet verified/uploaded by admin
            echo "<div class='waiting-icon'>⏳</div>";
            echo "<h2>Verification in Progress</h2>";
            echo "<p style='color: #666;'>Your Remita receipt is currently being verified by the Faculty Admin.</p>";
            echo "<p style='font-weight: bold; color: #0026ff;'>Please wait. Your original receipt will be available here once verification is complete.</p>";
            echo "<a href='uploadacceptance.php' class='btn-back'>Back to Upload Page</a>";
        }
        ?>
    </div>

</body>
</html>
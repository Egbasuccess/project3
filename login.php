<?php
include ("connection.php");
session_start();
$msg = '';

if(isset($_POST['submit'])){
    // Sanitize email input
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Do not sanitize the password; it breaks special characters

    // 1. Fetch the user by email ONLY
    $result = mysqli_query($conn, "SELECT * FROM `login_table` WHERE email = '$email'");

    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        
        // 2. Use password_verify to check the typed password against the hashed one in DB
        if(password_verify($password, $row['password'])){
            
            // 3. Password is correct! Set sessions based on user_type
            if($row['user_type'] == 'user'){
                $_SESSION['user'] = $row['email'];
                header('location:user.php');
            } else {
                $_SESSION['admin'] = $row['email'];
                header('location:admin.php');
            }
            exit();
            
        } else {
            // Password did not match the hash
            $msg = "Incorrect password!";
        }
    } else {
        // Email not found in database
        $msg = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Faculty of Computing</title>
    <link href="asset/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="regcaption">
            <h2>Login Now</h2>
            <p class="msg"><?= $msg ?></p>
        </div>
        <form action="" method="POST">
            <div class="form_group"><input type="email" name="email" placeholder="Email" required></div>
            <div class="form_group"><input type="password" name="password" placeholder="Password" required></div>
            <div class="form_btn"><button type="submit" name="submit">Login</button></div>
            <div class="signin_caption"><p>New here? <a href="register.php">Register Now</a></p></div>
        </form>
    </div>
</body>
</html>
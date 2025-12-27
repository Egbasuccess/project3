<?php
include ("connection.php");
$msg ='';

if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = $_POST['user_type'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    $check_email = mysqli_query($conn, "SELECT * FROM `login_table` WHERE email = '$email'");

    if(mysqli_num_rows($check_email) > 0){
        $msg = "User already exists!";
    } elseif ($password != $cpassword) {
        $msg = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert = "INSERT INTO `login_table`(`fullname`, `email`, `user_type`, `password`) 
                   VALUES ('$name', '$email', '$user_type', '$hashed_password')";
        
        if(mysqli_query($conn, $insert)){
            header('location:login.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Faculty of Computing</title>
    <link href="asset/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="regcaption">
            <h2>Register Account</h2>
            <p class="msg"><?php echo $msg; ?></p>
        </div>
        <form action="" method="POST">
            <div class="form_group"><input type="text" placeholder="Fullname" name="name" required></div>
            <div class="form_group"><input type="email" placeholder="Email" name="email" required></div>
            <div class="form_group">
                <select class="form_control" name="user_type">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form_group"><input type="password" placeholder="Password" name="password" required></div>
            <div class="form_group"><input type="password" placeholder="Confirm Password" name="cpassword" required></div>
            <div class="form_btn"><button type="submit" name="submit">Register</button></div>
            <div class="signin_caption"><p>Already have an account? <a href="login.php">Login Now</a></p></div>
        </form>
    </div>
</body>
</html>
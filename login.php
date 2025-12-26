<?php
session_start();
if(isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli("localhost", "root", "", "loan_system");
    if($conn->connect_error) { die("DB Error: ".$conn->connect_error); }

    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows == 1) {
        $stmt->bind_result($id, $user, $hash);
        $stmt->fetch();
        if(password_verify($password, $hash)) {
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $id;
            // Set a success message
            $_SESSION['login_success'] = "Login to dashboard successfully!";
            header("Location: index.php");
            exit;
        } else { $error = "Incorrect password."; }
    } else { $error = "User not found."; }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Loan Prediction</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: Arial, sans-serif; }
.form-container {
    max-width: 400px;
    margin: 80px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}
h4 { text-align:center; margin-bottom: 10px; color: #0d6efd; }
p.subtitle { text-align:center; color: #6c757d; margin-bottom: 25px; }
</style>
</head>
<body>
<div class="form-container">
    <h4>Login</h4>
    <p class="subtitle">Please login to the Loan Prediction System</p>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
        <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register</a></p>
    </form>
</div>
</body>
</html>

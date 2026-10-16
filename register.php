<?php
require 'db.php';
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        $msg = "Registration successfull! <a href='login.php'>Login Now</a>";
    } catch (PDOException $e) {
        $msg = "Error: Yeh email pehle se registered hai.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quad - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Student Register</h2>
        <p style="color: green;"><?php echo $msg; ?></p>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Register</button>
        </form>
        <p>Pehle se account hai? <a href="login.php">Login karein</a></p>
    </div>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php"); exit;
}

function checkAdminPassword($conn, $inputPass) {
    $stmt = @$conn->prepare("SELECT config_value FROM admin_config WHERE config_key = 'admin_password'");
    if (!$stmt) {
        return ($inputPass === 'Matteo2025');
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if (!$result) {
        $hash = password_hash('Matteo2025', PASSWORD_BCRYPT);
        $ins = $conn->prepare("INSERT INTO admin_config (config_key, config_value) VALUES ('admin_password', ?)");
        if ($ins) { $ins->bind_param("s", $hash); $ins->execute(); }
        return ($inputPass === 'Matteo2025');
    }
    return password_verify($inputPass, $result['config_value']);
}

if (isset($_POST['login']) && checkAdminPassword($conn, $_POST['pass'])) {
    $_SESSION['logged_in'] = true;
}

if (!isset($_SESSION['logged_in'])) {
    echo '<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Outfit",sans-serif;background:#f4f7f6;display:flex;justify-content:center;align-items:center;min-height:100dvh}
.login-box{background:white;padding:48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.08);text-align:center;width:100%;max-width:360px;border-top:3px solid #B8860B}
h2{color:#1C1C1C;margin-bottom:8px;font-size:1.4rem;font-weight:700}
.sub{color:#999;font-size:0.9rem;margin-bottom:28px}
input[type="password"]{width:100%;padding:13px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:1rem;margin-bottom:16px;transition:border-color 0.2s;outline:none;color:#1C1C1C}
input[type="password"]:focus{border-color:#B8860B}
button{width:100%;padding:14px;background:#1C1C1C;color:white;border:none;border-radius:8px;font-family:inherit;font-size:0.95rem;font-weight:600;cursor:pointer;letter-spacing:0.5px;transition:background 0.2s}
button:hover{background:#333}
button:active{transform:scale(0.98)}
</style>
</head>
<body>
<div class="login-box">
<h2>BarberAdmin</h2>
<p class="sub">Accesso riservato</p>
<form method="POST">
<input type="password" name="pass" placeholder="Password" autofocus>
<button type="submit" name="login">ENTRA</button>
</form>
</div>
</body>
</html>';
    exit;
}
?>

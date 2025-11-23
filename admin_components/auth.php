<?php
// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php"); exit;
}

// --- LOGIN ---
$password_segreta = "Matteo2025"; 
if (isset($_POST['login']) && $_POST['pass'] == $password_segreta) { $_SESSION['logged_in'] = true; }
if (!isset($_SESSION['logged_in'])) {
    echo '<div style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f0f2f5; font-family:sans-serif;">
            <form method="POST" style="background:white; padding:40px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); text-align:center;">
                <h2 style="color:#1C1C1C; margin-bottom:20px;">Admin Access</h2>
                <input type="password" name="pass" placeholder="Password" style="padding:12px; border:1px solid #ddd; border-radius:5px; width:100%; box-sizing:border-box; margin-bottom:15px;">
                <button type="submit" name="login" style="width:100%; padding:12px; background:#B8860B; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">ENTRA</button>
            </form>
          </div>'; exit;
}
?>
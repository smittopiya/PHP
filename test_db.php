<!-- Quick password tester — visit: http://localhost/milk-management/test_db.php -->
<?php
$host = 'localhost';
$user = 'root';
$passwords = ['root', 'laragon', 'password', '123456', 'admin', ''];

foreach ($passwords as $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "<h2 style='color:green;font-family:sans-serif'>✅ SUCCESS! Password is: <code style='background:#eee;padding:4px 10px;border-radius:4px'>" . ($pass === '' ? '(empty string)' : htmlspecialchars($pass)) . "</code></h2>";
        echo "<p style='font-family:sans-serif'>Now update <b>db.php</b> and <b>setup.php</b> with this password, then visit <a href='/milk-management/setup.php'>setup.php</a></p>";
        break;
    } catch (PDOException $e) {
        echo "<p style='color:red;font-family:sans-serif'>❌ Not: <b>" . ($pass === '' ? '(empty)' : htmlspecialchars($pass)) . "</b></p>";
    }
}
?>

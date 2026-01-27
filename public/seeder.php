<?php
// save as tools/create_admin.php then run in browser once: http://localhost/pregnancy_risk/tools/create_admin.php
require_once __DIR__ . '/../app/Core/Database.php';

$db = Database::connect();
$pass = password_hash('Admin@12345', PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,?)");
$stmt->execute(['Admin User','admin@local.test',$pass,'admin']);

echo "Admin created: admin@local.test / Admin@12345";
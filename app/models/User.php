<?php

class User {
  public static function findByEmail(string $email): ?array {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active=1 LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
  }
}

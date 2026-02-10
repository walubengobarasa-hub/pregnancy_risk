<?php

class User {

  public static function findByEmail(string $email): ?array {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function findById(int $id): ?array {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function emailExists(string $email): bool {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    return (bool)$stmt->fetchColumn();
  }

  public static function create(array $data): int {
    $db = Database::connect();

    $fullName = trim((string)($data['full_name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $passwordHash = (string)($data['password_hash'] ?? '');
    $role = (string)($data['role'] ?? 'user');

    $stmt = $db->prepare("
      INSERT INTO users (full_name, email, password_hash, role, is_active)
      VALUES (:full_name, :email, :password_hash, :role, 1)
    ");

    $stmt->execute([
      ':full_name' => $fullName,
      ':email' => $email,
      ':password_hash' => $passwordHash,
      ':role' => $role,
    ]);

    return (int)$db->lastInsertId();
  }
}

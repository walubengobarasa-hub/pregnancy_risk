<?php

class User {

  public static function findByEmail(string $email): ?array {
    $db = Database::connect();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function findById(int $id): ?array {
    $db = Database::connect();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function all(): array {
    $db = Database::connect();
    return $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
  }

  public static function emailExists(string $email, ?int $excludeId = null): bool {
    $db = Database::connect();
    if ($excludeId) {
      $stmt = $db->prepare('SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1');
      $stmt->execute([$email, $excludeId]);
    } else {
      $stmt = $db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
    }
    return (bool)$stmt->fetchColumn();
  }

  public static function create(array $data): int {
    $db = Database::connect();

    $stmt = $db->prepare(
      'INSERT INTO users (full_name, email, password_hash, role, is_active)
       VALUES (:full_name, :email, :password_hash, :role, :is_active)'
    );

    $stmt->execute([
      ':full_name' => trim((string)($data['full_name'] ?? '')),
      ':email' => strtolower(trim((string)($data['email'] ?? ''))),
      ':password_hash' => (string)($data['password_hash'] ?? ''),
      ':role' => self::sanitizeRole($data['role'] ?? 'user'),
      ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
    ]);

    return (int)$db->lastInsertId();
  }

  public static function update(int $id, array $data): bool {
    $db = Database::connect();

    $fields = [
      'full_name = :full_name',
      'email = :email',
      'role = :role',
      'is_active = :is_active',
    ];

    $params = [
      ':id' => $id,
      ':full_name' => trim((string)($data['full_name'] ?? '')),
      ':email' => strtolower(trim((string)($data['email'] ?? ''))),
      ':role' => self::sanitizeRole($data['role'] ?? 'user'),
      ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
    ];

    if (!empty($data['password_hash'])) {
      $fields[] = 'password_hash = :password_hash';
      $params[':password_hash'] = (string)$data['password_hash'];
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return true;
  }

  public static function delete(int $id): bool {
    $db = Database::connect();
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
  }

  public static function sanitizeRole(string $role): string {
    $role = strtolower(trim($role));
    return in_array($role, ['admin', 'clinician', 'user'], true) ? $role : 'user';
  }
}

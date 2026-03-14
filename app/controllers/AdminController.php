<?php
require_once __DIR__ . '/../Models/User.php';

class AdminController extends Controller {

  public function index(): void {
    Auth::requireRole(['admin']);
    $users = User::all();
    $this->view('admin/users', ['users' => $users]);
  }

  public function store(): void {
    Auth::requireRole(['admin']);
    $payload = $_POST ?: json_input();

    $fullName = trim((string)($payload['full_name'] ?? ''));
    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $password = (string)($payload['password'] ?? '');
    $role = User::sanitizeRole((string)($payload['role'] ?? 'user'));
    $isActive = !empty($payload['is_active']) ? 1 : 1;

    if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
      header('Location: ' . base_url('admin/users?error=validation'));
      exit;
    }

    if (User::emailExists($email)) {
      header('Location: ' . base_url('admin/users?error=email'));
      exit;
    }

    User::create([
      'full_name' => $fullName,
      'email' => $email,
      'password_hash' => password_hash($password, PASSWORD_DEFAULT),
      'role' => $role,
      'is_active' => $isActive,
    ]);

    header('Location: ' . base_url('admin/users?success=created'));
    exit;
  }

  public function update(): void {
    Auth::requireRole(['admin']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
      header('Location: ' . base_url('admin/users?error=notfound'));
      exit;
    }

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      header('Location: ' . base_url('admin/users?error=validation'));
      exit;
    }

    if (User::emailExists($email, $id)) {
      header('Location: ' . base_url('admin/users?error=email'));
      exit;
    }

    $password = trim((string)($_POST['password'] ?? ''));
    $data = [
      'full_name' => trim((string)($_POST['full_name'] ?? '')),
      'email' => $email,
      'role' => User::sanitizeRole((string)($_POST['role'] ?? 'user')),
      'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($password !== '') {
      $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    User::update($id, $data);
    header('Location: ' . base_url('admin/users?success=updated'));
    exit;
  }

  public function delete(): void {
    Auth::requireRole(['admin']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0 && $id !== Auth::id()) {
      User::delete($id);
    }
    header('Location: ' . base_url('admin/users?success=deleted'));
    exit;
  }
}

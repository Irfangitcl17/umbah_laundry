<?php
class User {
    private PDO $db;
    private string $table = "users";

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function login(string $identifier, string $password): ?array {
        $identifier = trim($identifier);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = :id1 OR no_hp = :id2 LIMIT 1");
        $stmt->execute([
            ':id1' => $identifier,
            ':id2' => $identifier
        ]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function isUsernameExists(string $username): bool {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return (bool)$stmt->fetch();
    }

    public function register(string $username, string $password, string $nama, string $no_hp = '', string $role = 'karyawan'): bool {
        if ($this->isUsernameExists($username)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (username, password, nama, no_hp, role) VALUES (:username, :password, :nama, :no_hp, :role)");
        return $stmt->execute([
            ':username' => htmlspecialchars(trim($username)),
            ':password' => $hashedPassword,
            ':nama'     => htmlspecialchars(trim($nama)),
            ':no_hp'    => htmlspecialchars(trim($no_hp)),
            ':role'     => $role
        ]);
    }
}

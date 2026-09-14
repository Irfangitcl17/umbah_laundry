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

    public function register(string $username, string $password, string $nama, string $no_hp = '', string $role = 'pelanggan', string $alamat = ''): bool {
        if ($this->isUsernameExists($username)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (username, password, nama, no_hp, alamat, role) VALUES (:username, :password, :nama, :no_hp, :alamat, :role)");
        $success = $stmt->execute([
            ':username' => htmlspecialchars(trim($username)),
            ':password' => $hashedPassword,
            ':nama'     => htmlspecialchars(trim($nama)),
            ':no_hp'    => htmlspecialchars(trim($no_hp)),
            ':alamat'   => htmlspecialchars(trim($alamat)),
            ':role'     => $role
        ]);

        if ($success && $role === 'pelanggan') {
            // Sinkronkan ke tabel pelanggan agar data pelanggan langsung terdaftar
            require_once __DIR__ . '/Pelanggan.php';
            $pelangganModel = new Pelanggan($this->db);
            $pelangganModel->findOrCreate(trim($nama), trim($no_hp), trim($alamat));
        }

        return $success;
    }

    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, username, nama, no_hp, alamat, role, created_at FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

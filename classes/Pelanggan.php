<?php
class Pelanggan {
    private PDO $db;
    private string $table = "pelanggan";

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findOrCreate(string $nama, string $no_hp, string $alamat = ''): int {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE no_hp = :no_hp LIMIT 1");
        $stmt->execute([':no_hp' => $no_hp]);
        $pelanggan = $stmt->fetch();

        if ($pelanggan) {
            return (int)$pelanggan['id'];
        }

        $insert = $this->db->prepare("INSERT INTO {$this->table} (nama, no_hp, alamat) VALUES (:nama, :no_hp, :alamat)");
        $insert->execute([
            ':nama'   => htmlspecialchars($nama),
            ':no_hp'  => htmlspecialchars($no_hp),
            ':alamat' => htmlspecialchars($alamat)
        ]);

        return (int)$this->db->lastInsertId();
    }
}
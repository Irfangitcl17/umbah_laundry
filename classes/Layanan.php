<?php
class Layanan {
    private PDO $db;
    private string $table = "layanan";

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function tambahLayanan(string $nama, string $kategori, string $satuan, float $harga): bool {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (nama_layanan, kategori, satuan, harga_per_satuan) VALUES (:nama, :kategori, :satuan, :harga)");
        return $stmt->execute([
            ':nama'     => htmlspecialchars($nama),
            ':kategori' => htmlspecialchars($kategori),
            ':satuan'   => htmlspecialchars($satuan),
            ':harga'    => $harga
        ]);
    }

    public function isLayananDigunakan(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM transaksi WHERE id_layanan = :id");
        $stmt->execute([':id' => $id]);
        return (int)($stmt->fetch()['total'] ?? 0) > 0;
    }

    public function hapusLayanan(int $id): bool {
        if ($this->isLayananDigunakan($id)) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateLayanan(int $id, string $nama, string $kategori, string $satuan, float $harga): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET nama_layanan = :nama, kategori = :kategori, satuan = :satuan, harga_per_satuan = :harga WHERE id = :id");
        return $stmt->execute([
            ':nama'     => htmlspecialchars($nama),
            ':kategori' => htmlspecialchars($kategori),
            ':satuan'   => htmlspecialchars($satuan),
            ':harga'    => $harga,
            ':id'       => $id
        ]);
    }
}
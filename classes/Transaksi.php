<?php
class Transaksi {
    private PDO $db;
    private string $table = "transaksi";

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function buatTransaksi(
        int $id_pelanggan, 
        int $id_layanan, 
        ?int $id_karyawan, 
        float $berat_jumlah, 
        string $metode,
        string $status_pembayaran = 'Lunas',
        string $catatan = ''
    ): string {
        // Ambil harga layanan
        $stmtLayanan = $this->db->prepare("SELECT harga_per_satuan FROM layanan WHERE id = :id");
        $stmtLayanan->execute([':id' => $id_layanan]);
        $layanan = $stmtLayanan->fetch();
        $total_harga = ($layanan ? (float)$layanan['harga_per_satuan'] : 0) * $berat_jumlah;

        // Awalan resmi Umbah Laundry (UMB)
        $kode_transaksi = 'UMB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $stmt = $this->db->prepare("INSERT INTO {$this->table} 
            (kode_transaksi, id_pelanggan, id_layanan, id_karyawan, berat_jumlah, total_harga, metode_pembayaran, status_pembayaran, status_cucian, catatan) 
            VALUES (:kode, :pelanggan, :layanan, :karyawan, :berat, :total, :metode, :status_bayar, 'Antrian', :catatan)");

        $stmt->execute([
            ':kode'         => $kode_transaksi,
            ':pelanggan'    => $id_pelanggan,
            ':layanan'      => $id_layanan,
            ':karyawan'     => $id_karyawan,
            ':berat'        => $berat_jumlah,
            ':total'        => $total_harga,
            ':metode'       => $metode,
            ':status_bayar' => $status_pembayaran,
            ':catatan'      => htmlspecialchars($catatan)
        ]);

        return $kode_transaksi;
    }

    public function getDaftarTransaksi(string $status = '', string $search = ''): array {
        $sql = "SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan, 
                       l.nama_layanan, l.satuan, l.harga_per_satuan, u.nama AS nama_karyawan
                FROM {$this->table} t
                JOIN pelanggan p ON t.id_pelanggan = p.id
                JOIN layanan l ON t.id_layanan = l.id
                LEFT JOIN users u ON t.id_karyawan = u.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($status)) {
            $sql .= " AND t.status_cucian = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (t.kode_transaksi LIKE :s1 OR p.nama LIKE :s2 OR p.no_hp LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        $sql .= " ORDER BY t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getTransaksiById(int $id): ?array {
        $sql = "SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan,
                       l.nama_layanan, l.satuan, l.harga_per_satuan, u.nama AS nama_karyawan
                FROM {$this->table} t
                JOIN pelanggan p ON t.id_pelanggan = p.id
                JOIN layanan l ON t.id_layanan = l.id
                LEFT JOIN users u ON t.id_karyawan = u.id
                WHERE t.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function getTransaksiByPelangganId(int $id_pelanggan): array {
        $sql = "SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan, 
                       l.nama_layanan, l.satuan, l.harga_per_satuan, u.nama AS nama_karyawan
                FROM {$this->table} t
                JOIN pelanggan p ON t.id_pelanggan = p.id
                JOIN layanan l ON t.id_layanan = l.id
                LEFT JOIN users u ON t.id_karyawan = u.id
                WHERE t.id_pelanggan = :id_pelanggan
                ORDER BY t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_pelanggan' => $id_pelanggan]);
        return $stmt->fetchAll();
    }

    public function lacakTransaksi(string $keyword): array {
        $keyword = trim($keyword);
        if (empty($keyword)) return [];

        // Bersihkan angka untuk pencarian nomor telepon
        $cleanPhone = preg_replace('/[^0-9]/', '', $keyword);
        $phoneAlt = '';
        if (str_starts_with($cleanPhone, '62')) {
            $phoneAlt = '0' . substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '0')) {
            $phoneAlt = '62' . substr($cleanPhone, 1);
        }

        $conditions = [
            "t.kode_transaksi LIKE :kCode",
            "p.no_hp = :kExact",
            "p.nama LIKE :kName"
        ];
        $params = [
            ':kCode'  => "%{$keyword}%",
            ':kExact' => $keyword,
            ':kName'  => "%{$keyword}%"
        ];

        if (!empty($cleanPhone)) {
            $conditions[] = "REPLACE(REPLACE(REPLACE(p.no_hp, '-', ''), ' ', ''), '+', '') = :kPhone";
            $params[':kPhone'] = $cleanPhone;
        }
        if (!empty($phoneAlt)) {
            $conditions[] = "REPLACE(REPLACE(REPLACE(p.no_hp, '-', ''), ' ', ''), '+', '') = :kPhoneAlt";
            $params[':kPhoneAlt'] = $phoneAlt;
        }

        $sql = "SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, p.alamat AS alamat_pelanggan, 
                       l.nama_layanan, l.satuan, l.harga_per_satuan, u.nama AS nama_karyawan
                FROM {$this->table} t
                JOIN pelanggan p ON t.id_pelanggan = p.id
                JOIN layanan l ON t.id_layanan = l.id
                LEFT JOIN users u ON t.id_karyawan = u.id
                WHERE (" . implode(" OR ", $conditions) . ")
                ORDER BY t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool {
        $allowed = ['Antrian', 'Dalam Proses', 'Selesai', 'Sudah Diambil', 'Batal'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        if (in_array($status, ['Selesai', 'Sudah Diambil'])) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status_cucian = :status, tanggal_selesai = IFNULL(tanggal_selesai, NOW()) WHERE id = :id");
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status_cucian = :status WHERE id = :id");
        }
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function updateStatusPembayaran(int $id, string $status): bool {
        $allowed = ['Belum Lunas', 'Lunas'];
        if (!in_array($status, $allowed)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status_pembayaran = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function getOmsetHariIni(): float {
        $stmt = $this->db->prepare("SELECT SUM(total_harga) as total FROM {$this->table} WHERE DATE(tanggal_masuk) = CURDATE() AND status_pembayaran = 'Lunas'");
        $stmt->execute();
        $res = $stmt->fetch();
        return (float)($res['total'] ?? 0.0);
    }

    public function getStatistikLaporan(string $tglMulai = '', string $tglSelesai = ''): array {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($tglMulai)) {
            $where .= " AND DATE(tanggal_masuk) >= :tglMulai";
            $params[':tglMulai'] = $tglMulai;
        }
        if (!empty($tglSelesai)) {
            $where .= " AND DATE(tanggal_masuk) <= :tglSelesai";
            $params[':tglSelesai'] = $tglSelesai;
        }

        $sqlOmset = "SELECT SUM(total_harga) as omset FROM {$this->table} {$where} AND status_pembayaran = 'Lunas'";
        $stmt = $this->db->prepare($sqlOmset);
        $stmt->execute($params);
        $omset = (float)($stmt->fetch()['omset'] ?? 0);

        $sqlTotalTrx = "SELECT COUNT(*) as total FROM {$this->table} {$where}";
        $stmt = $this->db->prepare($sqlTotalTrx);
        $stmt->execute($params);
        $totalTrx = (int)($stmt->fetch()['total'] ?? 0);

        $sqlSelesai = "SELECT COUNT(*) as total FROM {$this->table} {$where} AND status_cucian IN ('Selesai', 'Sudah Diambil')";
        $stmt = $this->db->prepare($sqlSelesai);
        $stmt->execute($params);
        $totalSelesai = (int)($stmt->fetch()['total'] ?? 0);

        $sqlBelumLunas = "SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM {$this->table} {$where} AND status_pembayaran = 'Belum Lunas'";
        $stmt = $this->db->prepare($sqlBelumLunas);
        $stmt->execute($params);
        $rowBelumLunas = $stmt->fetch();

        return [
            'omset'               => $omset,
            'total_transaksi'     => $totalTrx,
            'total_selesai'       => $totalSelesai,
            'total_belum_lunas'   => (int)($rowBelumLunas['total'] ?? 0),
            'nominal_belum_lunas' => (float)($rowBelumLunas['nominal'] ?? 0)
        ];
    }
}
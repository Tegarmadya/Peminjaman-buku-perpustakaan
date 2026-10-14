-- Database perpustakaan
DROP DATABASE IF EXISTS perpustakaan;
CREATE DATABASE perpustakaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan;

-- Tabel users
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','anggota') NOT NULL DEFAULT 'anggota',
    no_hp       VARCHAR(20)  DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel buku
CREATE TABLE buku (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_buku       VARCHAR(20)  NOT NULL UNIQUE,
    judul           VARCHAR(200) NOT NULL,
    penulis         VARCHAR(150) NOT NULL,
    penerbit        VARCHAR(150) NOT NULL,
    tahun_terbit    YEAR         NOT NULL,
    kategori        VARCHAR(80)  NOT NULL,
    stok            INT UNSIGNED NOT NULL DEFAULT 1,
    stok_tersedia   INT UNSIGNED NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel peminjaman
CREATE TABLE peminjaman (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    tanggal_pinjam      DATE         NOT NULL,
    tanggal_jatuh_tempo DATE         NOT NULL,
    tanggal_kembali     DATE         DEFAULT NULL,
    status              ENUM('dipinjam','dikembalikan') NOT NULL DEFAULT 'dipinjam',
    denda               DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_peminjaman_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel detail peminjaman
CREATE TABLE detail_peminjaman (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    peminjaman_id   INT UNSIGNED NOT NULL,
    buku_id         INT UNSIGNED NOT NULL,
    CONSTRAINT fk_detail_peminjaman FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_buku       FOREIGN KEY (buku_id)       REFERENCES buku(id)       ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data users
INSERT INTO users (nama, username, password, role, no_hp) VALUES
('Administrator',   'admin',    '$2y$10$cMlwemQYxjEKQjTxgY/p0OT1P5N4uq7R4Lw7ciUKst4kpNJS7sEbu', 'admin',   '081200000001'),
('Tegar Madya',     'tegar',    '$2y$10$nFf2sAppodvuwUjQgOgfUehIa5Yg85C15CpgqYNhjpFYce2MEQYva', 'anggota', '081200000002'),
('Sultan ananda',   'sultan',   '$2y$10$nFf2sAppodvuwUjQgOgfUehIa5Yg85C15CpgqYNhjpFYce2MEQYva', 'anggota', '081200000003'),
('Bernita agustien','bernita',  '$2y$10$nFf2sAppodvuwUjQgOgfUehIa5Yg85C15CpgqYNhjpFYce2MEQYva', 'anggota', '081200000004');

-- Data buku
INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, kategori, stok, stok_tersedia) VALUES
('BK-001', 'Laskar Pelangi',                    'Andrea Hirata',        'Bentang Pustaka',      2005, 'Fiksi',        3, 3),
('BK-002', 'Bumi Manusia',                       'Pramoedya Ananta Toer','Hasta Mitra',          1980, 'Fiksi',        2, 2),
('BK-003', 'Atomic Habits',                      'James Clear',          'Gramedia',             2019, 'Pengembangan Diri', 4, 4),
('BK-004', 'Sapiens: Riwayat Singkat Umat Manusia','Yuval Noah Harari',  'KPG',                  2017, 'Sejarah',      2, 2),
('BK-005', 'Python untuk Data Science',          'Wira Kusuma',          'Elex Media',           2021, 'Teknologi',    3, 3),
('BK-006', 'Belajar Pemrograman Web Modern',     'Andi Wijaya',          'InfoKomputer',         2022, 'Teknologi',    2, 2),
('BK-007', 'The Psychology of Money',            'Morgan Housel',        'Gramedia',             2021, 'Keuangan',     3, 3),
('BK-008', 'Rich Dad Poor Dad',                  'Robert T. Kiyosaki',   'GPU',                  2000, 'Keuangan',     2, 2),
('BK-009', 'Filosofi Teras',                     'Henry Manampiring',    'Kompas',               2018, 'Pengembangan Diri', 3, 3),
('BK-010', 'Pengantar Ilmu Hukum',               'Prof. Sudikno M.',     'Liberty',              2008, 'Hukum',        2, 2),
('BK-011', 'Biologi Sel dan Molekuler',          'Karp Gerald',          'Erlangga',             2016, 'Sains',        2, 2),
('BK-012', 'Matematika Diskrit',                 'Kenneth H. Rosen',     'McGraw-Hill',          2012, 'Matematika',   2, 2),
('BK-013', 'Dasar-Dasar Akuntansi',              'Al. Haryono Jusup',    'STIE YKPN',            2011, 'Ekonomi',      3, 3),
('BK-014', 'Clean Code',                         'Robert C. Martin',     'Prentice Hall',        2008, 'Teknologi',    2, 2),
('BK-015', 'The Alchemist',                      'Paulo Coelho',         'HarperCollins',        1988, 'Fiksi',        3, 3),
('BK-016', 'Homo Deus',                          'Yuval Noah Harari',    'KPG',                  2018, 'Sejarah',      2, 2),
('BK-017', 'Zero to One',                        'Peter Thiel',          'Crown Business',       2014, 'Bisnis',       2, 2),
('BK-018', 'Sejarah Indonesia Modern',           'M.C. Ricklefs',        'Gadjah Mada UP',       2008, 'Sejarah',      2, 2);

-- Data transaksi peminjaman
INSERT INTO peminjaman (user_id, tanggal_pinjam, tanggal_jatuh_tempo, tanggal_kembali, status, denda) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 3  DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 DAY), INTERVAL 7 DAY), NULL,       'dipinjam',      0.00),
(3, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 15 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'dikembalikan', 0.00),
(4, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 12 DAY), INTERVAL 7 DAY), NULL,       'dipinjam',      0.00);

-- Detail item peminjaman
INSERT INTO detail_peminjaman (peminjaman_id, buku_id) VALUES
(1, 1),
(1, 3),
(2, 5),
(3, 7);

-- Update stok buku yang sedang dipinjam
UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id = 1;
UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id = 3;
UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id = 7;

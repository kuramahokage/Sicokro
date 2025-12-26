-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Des 2025 pada 07.52
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sicokro`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `akun`
--

CREATE TABLE `akun` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `tipe` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `aset`
--

CREATE TABLE `aset` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `kode_aset` varchar(100) DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `kondisi` varchar(50) DEFAULT NULL,
  `nilai_perolehan` decimal(15,2) DEFAULT 0.00,
  `tgl_perolehan` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `aset`
--

INSERT INTO `aset` (`id`, `sekolah_id`, `kode_aset`, `nama`, `kategori`, `lokasi`, `kondisi`, `nilai_perolehan`, `tgl_perolehan`, `created_at`) VALUES
(1, 3, 'AST-001', 'Laptop Guru', 'Elektronik', 'Ruang Guru', 'Baik', 15000000.00, '2024-06-01', '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `before_text` text DEFAULT NULL,
  `after_text` text DEFAULT NULL,
  `tgl` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bantuan`
--

CREATE TABLE `bantuan` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT 0.00,
  `tgl` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `tagihan_id` int(11) DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT 0.00,
  `metode` varchar(50) DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `sekolah_id`, `tagihan_id`, `jumlah`, `metode`, `bukti`, `status`, `created_at`) VALUES
(1, 3, 1, 150000.00, 'Transfer Bank', NULL, 'confirmed', '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `referensi_type` varchar(50) DEFAULT NULL,
  `referensi_id` int(11) DEFAULT NULL,
  `akun_id` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT 0.00,
  `tgl` date DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkas`
--

CREATE TABLE `rkas` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `tahun` int(11) DEFAULT NULL,
  `anggaran_total` decimal(15,2) DEFAULT 0.00,
  `realisasi_total` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rkas`
--

INSERT INTO `rkas` (`id`, `sekolah_id`, `tahun`, `anggaran_total`, `realisasi_total`, `created_at`) VALUES
(1, 3, 2025, 15000000.00, 4500000.00, '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkas_item`
--

CREATE TABLE `rkas_item` (
  `id` int(11) NOT NULL,
  `rkas_id` int(11) NOT NULL,
  `akun_id` int(11) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `anggaran` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkjm`
--

CREATE TABLE `rkjm` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `periode_mulai` date DEFAULT NULL,
  `periode_selesai` date DEFAULT NULL,
  `visi` text DEFAULT NULL,
  `misi` text DEFAULT NULL,
  `anggaran_ringkasan` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rkjm`
--

INSERT INTO `rkjm` (`id`, `sekolah_id`, `periode_mulai`, `periode_selesai`, `visi`, `misi`, `anggaran_ringkasan`, `created_at`) VALUES
(1, 3, '2025-01-01', '2027-12-31', 'Menjadi sekolah unggul berbasis karakter', 'Meningkatkan kualitas pendidikan', 50000000.00, '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkt`
--

CREATE TABLE `rkt` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `tahun` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rkt`
--

INSERT INTO `rkt` (`id`, `sekolah_id`, `tahun`, `semester`, `created_at`) VALUES
(1, 3, 2025, 1, '2025-12-19 07:12:24'),
(2, 3, 2025, 2, '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rkt_item`
--

CREATE TABLE `rkt_item` (
  `id` int(11) NOT NULL,
  `rkt_id` int(11) NOT NULL,
  `akun_id` int(11) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `anggaran` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sekolah`
--

CREATE TABLE `sekolah` (
  `id` int(11) NOT NULL,
  `yayasan_id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `jenjang` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kepala_sekolah` varchar(150) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sekolah`
--

INSERT INTO `sekolah` (`id`, `yayasan_id`, `nama`, `jenjang`, `alamat`, `kepala_sekolah`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'SMP Cokroaminoto', 'SMP', 'Jl. Ahmad Yani No. 1, Banjarnegara', 'H. Ahmad Sutrisno, S.Pd', 'aktif', '2025-12-17 05:52:42', NULL),
(2, 1, 'SMA Cokroaminoto', 'SMA', 'Jl. Ahmad Yani No. 2, Banjarnegara', 'Drs. Budi Santoso, M.Pd', 'aktif', '2025-12-17 05:52:42', NULL),
(3, 1, 'SMP Cokroaminoto Banjarnegara', 'SMP', 'Jl. Merdeka No. 10, Banjarnegara', 'Drs. H. Ahmad Sukarno', 'aktif', '2025-12-19 07:12:24', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `nis` varchar(50) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `sekolah_id`, `nis`, `nama`, `kelas`, `created_at`) VALUES
(1, 1, 'SMP001', 'Ali bin Ahmad', '8A', '2025-12-17 05:52:42'),
(2, 1, 'SMP002', 'Fatimah binti Hasan', '8B', '2025-12-17 05:52:42'),
(3, 2, 'SMA001', 'Budi Santoso', '11IPA', '2025-12-17 05:52:42'),
(4, 3, 'SMP2025001', 'Ali bin Ahmad', '7A', '2025-12-19 07:12:24'),
(5, 3, 'SMP2025002', 'Budi Santoso', '7B', '2025-12-19 07:12:24'),
(6, 3, 'SMP2025003', 'Citra Lestari', '8A', '2025-12-19 07:12:24'),
(7, 3, 'SMP2025004', 'Dewi Kartika', '8B', '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sumbangan`
--

CREATE TABLE `sumbangan` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `nama_pendonasi` varchar(255) DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT 0.00,
  `tgl` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tagihan`
--

CREATE TABLE `tagihan` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `kode_tagihan` varchar(100) DEFAULT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT 0.00,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `tgl_dibuat` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tagihan`
--

INSERT INTO `tagihan` (`id`, `sekolah_id`, `siswa_id`, `kode_tagihan`, `deskripsi`, `jumlah`, `status`, `tgl_dibuat`, `due_date`, `created_at`) VALUES
(1, 3, 4, 'SPP-2025-4', 'SPP Bulanan Januari 2025', 150000.00, 'unpaid', '2025-01-01', '2025-01-15', '2025-12-19 07:12:24'),
(2, 3, 5, 'SPP-2025-5', 'SPP Bulanan Januari 2025', 150000.00, 'unpaid', '2025-01-01', '2025-01-15', '2025-12-19 07:12:24'),
(3, 3, 6, 'SPP-2025-6', 'SPP Bulanan Januari 2025', 150000.00, 'unpaid', '2025-01-01', '2025-01-15', '2025-12-19 07:12:24'),
(4, 3, 7, 'SPP-2025-7', 'SPP Bulanan Januari 2025', 150000.00, 'unpaid', '2025-01-01', '2025-01-15', '2025-12-19 07:12:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `sekolah_id` int(11) DEFAULT NULL,
  `yayasan_id` int(11) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `sekolah_id`, `yayasan_id`, `nama`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'Super Admin Yayasan', 'admin@cokroaminoto.sch.id', '$2y$10$DdVTnbgZ64/Wvk5fTe45su7LvPRuRiCHSu0GzZPNIxOIHZUAFOJyS', 'super_admin', '2025-12-17 05:52:42', '2025-12-19 06:50:47'),
(2, 1, 1, 'Admin SMP', 'admin_smp@cokroaminoto.sch.id', '$2y$10$OCQnaQ8K.R6aI.9OYdTtw.ggNt4xW.lnMbdUN5baE6wigNhvIHi72', 'admin_sekolah', '2025-12-17 05:52:42', NULL),
(3, 1, 1, 'Bendahara SMP', 'bendahara_smp@cokroaminoto.sch.id', '$2y$10$xoJO1kTvQCkBxc7ybEn3aeBIkgeiBkyKK3fxR1Vu4mxcrlbxbGvYC', 'bendahara', '2025-12-17 05:52:42', NULL),
(4, 2, 1, 'Admin SMA', 'admin_sma@cokroaminoto.sch.id', '$2y$10$JBuoShlxFxLGDtvjZLa3Xut5RTLZ09PwhdZn6rebSCLHoxMsv1lZq', 'admin_sekolah', '2025-12-17 05:52:42', NULL),
(5, NULL, NULL, 'API Test User', 'apitest+local@local', '$2y$10$s.sHeqgowB4z4VIMx0LKjeMm5fl4sC.P6I.TiH2yuCLJoOZAW0l1y', 'super_admin', '2025-12-19 06:39:10', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `yayasan`
--

CREATE TABLE `yayasan` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `npwp` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `yayasan`
--

INSERT INTO `yayasan` (`id`, `nama`, `alamat`, `email`, `telepon`, `npwp`, `logo`, `created_at`, `updated_at`) VALUES
(1, 'Yayasan Pendidikan Islam Cokroaminoto', 'Jl. Ahmad Yani No. 1, Banjarnegara, Jawa Tengah', 'info@cokroaminoto.sch.id', '(0286) 123456', NULL, NULL, '2025-12-17 05:52:42', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_akun_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `aset`
--
ALTER TABLE `aset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aset_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `bantuan`
--
ALTER TABLE `bantuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bantuan_sekolah` (`sekolah_id`),
  ADD KEY `fk_bantuan_siswa` (`siswa_id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pembayaran_sekolah` (`sekolah_id`),
  ADD KEY `fk_pembayaran_tagihan` (`tagihan_id`);

--
-- Indeks untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengeluaran_sekolah` (`sekolah_id`),
  ADD KEY `fk_pengeluaran_akun` (`akun_id`);

--
-- Indeks untuk tabel `rkas`
--
ALTER TABLE `rkas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkas_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `rkas_item`
--
ALTER TABLE `rkas_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkas_item_rkas` (`rkas_id`),
  ADD KEY `fk_rkas_item_akun` (`akun_id`);

--
-- Indeks untuk tabel `rkjm`
--
ALTER TABLE `rkjm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkjm_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `rkt`
--
ALTER TABLE `rkt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkt_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `rkt_item`
--
ALTER TABLE `rkt_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkt_item_rkt` (`rkt_id`),
  ADD KEY `fk_rkt_item_akun` (`akun_id`);

--
-- Indeks untuk tabel `sekolah`
--
ALTER TABLE `sekolah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sekolah_yayasan` (`yayasan_id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_siswa_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `sumbangan`
--
ALTER TABLE `sumbangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sumbangan_sekolah` (`sekolah_id`);

--
-- Indeks untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tagihan_sekolah` (`sekolah_id`),
  ADD KEY `fk_tagihan_siswa` (`siswa_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_sekolah` (`sekolah_id`),
  ADD KEY `fk_users_yayasan` (`yayasan_id`);

--
-- Indeks untuk tabel `yayasan`
--
ALTER TABLE `yayasan`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `akun`
--
ALTER TABLE `akun`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `aset`
--
ALTER TABLE `aset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `bantuan`
--
ALTER TABLE `bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rkas`
--
ALTER TABLE `rkas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rkas_item`
--
ALTER TABLE `rkas_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rkjm`
--
ALTER TABLE `rkjm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rkt`
--
ALTER TABLE `rkt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `rkt_item`
--
ALTER TABLE `rkt_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sekolah`
--
ALTER TABLE `sekolah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `sumbangan`
--
ALTER TABLE `sumbangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `yayasan`
--
ALTER TABLE `yayasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD CONSTRAINT `fk_akun_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `aset`
--
ALTER TABLE `aset`
  ADD CONSTRAINT `fk_aset_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bantuan`
--
ALTER TABLE `bantuan`
  ADD CONSTRAINT `fk_bantuan_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bantuan_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `fk_pengeluaran_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`id`),
  ADD CONSTRAINT `fk_pengeluaran_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rkas`
--
ALTER TABLE `rkas`
  ADD CONSTRAINT `fk_rkas_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rkas_item`
--
ALTER TABLE `rkas_item`
  ADD CONSTRAINT `fk_rkas_item_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`id`),
  ADD CONSTRAINT `fk_rkas_item_rkas` FOREIGN KEY (`rkas_id`) REFERENCES `rkas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rkjm`
--
ALTER TABLE `rkjm`
  ADD CONSTRAINT `fk_rkjm_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rkt`
--
ALTER TABLE `rkt`
  ADD CONSTRAINT `fk_rkt_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rkt_item`
--
ALTER TABLE `rkt_item`
  ADD CONSTRAINT `fk_rkt_item_akun` FOREIGN KEY (`akun_id`) REFERENCES `akun` (`id`),
  ADD CONSTRAINT `fk_rkt_item_rkt` FOREIGN KEY (`rkt_id`) REFERENCES `rkt` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sekolah`
--
ALTER TABLE `sekolah`
  ADD CONSTRAINT `fk_sekolah_yayasan` FOREIGN KEY (`yayasan_id`) REFERENCES `yayasan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sumbangan`
--
ALTER TABLE `sumbangan`
  ADD CONSTRAINT `fk_sumbangan_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  ADD CONSTRAINT `fk_tagihan_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tagihan_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_yayasan` FOREIGN KEY (`yayasan_id`) REFERENCES `yayasan` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql310.infinityfree.com
-- Generation Time: Dec 19, 2025 at 02:57 AM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40712867_simba`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL COMMENT 'Foreign key ke tabel pengajuan',
  `ktp` varchar(255) DEFAULT NULL COMMENT 'File KTP (WAJIB)',
  `kk` varchar(255) DEFAULT NULL COMMENT 'File Kartu Keluarga (WAJIB)',
  `slip_gaji` varchar(255) DEFAULT NULL COMMENT 'File slip gaji / bukti penghasilan',
  `foto_rumah` varchar(255) DEFAULT NULL COMMENT 'Foto rumah tampak depan',
  `surat_keterangan_rumah` varchar(255) DEFAULT NULL COMMENT 'Surat keterangan kepemilikan rumah',
  `rekening_listrik` varchar(255) DEFAULT NULL COMMENT 'Rekening listrik bulan terakhir',
  `daftar_pengeluaran` varchar(255) DEFAULT NULL COMMENT 'Daftar pengeluaran bulanan',
  `kartu_pelajar_anak` varchar(255) DEFAULT NULL COMMENT 'Kartu pelajar anak (jika ada)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel dokumen pendukung';

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id`, `id_pengajuan`, `ktp`, `kk`, `slip_gaji`, `foto_rumah`, `surat_keterangan_rumah`, `rekening_listrik`, `daftar_pengeluaran`, `kartu_pelajar_anak`) VALUES
(22, 22, '', '', '', '', '', '', '', ''),
(23, 23, '', '', '', '', '', '', '', ''),
(24, 24, '', '', '', '', '', '', '', ''),
(25, 25, '', '', '', '', '', '', '', ''),
(26, 26, '', '', '', '', '', '', '', ''),
(28, 28, '', '', '', '', '', '', '', ''),
(29, 29, '', '', '', '', '', '', '', ''),
(30, 30, '', '', '', '', '', '', '', ''),
(31, 31, '', '', '', '', '', '', '', ''),
(32, 32, '', '', '', '', '', '', '', ''),
(34, 34, 'user_4/ktp.png', 'user_4/kk.png', '', '', '', '', '', ''),
(35, 35, 'user_13/ktp.jpeg', 'user_13/kk.jpeg', '', '', '', '', '', ''),
(36, 36, '', '', '', '', '', '', '', ''),
(37, 37, 'user_15/ktp.jpg', 'user_15/kk.png', 'user_15/slip_gaji.png', 'user_15/foto_rumah.png', 'user_15/surat_keterangan_rumah.png', 'user_15/rekening_listrik.jpg', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL COMMENT 'Foreign key ke tabel user',
  `id_program` int(11) DEFAULT NULL COMMENT 'Foreign key ke program bantuan',
  `nik` varchar(16) NOT NULL COMMENT 'NIK pemohon',
  `no_kk` varchar(16) NOT NULL COMMENT 'Nomor Kartu Keluarga',
  `nama_lengkap` varchar(255) NOT NULL COMMENT 'Nama lengkap pemohon',
  `alamat` text NOT NULL COMMENT 'Alamat lengkap',
  `no_hp` varchar(15) NOT NULL COMMENT 'Nomor HP yang bisa dihubungi',
  `gaji` decimal(15,2) NOT NULL COMMENT 'Penghasilan per bulan (nilai tengah range)',
  `status_rumah` varchar(50) NOT NULL COMMENT 'Kepemilikan rumah: Sewa, Keluarga, Pribadi',
  `daya_listrik` varchar(50) NOT NULL COMMENT 'Kelistrikan: Menumpang, Pribadi 450 Watt, dst',
  `pengeluaran` decimal(15,2) NOT NULL COMMENT 'Pengeluaran per bulan (nilai tengah range)',
  `jml_keluarga` int(11) NOT NULL COMMENT 'Jumlah anggota keluarga (nilai representatif)',
  `jml_anak_sekolah` int(11) NOT NULL COMMENT 'Jumlah anak usia sekolah (5-18 tahun)',
  `status` enum('Menunggu Verifikasi','Sedang Diverifikasi','Terverifikasi','Ditolak') NOT NULL DEFAULT 'Menunggu Verifikasi' COMMENT 'Status pengajuan',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu pengajuan dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel pengajuan bantuan sosial';

--
-- Dumping data for table `pengajuan`
--

INSERT INTO `pengajuan` (`id`, `id_user`, `id_program`, `nik`, `no_kk`, `nama_lengkap`, `alamat`, `no_hp`, `gaji`, `status_rumah`, `daya_listrik`, `pengeluaran`, `jml_keluarga`, `jml_anak_sekolah`, `status`, `tanggal_dibuat`) VALUES
(22, 4, 6, '1111111111111111', '1111111111111112', 'EOM SEONGHYEON', 'kk', '0812564376437', '1500000.00', 'Sewa', 'Menumpang', '2750000.00', 1, 0, 'Terverifikasi', '2025-12-16 23:39:02'),
(23, 8, 6, '1231231231231231', '1231231231231232', 'GOJO SATORU', 'SATORU', '0812564376437', '2750000.00', 'Keluarga', 'Pribadi 900 Watt', '2750000.00', 4, 1, 'Terverifikasi', '2025-12-16 23:39:45'),
(24, 7, 6, '4444444444444444', '4444444444444442', 'LEE HEESEUNG', 'SATORU', '0812564376437', '500000.00', 'Sewa', 'Pribadi 450 Watt', '500000.00', 1, 0, 'Terverifikasi', '2025-12-16 23:40:24'),
(25, 9, 7, '1234123412341234', '1231231231231232', 'rake', 'klampengan', '0812564376437', '500000.00', 'Sewa', 'Menumpang', '500000.00', 5, 2, 'Terverifikasi', '2025-12-17 08:53:14'),
(26, 10, 7, '1122112211221122', '1111111111111112', 'sementara', 'aaa', '081234567890', '500000.00', 'Keluarga', 'Pribadi 900 Watt', '500000.00', 4, 3, 'Terverifikasi', '2025-12-17 08:55:20'),
(28, 4, 8, '1111111111111111', '1231231231231232', 'EOM SEONGHYEON', 'dsda', '081234567890', '1500000.00', 'Keluarga', 'Pribadi 450 Watt', '1500000.00', 1, 0, 'Terverifikasi', '2025-12-17 09:08:02'),
(29, 6, 8, '3333333333333333', '1111111111111112', 'MARTIN WIJAYA', 'qfqf', '0812564376437', '2750000.00', 'Sewa', 'Pribadi 900 Watt', '1500000.00', 3, 2, 'Terverifikasi', '2025-12-17 09:09:02'),
(30, 7, 8, '3333333333333333', '1231231231231232', 'LEE HEESEUNG', 'casdw', '0812564376437', '2750000.00', 'Sewa', 'Pribadi 900 Watt', '4000000.00', 4, 2, 'Ditolak', '2025-12-17 09:09:52'),
(31, 10, 9, '1122112211221122', '1111111111111112', 'GOJO SATORUe', 'ee', '0812345678333', '2750000.00', 'Keluarga', 'Pribadi 450 Watt', '1500000.00', 4, 1, 'Terverifikasi', '2025-12-17 09:14:31'),
(32, 9, 9, '1234123412341234', '1010101010101011', 'JAMES SIREGAR', '11', '0812564376437', '1500000.00', 'Keluarga', 'Pribadi 1200 Watt', '1500000.00', 5, 2, 'Terverifikasi', '2025-12-17 09:15:58'),
(34, 4, 10, '1111111111111111', '1111111111111112', 'EOM SEONGHYEON', 'JL. TAMBAKBAYAN', '0812564376437', '500000.00', 'Sewa', 'Pribadi 450 Watt', '1500000.00', 1, 1, 'Terverifikasi', '2025-12-18 15:18:04'),
(35, 13, 10, '1234567887654321', '8765432112345678', 'Na Daeman', 'Sleman', '08123456789', '1500000.00', 'Sewa', 'Pribadi 450 Watt', '1500000.00', 5, 3, 'Terverifikasi', '2025-12-18 15:43:06'),
(36, 14, 10, '7136137136137136', '1234567891234567', 'Chae Mong', 'Jalan Talon 15, Wildes District, Yogyakarta', '08123456789', '500000.00', 'Sewa', 'Menumpang', '500000.00', 3, 1, 'Terverifikasi', '2025-12-19 05:37:46'),
(37, 15, 10, '3328332833283328', '3328332833283328', 'nana', 'Gangnam', '0888888888', '500000.00', 'Sewa', 'Menumpang', '500000.00', 1, 0, 'Terverifikasi', '2025-12-19 06:51:29');

-- --------------------------------------------------------

--
-- Table structure for table `program_bantuan`
--

CREATE TABLE `program_bantuan` (
  `id` int(11) NOT NULL,
  `nama_program` varchar(255) NOT NULL COMMENT 'Nama program bantuan',
  `deskripsi` text DEFAULT NULL COMMENT 'Deskripsi program',
  `kuota` int(11) NOT NULL COMMENT 'Jumlah penerima',
  `tanggal_mulai` date NOT NULL COMMENT 'Tanggal mulai program',
  `tanggal_selesai` date NOT NULL COMMENT 'Tanggal selesai program',
  `status` enum('Aktif','Tutup') NOT NULL DEFAULT 'Aktif' COMMENT 'Status program',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel program bantuan';

--
-- Dumping data for table `program_bantuan`
--

INSERT INTO `program_bantuan` (`id`, `nama_program`, `deskripsi`, `kuota`, `tanggal_mulai`, `tanggal_selesai`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Program Makan Gratis Anies Pranowo', 'Makan gratis wok', 2, '2025-12-16', '2026-01-01', 'Tutup', '2025-12-16 12:15:48', '2025-12-16 23:36:53'),
(6, 'Program BLT Tahap 2 2026', 'cape gw ajg', 2, '2025-12-12', '2026-01-01', 'Tutup', '2025-12-16 23:38:01', '2025-12-16 23:44:56'),
(7, 'Program BLT Tahap 88 2026', 'sss', 2, '2025-12-16', '2025-12-18', 'Tutup', '2025-12-17 08:51:34', '2025-12-17 09:00:38'),
(8, 'TEST PROGRAM', 'ADILI JOKOWI', 1, '2025-11-11', '2026-11-01', 'Tutup', '2025-12-17 09:07:08', '2025-12-17 09:11:10'),
(9, 'testing 2 tolak 1', 'eeeee', 2, '2025-12-16', '2025-12-18', 'Tutup', '2025-12-17 09:13:04', '2025-12-17 09:18:27'),
(10, 'Program BLT Tahap 3 2025', 'Bantuan Tunai dari Anies Pranowo', 2, '2024-12-01', '2026-01-01', 'Tutup', '2025-12-18 15:16:18', '2025-12-19 06:58:37'),
(11, 'Program BLT Tahap 99 2025', '', 2, '2025-12-19', '2025-12-21', 'Aktif', '2025-12-19 07:00:15', '2025-12-19 07:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `total_nilai`
--

CREATE TABLE `total_nilai` (
  `id` int(11) NOT NULL,
  `id_program` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL COMMENT 'Foreign key ke tabel pengajuan',
  `skor_total` decimal(10,4) NOT NULL COMMENT 'Skor hasil perhitungan SAW (0-1)',
  `peringkat` int(11) DEFAULT NULL COMMENT 'Peringkat berdasarkan skor',
  `tanggal_hitung` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu perhitungan dilakukan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel hasil perhitungan SAW';

--
-- Dumping data for table `total_nilai`
--

INSERT INTO `total_nilai` (`id`, `id_program`, `id_pengajuan`, `skor_total`, `peringkat`, `tanggal_hitung`) VALUES
(20, 7, 26, '0.8425', 2, '2025-12-17 10:08:15'),
(21, 7, 25, '0.7550', 3, '2025-12-17 10:08:15'),
(22, 6, 23, '0.9000', 1, '2025-12-17 10:09:31'),
(23, 6, 24, '0.6450', 2, '2025-12-17 10:09:31'),
(24, 6, 22, '0.5750', 3, '2025-12-17 10:09:31'),
(25, 8, 29, '0.9400', 1, '2025-12-17 10:09:46'),
(26, 8, 28, '0.7583', 2, '2025-12-17 10:09:46'),
(27, 9, 32, '0.9250', 1, '2025-12-19 06:57:21'),
(28, 9, 31, '0.8792', 2, '2025-12-19 06:57:21'),
(31, 10, 35, '0.9333', 1, '2025-12-19 07:09:49'),
(32, 10, 36, '0.7950', 2, '2025-12-19 07:09:49'),
(33, 10, 37, '0.7325', 3, '2025-12-19 07:09:49'),
(34, 10, 34, '0.7108', 4, '2025-12-19 07:09:49');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL COMMENT 'Nama lengkap pengguna',
  `nik` char(16) NOT NULL COMMENT 'NIK 16 digit',
  `password` varchar(255) NOT NULL COMMENT 'Password terenkripsi',
  `role` enum('admin','user') NOT NULL DEFAULT 'user' COMMENT 'Role pengguna',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Tanggal registrasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel pengguna sistem';

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `nama`, `nik`, `password`, `role`, `created_at`) VALUES
(3, 'Administrator', '1234567890123456', '$2y$10$HdtXPDJzhxJgsLykMue/Ce/9z1AjrqB01lFdzd1jkvdeKoY2nBV9C', 'admin', '2025-12-01 19:04:40'),
(4, 'eom seonghyeon', '1111111111111111', '$2y$10$FlqC01kJfzu3zgsuAlsAq.MR734tzGSBn36.6GrbElLnWzUzJ/xU6', 'user', '2025-12-16 08:59:46'),
(6, 'MARTIN WIJAYA', '3333333333333333', '$2y$10$jORMAFh2.O0em9SatKh6Ve2PDbO1fAPJiJqAIlsE2WH/pmvs7mxl.', 'user', '2025-12-16 10:34:27'),
(7, 'LEE HEESEUNG', '4444444444444444', '$2y$10$kLdmD0VT4M0rvx5IXtDeQOITk1GEaPdHk2oxs4nwEloxXjS.1RiEa', 'user', '2025-12-16 12:21:14'),
(8, 'GOJO SATORU', '1231231231231231', '$2y$10$OSoHyfI01GHFfR59Ks8vguh6a7hwP6mc33ro2v.Vp3bnONQBfGgge', 'user', '2025-12-16 22:06:03'),
(9, 'rake', '1234123412341234', '$2y$10$3E6CZEFph4ssh5gLvy.f8eN0ID3nyWCVmOXfKL9TQ2hZPp68ORcTy', 'user', '2025-12-17 08:49:49'),
(10, 'sementara', '1122112211221122', '$2y$10$Ds7PgJIWCIbVYzMbSiLhXO16iTlfgmxPzlTtbg1nV8m/LbuLsqjzG', 'user', '2025-12-17 08:54:03'),
(12, 'andini andaresta', '1991991991991991', '$2y$10$GLTKH.KLlbO/Lz3OuLrwvOE3Fg8n7nhdhNZa.j1iBFQO13uieorHa', 'user', '2025-12-18 15:13:43'),
(13, 'Na Daeman', '1234567887654321', '$2y$10$TpXIaG0fw/3ieBDdzuuqCePsi9fAqwkfIuuG2trcqqz78J/fMNKWq', 'user', '2025-12-18 15:38:39'),
(14, 'Chae Mong', '7136137136137136', '$2y$10$YITI.y3r3C5u1xKyPXB8fu.eoo.pREUduCGGMMFiCLem0Jytt9fb2', 'user', '2025-12-18 16:48:11'),
(15, 'Nana', '3328332833283328', '$2y$10$QBcw5ETGqtqsarHHDdQqwuxx.u7GNOWDmqInA9Ho.e.QtQxgsDAGC', 'user', '2025-12-19 06:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `verifikasi`
--

CREATE TABLE `verifikasi` (
  `id` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL COMMENT 'Foreign key ke tabel pengajuan',
  `id_petugas` int(11) NOT NULL COMMENT 'Foreign key ke tabel user (admin)',
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu verifikasi',
  `status` enum('Layak','Tidak Layak','Perlu Perbaikan') NOT NULL COMMENT 'Hasil verifikasi',
  `catatan` text DEFAULT NULL COMMENT 'Catatan dari petugas verifikasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel hasil verifikasi';

--
-- Dumping data for table `verifikasi`
--

INSERT INTO `verifikasi` (`id`, `id_pengajuan`, `id_petugas`, `tanggal`, `status`, `catatan`) VALUES
(24, 22, 3, '2025-12-16 23:40:43', 'Layak', ''),
(25, 23, 3, '2025-12-16 23:40:58', 'Layak', ''),
(26, 24, 3, '2025-12-16 23:41:04', 'Layak', ''),
(28, 26, 3, '2025-12-17 08:59:53', 'Layak', ''),
(29, 25, 3, '2025-12-17 09:00:23', 'Layak', ''),
(30, 28, 3, '2025-12-17 09:10:26', 'Layak', ''),
(31, 29, 3, '2025-12-17 09:10:39', 'Layak', ''),
(32, 30, 3, '2025-12-17 09:10:46', 'Tidak Layak', ''),
(33, 31, 3, '2025-12-17 09:17:53', 'Layak', ''),
(34, 32, 3, '2025-12-17 09:18:03', 'Layak', ''),
(36, 34, 3, '2025-12-18 15:18:44', 'Layak', 'ok'),
(37, 37, 3, '2025-12-19 06:55:39', 'Layak', ''),
(38, 36, 3, '2025-12-19 07:08:53', 'Layak', ''),
(39, 35, 3, '2025-12-19 07:09:02', 'Layak', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajuan` (`id_pengajuan`);

--
-- Indexes for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `status` (`status`),
  ADD KEY `id_program` (`id_program`),
  ADD KEY `idx_pengajuan_user_program` (`id_user`,`id_program`,`status`);

--
-- Indexes for table `program_bantuan`
--
ALTER TABLE `program_bantuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_status` (`status`,`tanggal_mulai`);

--
-- Indexes for table `total_nilai`
--
ALTER TABLE `total_nilai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_program_pengajuan` (`id_program`,`id_pengajuan`),
  ADD KEY `id_pengajuan` (`id_pengajuan`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indexes for table `verifikasi`
--
ALTER TABLE `verifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajuan` (`id_pengajuan`),
  ADD KEY `id_petugas` (`id_petugas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `program_bantuan`
--
ALTER TABLE `program_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `total_nilai`
--
ALTER TABLE `total_nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `verifikasi`
--
ALTER TABLE `verifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `pengajuan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_program_fk` FOREIGN KEY (`id_program`) REFERENCES `program_bantuan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `total_nilai`
--
ALTER TABLE `total_nilai`
  ADD CONSTRAINT `total_nilai_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verifikasi`
--
ALTER TABLE `verifikasi`
  ADD CONSTRAINT `verifikasi_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verifikasi_ibfk_2` FOREIGN KEY (`id_petugas`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 12:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simba`
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
(16, 16, '', '', '', '', '', '', '', ''),
(17, 17, '', '', '', '', '', '', '', ''),
(18, 18, '', '', '', '', '', '', '', '');

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
(16, 4, 5, '1111111111111111', '1111111111111112', 'EOM SEONGHYEON', 'aaaa', '0812564376437', 1500000.00, 'Keluarga', 'Pribadi 900 Watt', 1500000.00, 3, 0, 'Terverifikasi', '2025-12-16 22:22:00'),
(17, 6, 5, '3333333333333333', '1231231231231232', 'MARTIN WIJAYA', 'ssafa', '0812564376437', 1500000.00, 'Sewa', 'Pribadi 450 Watt', 1500000.00, 3, 1, 'Terverifikasi', '2025-12-16 22:22:44'),
(18, 7, 5, '4444444444444444', '4444444444444442', 'LEE HEESEUNG', 'vgvh', '0812564376437', 2750000.00, 'Keluarga', 'Pribadi 450 Watt', 1500000.00, 4, 2, 'Terverifikasi', '2025-12-16 22:23:42');

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
(5, 'Program Makan Gratis Anies Pranowo', 'Makan gratis wok', 2, '2025-12-16', '2026-01-01', 'Tutup', '2025-12-16 12:15:48', '2025-12-16 22:38:37');

--
-- Triggers `program_bantuan`
--
DELIMITER $$
CREATE TRIGGER `before_insert_program_bantuan` BEFORE INSERT ON `program_bantuan` FOR EACH ROW BEGIN
    IF NEW.status = 'Aktif' THEN
        IF EXISTS (SELECT 1 FROM program_bantuan WHERE status = 'Aktif') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hanya boleh ada 1 program aktif';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_program_bantuan` BEFORE UPDATE ON `program_bantuan` FOR EACH ROW BEGIN
    IF NEW.status = 'Aktif' AND OLD.status != 'Aktif' THEN
        IF EXISTS (SELECT 1 FROM program_bantuan WHERE status = 'Aktif' AND id != NEW.id) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hanya boleh ada 1 program aktif';
        END IF;
    END IF;
END
$$
DELIMITER ;

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
(8, 'GOJO SATORU', '1231231231231231', '$2y$10$OSoHyfI01GHFfR59Ks8vguh6a7hwP6mc33ro2v.Vp3bnONQBfGgge', 'user', '2025-12-16 22:06:03');

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
(18, 16, 3, '2025-12-16 22:24:04', 'Layak', ''),
(19, 17, 3, '2025-12-16 22:24:20', 'Layak', ''),
(20, 18, 3, '2025-12-16 22:24:44', 'Layak', '');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `program_bantuan`
--
ALTER TABLE `program_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `total_nilai`
--
ALTER TABLE `total_nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `verifikasi`
--
ALTER TABLE `verifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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

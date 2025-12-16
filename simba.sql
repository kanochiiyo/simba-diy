-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 09:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `total_nilai`
--

CREATE TABLE `total_nilai` (
  `id` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL COMMENT 'Foreign key ke tabel pengajuan',
  `skor_total` decimal(10,4) NOT NULL COMMENT 'Skor hasil perhitungan SAW (0-1)',
  `peringkat` int(11) DEFAULT NULL COMMENT 'Peringkat berdasarkan skor',
  `tanggal_hitung` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu perhitungan dilakukan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel hasil perhitungan SAW';


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
(2, 'nindi', '1010101010101010', '$2y$10$VRhJ/mK3b/mGRb.U72.J4.NeTM3DxyIw6fQq9lZQqWXMmapKEi4NW', 'user', '2025-11-30 15:29:11'), -- dini123
(3, 'Administrator', '1234567890123456', '$2y$10$HdtXPDJzhxJgsLykMue/Ce/9z1AjrqB01lFdzd1jkvdeKoY2nBV9C', 'admin', '2025-12-01 19:04:40'); -- admin123

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
  ADD KEY `id_program` (`id_program`);

--
-- Indexes for table `program_bantuan`
--
ALTER TABLE `program_bantuan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `total_nilai`
--
ALTER TABLE `total_nilai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengajuan` (`id_pengajuan`),
  ADD KEY `peringkat` (`peringkat`);

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

-- Index untuk pencarian program aktif
CREATE INDEX idx_program_status ON program_bantuan(status, tanggal_mulai);

-- Index untuk pencarian pengajuan berdasarkan user dan program
CREATE INDEX idx_pengajuan_user_program ON pengajuan(id_user, id_program, status);

-- Index untuk ranking queries
CREATE INDEX idx_total_nilai_ranking ON total_nilai(peringkat, skor_total);

DELIMITER //
CREATE TRIGGER before_insert_program_bantuan
BEFORE INSERT ON program_bantuan
FOR EACH ROW
BEGIN
    IF NEW.status = 'Aktif' THEN
        IF EXISTS (SELECT 1 FROM program_bantuan WHERE status = 'Aktif') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hanya boleh ada 1 program aktif';
        END IF;
    END IF;
END//

CREATE TRIGGER before_update_program_bantuan
BEFORE UPDATE ON program_bantuan
FOR EACH ROW
BEGIN
    IF NEW.status = 'Aktif' AND OLD.status != 'Aktif' THEN
        IF EXISTS (SELECT 1 FROM program_bantuan WHERE status = 'Aktif' AND id != NEW.id) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Hanya boleh ada 1 program aktif';
        END IF;
    END IF;
END//
DELIMITER ;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `program_bantuan`
--
ALTER TABLE `program_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `total_nilai`
--
ALTER TABLE `total_nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `verifikasi`
--
ALTER TABLE `verifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 27 Nov 2026 pada 19.41
-- Versi server: 10.11.14-MariaDB-log
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `usgallery`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `harga_produksi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`, `harga`, `harga_produksi`, `subtotal`) VALUES
(1, 1, 2, 2, 45000.00, 0.00, 90000.00),
(2, 2, 2, 1, 45000.00, 0.00, 45000.00),
(3, 3, 3, 1, 30000.00, 22000.00, 30000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(80) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Botol Minum & Aksesoris', 'Tumbler dan botol minum'),
(2, 'Tas', 'Tote bag dan tas kampus'),
(3, 'Payung', 'Payung lipat dan panjang'),
(4, 'Custom', 'Pesanan desain sendiri'),
(5, 'Gelas Minum', NULL),
(6, 'Topi', NULL),
(7, 'Alat Tulis', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `no_telepon`, `alamat`) VALUES
(1, 'bella gila', '087', 'gunung setan'),
(2, 'bella gila', '087', 'gunung kawi'),
(6, 'fitri', '085233', 'surabaya');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nama_produk` varchar(120) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `harga_produksi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `nama_produk`, `harga`, `harga_produksi`, `stok`, `gambar`, `deskripsi`) VALUES
(1, 1, 'Black Tumblr', 55000.00, 50000.00, 40, 'prod_1795802411_0ed36166.png', 'Botol minum bergaya minimalis dengan desain elegan, ideal untuk menemani aktivitas kampus yang aktif, ramah lingkungan. Tidak Untuk air panas.'),
(2, 1, 'Sport Tumblr Medium', 35000.00, 30000.00, 30, 'prod_1795802675_89e45347.png', 'Botol minum ringan dengan gantungan karabiner. Praktis untuk aktivitas luar ruang, namun tidak cocok untuk air panas.'),
(3, 1, 'Rainbow Bottle', 30000.00, 22000.00, 49, 'prod_1795802876_40573bd7.jpg', 'Unusa rainbow bottle adalah botol minum warna-warni yang ringan dan stylish, cocok untuk gaya hidup aktif di kampus, namunmtidak disarankan untuk air panas.'),
(4, 5, 'Mug UNUSA', 30000.00, 20000.00, 35, 'prod_1795803062_429ab5e1.jpg', 'Cangkir keramik elegan yang cocok untuk menyajikan minuman panas, sekaligus memperkuat identitas kampus di setiap momen santai atau formal.'),
(5, 1, 'Reliable Mug', 40000.00, 36000.00, 32, 'prod_1795803183_88a3f0bc.png', 'Mug stainless bergaya modern yang ringan dan praktis untuk penggunaan harian, namun tidak direkomendasikan untuk minuman panas.'),
(6, 2, 'Maxi Bag', 40000.00, 30000.00, 100, 'prod_1795803841_5b72e322.jpg', 'Unusa maxi bag adalah tas serbaguna berukuran besar yang ideal untuk membawa buku, dokumen, atau perlengkapan harian'),
(7, 2, 'Midi Bag', 30000.00, 20000.00, 50, 'prod_1795804105_b13f2d98.jpg', 'Unusa midi bag adalah tas best-seller berukuran sedang yang praktis dan stylish, cocok untuk aktivitas kampus harian tanpa mengorbankan gaya dan kenyamanan.'),
(8, 1, 'Pearl Tumblr', 75000.00, 55000.00, 25, 'prod_1795805661_c7c1aaf4.jpg', 'Botol minum elegan berlapis doff yang dirancang dengan insulasi berkualitas, tahan lama untuk air panas maupun dingin,'),
(9, 2, 'Shopping Bag', 30000.00, 20000.00, 80, 'prod_1795804346_47fd3e3e.jpg', 'Unusa shopping bag adalah tas lipat praktis yang ramah lingkungan, dirancang untuk kebutuhan belanja harian dengan sentuhan identitas kampus yang stylish.'),
(10, 2, 'Canvas Pouch', 30000.00, 20000.00, 35, 'prod_1795804555_25ab6c36.jpg', 'Pouch kokoh berbahan kanvas dengan sentuhan kulit sintetis, ideal untuk menyimpan perlengkapan harian dengan tampilan simpel dan profesional.'),
(11, 2, 'Pouch Batik', 50000.00, 25000.00, 30, 'prod_1795804720_27e31306.png', 'Cangkir keramik elegan yang cocok untuk menyajikan minuman panas, sekaligus memperkuat identitas kampus di setiap momen santai atau formal.'),
(12, 2, 'Girly Pouch', 50000.00, 35000.00, 25, 'prod_1795804796_aefb72bb.jpg', 'Pouch cantik dengan motif floral ceria dan desain feminin, cocok untuk menyimpan kebutuhan harian dengan gaya yang tetap stylish dan praktis.'),
(13, 2, 'Lengcim Bag', 35000.00, 22000.00, 25, 'prod_1795804868_9faf6720.jpg', 'Tas lengcim unusa adalah tote bag elegan dan tahan lama yang memadukan kebanggaan kampus dengan fungsionalitas sehari-hari, cocok untuk semua kalangan'),
(14, 2, 'Backpack', 175000.00, 150000.00, 15, 'prod_1795805004_d3040dd7.png', 'Ransel bergaya kasual dengan desain ergonomis dan kompartemen fungsional, cocok untuk aktivitas kuliah, kerja, maupun perjalanan harian.'),
(15, 2, 'Slingbag', 50000.00, 30000.00, 25, 'prod_1795805096_6f56e03d.jpg', 'Slingbag unusa adalah tas bergaya minimalis dan stylish yang simple dipakai untuk sekedar makan siang ataupun untuk hangout bersama teman teman'),
(16, 6, 'Bucket Hat', 45000.00, 35000.00, 75, 'prod_1795805231_b54e74a4.jpg', 'Buckethat unusa adalah topi bergaya trendy yang sangat cocok digunakan untuk mengahalau sinar matahari ketika melakukan aktivitas di luar. Tetap bergaya di bawah teriknya matahari'),
(17, 7, 'Bullpoint Putih', 10000.00, 7000.00, 50, 'prod_1795805368_7d95319e.jpg', 'Pulpen ergonomis dengan warna cerah dan logo resmi kampus, cocok sebagai alat tulis serbaguna untuk kegiatan akademik, seminar, maupun souvenir formal.'),
(18, 7, 'Bullpoint Doff', 12000.00, 7500.00, 50, 'prod_1795805453_0dd37cac.jpg', 'Pulpen ergonomis dengan warna cerah dan logo resmi kampus, cocok sebagai alat tulis serbaguna untuk kegiatan akademik, seminar, maupun souvenir formal.'),
(19, 1, 'Keychain', 7000.00, 2000.00, 150, 'prod_1795805529_bab1b85e.jpg', 'Gantungan kunci simpel namun ikonik yang membawa simbol kebanggaan kampus ke dalam setiap aktivitas harian anda.'),
(20, 1, 'Kipas', 5000.00, 3500.00, 50, 'prod_1795805596_1696bede.jpg', 'Kipas tangan ringan dengan desain bergambar Gedung kampus, praktis digunakan di acara luar ruang sekaligus menambah sentuhan identitas kampus.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `kode` varchar(20) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `tanggal_transaksi` datetime DEFAULT current_timestamp(),
  `total_transaksi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'Menunggu',
  `sumber` varchar(10) NOT NULL DEFAULT 'WhatsApp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `kode`, `id_admin`, `id_pelanggan`, `tanggal_transaksi`, `total_transaksi`, `status`, `sumber`) VALUES
(1, 'ORD-2026-0001', NULL, 1, '2026-11-27 23:47:10', 90000.00, 'Menunggu Pembayaran', 'WhatsApp'),
(2, 'ORD-2026-0002', NULL, 2, '2026-11-27 23:49:25', 45000.00, 'Menunggu Pembayaran', 'WhatsApp'),
(3, 'ORD-2026-0003', 1, 6, '2026-11-28 02:28:15', 30000.00, 'Sudah Diambil', 'Admin');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

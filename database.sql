CREATE DATABASE IF NOT EXISTS usgallery CHARACTER SET utf8mb4;
USE usgallery;
CREATE TABLE admin(id_admin INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL);
CREATE TABLE kategori(id_kategori INT AUTO_INCREMENT PRIMARY KEY,nama_kategori VARCHAR(80) NOT NULL,deskripsi TEXT);
CREATE TABLE produk(id_produk INT AUTO_INCREMENT PRIMARY KEY,id_kategori INT NOT NULL,nama_produk VARCHAR(120) NOT NULL,harga DECIMAL(12,2) NOT NULL,stok INT NOT NULL DEFAULT 0,gambar VARCHAR(255),deskripsi TEXT,FOREIGN KEY(id_kategori) REFERENCES kategori(id_kategori));
CREATE TABLE pelanggan(id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,nama_pelanggan VARCHAR(100) NOT NULL,no_telepon VARCHAR(20),alamat TEXT);
CREATE TABLE transaksi(id_transaksi INT AUTO_INCREMENT PRIMARY KEY,kode VARCHAR(20),id_admin INT NULL,id_pelanggan INT NOT NULL,tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,total_transaksi DECIMAL(12,2) NOT NULL DEFAULT 0,status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',sumber VARCHAR(10) NOT NULL DEFAULT 'WhatsApp',FOREIGN KEY(id_pelanggan) REFERENCES pelanggan(id_pelanggan));
CREATE TABLE detail_transaksi(id_detail INT AUTO_INCREMENT PRIMARY KEY,id_transaksi INT NOT NULL,id_produk INT NOT NULL,jumlah INT NOT NULL,harga DECIMAL(12,2) NOT NULL,subtotal DECIMAL(12,2) NOT NULL,FOREIGN KEY(id_transaksi) REFERENCES transaksi(id_transaksi) ON DELETE CASCADE,FOREIGN KEY(id_produk) REFERENCES produk(id_produk));
INSERT INTO admin(username,password) VALUES('admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
INSERT INTO kategori(nama_kategori,deskripsi) VALUES('Botol Minum & Aksesoris','Tumbler dan botol minum'),('Tas','Tote bag dan tas kampus'),('Payung','Payung lipat dan panjang'),('Custom','Pesanan desain sendiri');
INSERT INTO produk(id_kategori,nama_produk,harga,stok,deskripsi) VALUES
(1,'Tumbler Custom 500ml',65000,30,'Tumbler stainless 500ml, bisa cetak logo atau nama.'),
(1,'Botol Minum UNUSA',45000,25,'Botol minum BPA free dengan logo UNUSA.'),
(2,'Totebag Canvas',35000,50,'Totebag kanvas tebal, muat laptop dan buku.'),
(2,'Tas Ransel Kampus',150000,12,'Ransel dengan kompartemen laptop 14 inci.'),
(3,'Payung Lipat UNUSA',55000,20,'Payung lipat ringan dengan logo UNUSA.'),
(4,'Souvenir Custom Desain Sendiri',0,99,'Hubungi kami via WhatsApp untuk harga sesuai desain dan jumlah.');

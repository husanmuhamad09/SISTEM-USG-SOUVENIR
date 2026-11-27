# USGallery – Sistem Katalog Produk (PHP native + MySQL)
1. Ekstrak folder `usgallery` ke `C:\laragon\www\`.
2. Buka phpMyAdmin/HeidiSQL, import `database.sql` (membuat database `usgallery`).
3. Edit `config.php`: ganti `WA_NUMBER` ke nomor WhatsApp USG (format 62xxxxxxxxxx) dan kredensial DB jika perlu.
4. Buka http://localhost/usgallery (customer) dan http://localhost/usgallery/admin (admin).
Login admin awal: `admin` / `password` — segera ganti hash di tabel `admin` (pakai password_hash PHP).
Gambar produk diunggah lewat admin → Data Produk (folder `uploads/`).

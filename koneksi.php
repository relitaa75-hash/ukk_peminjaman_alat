<?php
mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect("localhost", "root", "", "db_peminjaman");
if (!$koneksi) {
    die("Koneksi database gagal. Pastikan MySQL aktif dan database db_ukk2 sudah diimpor: " . htmlspecialchars(mysqli_connect_error()));
}
mysqli_set_charset($koneksi, "utf8mb4");

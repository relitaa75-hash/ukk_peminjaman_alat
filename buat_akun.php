
<?php
session_start();
require_once "koneksi.php";

$akun = [
    ["Administrator", "admin", "admin123", "admin"],
    ["Petugas", "petugas", "petugas123", "petugas"],
    ["Peminjam", "peminjam", "peminjam123", "peminjam"]
];

foreach ($akun as $a) {
    $nama = $a[0];
    $username = $a[1];
    $password = password_hash($a[2], PASSWORD_DEFAULT);
    $level = $a[3];

    $cek = mysqli_prepare(
        $koneksi,
        "SELECT id_user FROM `user` WHERE username = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($cek, "s", $username);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        mysqli_stmt_close($cek);

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE `user`
             SET nama = ?, password = ?, level = ?
             WHERE username = ?"
        );

        mysqli_stmt_bind_param(
            $stmt, "ssss",
            $nama, $password, $level, $username
        );
    } else {
        mysqli_stmt_close($cek);

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO `user` (nama, username, password, level)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt, "ssss",
            $nama, $username, $password, $level
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        die("Gagal membuat akun: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
}

echo "Semua akun berhasil diperbarui.";
echo '<br><a href="login.php">Kembali ke Login</a>';
?>

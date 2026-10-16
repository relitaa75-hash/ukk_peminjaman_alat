<?php
require_once 'includes/auth.php'; wajib_login(['admin']); $namaHalaman='Log Aktivitas';
$q=mysqli_query($koneksi,"SELECT l.*,u.nama,u.username FROM log_aktivitas l LEFT JOIN user u ON u.id_user=l.id_user ORDER BY l.id_log DESC LIMIT 500");
require 'includes/header.php'; ?><h2>Log Aktivitas</h2><div class="panel table-responsive"><table class="table"><thead><tr><th>Nama</th><th>Username</th><th>Aktivitas</th><th>Waktu</th></tr></thead><tbody><?php while($l=mysqli_fetch_assoc($q)): ?><tr><td><?= e($l['nama']??'Akun dihapus') ?></td><td><?= e($l['username']??'-') ?></td><td><?= e($l['aktivitas']) ?></td><td><?= e($l['waktu']) ?></td></tr><?php endwhile; ?></tbody></table></div><?php require 'includes/footer.php'; ?>

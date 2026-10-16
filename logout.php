<?php
session_start(); require_once 'koneksi.php';
if (!empty($_SESSION['id_user'])) {
  $id=(int)$_SESSION['id_user']; $s=mysqli_prepare($koneksi,"INSERT INTO log_aktivitas (id_user,aktivitas,waktu) VALUES (?, 'Logout dari aplikasi', NOW())");
  if($s){mysqli_stmt_bind_param($s,'i',$id);mysqli_stmt_execute($s);mysqli_stmt_close($s);}
}
$_SESSION=[]; session_destroy(); header('Location: login.php'); exit;

<?php
session_start(); require_once 'koneksi.php';
if (!empty($_SESSION['id_user'])) { header('Location: '.($_SESSION['level']==='admin'?'admin/index.php':($_SESSION['level']==='petugas'?'petugas/index.php':'peminjam/index.php'))); exit; }
$pesan = $_SESSION['pesan'] ?? ''; unset($_SESSION['pesan']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $username=trim($_POST['username']??''); $password=$_POST['password']??'';
  $stmt=mysqli_prepare($koneksi,"SELECT id_user,nama,username,password,level FROM user WHERE username=? LIMIT 1");
  mysqli_stmt_bind_param($stmt,'s',$username); mysqli_stmt_execute($stmt);
  $res=mysqli_stmt_get_result($stmt); $u=mysqli_fetch_assoc($res); mysqli_stmt_close($stmt);
  if ($u && password_verify($password,$u['password']) && in_array($u['level'],['admin','petugas','peminjam'],true)) {
    session_regenerate_id(true); $_SESSION['id_user']=(int)$u['id_user']; $_SESSION['nama']=$u['nama']; $_SESSION['username']=$u['username']; $_SESSION['level']=$u['level'];
    $log=mysqli_prepare($koneksi,"INSERT INTO log_aktivitas (id_user,aktivitas,waktu) VALUES (?, 'Login ke aplikasi', NOW())");
    if($log){mysqli_stmt_bind_param($log,'i',$_SESSION['id_user']);mysqli_stmt_execute($log);mysqli_stmt_close($log);}
    $dest=$u['level']==='admin'?'admin/index.php':($u['level']==='petugas'?'petugas/index.php':'peminjam/index.php'); header("Location: $dest"); exit;
  }
  $pesan='Username atau password salah.'; 
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - SIPERAL</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{min-height:100vh;display:grid;place-items:center;background:#eef3f9;font-family:Arial}.login{width:min(420px,92vw);background:white;padding:32px;border-radius:16px;box-shadow:0 8px 30px #10284b18}.logo{color:#173b72;font-weight:bold}</style></head><body><div class="login"><h3 class="text-center logo">SIPERAL</h3><p class="text-center text-muted">Sistem Informasi Peminjaman Alat</p>
<?php if($pesan): ?><div class="alert alert-danger"><?= htmlspecialchars($pesan,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<form method="post"><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required autofocus></div><div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div><button class="btn btn-primary w-100">Login</button></form>
<div class="small text-muted mt-3"><b></b>.</div></div></body></html>

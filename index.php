<?php
session_start();
if (!empty($_SESSION['id_user'])) {
  $level=$_SESSION['level']??'';
  header('Location: '.($level==='admin'?'admin/index.php':($level==='petugas'?'petugas/index.php':'peminjam/index.php'))); exit;
}
header('Location: login.php'); exit;

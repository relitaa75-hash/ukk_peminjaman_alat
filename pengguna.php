
<?php

require_once 'includes/auth.php';

wajib_login(['admin']);

$namaHalaman = 'Manajemen Pengguna';
$pesan = '';

/* =========================
   TAMBAH PENGGUNA
========================= */

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $level = $_POST['level'] ?? '';

    if (
        $nama === '' ||
        $username === '' ||
        $pass === '' ||
        !in_array($level, ['admin', 'petugas', 'peminjam'], true)
    ) {
        $pesan = 'Semua kolom wajib diisi.';
    } elseif (strlen($pass) < 6) {
        $pesan = 'Password minimal 6 karakter.';
    } else {

        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO user (nama, username, password, level)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            $pesan = 'Gagal menyiapkan query: ' . mysqli_error($koneksi);
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'ssss',
                $nama,
                $username,
                $hash,
                $level
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header('Location: pengguna.php?ok=1');
                exit;

            } else {

                if (mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = 'Username sudah digunakan. Silakan gunakan username lain.';
                } else {
                    $pesan = 'Gagal menambahkan pengguna: '
                        . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

/* =========================
   UPDATE PENGGUNA
========================= */

if (isset($_POST['update'])) {

    $id = (int) ($_POST['id_user'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $level = $_POST['level'] ?? '';

    if (
        $id <= 0 ||
        $nama === '' ||
        $username === '' ||
        !in_array($level, ['admin', 'petugas', 'peminjam'], true)
    ) {
        $pesan = 'Data pengguna tidak valid. Periksa kembali semua kolom.';
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $pesan = 'Password baru minimal 6 karakter.';
    } else {

        // Periksa apakah pengguna tersedia.
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user
             FROM user
             WHERE id_user = ?"
        );

        if (!$stmt) {
            $pesan = 'Gagal memeriksa pengguna: ' . mysqli_error($koneksi);
        } else {

            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $penggunaAda = mysqli_fetch_assoc($hasil);

            mysqli_stmt_close($stmt);

            if (!$penggunaAda) {

                $pesan = 'Pengguna tidak ditemukan.';

            } else {

                // Jika password dikosongkan, password lama tetap digunakan.
                if ($pass !== '') {

                    $hash = password_hash($pass, PASSWORD_DEFAULT);

                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE user
                         SET nama = ?, username = ?, password = ?, level = ?
                         WHERE id_user = ?"
                    );

                    if ($stmt) {
                        mysqli_stmt_bind_param(
                            $stmt,
                            'ssssi',
                            $nama,
                            $username,
                            $hash,
                            $level,
                            $id
                        );
                    }

                } else {

                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE user
                         SET nama = ?, username = ?, level = ?
                         WHERE id_user = ?"
                    );

                    if ($stmt) {
                        mysqli_stmt_bind_param(
                            $stmt,
                            'sssi',
                            $nama,
                            $username,
                            $level,
                            $id
                        );
                    }
                }

                if (!$stmt) {

                    $pesan = 'Gagal menyiapkan proses edit: '
                        . mysqli_error($koneksi);

                } else {

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        header('Location: pengguna.php?edit_ok=1');
                        exit;

                    } else {

                        if (mysqli_stmt_errno($stmt) === 1062) {
                            $pesan = 'Username sudah digunakan. Silakan gunakan username lain.';
                        } else {
                            $pesan = 'Gagal mengubah pengguna: '
                                . mysqli_stmt_error($stmt);
                        }

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

/* =========================
   HAPUS PENGGUNA
========================= */

if (isset($_POST['hapus'])) {

    $id = (int) ($_POST['id_user'] ?? 0);
    $idAktif = (int) ($_SESSION['id_user'] ?? 0);

    if ($id <= 0) {

        $pesan = 'ID pengguna tidak valid.';

    } elseif ($id === $idAktif) {

        $pesan = 'Akun yang sedang digunakan tidak dapat dihapus.';

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user
             FROM user
             WHERE id_user = ?"
        );

        if (!$stmt) {

            $pesan = 'Gagal memeriksa pengguna: ' . mysqli_error($koneksi);

        } else {

            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $penggunaAda = mysqli_fetch_assoc($hasil);

            mysqli_stmt_close($stmt);

            if (!$penggunaAda) {

                $pesan = 'Pengguna tidak ditemukan.';

            } else {

                $stmt = mysqli_prepare(
                    $koneksi,
                    "DELETE FROM user
                     WHERE id_user = ?"
                );

                if (!$stmt) {

                    $pesan = 'Gagal menyiapkan proses hapus: '
                        . mysqli_error($koneksi);

                } else {

                    mysqli_stmt_bind_param($stmt, 'i', $id);

                    if (mysqli_stmt_execute($stmt)) {

                        if (mysqli_stmt_affected_rows($stmt) > 0) {

                            mysqli_stmt_close($stmt);

                            header('Location: pengguna.php?hapus_ok=1');
                            exit;

                        } else {

                            $pesan = 'Pengguna tidak berhasil dihapus.';
                        }

                    } else {

                        $kodeError = mysqli_stmt_errno($stmt);

                        if ($kodeError === 1451) {
                            $pesan = 'Pengguna tidak dapat dihapus karena masih memiliki riwayat peminjaman atau aktivitas.';
                        } else {
                            $pesan = 'Gagal menghapus pengguna: '
                                . mysqli_stmt_error($stmt);
                        }
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

/* =========================
   AMBIL DATA EDIT
========================= */

$dataEdit = null;

if (isset($_GET['edit'])) {

    $idEdit = (int) $_GET['edit'];

    if ($idEdit > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user, nama, username, level
             FROM user
             WHERE id_user = ?"
        );

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, 'i', $idEdit);
            mysqli_stmt_execute($stmt);

            $hasilEdit = mysqli_stmt_get_result($stmt);
            $dataEdit = mysqli_fetch_assoc($hasilEdit);

            mysqli_stmt_close($stmt);
        }

        if (!$dataEdit) {
            $pesan = 'Data pengguna yang akan diedit tidak ditemukan.';
        }
    }
}

/* =========================
   AMBIL DATA PENGGUNA
========================= */

$q = mysqli_query(
    $koneksi,
    "SELECT id_user, nama, username, level
     FROM user
     ORDER BY id_user DESC"
);

if (!$q) {
    die('Gagal mengambil data pengguna: ' . mysqli_error($koneksi));
}

/* =========================
   TAMPILAN HALAMAN
========================= */

require 'includes/header.php';

?>

<h2>Manajemen Pengguna</h2>

<?php if ($pesan): ?>

    <div class="alert alert-warning">
        <?= e($pesan) ?>
    </div>

<?php endif; ?>

<?php if (isset($_GET['ok'])): ?>

    <div class="alert alert-success">
        Pengguna berhasil ditambahkan.
    </div>

<?php endif; ?>

<?php if (isset($_GET['edit_ok'])): ?>

    <div class="alert alert-success">
        Pengguna berhasil diubah.
    </div>

<?php endif; ?>

<?php if (isset($_GET['hapus_ok'])): ?>

    <div class="alert alert-success">
        Pengguna berhasil dihapus.
    </div>

<?php endif; ?>


<!-- FORM TAMBAH / EDIT PENGGUNA -->

<div class="panel">

    <h5>
        <?= $dataEdit ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
    </h5>

    <form method="post" class="row g-3">

        <?php if ($dataEdit): ?>

            <input
                type="hidden"
                name="id_user"
                value="<?= (int) $dataEdit['id_user'] ?>"
            >

        <?php endif; ?>

        <div class="col-md-3">

            <input
                type="text"
                class="form-control"
                name="nama"
                placeholder="Nama lengkap"
                required
                value="<?= e($dataEdit['nama'] ?? '') ?>"
            >

        </div>

        <div class="col-md-3">

            <input
                type="text"
                class="form-control"
                name="username"
                placeholder="Username"
                required
                value="<?= e($dataEdit['username'] ?? '') ?>"
            >

        </div>

        <div class="col-md-3">

            <input
                type="password"
                class="form-control"
                name="password"
                placeholder="<?= $dataEdit ? 'Password baru (opsional)' : 'Password (min. 6)' ?>"
                minlength="6"
                <?= $dataEdit ? '' : 'required' ?>
            >

        </div>

        <div class="col-md-2">

            <select
                class="form-select"
                name="level"
                required
            >

                <option value="">Role</option>

                <option
                    value="admin"
                    <?= ($dataEdit['level'] ?? '') === 'admin' ? 'selected' : '' ?>
                >
                    Admin
                </option>

                <option
                    value="petugas"
                    <?= ($dataEdit['level'] ?? '') === 'petugas' ? 'selected' : '' ?>
                >
                    Petugas
                </option>

                <option
                    value="peminjam"
                    <?= ($dataEdit['level'] ?? '') === 'peminjam' ? 'selected' : '' ?>
                >
                    Peminjam
                </option>

            </select>

        </div>

        <div class="col-md-1">

            <?php if ($dataEdit): ?>

                <button
                    type="submit"
                    name="update"
                    value="1"
                    class="btn btn-primary"
                >
                    Simpan
                </button>

            <?php else: ?>

                <button
                    type="submit"
                    name="simpan"
                    value="1"
                    class="btn btn-primary"
                >
                    Tambah
                </button>

            <?php endif; ?>

        </div>

        <?php if ($dataEdit): ?>

            <div class="col-12">

                <small class="text-muted">
                    Kosongkan password jika tidak ingin mengubah password lama.
                </small>

                <a
                    href="pengguna.php"
                    class="btn btn-sm btn-secondary ms-2"
                >
                    Batal
                </a>

            </div>

        <?php endif; ?>

    </form>

</div>


<!-- TABEL DATA PENGGUNA -->

<div class="panel table-responsive">

    <table class="table">

        <thead>

            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>

        </thead>

        <tbody>

            <?php while ($u = mysqli_fetch_assoc($q)): ?>

                <tr>

                    <td>
                        <?= e($u['nama']) ?>
                    </td>

                    <td>
                        <?= e($u['username']) ?>
                    </td>

                    <td>
                        <?= e(ucfirst($u['level'])) ?>
                    </td>

                    <td>

                        <a
                            href="pengguna.php?edit=<?= (int) $u['id_user'] ?>"
                            class="btn btn-sm btn-outline-primary"
                        >
                            Edit
                        </a>

                        <?php if (
                            (int) $u['id_user']
                            !== (int) ($_SESSION['id_user'] ?? 0)
                        ): ?>

                            <form
                                method="post"
                                class="d-inline"
                                onsubmit="return confirm('Hapus pengguna ini?')"
                            >

                                <input
                                    type="hidden"
                                    name="id_user"
                                    value="<?= (int) $u['id_user'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="hapus"
                                    value="1"
                                    class="btn btn-sm btn-outline-danger"
                                >
                                    Hapus
                                </button>

                            </form>

                        <?php else: ?>

                            Akun aktif

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

</div>

<?php require 'includes/footer.php'; ?>

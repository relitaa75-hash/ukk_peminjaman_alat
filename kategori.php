
<?php

require_once 'includes/auth.php';

wajib_login(['admin']);

$namaHalaman = 'Data Kategori';
$pesan = '';

/* =========================
   AMBIL DATA KATEGORI UNTUK EDIT
========================= */

$dataEdit = null;

if (isset($_GET['edit'])) {

    $idEdit = (int) $_GET['edit'];

    if ($idEdit > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT *
             FROM kategori
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $idEdit);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $dataEdit = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if (!$dataEdit) {
            $pesan = 'Data kategori tidak ditemukan.';
        }
    }
}

/* =========================
   TAMBAH KATEGORI
========================= */

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama_kategori'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    if ($nama === '') {

        $pesan = 'Nama kategori wajib diisi.';

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO kategori
                (nama_kategori, keterangan)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'ss',
            $nama,
            $ket
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            header('Location: kategori.php?ok=1');
            exit;

        } else {

            if (mysqli_stmt_errno($stmt) === 1062) {
                $pesan = 'Nama kategori sudah digunakan.';
            } else {
                $pesan = 'Gagal menyimpan kategori: '
                    . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================
   UPDATE KATEGORI
========================= */

if (isset($_POST['update'])) {

    $id = (int) ($_POST['id_kategori'] ?? 0);
    $nama = trim($_POST['nama_kategori'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    if ($id < 1 || $nama === '') {

        $pesan = 'ID dan nama kategori wajib diisi dengan benar.';

    } else {

        // Pastikan kategori tersedia.
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori
             FROM kategori
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($cek, 'i', $id);
        mysqli_stmt_execute($cek);

        $hasilCek = mysqli_stmt_get_result($cek);
        $kategoriAda = mysqli_fetch_assoc($hasilCek);

        mysqli_stmt_close($cek);

        if (!$kategoriAda) {

            $pesan = 'Kategori yang akan diedit tidak ditemukan.';

        } else {

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE kategori
                 SET nama_kategori = ?,
                     keterangan = ?
                 WHERE id_kategori = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'ssi',
                $nama,
                $ket,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                catat_aktivitas(
                    $koneksi,
                    (int) $_SESSION['id_user'],
                    'Mengubah kategori: ' . $nama
                );

                header('Location: kategori.php?edit_ok=1');
                exit;

            } else {

                if (mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = 'Nama kategori sudah digunakan.';
                } else {
                    $pesan = 'Gagal memperbarui kategori: '
                        . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            }
        }

        // Tampilkan kembali data jika proses edit gagal.
        $dataEdit = [
            'id_kategori' => $id,
            'nama_kategori' => $nama,
            'keterangan' => $ket
        ];
    }
}

/* =========================
   HAPUS KATEGORI
========================= */

if (isset($_POST['hapus'])) {

    $id = (int) ($_POST['id_kategori'] ?? 0);

    if ($id < 1) {

        $pesan = 'ID kategori tidak valid.';

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM kategori
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (mysqli_stmt_execute($stmt)) {

            $terhapus = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($terhapus > 0) {

                catat_aktivitas(
                    $koneksi,
                    (int) $_SESSION['id_user'],
                    'Menghapus kategori ID: ' . $id
                );

                header('Location: kategori.php?hapus_ok=1');
                exit;

            } else {

                $pesan = 'Kategori tidak ditemukan.';
            }

        } else {

            if (mysqli_stmt_errno($stmt) === 1451) {
                $pesan = 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.';
            } else {
                $pesan = 'Gagal menghapus kategori: '
                    . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================
   AMBIL DATA KATEGORI
========================= */

$q = mysqli_query(
    $koneksi,
    "SELECT
        k.*,
        COUNT(a.id_alat) AS total_alat
     FROM kategori k
     LEFT JOIN alat a
        ON a.id_kategori = k.id_kategori
     GROUP BY
        k.id_kategori,
        k.nama_kategori,
        k.keterangan
     ORDER BY k.nama_kategori"
);

if (!$q) {
    die('Gagal mengambil data kategori: ' . mysqli_error($koneksi));
}

/* =========================
   TAMPILAN HALAMAN
========================= */

require 'includes/header.php';

?>

<h2>Data Kategori</h2>

<div class="panel">

    <h5>
        <?= $dataEdit ? 'Edit Kategori' : 'Tambah Kategori' ?>
    </h5>

    <?php if ($pesan): ?>

        <div class="alert alert-warning">
            <?= e($pesan) ?>
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['ok'])): ?>

        <div class="alert alert-success">
            Kategori ditambahkan.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['edit_ok'])): ?>

        <div class="alert alert-success">
            Kategori berhasil diperbarui.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['hapus_ok'])): ?>

        <div class="alert alert-success">
            Kategori berhasil dihapus.
        </div>

    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT KATEGORI -->

    <form method="post" class="row g-2">

        <?php if ($dataEdit): ?>

            <input
                type="hidden"
                name="id_kategori"
                value="<?= (int) $dataEdit['id_kategori'] ?>"
            >

        <?php endif; ?>

        <div class="col-md-4">

            <input
                type="text"
                class="form-control"
                name="nama_kategori"
                placeholder="Nama kategori"
                value="<?= e($dataEdit['nama_kategori'] ?? '') ?>"
                required
            >

        </div>

        <div class="col-md-6">

            <input
                type="text"
                class="form-control"
                name="keterangan"
                placeholder="Keterangan"
                value="<?= e($dataEdit['keterangan'] ?? '') ?>"
            >

        </div>

        <div class="col-md-2">

            <?php if ($dataEdit): ?>

                <button
                    type="submit"
                    name="update"
                    value="1"
                    class="btn btn-primary w-100"
                >
                    Simpan Perubahan
                </button>

            <?php else: ?>

                <button
                    type="submit"
                    name="simpan"
                    value="1"
                    class="btn btn-primary w-100"
                >
                    Simpan
                </button>

            <?php endif; ?>

        </div>

        <?php if ($dataEdit): ?>

            <div class="col-12">

                <a
                    href="kategori.php"
                    class="btn btn-secondary btn-sm"
                >
                    Batal
                </a>

            </div>

        <?php endif; ?>

    </form>

</div>


<!-- TABEL DATA KATEGORI -->

<div class="panel table-responsive">

    <table class="table">

        <thead>

            <tr>
                <th>Nama Kategori</th>
                <th>Keterangan</th>
                <th>Jumlah Alat</th>
                <th>Aksi</th>
            </tr>

        </thead>

        <tbody>

            <?php if (mysqli_num_rows($q) > 0): ?>

                <?php while ($k = mysqli_fetch_assoc($q)): ?>

                    <tr>

                        <td>
                            <?= e($k['nama_kategori']) ?>
                        </td>

                        <td>
                            <?= e($k['keterangan'] ?? '') ?>
                        </td>

                        <td>
                            <?= (int) $k['total_alat'] ?>
                        </td>

                        <td>

                            <a
                                class="btn btn-sm btn-warning"
                                href="kategori.php?edit=<?= (int) $k['id_kategori'] ?>"
                            >
                                Edit
                            </a>

                            <form
                                method="post"
                                class="d-inline"
                                onsubmit="return confirm('Hapus kategori ini?')"
                            >

                                <input
                                    type="hidden"
                                    name="id_kategori"
                                    value="<?= (int) $k['id_kategori'] ?>"
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

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" class="text-center">
                        Belum ada data kategori.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require 'includes/footer.php'; ?>

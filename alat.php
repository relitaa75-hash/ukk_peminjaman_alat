
<?php

require_once 'includes/auth.php';

wajib_login(['admin', 'petugas']);

$namaHalaman = 'Data Alat';
$pesan = '';

/* =========================
   AMBIL DATA ALAT UNTUK EDIT
========================= */

$dataEdit = null;

if (isset($_GET['edit'])) {

    $idEdit = (int) $_GET['edit'];

    if ($idEdit > 0) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT *
             FROM alat
             WHERE id_alat = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $idEdit);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $dataEdit = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if (!$dataEdit) {
            $pesan = 'Data alat tidak ditemukan.';
        }
    }
}

/* =========================
   TAMBAH ALAT
========================= */

if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama_alat'] ?? '');
    $kat = (int) ($_POST['id_kategori'] ?? 0);
    $jumlah = (int) ($_POST['jumlah'] ?? 0);
    $kondisi = trim($_POST['kondisi'] ?? 'Baik');
    $ket = trim($_POST['keterangan'] ?? '');

    if (
        $nama === '' ||
        $kat < 1 ||
        $jumlah < 0 ||
        !in_array(
            $kondisi,
            ['Baik', 'Rusak Ringan', 'Rusak'],
            true
        )
    ) {

        $pesan = 'Lengkapi data alat dengan benar.';

    } else {

        // Pastikan kategori tersedia.
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori
             FROM kategori
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($cek, 'i', $kat);
        mysqli_stmt_execute($cek);

        $hasilCek = mysqli_stmt_get_result($cek);
        $kategoriAda = mysqli_fetch_assoc($hasilCek);

        mysqli_stmt_close($cek);

        if (!$kategoriAda) {

            $pesan = 'Kategori yang dipilih tidak ditemukan.';

        } else {

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO alat
                    (
                        nama_alat,
                        id_kategori,
                        jumlah,
                        kondisi,
                        keterangan
                    )
                 VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'siiss',
                $nama,
                $kat,
                $jumlah,
                $kondisi,
                $ket
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                catat_aktivitas(
                    $koneksi,
                    (int) $_SESSION['id_user'],
                    'Menambahkan alat: ' . $nama
                );

                header('Location: alat.php?ok=1');
                exit;

            } else {

                $pesan = 'Gagal menyimpan data alat: '
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }
}

/* =========================
   UPDATE ALAT
========================= */

if (isset($_POST['update'])) {

    $id = (int) ($_POST['id_alat'] ?? 0);
    $nama = trim($_POST['nama_alat'] ?? '');
    $kat = (int) ($_POST['id_kategori'] ?? 0);
    $jumlah = (int) ($_POST['jumlah'] ?? 0);
    $kondisi = trim($_POST['kondisi'] ?? 'Baik');
    $ket = trim($_POST['keterangan'] ?? '');

    if (
        $id < 1 ||
        $nama === '' ||
        $kat < 1 ||
        $jumlah < 0 ||
        !in_array(
            $kondisi,
            ['Baik', 'Rusak Ringan', 'Rusak'],
            true
        )
    ) {

        $pesan = 'Lengkapi data alat dengan benar.';

    } else {

        // Pastikan alat yang diedit tersedia.
        $cekAlat = mysqli_prepare(
            $koneksi,
            "SELECT id_alat
             FROM alat
             WHERE id_alat = ?"
        );

        mysqli_stmt_bind_param($cekAlat, 'i', $id);
        mysqli_stmt_execute($cekAlat);

        $hasilAlat = mysqli_stmt_get_result($cekAlat);
        $alatAda = mysqli_fetch_assoc($hasilAlat);

        mysqli_stmt_close($cekAlat);

        // Pastikan kategori tersedia.
        $cekKategori = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori
             FROM kategori
             WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($cekKategori, 'i', $kat);
        mysqli_stmt_execute($cekKategori);

        $hasilKategori = mysqli_stmt_get_result($cekKategori);
        $kategoriAda = mysqli_fetch_assoc($hasilKategori);

        mysqli_stmt_close($cekKategori);

        if (!$alatAda) {

            $pesan = 'Data alat yang akan diedit tidak ditemukan.';

        } elseif (!$kategoriAda) {

            $pesan = 'Kategori yang dipilih tidak ditemukan.';

        } else {

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE alat
                 SET
                    nama_alat = ?,
                    id_kategori = ?,
                    jumlah = ?,
                    kondisi = ?,
                    keterangan = ?
                 WHERE id_alat = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'siissi',
                $nama,
                $kat,
                $jumlah,
                $kondisi,
                $ket,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                catat_aktivitas(
                    $koneksi,
                    (int) $_SESSION['id_user'],
                    'Mengubah data alat: ' . $nama
                );

                header('Location: alat.php?edit_ok=1');
                exit;

            } else {

                $pesan = 'Gagal memperbarui data alat: '
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }
        }

        // Pertahankan data yang sedang diedit jika gagal.
        $dataEdit = [
            'id_alat' => $id,
            'nama_alat' => $nama,
            'id_kategori' => $kat,
            'jumlah' => $jumlah,
            'kondisi' => $kondisi,
            'keterangan' => $ket
        ];
    }
}

/* =========================
   HAPUS ALAT
========================= */

if (isset($_POST['hapus']) && $_SESSION['level'] === 'admin') {

    $id = (int) ($_POST['id_alat'] ?? 0);

    if ($id < 1) {

        $pesan = 'ID alat tidak valid.';

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM alat
             WHERE id_alat = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (mysqli_stmt_execute($stmt)) {

            $terhapus = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($terhapus > 0) {

                catat_aktivitas(
                    $koneksi,
                    (int) $_SESSION['id_user'],
                    'Menghapus alat ID: ' . $id
                );

                header('Location: alat.php?hapus_ok=1');
                exit;

            } else {

                $pesan = 'Data alat tidak ditemukan.';
            }

        } else {

            if (mysqli_stmt_errno($stmt) === 1451) {
                $pesan = 'Alat tidak bisa dihapus karena memiliki riwayat peminjaman.';
            } else {
                $pesan = 'Gagal menghapus alat: '
                    . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================
   AMBIL DATA KATEGORI
========================= */

$kat = mysqli_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori"
);

if (!$kat) {
    die('Gagal mengambil kategori: ' . mysqli_error($koneksi));
}

$daftarKategori = [];

while ($k = mysqli_fetch_assoc($kat)) {
    $daftarKategori[] = $k;
}

/* =========================
   AMBIL DAFTAR ALAT
========================= */

$q = mysqli_query(
    $koneksi,
    "SELECT
        a.*,
        k.nama_kategori
     FROM alat a
     LEFT JOIN kategori k
        ON k.id_kategori = a.id_kategori
     ORDER BY a.id_alat DESC"
);

if (!$q) {
    die('Gagal mengambil data alat: ' . mysqli_error($koneksi));
}

/* =========================
   TAMPILAN HALAMAN
========================= */

require 'includes/header.php';

?>

<h2>Data Alat</h2>

<?php if ($pesan): ?>

    <div class="alert alert-warning">
        <?= e($pesan) ?>
    </div>

<?php endif; ?>

<?php if (isset($_GET['ok'])): ?>

    <div class="alert alert-success">
        Data alat berhasil disimpan.
    </div>

<?php endif; ?>

<?php if (isset($_GET['edit_ok'])): ?>

    <div class="alert alert-success">
        Data alat berhasil diperbarui.
    </div>

<?php endif; ?>

<?php if (isset($_GET['hapus_ok'])): ?>

    <div class="alert alert-success">
        Data alat berhasil dihapus.
    </div>

<?php endif; ?>


<!-- FORM TAMBAH / EDIT ALAT -->

<div class="panel">

    <h5>
        <?= $dataEdit ? 'Edit Alat' : 'Tambah Alat' ?>
    </h5>

    <form method="post" class="row g-3">

        <?php if ($dataEdit): ?>

            <input
                type="hidden"
                name="id_alat"
                value="<?= (int) $dataEdit['id_alat'] ?>"
            >

        <?php endif; ?>

        <div class="col-md-4">

            <label class="form-label">
                Nama Alat
            </label>

            <input
                type="text"
                class="form-control"
                name="nama_alat"
                value="<?= e($dataEdit['nama_alat'] ?? '') ?>"
                required
            >

        </div>

        <div class="col-md-3">

            <label class="form-label">
                Kategori
            </label>

            <select
                class="form-select"
                name="id_kategori"
                required
            >

                <option value="">
                    Pilih kategori
                </option>

                <?php foreach ($daftarKategori as $k): ?>

                    <option
                        value="<?= (int) $k['id_kategori'] ?>"
                        <?= (
                            (int) ($dataEdit['id_kategori'] ?? 0)
                            === (int) $k['id_kategori']
                        ) ? 'selected' : '' ?>
                    >
                        <?= e($k['nama_kategori']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-2">

            <label class="form-label">
                Jumlah
            </label>

            <input
                type="number"
                min="0"
                class="form-control"
                name="jumlah"
                value="<?= (int) ($dataEdit['jumlah'] ?? 1) ?>"
                required
            >

        </div>

        <div class="col-md-3">

            <label class="form-label">
                Kondisi
            </label>

            <select
                class="form-select"
                name="kondisi"
            >

                <option
                    value="Baik"
                    <?= ($dataEdit['kondisi'] ?? 'Baik') === 'Baik' ? 'selected' : '' ?>
                >
                    Baik
                </option>

                <option
                    value="Rusak Ringan"
                    <?= ($dataEdit['kondisi'] ?? '') === 'Rusak Ringan' ? 'selected' : '' ?>
                >
                    Rusak Ringan
                </option>

                <option
                    value="Rusak"
                    <?= ($dataEdit['kondisi'] ?? '') === 'Rusak' ? 'selected' : '' ?>
                >
                    Rusak
                </option>

            </select>

        </div>

        <div class="col-12">

            <label class="form-label">
                Keterangan
            </label>

            <input
                type="text"
                class="form-control"
                name="keterangan"
                value="<?= e($dataEdit['keterangan'] ?? '') ?>"
            >

        </div>

        <div class="col-12">

            <?php if ($dataEdit): ?>

                <button
                    type="submit"
                    name="update"
                    value="1"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
                </button>

                <a
                    href="alat.php"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

            <?php else: ?>

                <button
                    type="submit"
                    name="simpan"
                    value="1"
                    class="btn btn-primary"
                >
                    Simpan Alat
                </button>

            <?php endif; ?>

        </div>

    </form>

</div>


<!-- TABEL DAFTAR ALAT -->

<div class="panel table-responsive">

    <h5>Daftar Alat</h5>

    <table class="table table-hover">

        <thead>

            <tr>
                <th>Nama Alat</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Kondisi</th>
                <th>Keterangan</th>

                <?php if ($_SESSION['level'] === 'admin'): ?>
                    <th>Aksi</th>
                <?php endif; ?>

            </tr>

        </thead>

        <tbody>

            <?php if (mysqli_num_rows($q) > 0): ?>

                <?php while ($a = mysqli_fetch_assoc($q)): ?>

                    <tr>

                        <td>
                            <?= e($a['nama_alat']) ?>
                        </td>

                        <td>
                            <?= e($a['nama_kategori'] ?? '-') ?>
                        </td>

                        <td>
                            <?= (int) $a['jumlah'] ?>
                        </td>

                        <td>
                            <?= e($a['kondisi']) ?>
                        </td>

                        <td>
                            <?= e($a['keterangan'] ?? '') ?>
                        </td>

                        <?php if ($_SESSION['level'] === 'admin'): ?>

                            <td>

                                <a
                                    href="alat.php?edit=<?= (int) $a['id_alat'] ?>"
                                    class="btn btn-sm btn-warning"
                                >
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    class="d-inline"
                                    onsubmit="return confirm('Hapus alat ini?')"
                                >

                                    <input
                                        type="hidden"
                                        name="id_alat"
                                        value="<?= (int) $a['id_alat'] ?>"
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

                        <?php endif; ?>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td
                        colspan="<?= $_SESSION['level'] === 'admin' ? 6 : 5 ?>"
                        class="text-center"
                    >
                        Belum ada data alat.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require 'includes/footer.php'; ?>

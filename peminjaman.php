
<?php

require_once 'includes/auth.php';

wajib_login(['admin', 'petugas']);

$namaHalaman = 'Data Peminjaman';
$pesan = '';

/* =========================
   SIMPAN PEMINJAMAN
========================= */

if (isset($_POST['simpan'])) {

    $uid = (int) ($_POST['id_user'] ?? 0);
    $aid = (int) ($_POST['id_alat'] ?? 0);
    $tgl = $_POST['tanggal_pinjam'] ?? '';
    $jumlah = (int) ($_POST['jumlah'] ?? 0);
    $ket = trim($_POST['keterangan'] ?? '');

    // Periksa stok alat
    $s = mysqli_prepare(
        $koneksi,
        "SELECT jumlah FROM alat WHERE id_alat = ?"
    );

    mysqli_stmt_bind_param($s, 'i', $aid);
    mysqli_stmt_execute($s);

    $a = mysqli_fetch_assoc(
        mysqli_stmt_get_result($s)
    );

    mysqli_stmt_close($s);

    // Validasi data peminjaman
    if (
        !$uid ||
        !$a ||
        !$tgl ||
        $jumlah < 1 ||
        $jumlah > (int) $a['jumlah']
    ) {

        $pesan = 'Periksa pengguna, alat, tanggal, dan jumlah stok.';

    } else {

        // Simpan data peminjaman
        $s = mysqli_prepare(
            $koneksi,
            "INSERT INTO peminjaman
            (
                id_user,
                id_alat,
                tanggal_pinjam,
                jumlah,
                status,
                keterangan
            )
            VALUES (?, ?, ?, ?, 'Dipinjam', ?)"
        );

        mysqli_stmt_bind_param(
            $s,
            'iisis',
            $uid,
            $aid,
            $tgl,
            $jumlah,
            $ket
        );

        if (mysqli_stmt_execute($s)) {

            catat_aktivitas(
                $koneksi,
                (int) $_SESSION['id_user'],
                'Mencatat peminjaman'
            );

            mysqli_stmt_close($s);

            header('Location: peminjaman.php?ok=1');
            exit;

        } else {

            $pesan = 'Gagal menyimpan: ' . mysqli_error($koneksi);

        }

        mysqli_stmt_close($s);
    }
}

/* =========================
   AMBIL DATA PEMINJAM
========================= */

$users = mysqli_query(
    $koneksi,
    "SELECT id_user, nama
     FROM user
     WHERE level = 'peminjam'
     ORDER BY nama"
);

/* =========================
   AMBIL DATA ALAT
========================= */

$alat = mysqli_query(
    $koneksi,
    "SELECT id_alat, nama_alat, jumlah
     FROM alat
     ORDER BY nama_alat"
);

/* =========================
   AMBIL DATA PEMINJAMAN
========================= */

$q = mysqli_query(
    $koneksi,
    "SELECT
        p.*,
        u.nama,
        a.nama_alat
     FROM peminjaman p
     JOIN user u
        ON u.id_user = p.id_user
     JOIN alat a
        ON a.id_alat = p.id_alat
     ORDER BY p.id_peminjaman DESC"
);

/* =========================
   TAMPILAN HALAMAN
========================= */

require 'includes/header.php';

?>

<h2>Data Peminjaman</h2>

<?php if ($pesan): ?>

    <div class="alert alert-warning">
        <?= e($pesan) ?>
    </div>

<?php endif; ?>

<?php if (isset($_GET['ok'])): ?>

    <div class="alert alert-success">
        Peminjaman berhasil dicatat.
    </div>

<?php endif; ?>


<!-- FORM CATAT PEMINJAMAN -->

<div class="panel">

    <h5>Catat Peminjaman</h5>

    <form method="post" class="row g-3">

        <div class="col-md-3">

            <label class="form-label">
                Peminjam
            </label>

            <select
                class="form-select"
                name="id_user"
                required
            >

                <option value="">
                    Pilih peminjam
                </option>

                <?php while ($u = mysqli_fetch_assoc($users)): ?>

                    <option value="<?= (int) $u['id_user'] ?>">
                        <?= e($u['nama']) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <div class="col-md-3">

            <label class="form-label">
                Alat
            </label>

            <select
                class="form-select"
                name="id_alat"
                required
            >

                <option value="">
                    Pilih alat
                </option>

                <?php while ($a = mysqli_fetch_assoc($alat)): ?>

                    <option value="<?= (int) $a['id_alat'] ?>">
                        <?= e($a['nama_alat']) ?>
                        (<?= (int) $a['jumlah'] ?>)
                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <div class="col-md-2">

            <label class="form-label">
                Tanggal
            </label>

            <input
                type="date"
                name="tanggal_pinjam"
                class="form-control"
                value="<?= date('Y-m-d') ?>"
                required
            >

        </div>


        <div class="col-md-2">

            <label class="form-label">
                Jumlah
            </label>

            <input
                type="number"
                name="jumlah"
                class="form-control"
                min="1"
                value="1"
                required
            >

        </div>


        <div class="col-md-12">

            <label class="form-label">
                Keterangan
            </label>

            <input
                class="form-control"
                name="keterangan"
            >

        </div>


        <div class="col-12">

            <button
                type="submit"
                name="simpan"
                class="btn btn-primary"
            >
                Simpan Peminjaman
            </button>

        </div>

    </form>

</div>


<!-- TABEL DATA PEMINJAMAN -->

<div class="panel table-responsive">

    <table class="table">

        <thead>

            <tr>
                <th>Peminjam</th>
                <th>Alat</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>

        </thead>

        <tbody>

            <?php while ($p = mysqli_fetch_assoc($q)): ?>

                <tr>

                    <td>
                        <?= e($p['nama']) ?>
                    </td>

                    <td>
                        <?= e($p['nama_alat']) ?>
                    </td>

                    <td>
                        <?= e($p['tanggal_pinjam']) ?>
                    </td>

                    <td>
                        <?= e($p['tanggal_kembali'] ?: '-') ?>
                    </td>

                    <td>
                        <?= (int) $p['jumlah'] ?>
                    </td>

                    <td>
                        <?= e($p['status']) ?>
                    </td>

                    <td>
                        <?= e($p['keterangan']) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

</div>

<?php require 'includes/footer.php'; ?>

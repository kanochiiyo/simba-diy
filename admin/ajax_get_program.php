<?php
require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/program.php");
require_once(__DIR__ . "/../functions/connection.php");


$id = intval($_GET['id']);
$connection = getConnection();

// 1. Ambil Data Program
$program = getProgramById($id);

if (!$program) {
    echo '<div class="text-center p-4"><i class="fas fa-search fa-3x text-muted mb-3"></i><p>Program tidak ditemukan.</p></div>';
    exit;
}

// 2. Ambil Statistik
$stats = getProgramStats($id);

// 3. Ambil Daftar Pendaftar (Semua)
$querySub = "SELECT * FROM pengajuan 
             WHERE id_program = $id 
             ORDER BY CASE 
                WHEN status = 'Terverifikasi' THEN 1 
                WHEN status = 'Sedang Diverifikasi' THEN 2 
                WHEN status = 'Menunggu Verifikasi' THEN 3 
                ELSE 4 
             END ASC, tanggal_dibuat DESC";

$resultSub = $connection->query($querySub);
$submissions = $resultSub->fetch_all(MYSQLI_ASSOC);

// 4. Ambil Daftar Penerima (Hanya yang masuk kuota berdasarkan SAW)
$recipients = [];
if ($program['status'] == 'Tutup') {
    $kuota = intval($program['kuota']);
    $queryRec = "SELECT p.nama_lengkap, p.nik, tn.skor_total, tn.peringkat 
                 FROM total_nilai tn
                 JOIN pengajuan p ON tn.id_pengajuan = p.id
                 WHERE p.id_program = $id AND tn.peringkat <= $kuota
                 ORDER BY tn.peringkat ASC";

    $resultRec = $connection->query($queryRec);
    if ($resultRec) {
        $recipients = $resultRec->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<div class="container-fluid p-0">
    <div class="text-center mb-4">
        <h4 style="font-weight: 700; color: #1e40af; margin-bottom: 8px;">
            <?php echo htmlspecialchars($program['nama_program']); ?>
        </h4>

        <?php if ($program['status'] == 'Aktif'): ?>
            <span class="badge rounded-pill bg-success px-3 py-2">
                <i class="fas fa-check-circle"></i> Status: AKTIF
            </span>
        <?php else: ?>
            <span class="badge rounded-pill bg-danger px-3 py-2">
                <i class="fas fa-lock"></i> Status: DITUTUP
            </span>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 text-center rounded-3" style="background-color: #eff6ff; border: 1px solid #dbeafe;">
                <div class="h3 mb-0" style="color: #2563eb; font-weight: 700;"><?php echo $stats['total_pengajuan']; ?></div>
                <div class="small text-muted" style="font-size: 11px;">TOTAL PENDAFTAR</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 text-center rounded-3" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
                <div class="h3 mb-0" style="color: #16a34a; font-weight: 700;"><?php echo $stats['terverifikasi']; ?></div>
                <div class="small text-muted" style="font-size: 11px;">TERVERIFIKASI</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 text-center rounded-3" style="background-color: #fff7ed; border: 1px solid #ffedd5;">
                <div class="h3 mb-0" style="color: #ea580c; font-weight: 700;"><?php echo $stats['menunggu'] + $stats['diverifikasi']; ?></div>
                <div class="small text-muted" style="font-size: 11px;">PROSES</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 text-center rounded-3" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
                <div class="h3 mb-0" style="color: #dc2626; font-weight: 700;"><?php echo $stats['ditolak']; ?></div>
                <div class="small text-muted" style="font-size: 11px;">DITOLAK</div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="detailTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                <i class="fas fa-info-circle"></i> Informasi
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="penerima-tab" data-bs-toggle="tab" data-bs-target="#penerima" type="button" role="tab">
                <i class="fas fa-award"></i> Daftar Penerima
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pendaftar-tab" data-bs-toggle="tab" data-bs-target="#pendaftar" type="button" role="tab">
                <i class="fas fa-users"></i> Semua Pendaftar
            </button>
        </li>
    </ul>

    <div class="tab-content" id="detailTabContent">
        <div class="tab-pane fade show active" id="info" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-borderless">
                    <tr>
                        <td width="30%" class="text-muted"><i class="fas fa-users me-2"></i> Kuota Penerima</td>
                        <td style="font-weight: 600;"><?php echo $program['kuota']; ?> Orang</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-calendar-alt me-2"></i> Periode</td>
                        <td style="font-weight: 600;">
                            <?php echo date('d M Y', strtotime($program['tanggal_mulai'])); ?>
                            <span class="text-muted mx-1">-</span>
                            <?php echo date('d M Y', strtotime($program['tanggal_selesai'])); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-align-left me-2"></i> Deskripsi</td>
                        <td>
                            <p class="mb-0 text-secondary" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($program['deskripsi'])); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php if ($program['status'] == 'Aktif'): ?>
                <div class="alert alert-info d-flex align-items-center mt-3 mb-0" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div class="small">
                        Program ini sedang berjalan. Tutup program untuk melihat hasil ranking final.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success d-flex align-items-center mt-3 mb-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div class="small">
                        Program ditutup. Lihat tab <strong>Daftar Penerima</strong> untuk hasil seleksi.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="penerima" role="tabpanel">
            <?php if ($program['status'] == 'Aktif'): ?>
                <div class="text-center py-5">
                    <div style="font-size: 48px; color: #e5e7eb; margin-bottom: 16px;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h5 style="color: #6b7280;">Hasil Seleksi Belum Tersedia</h5>
                    <p class="text-muted small">
                        Daftar penerima akan muncul secara otomatis setelah program ditutup<br>dan sistem menghitung ranking metode SAW.
                    </p>
                </div>
            <?php elseif (empty($recipients)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i> Belum ada data penerima. Pastikan ada pendaftar yang terverifikasi.
                </div>
            <?php else: ?>
                <div class="alert alert-success py-2 mb-3 small">
                    <i class="fas fa-trophy me-2"></i> Menampilkan Top <?php echo count($recipients); ?> penerima sesuai kuota (Total Kuota: <?php echo $program['kuota']; ?>).
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-bordered table-sm align-middle">
                        <thead class="table-success position-sticky top-0">
                            <tr>
                                <th class="text-center" width="10%">Rank</th>
                                <th>Nama Penerima</th>
                                <th>NIK</th>
                                <th class="text-center">Skor SAW</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipients as $rec): ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="badge bg-success rounded-circle" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                            <?php echo $rec['peringkat']; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($rec['nama_lengkap']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($rec['nik']); ?></td>
                                    <td class="text-center fw-bold text-primary">
                                        <?php echo number_format($rec['skor_total'], 4); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="pendaftar" role="tabpanel">
            <?php if (empty($submissions)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                    <p>Belum ada pendaftar untuk program ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-bordered table-sm align-middle">
                        <thead class="table-light position-sticky top-0">
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>NIK</th>
                                <th>Tanggal Daftar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            foreach ($submissions as $sub):
                                $statusClass = 'secondary';
                                $icon = 'clock';

                                if ($sub['status'] == 'Terverifikasi') {
                                    $statusClass = 'success';
                                    $icon = 'check-circle';
                                } elseif ($sub['status'] == 'Ditolak') {
                                    $statusClass = 'danger';
                                    $icon = 'times-circle';
                                } elseif ($sub['status'] == 'Sedang Diverifikasi') {
                                    $statusClass = 'warning';
                                    $icon = 'spinner';
                                }
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sub['nama_lengkap']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub['nik']); ?></td>
                                    <td><?php echo date('d/m/y', strtotime($sub['tanggal_dibuat'])); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo $sub['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-end">
                    <a href="manage_submissions.php?program=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt"></i> Kelola Pendaftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
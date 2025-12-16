<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/program.php");
require_once(__DIR__ . "/../functions/saw.php");

if (!isLogged() || !isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$success = '';
$error = '';

// Handle create program
if (isset($_POST['create_program'])) {
    if (createProgram($_POST)) {
        $success = 'Program berhasil dibuat!';
    } else {
        $error = 'Gagal membuat program.';
    }
}

// Handle update program
if (isset($_POST['update_program'])) {
    $id = intval($_POST['program_id']);
    if (updateProgram($id, $_POST)) {
        $success = 'Program berhasil diperbarui!';
    } else {
        $error = 'Gagal memperbarui program.';
    }
}

// Handle close program
if (isset($_POST['close_program'])) {
    $id = intval($_POST['program_id']);
    if (closeProgram($id)) {
        $success = 'Program ditutup dan perhitungan SAW telah dijalankan!';
    } else {
        $error = 'Gagal menutup program.';
    }
}

// Handle delete program
if (isset($_POST['delete_program'])) {
    $id = intval($_POST['program_id']);
    if (deleteProgram($id)) {
        $success = 'Program berhasil dihapus!';
    } else {
        $error = 'Gagal menghapus program. Pastikan tidak ada pengajuan terkait.';
    }
}

// Get all programs
$programs = getAllPrograms();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Kelola Program - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <div class="page-header">
                <h1 class="page-title" style="color: #1e40af;">
                    <i class="fas fa-clipboard-list" style="color: #2563eb;"></i> Kelola Program Bantuan
                </h1>
                <p class="page-subtitle">Buat dan kelola program bantuan sosial</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <!-- Create Program Form -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-plus-circle" style="color: #2563eb;"></i> Buat Program Baru
                </h2>

                <form method="POST" id="createProgramForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Program <span class="required">*</span></label>
                                <input type="text" name="nama_program" class="form-control" required
                                    placeholder="Contoh: Program BLT Tahap 1 2025">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Kuota Penerima <span class="required">*</span></label>
                                <input type="number" name="kuota" class="form-control" required min="1"
                                    placeholder="Jumlah penerima bantuan">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi Program</label>
                        <textarea name="deskripsi" class="form-control" rows="3"
                            placeholder="Deskripsi singkat tentang program bantuan"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Mulai <span class="required">*</span></label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal Selesai <span class="required">*</span></label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="create_program" class="btn btn-primary">
                        <i class="fas fa-save"></i> Buat Program
                    </button>
                </form>
            </div>

            <!-- Programs List -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-list" style="color: #2563eb;"></i> Daftar Program
                </h2>

                <?php if (empty($programs)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Program</div>
                        <div class="empty-state-description">Buat program bantuan baru menggunakan form di atas</div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Program</th>
                                    <th>Periode</th>
                                    <th>Kuota</th>
                                    <th>Penerima</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs as $program):
                                    $programStats = getProgramStats($program['id']);
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $program['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($program['nama_program']); ?></strong>
                                            <?php if ($program['deskripsi']): ?>
                                                <br><small style="color: #6b7280;"><?php echo htmlspecialchars(substr($program['deskripsi'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px;">
                                                <div><?php echo date('d M Y', strtotime($program['tanggal_mulai'])); ?></div>
                                                <div style="color: #6b7280;">s/d</div>
                                                <div><?php echo date('d M Y', strtotime($program['tanggal_selesai'])); ?></div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo $program['kuota']; ?></strong></td>
                                        <td>
                                            <strong style="color: #10b981;"><?php echo $programStats['terverifikasi']; ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($program['status'] == 'Aktif'): ?>
                                                <span class="status-badge" style="background-color: #d1fae5; color: #065f46;">
                                                    <i class="fas fa-check-circle"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background-color: #fee2e2; color: #991b1b;">
                                                    <i class="fas fa-times-circle"></i> Tutup
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm" style="background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 6px 0 0 6px; font-size: 13px;"
                                                    onclick="viewProgram(<?php echo $program['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($program['status'] == 'Aktif'): ?>
                                                    <button class="btn btn-sm" style="background-color: #ef4444; color: white; padding: 6px 12px; border-radius: 0 6px 6px 0; font-size: 13px;"
                                                        onclick="closeProgram(<?php echo $program['id']; ?>, '<?php echo htmlspecialchars($program['nama_program']); ?>')">
                                                        <i class="fas fa-lock"></i> Tutup
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal View Program -->
    <div class="modal fade" id="viewProgramModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white;">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detail Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="programDetailContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Instance modal global agar tidak duplicate instance
        let programModal;

        function viewProgram(id) {
            // Inisialisasi modal jika belum ada
            const modalEl = document.getElementById('viewProgramModal');
            if (!programModal) {
                programModal = new bootstrap.Modal(modalEl);
            }
            programModal.show();

            const content = document.getElementById('programDetailContent');

            // Tampilkan Loading
            content.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data program...</p>
                </div>`;

            // Fetch Data via AJAX
            fetch(`ajax_get_program.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    content.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                            Gagal memuat data program.<br>
                            <small class="text-muted">${error.message}</small>
                        </div>`;
                });
        }

        function closeProgram(id, nama) {
            if (confirm(`PERHATIAN!\n\nAnda akan menutup program:\n"${nama}"\n\nSetelah ditutup:\n- Tidak ada pengajuan baru yang bisa masuk\n- Sistem akan otomatis menghitung ranking SAW\n- Proses ini TIDAK BISA dibatalkan\n\nLanjutkan menutup program?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="program_id" value="${id}">
                    <input type="hidden" name="close_program" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
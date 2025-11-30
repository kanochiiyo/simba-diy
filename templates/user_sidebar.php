<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info
$connection = getConnection();
$id_user = $_SESSION['id'];
$userData = $connection->query("SELECT nama, nik FROM user WHERE id = '$id_user'")->fetch_assoc();
?>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>SIMBA</h2>
        <p style="font-size: 13px; color: #6b7280; margin: 5px 0 0 0; font-weight: 500;">Sistem Informasi Bantuan Sosial</p>
    </div>

    <!-- User Info Card -->
    <div style="padding: 20px 24px; background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); margin: 16px 8px; border-radius: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-btn-bg) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: 700; flex-shrink: 0;">
                <?php echo strtoupper(substr($userData['nama'], 0, 1)); ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 600; color: var(--color-text); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($userData['nama']); ?>
                </div>
                <div style="font-size: 12px; color: #6b7280;">
                    <?php echo htmlspecialchars($userData['nik']); ?>
                </div>
            </div>
        </div>
        <div style="padding: 8px 12px; background-color: rgba(255, 255, 255, 0.7); border-radius: 8px; text-align: center;">
            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 2px;">Role</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--color-text);">Pengguna</div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="apply.php" class="<?php echo $current_page == 'apply.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Ajukan Bantuan</span>
            </a>
        </li>
        <li>
            <a href="submission_status.php" class="<?php echo $current_page == 'submission_status.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Status Pengajuan</span>
            </a>
        </li>
        <li>
            <a href="ranking.php" class="<?php echo $current_page == 'ranking.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Hasil Ranking</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Profil Saya</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <form action="../auth/logout.php" method="POST" style="margin: 0;">
            <button type="submit" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Keluar</span>
            </button>
        </form>
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="font-size: 11px; color: #9ca3af; margin: 0;">SIMBA DIY v1.0</p>
            <p style="font-size: 10px; color: #d1d5db; margin: 4px 0 0 0;">© 2025 Kelompok Nindi</p>
        </div>
    </div>
</div>
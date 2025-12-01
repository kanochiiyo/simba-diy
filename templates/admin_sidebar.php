<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin info
$connection = getConnection();
$id_user = $_SESSION['id'];
$userData = $connection->query("SELECT nama, nik FROM user WHERE id = '$id_user'")->fetch_assoc();
?>

<style>
    /* Admin theme - Biru profesional */
    :root {
        --admin-primary: #2563eb;
        --admin-secondary: #dbeafe;
        --admin-accent: #1e40af;
        --admin-bg: #eff6ff;
    }

    .sidebar {
        background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
        border-right: none;
    }

    .sidebar-logo {
        background: rgba(255, 255, 255, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .sidebar-logo h2 {
        background: linear-gradient(135deg, #fff 0%, #bfdbfe 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .sidebar-menu a {
        color: rgba(255, 255, 255, 0.8);
        border-left-color: transparent;
    }

    .sidebar-menu a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-left-color: #60a5fa;
    }

    .admin-badge {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #78350f;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }
</style>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>SIMBA</h2>
        <p style="font-size: 13px; color: rgba(255, 255, 255, 0.8); margin: 5px 0 0 0; font-weight: 500;">Panel Administrator</p>
    </div>

    <!-- Admin Info Card -->
    <div style="padding: 20px 24px; background: rgba(255, 255, 255, 0.1); margin: 16px 8px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #78350f; font-size: 20px; font-weight: 700; flex-shrink: 0;">
                <i class="fas fa-user-shield"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($userData['nama']); ?>
                </div>
                <div style="font-size: 12px; color: rgba(255, 255, 255, 0.7);">
                    <?php echo htmlspecialchars($userData['nik']); ?>
                </div>
            </div>
        </div>
        <div style="padding: 8px 12px; background-color: rgba(255, 255, 255, 0.15); border-radius: 8px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.2);">
            <span class="admin-badge">
                <i class="fas fa-crown"></i> ADMIN
            </span>
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
            <a href="manage_programs.php" class="<?php echo $current_page == 'manage_programs.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Kelola Program</span>
            </a>
        </li>
        <li>
            <a href="manage_submissions.php" class="<?php echo $current_page == 'manage_submissions.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Kelola Pengajuan</span>
            </a>
        </li>
        <li>
            <a href="ranking.php" class="<?php echo $current_page == 'ranking.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Hasil Ranking</span>
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <form action="../auth/logout.php" method="POST" style="margin: 0;">
            <button type="submit" class="btn-logout" style="background-color: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: #fecaca;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Keluar</span>
            </button>
        </form>
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
            <p style="font-size: 11px; color: rgba(255, 255, 255, 0.6); margin: 0;">SIMBA DIY v1.0</p>
            <p style="font-size: 10px; color: rgba(255, 255, 255, 0.4); margin: 4px 0 0 0;">© 2025 Kelompok Nindi</p>
        </div>
    </div>
</div>
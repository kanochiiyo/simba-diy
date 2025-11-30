<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>SIMBA</h2>
        <p style="font-size: 13px; color: var(--color-text); opacity: 0.7; margin: 5px 0 0 0;">Sistem Informasi Bantuan
            Sosial</p>
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
            <a href="result.php" class="<?php echo $current_page == 'result.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Hasil Ranking</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
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
    </div>
</div>
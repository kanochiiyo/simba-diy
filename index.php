<?php
include(__DIR__ . '/templates/header.php');
?>

<div class="landing-page">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="#">SIMBA</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item">
            <a class="nav-link" href="#program">Program Bantuan</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#tentang">Tentang</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#faq">FAQ</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#layanan">Layanan</a>
          </li>
          <li class="nav-item">
            <button class="btn-daftar" onclick="window.location.href='auth/register.php'">Daftar</button>
          </li>
          <li class="nav-item">
            <button class="btn-masuk" onclick="window.location.href='auth/login.php'">Masuk</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <h1 class="hero-title">Wujudkan Bantuan Langsung Tunai (BLT) Tepat Sasaran dan Bebas Subjektivitas.</h1>
          <p class="hero-description">
           Lorem ipsum dolor, sit amet consectetur adipisicing elit. Possimus autem aut veniam itaque. Dolor aut pariatur officia necessitatibus repudiandae! Aperiam sequi, officiis eius in assumenda deserunt ullam dicta labore minus?
          </p>
          <button class="btn-daftar-sekarang" onclick="window.location.href='auth/register.php'">Daftar Sekarang</button>
        </div>
        <div class="col-lg-6">
          <div class="hero-image">
            <div class="hero-image-circle">
              <div class="hero-image-placeholder">
                <img src="assets/images/hero-img.png" alt="Hero Image">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Steps Section -->
  <section class="steps-section" id="program">
    <div class="container">
      <h2 class="steps-title">Caranya mudah!</h2>
      <div class="row">
        <div class="col-lg-3 col-md-6 mb-5">
          <div class="step-card">
            <div class="step-icon">
              <i class="fas fa-user-plus"></i>
            </div>
            <h3 class="step-title">Buat Akun</h3>
            <p class="step-description">Registrasi dan daftarkan akun dengan NIK</p>
            <div class="step-number">1</div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-5">
          <div class="step-card">
            <div class="step-icon">
              <i class="fas fa-database"></i>
            </div>
            <h3 class="step-title">Lengkapi Data</h3>
            <p class="step-description">Unggah semua berkas dan data yang diperlukan</p>
            <div class="step-number">2</div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-5">
          <div class="step-card">
            <div class="step-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="step-title">Verifikasi Sistem</h3>
            <p class="step-description">Sistem akan mengecek dan menilai kelayakan pendaftar</p>
            <div class="step-number">3</div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-5">
          <div class="step-card">
            <div class="step-icon">
              <i class="fas fa-eye"></i>
            </div>
            <h3 class="step-title">Penerimaan Bantuan</h3>
            <p class="step-description">Pendaftar yang layak akan dihubungi untuk menerima bantuan BLT</p>
            <div class="step-number">4</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section" id="faq">
    <div class="container">
      <div class="row">
        <div class="col-lg-6">
          <h2 class="faq-title">FAQ</h2>
          <p class="faq-subtitle">Pertanyaan yang sering ditanyakan</p>
        </div>
        <div class="col-lg-6">
          <div class="faq-item">
            <button class="faq-question">
              Siapa saja yang bisa mendaftar?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Program BLT ini terbuka untuk semua warga yang memenuhi kriteria tertentu dan memiliki NIK yang valid.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Apa saja data atau berkas yang dibutuhkan?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Anda perlu menyiapkan NIK, Kartu Keluarga, foto rumah, dan dokumen pendukung lainnya sesuai persyaratan.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Bagaimana cara mengecek status pendaftaran saya?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Anda dapat login ke akun Anda dan melihat status pendaftaran di dashboard pengguna.
              </div>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Saya tidak paham cara daftar, apakah bisa dibantu?
            </button>
            <div class="faq-answer">
              <div class="faq-answer-content">
                Tentu! Anda dapat menghubungi layanan kami melalui WhatsApp atau datang langsung ke kantor untuk mendapatkan bantuan.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer" id="layanan">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 mb-4">
          <div class="footer-logo">
            <img src="assets/images/logo-diy.png" alt="Logo DIY">
            <img src="assets/images/logo-kemensos.png" alt="Logo Kemensos">
          </div>
          <div class="footer-address">
            Jalan Muh Haji Thamrin Nomor 8,<br>
            Kota Yogyakarta, Daerah<br>
            Istimewa Yogyakarta 12345
          </div>
        </div>
        <div class="col-lg-4 mb-4">
          <h3 class="footer-title">Layanan</h3>
          <div class="footer-contact">08123454321</div>
          <div class="footer-contact">08987656789</div>
          <div class="footer-whatsapp">Hanya melayani chat via WhatsApp</div>
        </div>
        <div class="col-lg-4 mb-4">
          <h3 class="footer-title">Sosial Media</h3>
          <div class="social-icons">
            <a href="#" class="social-icon facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="social-icon twitter">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="social-icon instagram">
              <i class="fab fa-instagram"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        2025 Copyright By Kelompok Nindi.<br>
        All Rights reserved.
      </div>
    </div>
  </footer>
</div>

<?php
include(__DIR__ . '/templates/footer.php');
?>
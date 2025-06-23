<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT. Azrina Solusi Indonesia - Konstruksi & Konsultan</title>

    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <!-- Memanggil Tailwind CSS dari CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Memanggil Font dari Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Memanggil Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Memanggil File CSS Eksternal (Path Diperbaiki dengan Cache Buster) -->
    <link rel="stylesheet" href="assets/css/index.css?v=10">
</head>

<body class="bg-white">
    <!-- Header -->
    <header class="header fixed w-full z-50 py-4 px-6 lg:px-16">
        <div class="container mx-auto flex justify-between items-center">
            <!-- Logo Perusahaan -->
            <div class="flex items-center">
                <img src="assets/img/azrina_logo.png" alt="Logo Azrina" class="h-12 w-auto logo-img" onerror="this.onerror=null;this.src='https://placehold.co/200x60/FFFFFF/000000?text=AZRINA';">     
            </div>

            <!-- Navigasi Menu -->
            <nav class="hidden md:flex items-center">
                <ul class="flex space-x-8">
                    <li><a href="#home" class="nav-link px-3 py-2 hover:text-orange-600 transition">Beranda</a></li>
                    <li><a href="#about" class="nav-link px-3 py-2 hover:text-orange-600 transition">Tentang Kami</a></li>
                    <li><a href="#services" class="nav-link px-3 py-2 hover:text-orange-600 transition">Layanan</a></li>
                    <li><a href="#portfolio" class="nav-link px-3 py-2 hover:text-orange-600 transition">Portofolio</a></li>
                    <li><a href="#contact" class="nav-link px-3 py-2 hover:text-orange-600 transition">Kontak</a></li>
                </ul>
            </nav>
             <a href="auth/login.php" class="hidden md:inline-block bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-300 z-50" style="position:relative;">Login</a>

            <!-- Tombol Menu Mobile -->
            <button id="mobile-menu-button" class="md:hidden text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white mt-4 rounded-lg shadow-lg">
             <a href="#home" class="block text-gray-800 hover:bg-orange-100 py-2 px-4">Beranda</a>
             <a href="#about" class="block text-gray-800 hover:bg-orange-100 py-2 px-4">Tentang Kami</a>
             <a href="#services" class="block text-gray-800 hover:bg-orange-100 py-2 px-4">Layanan</a>
             <a href="#portfolio" class="block text-gray-800 hover:bg-orange-100 py-2 px-4">Portofolio</a>
             <a href="#contact" class="block text-gray-800 hover:bg-orange-100 py-2 px-4">Kontak</a>
             <a href="auth/login.php" class="block text-gray-800 hover:bg-orange-100 py-2 px-4 font-semibold py-2 px-4 rounded-lg transition duration-300 mt-2">Login</a>
        </div>
    </header>

    <!-- Bagian Hero -->
    <section id="home" class="hero min-h-screen flex items-center justify-center text-white pt-20">
        <div class="container mx-auto px-6 lg:px-16 text-center scroll-reveal">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                <span class="typing-text">PT. Azrina Solusi Indonesia</span>
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">Contractor –  Supplier – Consultant Perusahaan jasa kontruksi yang amanah, berkualitas, terkemuka yang menjadi solusi bagi masyarakat dan sebesar-besarnya dalam kepuasan pelanggan.</p>
            <a href="#contact" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-8 rounded-lg inline-block transition duration-300">Hubungi Kami</a>
        </div>
    </section>

    <!-- Bagian Tentang Kami -->
    <section id="about" class="py-20 px-6 lg:px-16 bg-gray-50">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 scroll-reveal">Tentang <span class="text-orange-600">Kami</span></h2>
            <div class="flex flex-col lg:flex-row items-center gap-12">
                <div class="lg:w-1/2 scroll-reveal">
                    <h3 class="text-2xl font-semibold mb-4 text-gray-800">PT. AZRINA SOLUSI INDONESIA</h3>
                    <p class="mb-6 text-gray-700 leading-relaxed">Kami adalah perusahaan multifaset yang bergerak di bidang konstruksi, pemasok, dan konsultan. Dengan komitmen tinggi, kami hadir untuk memberikan solusi nyata dan kepuasan maksimal bagi setiap klien di seluruh Indonesia.</p>
                    <p class="text-gray-700 leading-relaxed">Tim profesional kami siap menangani berbagai proyek, mulai dari perumahan, perkantoran, hingga fasilitas umum dengan jangkauan layanan di JABODETABEK dan berbagai wilayah lainnya.</p>
                </div>
                <div class="lg:w-1/2">
                    <div class="bg-white p-8 rounded-lg shadow-md mb-8 scroll-reveal" style="transition-delay: 0.2s;">
                        <div class="flex items-center mb-4">
                            <div class="bg-orange-100 p-3 rounded-full mr-4">
                                <i class="fas fa-eye text-orange-600 text-xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold">Visi Kami</h3>
                        </div>
                        <p class="text-gray-700 leading-relaxed">Menjadi perusahaan jasa konstruksi terkemuka yang terpercaya dan berkualitas tinggi, serta memberikan solusi inovatif bagi masyarakat.</p>
                    </div>
                    <div class="bg-white p-8 rounded-lg shadow-md scroll-reveal" style="transition-delay: 0.4s;">
                        <div class="flex items-center mb-4">
                            <div class="bg-orange-100 p-3 rounded-full mr-4">
                                <i class="fas fa-bullseye text-orange-600 text-xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold">Misi Kami</h3>
                        </div>
                        <p class="text-gray-700 leading-relaxed">Memberikan pelayanan terbaik dengan integritas, bekerja secara profesional berbasis ilmu, dan membangun hubungan jangka panjang dengan klien.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Layanan yang Baru -->
    <section id="services" class="py-20 px-6 lg:px-16 bg-white">
        <div class="container mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 scroll-reveal">Layanan <span class="text-orange-600">Kami</span></h2>
                <p class="text-gray-600 mt-4 max-w-2xl mx-auto scroll-reveal">Kami menyediakan berbagai solusi untuk kebutuhan konstruksi dan renovasi Anda.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Layanan 1 -->
                <div class="service-flyer group scroll-reveal">
                    <img src="assets/img/layanan.png" alt="Layanan Design Build & Renovasi" class="service-img w-full h-auto rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/800x1100/e2e8f0/475569?text=Gambar+Layanan+1'; this.classList.remove('shadow-lg');">
                </div>
                
                <!-- Layanan 2 -->
                <div class="service-flyer group scroll-reveal" style="transition-delay: 0.1s;">
                     <img src="assets/img/layanan2.png" alt="Biaya Bangun Rumah" class="service-img w-full h-auto rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/800x1100/e2e8f0/475569?text=Gambar+Layanan+2'; this.classList.remove('shadow-lg');">
                </div>
                
                <!-- Layanan 3 -->
                <div class="service-flyer group scroll-reveal" style="transition-delay: 0.2s;">
                     <img src="assets/img/layanan3.png" alt="Layanan Welding & Aluminium" class="service-img w-full h-auto rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/800x1100/e2e8f0/475569?text=Gambar+Layanan+3'; this.classList.remove('shadow-lg');">
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Portofolio -->
    <section id="portfolio" class="py-20 px-6 lg:px-16 bg-gray-50">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 scroll-reveal">Portofolio <span class="text-orange-600">Kami</span></h2>
            <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto scroll-reveal">Beberapa hasil karya kami yang menunjukkan komitmen pada kualitas dan detail.</p>
            
            <div class="flex justify-center mb-12 scroll-reveal">
                <div class="inline-flex rounded-md shadow-sm" role="group">
                    <button type="button" class="portfolio-filter active px-4 py-2 text-sm font-medium rounded-l-lg border border-gray-200" data-filter="all">
                        Semua Proyek
                    </button>
                    <button type="button" class="portfolio-filter px-4 py-2 text-sm font-medium border-t border-b border-gray-200" data-filter="perumahan">
                        Perumahan
                    </button>
                    <button type="button" class="portfolio-filter px-4 py-2 text-sm font-medium rounded-r-md border border-gray-200" data-filter="komersial">
                        Komersial
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Proyek 1 -->
                <div class="portfolio-item perumahan scroll-reveal">
                    <div class="relative overflow-hidden rounded-lg group">
                        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60" alt="Proyek Perumahan Modern" class="w-full h-64 object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-end p-6">
                            <h3 class="text-white text-xl font-semibold">Perumahan Modern</h3>
                            <p class="text-gray-300">Perumahan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Proyek 2 -->
                <div class="portfolio-item komersial scroll-reveal" style="transition-delay: 0.1s;">
                    <div class="relative overflow-hidden rounded-lg group">
                        <img src="https://images.unsplash.com/photo-1486406149866-6264efc7e1fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60" alt="Proyek Kompleks Kantor" class="w-full h-64 object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-end p-6">
                            <h3 class="text-white text-xl font-semibold">Kompleks Perkantoran</h3>
                            <p class="text-gray-300">Komersial</p>
                        </div>
                    </div>
                </div>
                
                <!-- Proyek 3 -->
                <div class="portfolio-item perumahan scroll-reveal" style="transition-delay: 0.2s;">
                    <div class="relative overflow-hidden rounded-lg group">
                        <img src="https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60" alt="Proyek Villa Mewah" class="w-full h-64 object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-end p-6">
                            <h3 class="text-white text-xl font-semibold">Villa Pribadi</h3>
                            <p class="text-gray-300">Perumahan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bagian Kontak -->
    <section id="contact" class="py-20 px-6 lg:px-16 bg-white">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 scroll-reveal">Hubungi <span class="text-orange-600">Kami</span></h2>
            <p class="text-center text-gray-600 mb-16 max-w-2xl mx-auto scroll-reveal">Diskusikan proyek Anda selanjutnya bersama kami.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Info Kontak 1: Alamat -->
                <div class="bg-gray-50 p-8 rounded-lg shadow-md text-center scroll-reveal">
                    <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-map-marker-alt text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Alamat Kantor</h3>
                    <p class="text-gray-700">Jl. Curug Agung No.53, RT.001/RW.08, Tanah Baru, Beji, Kota Depok, Jawa Barat 16426</p>
                </div>
                
                <!-- Info Kontak 2: Email -->
                <div class="bg-gray-50 p-8 rounded-lg shadow-md text-center scroll-reveal" style="transition-delay: 0.2s;">
                    <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-envelope text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Email Kami</h3>
                    <p class="text-gray-700">info@azrina.co.id</p>
                </div>
                
                <!-- Info Kontak 3: Telepon -->
                <div class="bg-gray-50 p-8 rounded-lg shadow-md text-center scroll-reveal" style="transition-delay: 0.4s;">
                    <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-phone-alt text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Telepon</h3>
                    <p class="text-gray-700">(021) 123-4567</p>
                </div>
            </div>
        </div>
    </section>

        <!-- BARU: Bagian Lokasi & Peta -->
    <section id="location" class="bg-gray-50 pb-20">
        <div class="container mx-auto px-6 lg:px-16 scroll-reveal">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">Lokasi <span class="text-orange-600">Kami</span></h2>
            <div class="overflow-hidden rounded-lg shadow-xl">
                <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7930.402381705629!2d106.805336!3d-6.368004!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69efb0465a472f%3A0x54b9e3525725ec40!2sAzrina%20Construction%20%7C%20Jasa%20bangun%20%26%20Renovasi%20rumah!5e0!3m2!1sid!2sid!4v1749973341925!5m2!1sid!2sid"
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-6 bg-gray-900 text-white">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left">
                 <div class="flex items-center mb-4 md:mb-0">
                    <img src="assets/img/azrina_logo.png" alt="Logo Azrina Footer" class="h-8 w-auto" onerror="this.onerror=null;this.src='https://placehold.co/150x40/FFFFFF/000000?text=AZRINA';">     
                </div>
                <div class="mb-4 md:mb-0">
                    <p>&copy; <span id="year"></span> PT. Azrina Solusi Indonesia. Hak Cipta Dilindungi.</p>
                </div>
                <div class="flex justify-center space-x-4">
                    <a href="#" class="hover:text-orange-500 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="hover:text-orange-500 transition"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="hover:text-orange-500 transition"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

   <!-- Tombol WhatsApp Melayang -->
   <a href="https://wa.me/6281234567890?text=Halo%2C%20saya%20tertarik%20dengan%20layanan%20dari%20Azrina%20Construction."
       id="whatsapp-float-button"
       target="_blank"
       rel="noopener noreferrer"
       title="Hubungi Kami di WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Struktur Lightbox/Modal untuk Gambar -->
    <div id="image-modal" class="fixed inset-0 bg-black bg-opacity-90 z-[9999] hidden items-center justify-center p-4 transition-opacity duration-300">
        <span id="close-modal" class="absolute top-4 right-6 text-white text-5xl font-bold cursor-pointer hover:text-gray-300 transition-colors">&times;</span>
        <img id="modal-image" src="" alt="Layanan Diperbesar" class="max-w-[95vw] max-h-[90vh] rounded-lg shadow-2xl">
    </div>

    <!-- Memanggil File JavaScript Eksternal -->
    <script src="assets/js/index.js?v=8"></script>
</body>
</html>

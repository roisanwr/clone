document.addEventListener('DOMContentLoaded', function() {

    // --- Efek Header Berubah Saat Scroll ---
    const header = document.querySelector('.header');
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const initialTextColor = mobileMenuButton.style.color; // Simpan warna awal

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // --- Fungsionalitas Menu Mobile ---
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // --- Animasi Teks Mengetik di Hero Section ---
    const typingText = document.querySelector('.typing-text');
    if (typingText) {
        const phrases = [
            "Melayani dengan Hati",
            "Bekerja dengan Ilmu",
            "Solusi Konstruksi Terpercaya"
        ];
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        function type() {
            const currentPhrase = phrases[phraseIndex];
            let displayText = '';

            if (isDeleting) {
                displayText = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                displayText = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }

            typingText.textContent = displayText;

            let typeSpeed = isDeleting ? 75 : 150;

            if (!isDeleting && charIndex === currentPhrase.length) {
                typeSpeed = 2000; // Jeda setelah selesai mengetik
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typeSpeed = 500; // Jeda sebelum mengetik frasa baru
            }

            setTimeout(type, typeSpeed);
        }
        // Mulai animasi setelah halaman dimuat
        setTimeout(type, 1000);
    }


    // --- Smooth Scrolling untuk Link Navigasi ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
             // Sembunyikan menu mobile setelah link di-klik
            if (!mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
            }
        });
    });

    // --- Fungsionalitas Filter Portofolio ---
    const filterBtns = document.querySelectorAll('.portfolio-filter');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Hapus kelas 'active' dari semua tombol
            filterBtns.forEach(b => b.classList.remove('active'));
            // Tambah kelas 'active' ke tombol yang diklik
            btn.classList.add('active');

            const filter = btn.dataset.filter;

            portfolioItems.forEach(item => {
                item.style.transition = 'opacity 0.3s ease-in-out, transform 0.3s ease-in-out';
                if (filter === 'all' || item.classList.contains(filter)) {
                    item.style.display = 'block';
                    setTimeout(() => {
                         item.style.opacity = '1';
                         item.style.transform = 'scale(1)';
                    }, 50);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });

    // --- Animasi Muncul Saat Scroll (Scroll Reveal) ---
    const scrollReveals = document.querySelectorAll('.scroll-reveal');
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    scrollReveals.forEach(reveal => {
        revealObserver.observe(reveal);
    });

    // --- Set Tahun di Footer Secara Dinamis ---
    const yearSpan = document.getElementById('year');
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }

    // --- BARU: Fungsionalitas Lightbox/Modal untuk Gambar Layanan ---
    const modal = document.getElementById('image-modal');
    if (modal) {
        const modalImage = document.getElementById('modal-image');
        const closeModalBtn = document.getElementById('close-modal');
        const serviceImages = document.querySelectorAll('.service-img');

        const showModal = (imgSrc) => {
             // Jangan buka modal jika gambar adalah placeholder
            if (imgSrc.includes('placehold.co')) {
                return;
            }
            modalImage.src = imgSrc;
            modal.classList.add('show');
            document.body.classList.add('modal-open'); // Kunci scroll body
        };

        const hideModal = () => {
            modal.classList.remove('show');
            document.body.classList.remove('modal-open'); // Buka lagi kunci scroll
        };

        serviceImages.forEach(img => {
            img.addEventListener('click', function() {
                showModal(this.src);
            });
        });

        closeModalBtn.addEventListener('click', hideModal);

        modal.addEventListener('click', function(e) {
            // Jika yang diklik adalah area overlay gelap (bukan gambar), tutup modal
            if (e.target === modal) {
                hideModal();
            }
        });

        // Menutup modal dengan menekan tombol 'Escape' di keyboard
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                hideModal();
            }
        });
    }

});


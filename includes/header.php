<?php
// Check if user is not logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <!-- Alpine.js and Tailwind CSS -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#1a1a1a',
                            surface: '#2d2d2d',
                            text: '#ffffff'
                        }
                    },
                    rotate: {
                        '360': '360deg',
                        '-360': '-360deg'
                    },
                    transitionProperty: {
                        'colors': 'background-color, border-color, color, fill, stroke'
                    },
                    transitionDuration: {
                        '300': '300ms'
                    },
                    transitionTimingFunction: {
                        'in-out': 'cubic-bezier(0.4, 0, 0.2, 1)'
                    }
                }
            }
        }
    </script>
    <style>
        /* Smooth page transition for dark mode */
        html.transitioning * {
            transition: background-color 0.5s ease-in-out,
                      border-color 0.5s ease-in-out,
                      color 0.5s ease-in-out !important;
        }

        body {
            transition: background-color 0.5s ease-in-out;
        }

        /* Prevent flash of unstyled content */
        html.dark body {
            background-color: #1a1a1a;
        }
    </style>
    <style>
        /* Add smooth transitions for dark mode */
        * {
            transition: background-color 0.3s ease-in-out,
                      border-color 0.3s ease-in-out,
                      color 0.3s ease-in-out;
        }
        
        /* Icon rotation animations */
        .rotate-360 {
            animation: rotate360 0.3s ease-in-out;
        }
        
        .rotate-reverse {
            animation: rotateReverse 0.3s ease-in-out;
        }
        
        @keyframes rotate360 {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes rotateReverse {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        /* Ripple effect */
        #darkModeToggle {
            position: relative;
            overflow: hidden;
        }

        #darkModeToggle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }

        #darkModeToggle:focus:not(:active)::after {
            animation: ripple 0.5s ease-out;
        }
    </style>
    <!-- Initialize dark mode -->
    <script>
        // Check for saved dark mode preference or system preference
        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- CSS Coices -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-200 flex flex-col min-h-screen" x-data="{ isSidebarOpen: false }">
    <!-- Top Navigation Bar -->
    <nav class="bg-gradient-to-r from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-lg fixed w-full top-0 z-50 transition-all duration-300">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Left side - Brand -->
                <div class="flex items-center space-x-4">
                    <button id="sidebar-toggle" 
                            class="relative text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white focus:outline-none p-2 transition-all duration-200 ease-in-out hover:scale-110 hover:bg-gray-100/50 dark:hover:bg-gray-700/50 rounded-lg group"
                            onclick="toggleSidebar()">
                        <i class="fas fa-bars text-xl transform transition-transform duration-300 group-hover:rotate-180"></i>
                    </button>
                    <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-400 dark:to-blue-600 bg-clip-text text-transparent">Azrina</span>
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-2">
                    <!-- Search (hidden on mobile) -->


                    <!-- Dark Mode -->
                    <button id="darkModeToggle" 
                            class="p-1.5 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none rounded-lg bg-gray-100/50 dark:bg-gray-700/50 hover:bg-gray-200/50 dark:hover:bg-gray-600/50"
                            onclick="toggleDarkMode()">
                        <i class="fas fa-moon text-lg"></i>
                        <i class="fas fa-sun text-lg hidden"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative" x-data="{ notifyOpen: false }">
                        <button @click="notifyOpen = !notifyOpen"
                                class="p-1.5 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none rounded-lg bg-gray-100/50 dark:bg-gray-700/50 hover:bg-gray-200/50 dark:hover:bg-gray-600/50 relative">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-4 h-4 text-xs font-bold text-white bg-red-600 rounded-full">3</span>
                        </button>

                        <!-- Notifications Panel -->
                        <div x-show="notifyOpen"
                             @click.away="notifyOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
                                <span class="px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 rounded-full">3 New</span>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-blue-500 bg-opacity-10 flex items-center justify-center">
                                                <i class="fas fa-user-plus text-blue-500"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">New user registered</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">2 minutes ago</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-yellow-500 bg-opacity-10 flex items-center justify-center">
                                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">System update required</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-green-500 bg-opacity-10 flex items-center justify-center">
                                                <i class="fas fa-chart-line text-green-500"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Activity spike detected</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">3 hours ago</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                <a href="#" class="text-sm text-blue-600 dark:text-blue-400 hover:underline hover:text-blue-800 dark:hover:text-blue-200 transition-colors duration-200">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Account -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen"
                                class="p-1.5 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none rounded-lg bg-gray-100/50 dark:bg-gray-700/50 hover:bg-gray-200/50 dark:hover:bg-gray-600/50 inline-flex items-center">
                            <i class="fas fa-user text-lg"></i>
                            <i class="fas fa-chevron-down ml-0.5 text-sm" :class="{'rotate-180': userMenuOpen}"></i>
                        </button>

                        <!-- User Menu Panel -->
                        <div x-show="userMenuOpen"
                             @click.away="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User Account'; ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo isset($_SESSION['role']) ? htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role']))) : 'Role'; ?></p> 
                            </div>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <a href="activity.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                <i class="fas fa-history mr-2"></i> Activity Log
                            </a>
                            <div class="border-t border-gray-200 dark:border-gray-700 mt-2 pt-2">
                                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="lg:hidden hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3">
                <a href="logout.php" class="block text-base font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </nav> <div class="content-wrapper flex-1 flex flex-col">

    <!-- Enhanced mobile menu and keyboard shortcuts -->
    <script>
        // Dark Mode Toggle Function
        function toggleDarkMode() {
            const html = document.documentElement;
            const moonIcon = document.querySelector('.fa-moon');
            const sunIcon = document.querySelector('.fa-sun');
            
            // Add transitioning class
            html.classList.add('transitioning');
            
            if (html.classList.contains('dark')) {
                // Switching to light mode
                moonIcon.classList.remove('hidden');
                moonIcon.classList.add('rotate-reverse');
                requestAnimationFrame(() => {
                    html.classList.remove('dark');
                    setTimeout(() => {
                        sunIcon.classList.add('hidden');
                        localStorage.setItem('darkMode', 'false');
                    }, 150);
                });
            } else {
                // Switching to dark mode
                sunIcon.classList.remove('hidden');
                sunIcon.classList.add('rotate-360');
                requestAnimationFrame(() => {
                    html.classList.add('dark');
                    setTimeout(() => {
                        moonIcon.classList.add('hidden');
                        localStorage.setItem('darkMode', 'true');
                    }, 150);
                });
            }

            // Remove transitioning and animation classes after they complete
            setTimeout(() => {
                html.classList.remove('transitioning');
                moonIcon.classList.remove('rotate-reverse');
                sunIcon.classList.remove('rotate-360');
            }, 500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const darkModeToggle = document.getElementById('darkModeToggle');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const searchInput = document.querySelector('input[type="text"]');
            const sidebar = document.getElementById('sidebar');

            // Initialize dark mode
            const isDark = localStorage.getItem('darkMode') === 'true';
            const moonIcon = document.querySelector('.fa-moon');
            const sunIcon = document.querySelector('.fa-sun');

            if (isDark) {
                document.documentElement.classList.add('dark');
                moonIcon.classList.add('hidden');
                sunIcon.classList.remove('hidden');
            }

            // Enhanced mobile menu with animations
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                    // Add slide animation
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('animate-slide-in-top');
                        setTimeout(() => {
                            mobileMenu.classList.remove('animate-slide-in-top');
                        }, 300);
                    }
                });
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Press '/' to focus search
                if (e.key === '/' && searchInput && document.activeElement !== searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }

                // Press 'Esc' to close mobile menu and blur search
                if (e.key === 'Escape') {
                    if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                    }
                    if (document.activeElement === searchInput) {
                        searchInput.blur();
                    }
                }

                // Press 'b' to toggle sidebar
                if (e.key === 'b' && e.ctrlKey && sidebar) {
                    e.preventDefault();
                    toggleSidebar();
                }
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', (e) => {
                if (mobileMenu && !mobileMenu.classList.contains('hidden') && 
                    !mobileMenu.contains(e.target) && 
                    !mobileMenuButton.contains(e.target)) {
                    mobileMenu.classList.add('hidden');
                }
            });

            // Add slide-in animation class
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInTop {
                    from { transform: translateY(-100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .animate-slide-in-top {
                    animation: slideInTop 0.3s ease-out forwards;
                }
            `;
            document.head.appendChild(style);
        });
    </script>

    <!-- Sidebar Control Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                const toggleBtn = document.getElementById('sidebar-toggle');
                const icon = toggleBtn.querySelector('i');
                
                if (sidebar && overlay) {
                    // Toggle sidebar
                    sidebar.classList.toggle('-translate-x-full');
                    
                    // Toggle overlay with animation
                    if (overlay.classList.contains('invisible')) {
                        overlay.classList.remove('invisible');
                        setTimeout(() => {
                            overlay.classList.remove('opacity-0');
                        }, 10);
                    } else {
                        overlay.classList.add('opacity-0');
                        setTimeout(() => {
                            overlay.classList.add('invisible');
                        }, 300);
                    }
                    
                    // Toggle body scroll
                    document.body.classList.toggle('overflow-hidden');
                    
                    // Animate hamburger icon
                    icon.classList.toggle('rotate-90');
                }
            }

            // Initialize event listeners
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const overlay = document.getElementById('sidebar-overlay');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            // Close sidebar when clicking links (mobile only)
            if (sidebar) {
                const links = sidebar.getElementsByTagName('a');
                Array.from(links).forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth < 768) {
                            toggleSidebar();
                        }
                    });
                });
            }

            // Close sidebar on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                        toggleSidebar();
                    }
                }
            });

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebar-overlay');
                    
                    if (window.innerWidth >= 768 && sidebar && overlay) {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('opacity-0', 'invisible');
                        document.body.classList.remove('overflow-hidden');
                    }
                }, 100);
            });
        });
    </script>
</body>
</html>

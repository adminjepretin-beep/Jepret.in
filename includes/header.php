<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
admin_start_session();

if (!isset($page_title)) {
    $page_title = 'Jepret.in - Rental Kamera & Gear Surabaya';
}
if (!isset($current_page)) {
    $current_page = 'home';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Jepret.in Surabaya - Sewa kamera mirrorless dan gear di Surabaya. Sensor bersih, dapet 3 baterai, harga terjangkau, proses cepat via WhatsApp.">

    <!-- Favicon: Logo Jepret.in (hasil generate) -->
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/favicon.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon.png">

    <!-- Theme Initialization (DEFAULT: LIGHT) — runs before paint to avoid flash -->
    <script>
        (function() {
            if (localStorage.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#050608',
                            900: '#090a0f',
                            850: '#11131a',
                            800: '#151822',
                            700: '#232734',
                        },
                        brand: {
                            purple: '#7c3aed',
                            purpleHover: '#6d28d9',
                            purpleDark: '#9333ea',
                            purpleDarkHover: '#7e22ce',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        heading: ['"Outfit"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />


    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Theme Transition CSS (View Transitions: lingkaran menyebar dari tengah) -->
    <style>
        /* Tema baru ditumpuk di atas tema lama, lalu dibuka dengan clip-path lingkaran.
           Karena tema lama tetap terlihat di baliknya, tidak ada momen layar kosong. */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none;
            mix-blend-mode: normal;
        }
        ::view-transition-old(root) { z-index: 1; }
        ::view-transition-new(root) { z-index: 2; }
    </style>

    <!-- Navigasi instan: halaman di-prerender saat link di-hover (Speculation Rules API, Chrome/Edge) -->
    <!-- Tema tetap gelap/terang saat pindah halaman karena localStorage dibaca sebelum paint (skrip di atas). -->
    <script type="speculationrules">
    {
        "prerender": [{
            "where": {
                "and": [
                    { "href_matches": "/*" },
                    { "not": { "href_matches": "/admin/*" } },
                    { "not": { "href_matches": "/login.php*" } },
                    { "not": { "href_matches": "/daftar.php*" } },
                    { "not": { "href_matches": "/logout.php*" } },
                    { "not": { "href_matches": "/proses-booking.php*" } },
                    { "not": { "selector_matches": "[data-no-prerender]" } }
                ]
            },
            "eagerness": "moderate"
        }]
    }
    </script>
</head>
<body class="bg-[#f8fafc] text-slate-900 dark:bg-[#090a0f] dark:text-slate-100 antialiased min-h-screen flex flex-col relative selection:bg-purple-600 selection:text-white">

    <!-- Main Navigation Bar -->
    <?php include_once __DIR__ . '/navbar.php'; ?>

    <main class="flex-grow z-10">

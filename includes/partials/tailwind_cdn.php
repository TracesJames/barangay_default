<?php
/**
 * Tailwind CSS via CDN — include once in <head>.
 * Optional: $tailwindDepth = 1 for pages one folder below project root (default 1 from admin/).
 */
$tailwindDepth = isset($tailwindDepth) ? (int) $tailwindDepth : 1;
$tailwindPrefix = str_repeat('../', max(0, $tailwindDepth));
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    important: '#tailwind-scope',
    theme: {
      extend: {
        colors: {
          brand: {
            DEFAULT: '#14b8a6',
            dark: '#0d9488',
            light: '#2dd4bf',
          },
          accent: {
            DEFAULT: '#6610f2',
            light: '#7c3aed',
          },
          nutrition: {
            DEFAULT: '#16a34a',
            dark: '#14532d',
            light: '#4ade80',
          },
        },
        fontFamily: {
          sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'],
        },
        boxShadow: {
          glow: '0 0 40px rgba(102, 16, 242, 0.15)',
          card: '0 16px 40px rgba(0, 0, 0, 0.35)',
        },
      },
    },
  };
</script>
<?php require_once __DIR__ . '/local_fonts.php'; ?>

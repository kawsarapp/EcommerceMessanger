/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/views/shop/**/*.blade.php",
    "./resources/views/shop/partials/**/*.blade.php",
  ],
  // Safelist dynamic color variants generated at runtime per client
  safelist: [
    { pattern: /bg-(primary|dark|lightgreen|vgtext)/ },
    { pattern: /text-(primary|dark|lightgreen|vgtext)/ },
    { pattern: /border-(primary|dark)/ },
    { pattern: /ring-(primary)/ },
    { pattern: /(hover|focus|active|group-hover):(bg|text|border)-(primary|dark)/ },
    { pattern: /bg-primary\/(5|10|20|30|40|50)/ },
    { pattern: /border-primary\/(20|40)/ },
    'aspect-square', 'aspect-video',
    'line-clamp-1', 'line-clamp-2', 'line-clamp-3',
    'snap-x', 'snap-mandatory', 'snap-start',
    'backdrop-blur', 'backdrop-blur-md', 'backdrop-blur-sm',
    'mix-blend-multiply',
    'z-[9999]', 'z-40', 'z-50',
    'bottom-[56px]',
  ],
  theme: {
    extend: {
      colors: {
        primary:    'var(--color-primary, #f6a52a)',
        dark:       '#222222',
        lightgreen: '#f5f7f0',
        vgtext:     '#777777',
      },
      fontFamily: {
        sans: ['"Poppins"', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
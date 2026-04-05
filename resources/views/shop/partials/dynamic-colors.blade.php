{{--
    Dynamic Color CSS Variables & Overrides Partial
    Include this in the <style> section (or after it) in every theme layout.
    This fixes the issue where Tailwind CDN does not apply dynamically-configured colors.
    Usage: @include('shop.partials.dynamic-colors', ['client' => $client])
--}}
<style>
    :root {
        --primary: {{ $client->primary_color ?? '#4f46e5' }};
        --secondary: {{ $client->secondary_color ?? $client->primary_color ?? '#facc15' }};
        --tw-color-primary: {{ $client->primary_color ?? '#4f46e5' }};
        --mob-primary: {{ $client->primary_color ?? '#4f46e5' }};
    }

    /* ──────────────────────────────────────────────────────
       Tailwind CDN Dynamic Color Override
       Forces Tailwind utility classes to use admin-configured
       primary color via CSS custom properties.
    ────────────────────────────────────────────────────── */

    /* Background */
    .bg-primary       { background-color: var(--primary) !important; }
    .bg-secondary     { background-color: var(--secondary) !important; }

    /* Text */
    .text-primary     { color: var(--primary) !important; }
    .text-secondary   { color: var(--secondary) !important; }

    /* Border */
    .border-primary   { border-color: var(--primary) !important; }
    .border-secondary { border-color: var(--secondary) !important; }

    /* Hover states */
    .hover\:bg-primary:hover      { background-color: var(--primary) !important; }
    .hover\:bg-secondary:hover    { background-color: var(--secondary) !important; }
    .hover\:text-primary:hover    { color: var(--primary) !important; }
    .hover\:text-secondary:hover  { color: var(--secondary) !important; }
    .hover\:border-primary:hover  { border-color: var(--primary) !important; }

    /* Focus states */
    .focus\:ring-primary:focus    { --tw-ring-color: var(--primary) !important; box-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width,0px)) var(--primary) !important; }
    .focus\:border-primary:focus  { border-color: var(--primary) !important; }

    /* Ring */
    .ring-primary     { --tw-ring-color: var(--primary); }

    /* Gradient */
    .from-primary     { --tw-gradient-from: var(--primary) !important; }
    .to-primary       { --tw-gradient-to: var(--primary) !important; }
    .via-primary      { --tw-gradient-via: var(--primary) !important; }

    /* Opacity variants (color-mix for modern browsers) */
    .bg-primary\/5    { background-color: color-mix(in srgb, var(--primary) 5%,  transparent) !important; }
    .bg-primary\/10   { background-color: color-mix(in srgb, var(--primary) 10%, transparent) !important; }
    .bg-primary\/20   { background-color: color-mix(in srgb, var(--primary) 20%, transparent) !important; }
    .bg-primary\/30   { background-color: color-mix(in srgb, var(--primary) 30%, transparent) !important; }
    .bg-primary\/40   { background-color: color-mix(in srgb, var(--primary) 40%, transparent) !important; }
    .bg-primary\/50   { background-color: color-mix(in srgb, var(--primary) 50%, transparent) !important; }

    .text-primary\/70 { color: color-mix(in srgb, var(--primary) 70%, transparent) !important; }
    .text-primary\/80 { color: color-mix(in srgb, var(--primary) 80%, transparent) !important; }
    .text-primary\/90 { color: color-mix(in srgb, var(--primary) 90%, transparent) !important; }

    .border-primary\/20 { border-color: color-mix(in srgb, var(--primary) 20%, transparent) !important; }
    .border-primary\/30 { border-color: color-mix(in srgb, var(--primary) 30%, transparent) !important; }

    /* Selection */
    ::selection {
        background-color: color-mix(in srgb, var(--primary) 25%, transparent);
        color: var(--primary);
    }
</style>

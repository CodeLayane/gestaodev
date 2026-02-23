<?php
header('Content-Type: application/manifest+json');
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
echo json_encode([
    'name' => 'Gestão Dev ASSEGO',
    'short_name' => 'GestãoDev',
    'description' => 'Sistema de Gestão de Demandas - ASSEGO',
    'start_url' => $base . '/index.php',
    'scope' => $base . '/',
    'display' => 'standalone',
    'orientation' => 'any',
    'background_color' => '#0f172a',
    'theme_color' => '#6366f1',
    'icons' => [
        ['src' => $base . '/assets/img/favicon.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ['src' => $base . '/assets/img/favicon.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']
    ]
], JSON_UNESCAPED_SLASHES);

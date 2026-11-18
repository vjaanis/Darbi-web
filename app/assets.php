<?php
declare(strict_types=1);

namespace Darbi;

/**
 * Statisko failu versijas nosaka faila maiņas laiks, tāpēc tās nav jāraksta ar roku.
 * Mainīts fails saņem jaunu URL, un pārlūks to drīkst ilgi kešot (.htaccess).
 */
function assetUrl(string $file): string
{
    $mtime = @filemtime(dirname(__DIR__) . '/assets/' . $file);
    return 'assets/' . $file . '?v=' . ($mtime ?: APP_VERSION);
}

/** Import map piešķir versiju arī JS moduļu savstarpējiem importiem (./ui.js u.c.). */
function assetImportMap(): string
{
    $imports = [];
    foreach (glob(dirname(__DIR__) . '/assets/*.js') ?: [] as $path) {
        $file = basename($path);
        $imports['./assets/' . $file] = './' . assetUrl($file);
    }
    return json_encode(['imports' => $imports], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
}

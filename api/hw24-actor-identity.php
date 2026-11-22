<?php
declare(strict_types=1);
/**
 * Stable owner identity when the HTTP Basic username is renamed.
 *
 * Never accept an actor or alias from GET/POST. The caller supplies only the
 * successfully authenticated web-server identity. An optional PRIVATE file
 * outside every webroot may explicitly map that login to its old owner key:
 *
 *   /var/www/vhosts/hairworld24.de/hw24_private/actor-aliases.php
 *   <?php return ['aliases' => ['NEW_HTTP_LOGIN' => 'OLD_OWNER_KEY']];
 *
 * No file, invalid file or no mapping = existing behavior. This does not
 * grant authentication: the web server must have authenticated the user.
 * Do not retroactively change historical created_by / actor / audit rows.
 */
function hw24ActorFromAliases(string $authenticatedActor, array $aliases): string
{
    $authenticatedActor = trim($authenticatedActor);
    if ($authenticatedActor === '' || strlen($authenticatedActor) > 180) {
        return $authenticatedActor;
    }
    if (!array_key_exists($authenticatedActor, $aliases)) {
        return $authenticatedActor;
    }
    $canonical = $aliases[$authenticatedActor];
    // Fail closed to unchanged owner on invalid private alias settings.
    if (!is_string($canonical)
        || !preg_match('/^[A-Za-z0-9._@-]{1,180}$/D', $canonical)) {
        return $authenticatedActor;
    }
    return $canonical;
}

function hw24StoredActor(string $authenticatedActor): string
{
    $authenticatedActor = trim($authenticatedActor);
    if ($authenticatedActor === '' || strlen($authenticatedActor) > 180) {
        return $authenticatedActor;
    }
    $file = dirname(__DIR__, 2) . '/hw24_private/actor-aliases.php';
    if (!is_file($file) || !is_readable($file)) {
        return $authenticatedActor;
    }
    try {
        $settings = require $file;
    } catch (Throwable $e) {
        error_log('HW24 owner alias config unavailable: ' . get_class($e));
        return $authenticatedActor;
    }
    if (!is_array($settings) || !isset($settings['aliases'])
        || !is_array($settings['aliases'])) {
        return $authenticatedActor;
    }
    return hw24ActorFromAliases($authenticatedActor, $settings['aliases']);
}

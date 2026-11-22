<?php
declare(strict_types=1);
/**
 * Private, explicit ownership delegation for HTTP-Basic login transitions.
 *
 * A user may view older reports and batches after a username change, while
 * all NEW records are still owned by the currently authenticated username.
 *
 * Example PRIVATE file, never checked into Git:
 *   hw24_private/actor-aliases.php
 *   <?php return ['read_aliases' => ['NEW_LOGIN' => ['OLD_LOGIN']]];
 *
 * No request parameter can assign an actor. If the private config is
 * absent, malformed, or unreadable, no delegation is granted.
 */
function hw24ReadOwnersFromAliases(string $authenticatedActor, array $readAliases): array
{
    $authenticatedActor = trim($authenticatedActor);
    if ($authenticatedActor === '' || strlen($authenticatedActor) > 180) {
        return [];
    }
    $owners = [$authenticatedActor];
    if (!array_key_exists($authenticatedActor, $readAliases)) {
        return $owners;
    }
    $historical = $readAliases[$authenticatedActor];
    if (!is_array($historical) || count($historical) > 5) {
        return $owners;
    }
    foreach ($historical as $candidate) {
        if (!is_string($candidate)
            || !preg_match('/^[A-Za-z0-9._@-]{1,180}$/D', $candidate)) {
            // Fail closed, never apply a partially valid delegation list.
            return [$authenticatedActor];
        }
        if (!in_array($candidate, $owners, true)) {
            $owners[] = $candidate;
        }
    }
    return $owners;
}

function hw24ReadableOwners(string $authenticatedActor): array
{
    $authenticatedActor = trim($authenticatedActor);
    if ($authenticatedActor === '' || strlen($authenticatedActor) > 180) {
        return [];
    }
    $privateFile = dirname(__DIR__, 2) . '/hw24_private/actor-aliases.php';
    if (!is_file($privateFile) || !is_readable($privateFile)) {
        return [$authenticatedActor];
    }
    try {
        $cfg = require $privateFile;
    } catch (Throwable $error) {
        error_log('HW24 read-alias config unavailable: ' . get_class($error));
        return [$authenticatedActor];
    }
    if (!is_array($cfg) || !isset($cfg['read_aliases'])
        || !is_array($cfg['read_aliases'])) {
        return [$authenticatedActor];
    }
    return hw24ReadOwnersFromAliases($authenticatedActor, $cfg['read_aliases']);
}

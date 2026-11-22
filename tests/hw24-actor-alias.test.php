<?php
declare(strict_types=1);

require __DIR__ . '/../api/hw24-actor-identity.php';

function verify(bool $actual, string $message): void {
    if (!$actual) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$aliases = [
    'new-user' => 'old-user',
    'another-user' => 'historical-owner',
    'invalid' => '../../secrets',
];

verify(hw24ActorFromAliases('new-user', $aliases) === 'old-user',
    'renamed Basic login must retain stable ownership key');
verify(hw24ActorFromAliases('old-user', $aliases) === 'old-user',
    'historical login must retain historical owner');
verify(hw24ActorFromAliases('unrelated', $aliases) === 'unrelated',
    'other users must never inherit historical owner');
verify(hw24ActorFromAliases('another-user', $aliases) === 'historical-owner',
    'each mapping is explicit');
verify(hw24ActorFromAliases('invalid', $aliases) === 'invalid',
    'unsafe identity target must not be used');
verify(hw24ActorFromAliases('  new-user  ', $aliases) === 'old-user',
    'server login normalization preserves existing behavior');
verify(hw24ActorFromAliases('', $aliases) === '',
    'empty login must not acquire an identity');
verify(hw24ActorFromAliases(str_repeat('x',181), $aliases) === str_repeat('x',181),
    'oversized login must not resolve an alias');

$scan = file_get_contents(__DIR__.'/../api/scanliste.php');
$archive = file_get_contents(__DIR__.'/../api/ai-reviews.php');
$review = file_get_contents(__DIR__.'/../api/ai-review.php');
foreach (['scanliste' => $scan, 'ai archive' => $archive, 'paid AI' => $review] as $name => $code) {
    verify(is_string($code) && str_contains($code, 'hw24StoredActor($actor)'),
        $name.' must normalize the authenticated owner');
    verify(str_contains($code, "require_once __DIR__.'/hw24-actor-identity.php'"),
        $name.' must load centralized private alias');
}
verify(str_contains($scan, 'created_by=?'),
    'Scan batches remain owned by stable historical actor');
verify(str_contains($archive, 'WHERE actor=?'),
    'Normal AI archive listing remains actor-scoped');
verify(str_contains($review, 'WHERE actor=? AND ean=?'),
    'Existing paid AI review deduplication remains actor-scoped');
verify(!str_contains(file_get_contents(__DIR__.'/../api/hw24-actor-identity.php'), '$_GET'),
    'Never accept identity alias from a browser GET parameter');
verify(!str_contains(file_get_contents(__DIR__.'/../api/hw24-actor-identity.php'), '$_POST'),
    'Never accept identity alias from a browser POST parameter');

echo "PASS: Private Basic-login aliases preserve Scanliste and KI ownership without DB rewrites".PHP_EOL;

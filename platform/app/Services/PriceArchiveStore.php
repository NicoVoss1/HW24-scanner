<?php
declare(strict_types=1);
namespace App\Services;
interface PriceArchiveStore
{
    public function record(array $raw,string $expectedAsin): array;
    public function latest(string $asin,int $limit=2): array;
    public function purgeExpired(): int;
}

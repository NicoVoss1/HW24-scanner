<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubTestBatchCycle;
use PHPUnit\Framework\TestCase;
final class HubMultiBatchRotationTest extends TestCase
{
    public function test_new_open_batches_join_rotation_and_closed_batches_drop_out(): void
    {
        self::assertSame(4,HubTestBatchCycle::nextBatch([7,3,4],3));
        self::assertSame(7,HubTestBatchCycle::nextBatch([3,4,7],4));
        self::assertSame(3,HubTestBatchCycle::nextBatch([3,4,7],7));
        self::assertSame(7,HubTestBatchCycle::nextBatch([3,7],4));
        self::assertNull(HubTestBatchCycle::nextBatch([],4));
    }
}

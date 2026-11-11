<?php

declare(strict_types=1);

namespace DNADesign\IdleLock\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;

/**
 * NOTE: at time of writing, I couldn't get the test to run at all
 * Seems to be a widespread issue.
 */
final class IdleLockTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        DBDatetime::set_mock_now('2024-02-01 10:00:00');
    }

    protected function tearDown(): void
    {
        DBDatetime::clear_mock_now();

        parent::tearDown();
    }

    public function testDefaultThreshold(): void
    {
        $this->assertSame(
            30,
            Member::config()->get('lockout_threshold_days')
        );
    }

    // TODO: Add other tests to check every functions
}

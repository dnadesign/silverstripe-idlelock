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
    /**
     * Defines the fixture file to use for this test class
     * @var string $fixture_file
     */
    protected static $fixture_file = 'dnadesign/silverstripe-idlelock:tests/fixtures.yml';

    protected function setUp() : void
    {
        DBDatetime::set_mock_now('2024-02-01 10:00:00');
    }

    public function testDefaultThreshold()
    {
        $threshold = (int) Member::config()->get('lockout_threshold_days');
        $this->assertSame(30, $threshold);
    }

    // TODO: Add other tests to check every functions
}

<?php

namespace DNADesign\IdleLock\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * This is in lieu of proper unit test as I can't seem to be able to make them work.
 * We'll revisit when we ge the time.
 */
class UnitTestTask extends BuildTask
{
    protected static string $commandName = 'idlelock-unittest';

    protected bool $enabled = false;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('testDefaultThreshold => '.$this->testDefaultThreshold());
        $output->writeln('testNotLockedOut => '.$this->testNotLockedOut());
        $output->writeln('testLockedOut => '.$this->testLockedOut());

        $output->writeln('Done.');
        return Command::SUCCESS;
    }

    private function testDefaultThreshold()
    {
        $threshold = (int) Member::config()->get('lockout_threshold_days');
        return 30 === $threshold;
    }

    private function testNotLockedOut()
    {
        DBDatetime::set_mock_now('2024-02-01 10:00:00');

        $member = Member::create();
        $member->LastEdited = '2024-01-15 10:00:00';

        return $member->shouldBeLockedOut() === false;
    }

    private function testLockedOut()
    {
        DBDatetime::set_mock_now('2024-02-01 10:00:00');

        $member = Member::create();
        $member->LastEdited = '2023-01-01 10:00:00';

        return $member->shouldBeLockedOut() === true;
    }

    // NOTE: cannot test the group threshold as it would require
    // writing in the database.
}

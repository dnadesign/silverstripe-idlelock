<?php

namespace DNADesign\IdleLock\Tasks;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use function sprintf;

class LockMembersTask extends BuildTask
{
    protected string $title = 'Lock Members Task';

    protected static string $commandName = 'LockMembersTask';

    protected static string $description = 'Locks member accounts based on last login time. Lockout threshold determined per security Group.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Injector::inst()->get(LoggerInterface::class)->info('Check for idle member accounts...');

        foreach (Member::get() as $member) {
            if ($member->shouldBeLockedOut()) {
                $member->doLockOutAfterIdle();
                Injector::inst()->get(LoggerInterface::class)->info(sprintf('Member %s (%s) is now locked out of the CMS after reaching the idle lockout threshold', $member->getTitle(), $member->ID));
            }
        }

        return Command::SUCCESS;
    }
}

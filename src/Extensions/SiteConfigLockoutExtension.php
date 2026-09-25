<?php

namespace DNADesign\IdleLock\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use function is_string;
use function sprintf;

/**
 * Allow a CMS override for the login screen message displayed to locked users
 *
 * @extends Extension<SiteConfig>
 */
class SiteConfigLockoutExtension extends Extension
{
    private static array $db = [
        'LockoutMessage' => 'Varchar(255)',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $lockoutMessage = Config::inst()->get(Member::class, 'lockout_message');
        $lockoutMessage = is_string($lockoutMessage) ? $lockoutMessage : '';

        $fields->addFieldToTab(
            'Root.Access',
            TextField::create('LockoutMessage')
                ->setDescription(sprintf(
                    'Override the login screen message displayed to locked users.<br><strong>Default:</strong> %s',
                    $lockoutMessage
                ))
        );
    }
}

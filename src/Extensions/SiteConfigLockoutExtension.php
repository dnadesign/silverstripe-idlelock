<?php

namespace DNADesign\IdleLock\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;

/**
 * Allow a CMS override for the login screen message displayed to locked users
 */
class SiteConfigLockoutExtension extends Extension
{
    private static $db = [
        'LockoutMessage' => 'Varchar(255)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Access',
            TextField::create('LockoutMessage')
                ->setDescription(sprintf(
                    'Override the login screen message displayed to locked users.<br><strong>Default:</strong> %s',
                    Config::inst()->get(Member::class, 'lockout_message')
            ))
        );
    }
}

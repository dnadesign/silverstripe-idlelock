<?php

namespace DNADesign\IdleLock\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;

class GroupLockoutExtension extends Extension
{
    private static $db = [
        'LockoutEnabled' => 'Boolean',
        'LockoutExempt' => 'Boolean',
        'LockoutThresholdDays' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'LockoutEnabled',
            'LockoutExempt',
            'LockoutThresholdDays'
        ]);

        if (Permission::check('ADMIN')) {
            // If configured, groups may exempt members from the idle-lockout feature
            if (Config::inst()->get(Group::class, 'lockout_exempt')) {
                $fields->insertAfter(
                    'Description',
                    CheckboxField::create('LockoutExempt', 'Lockout exempt')
                        ->setDescription('Membership of this group exempts members from the idle-lockout feature'),
                );
            }

        }
            // Enable lockout for this group
            if (!$this->getOwner()->LockoutExempt) {
                $fields->insertAfter(
                    'Description',
                    CheckboxField::create('LockoutEnabled', 'Enable lockout')
                        ->setDescription('Activates the idle-lockout feature for members of this group'),
                );
            }

            // Set the lockout threshold
            if ($this->getOwner()->LockoutEnabled) {
                $fields->insertAfter(
                    'LockoutEnabled',
                    NumericField::create('LockoutThresholdDays', 'Lockout threshold in days')
                        ->setDescription(sprintf(
                            'Inactive time in days. Members of this group will be locked out if they
                            do not log in for this amount of time.<br>Set to 0 to use the default of
                            %s days.', Config::inst()->get(Member::class, 'lockout_threshold_days')))
                        ->setAttribute('max', 999),
                );
            }
    }

    public function onBeforeWrite()
    {
        if ($this->getOwner()->LockoutExempt) {
            $this->getOwner()->LockoutEnabled = false;
        }
    }
}

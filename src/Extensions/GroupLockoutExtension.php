<?php

namespace DNADesign\IdleLock\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use function is_scalar;
use function sprintf;

/**
 * @extends Extension<Group>
 */
class GroupLockoutExtension extends Extension
{
    private static array $db = [
        'LockoutEnabled' => 'Boolean',
        'LockoutExempt' => 'Boolean',
        'LockoutThresholdDays' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName([
            'LockoutEnabled',
            'LockoutExempt',
            'LockoutThresholdDays',
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
            $defaultThreshold = Config::inst()->get(
                Member::class,
                'lockout_threshold_days'
            );

            $defaultThreshold = is_scalar($defaultThreshold)
                ? $defaultThreshold
                : '';

            $fields->insertAfter(
                'LockoutEnabled',
                NumericField::create('LockoutThresholdDays', 'Lockout threshold in days')
                    ->setDescription(sprintf(
                        'Inactive time in days. Members of this group will be locked out if they
                            do not log in for this amount of time.<br>Set to 0 to use the default of
                            %s days.',
                        $defaultThreshold
                    ))
                    ->setAttribute('max', 999),
            );
        }
    }

    public function onBeforeWrite(): void
    {
        if ($this->getOwner()->LockoutExempt) {
            $this->getOwner()
                ->LockoutEnabled = false;
        }
    }
}

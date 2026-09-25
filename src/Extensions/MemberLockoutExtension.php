<?php

namespace DNADesign\IdleLock\Extensions;

use LogicException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Group;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use function implode;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * @extends Extension<Member>
 */
class MemberLockoutExtension extends Extension
{
    private static array $db = [
        'Locked' => 'Boolean',
        'LockoutExempt' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName(['Locked', 'LockoutExempt']);

        if (Permission::check('ADMIN')) {
            if (!$this->getOwner()->LockoutExempt) {
                $defaultLockoutThreshold = Config::inst()->get(
                    Member::class,
                    'lockout_threshold_days'
                );

                $defaultLockoutThreshold = is_scalar($defaultLockoutThreshold)
                    ? $defaultLockoutThreshold
                    : '';
                $fields->addFieldToTab('Root.Main', CheckboxField::create('Locked')->setDescription(sprintf('When checked, this user cannot log in. Set either manually here, or by idle timeout (default %s days or specified per security group).', $defaultLockoutThreshold)), 'FirstName');
            }

            // If configured, users may be exempted from the idle-lockout feature
            if (Config::inst()->get(Member::class, 'lockout_exempt')) {
                $fields->addFieldToTab('Root.Main', CheckboxField::create('LockoutExempt')->setDescription('When checked, this user cannot be locked out by the idle-lock feature'), 'FirstName');
            }
        }

    }

    public function onBeforeWrite(): void
    {
        if ($this->getOwner()->LockoutExempt) {
            $this->getOwner()
                ->Locked = false;
        }
    }

    /**
     * Prevent login if the Member is locked out, allow a custom message
     */
    public function canLogIn(ValidationResult &$result): void
    {
        if ($this->getOwner()->Locked) {
            $config = SiteConfig::current_site_config();

            $defaultLockoutMessage = Config::inst()->get(
                Member::class,
                'lockout_message'
            );

            $defaultLockoutMessage = is_string($defaultLockoutMessage)
                ? $defaultLockoutMessage
                : '';

            $errorMessage = $config->LockoutMessage ?: $defaultLockoutMessage;
            $result->addError($errorMessage);
        }
    }

    /**
     * Return whether the user should be locked out
     * Depending on whether they haven't logged in for a certain amount of time.
     */
    public function shouldBeLockedOut(): bool
    {
        // False if exempt, or a member of an exempt group
        if ($this->getOwner()->LockoutExempt || $this->getOwner()->Groups()->filter('LockoutExempt', true)->first()) {
            return false;
        }

        $lockoutGroups = $this->getOwner()
            ->Groups()
            ->filter([
                'LockoutEnabled' => true,
            ]);

        if ($lockoutGroups->count() > 0) {
            $lockAfterDate = $this->getOwner()
                ->getLockAfterDate($lockoutGroups);
            $lastAccessedDate = $this->getOwner()
                ->getLastLogin();

            // For accounts for new users who haven't logged in yet, use the created date
            $lastAccessedDate ??= $this->getOwner()
                ->dbObject('Created');

            return $lockAfterDate > $lastAccessedDate;
        }

        // If not a member of any lockout groups, return false
        return false;
    }

    /**
     * Return the Date after which a user should be locked out
     *
     * @param DataList<Group> $lockoutGroups
     */
    public function getLockAfterDate(DataList $lockoutGroups): DBDatetime
    {
        if ($lockoutGroups->count() > 0) {
            $lowestThreshold = $lockoutGroups->min('LockoutThresholdDays');
        } else {
            $lowestThreshold = Config::inst()->get(Member::class, 'lockout_threshold_days');
        }

        if (!is_numeric($lowestThreshold)) {
            throw new LogicException('The lockout threshold must be numeric');
        }

        return DBDatetime::now()->modify(
            sprintf('-%d days', (int) $lowestThreshold)
        );
    }

    /**
     * Return the date the user has last accessed the CMS
     */
    public function getLastAccessed(): ?DBDatetime
    {
        if ($lastTime = $this->getOwner()->getLastLogin()) {
            return $lastTime;
        }

        return null;
    }

    /**
     * Get the last login attempt
     */
    public function getLastLogin(): ?DBDatetime
    {
        $lastTime = LoginAttempt::get()
            ->filter([
                'MemberID' => $this->getOwner()
                    ->ID,
                'Status' => 'Success',
            ])
            ->sort('Created', 'DESC')
            ->first();

        if (!$lastTime) {
            return null;
        }

        $created = $lastTime->dbObject('Created');

        return $created instanceof DBDatetime ? $created : null;
    }

    /**
     * Lock the user if their last login was more than the lockout threshold ago
     */
    public function doLockOutAfterIdle(): bool
    {
        $this->getOwner()
            ->Locked = true;
        $this->getOwner()
            ->write();

        return true;
    }

    /**
     * List the groups this Member is a member of
     *
     * @return string
     */
    public function getGroupNames()
    {
        $groups = $this->getOwner()
            ->Groups()
            ->sort('Title ASC');

        $groupNames = $groups->map('ID', 'Title')
            ->toArray();
        $groupNamesString = implode(' | ', $groupNames);

        return $groupNamesString;
    }
}

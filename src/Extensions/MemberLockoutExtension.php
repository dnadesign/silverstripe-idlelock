<?php

namespace DNADesign\IdleLock\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\FieldType\DBDatetime;

class MemberLockoutExtension extends Extension
{
    private static $db = [
        'Locked' => 'Boolean',
        'LockoutExempt' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['Locked', 'LockoutExempt']);

        if (Permission::check('ADMIN')) {
            if (!$this->getOwner()->LockoutExempt) {
                $defaultLockoutThreshold = Config::inst()->get(Member::class, 'lockout_threshold_days');
                $fields->addFieldToTab('Root.Main', CheckboxField::create('Locked')->setDescription(sprintf('When checked, this user cannot log in. Set either manually here, or by idle timeout (default %s days or specified per security group).', $defaultLockoutThreshold)), 'FirstName');
            }

            // If configured, users may be exempted from the idle-lockout feature
            if (Config::inst()->get(Member::class, 'lockout_exempt')) {
                $fields->addFieldToTab('Root.Main', CheckboxField::create('LockoutExempt')->setDescription('When checked, this user cannot be locked out by the idle-lock feature'), 'FirstName');
            }
        }

    }

    public function onBeforeWrite()
    {
        if ($this->getOwner()->LockoutExempt) {
            $this->getOwner()->Locked = false;
        }
    }

    /**
     * Prevent login if the Member is locked out, allow a custom message
     *
     * @param  $result
     * @return void
     */
    public function canLogIn(&$result)
    {
        if ($this->getOwner()->Locked) {
            $config = SiteConfig::current_site_config();
            $defaultLockoutMessage = Config::inst()->get(Member::class, 'lockout_message');
            $errorMessage = $config->LockoutMessage ?: $defaultLockoutMessage;
            $result->addError($errorMessage);
        }
    }

    /**
     * Return whether the user should be locked out
     * Depending on whether they haven't logged in for a certain amount of time.
     */
    public function shouldBeLockedOut() : bool
    {
        // False if exempt, or a member of an exempt group
        if ($this->getOwner()->LockoutExempt || $this->getOwner()->Groups()->filter('LockoutExempt', true)->first()) {
            return false;
        }

        $lockoutGroups = $this->getOwner()->Groups()->filter([
            'LockoutEnabled' => true
        ]);


        if ($lockoutGroups->count() > 0) {
            $lockAfterDate = $this->getOwner()->getLockAfterDate($lockoutGroups);
            $lastAccessedDate = $this->getOwner()->getLastLogin();

            // For accounts for new users who haven't logged in yet, use the created date
            $lastAccessedDate ??= $this->getOwner()->dbObject('Created');

            return $lockAfterDate > $lastAccessedDate;
        }

        // If not a member of any lockout groups, return false
        return false;
    }

    /**
     * Return the Date after which a user should be locked out
     */
    public function getLockAfterDate($lockoutGroups) : DBDateTime
    {
        if ($lockoutGroups->count() > 0) {
            $lowestThreshold = $lockoutGroups->min('LockoutThresholdDays');
        } else {
            $lowestThreshold = Config::inst()->get(Member::class, 'lockout_threshold_days');
        }

        return DBDateTime::now()->modify("-{$lowestThreshold} days");
    }

    /**
     * Return the date the user has last accessed the CMS
     *
     * @return DBDatetime|null
     */
    public function getLastAccessed(): ?DBDateTime
    {
        if ($lastTime = $this->getOwner()->getLastLogin()) {
            return $lastTime;
        }

        return null;
    }

    /**
     * Get the last login attempt
     *
     * @return DBDatetime|null
     */
    public function getLastLogin(): ?DBDateTime
    {
        $lastTime = LoginAttempt::get()
            ->filter(
                [
                    'MemberID' => $this->getOwner()->ID,
                    'Status' => 'Success',
                    ]
            )
            ->sort('Created', 'DESC')
            ->first();

        if ($lastTime) {
            return $lastTime->dbObject('Created');
        }

        return null;
    }

    /**
     * Lock the user if their last login was more than the lockout threshold ago
     *
     * @return boolean
     */
    public function doLockOutAfterIdle() : bool
    {
        $this->getOwner()->Locked = true;
        $this->getOwner()->write();

        return true;
    }

    /**
     * List the groups this Member is a member of
     *
     * @return string
     */
    public function getGroupNames()
    {
        $groups = $this->getOwner()->Groups()->sort('Title ASC');

        $groupNames = $groups->map('ID', 'Title')->toArray();
        $groupNamesString = implode(' | ', $groupNames);

        return $groupNamesString;
    }
}

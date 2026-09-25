<?php

namespace DNADesign\IdleLock\Reports;

use LogicException;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;
use SilverStripe\Security\Member;
use function implode;

/**
 * List users and their locked status
 */
class LockedUserReport extends Report
{
    protected $title = 'Locked users Report';

    protected $description = 'Report on users and lock status';

    /**
     * Get the source records for the report, including user data.
     *
     * @param array<string, mixed>|null $params
     * @param array<string, string>|string|null $sort
     * @return DataList<Member>
     */
    public function sourceRecords(?array $params = null, array|string|null $sort = null, ?int $limit = null)
    {
        // Fetch user data
        /** @var DataList<Member> $users */
        $users = Member::get();

        $users = $users->leftJoin(
            'Group_Members',
            'Group_Members.MemberID = Member.ID'
        );

        // Apply sorting
        if ($sort !== null && $sort !== []) {
            $users = $users->sort($sort);
        }

        if ($limit !== null) {
            $users = $users->limit($limit);
        }

        return $users;
    }

    /**
     * Get the columns for the report.
     *
     * @return array<
     *      string,
     *      string|array{
     *          title: string,
     *          formatting: callable(mixed, Member): string
     *      }
     *  >
     */
    public function columns(): array
    {
        return [
            'FirstName' => 'First Name',
            'Surname' => 'Last Name',
            'Email' => 'Email',
            'Locked' => 'Locked',
            'getGroupNames' => 'Groups',
            'Date' => [
                'title' => 'Last logged in',
                'formatting' => static function (mixed $value, Member $item): string {
                    return $item->getLastLogin()?->format('dd/MM/y') ?? 'Never';
                },
            ],
        ];
    }

    /**
     * Get the report field with added export button.
     */
    public function getReportField(): GridField
    {
        $field = parent::getReportField();

        if (!$field instanceof GridField) {
            throw new LogicException('Expected the report field to be a GridField');
        }

        // Add export button
        $field->getConfig()
            ->removeComponentsByType(GridFieldPrintButton::class)
            ->removeComponentsByType(GridFieldPageCount::class)
            ->removeComponentsByType(GridFieldPaginator::class);

        return $field;
    }

    /**
     * Format the list of groups for a user.
     */
    protected function formatGroupsList(Member $record): string
    {
        return implode(', ', $record->Groups()->column('Title'));
    }
}

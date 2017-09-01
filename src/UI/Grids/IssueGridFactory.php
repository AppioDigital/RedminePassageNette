<?php
declare(strict_types=1);

namespace Appio\RedmineNette\UI\Grids;

use Appio\Redmine\Entity\Issue;
use Appio\RedmineNette\Services\Providers\ProjectIssueServiceProvider;
use Nette\SmartObject;
use Ublaboo\DataGrid\DataGrid;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class IssueGridFactory
{
    use SmartObject;

    /** @var ProjectIssueServiceProvider */
    private $issueServiceProvider;

    /**
     * @param ProjectIssueServiceProvider $issueServiceProvider
     */
    public function __construct(ProjectIssueServiceProvider $issueServiceProvider)
    {
        $this->issueServiceProvider = $issueServiceProvider;
    }

    /**
     * @param int|null $projectId
     * @return DataGrid
     */
    public function create(int $projectId = null): DataGrid
    {
        $issueService = $this->issueServiceProvider->getService($projectId);

        $grid = new DataGrid;
        $grid->setDataSource($issueService->getAllIssues());

        $grid->addColumnText('id', 'ID')
            ->setFilterText()
            ->setCondition(function (array $issues, $id) {
                return array_filter($issues, function (Issue $issue) use ($id) {
                    return strpos((string) $issue->getId(), $id) !== false;
                });
            });

        $grid->addColumnText('tracker', 'Typ', 'tracker.name')
            ->setFilterSelect([null => 'Vše'] + $issueService->getTrackersList())
            ->setCondition(function (array $issues, $id) {
                return array_filter($issues, function (Issue $issue) use ($id) {
                    return $issue->getTrackerId() === (int) $id;
                });
            });

        $grid->addColumnText('subject', 'Název')
            ->setFilterText()
            ->setCondition(function (array $issues, $text) {
                return array_filter($issues, function (Issue $issue) use ($text) {
                    return stripos($issue->getSubject(), $text) !== false;
                });
            });

        $grid->addColumnText('status', 'Stav', 'status.name')
            ->setFilterSelect([null => 'Vše'] + $issueService->getStatusesList())
            ->setCondition(function (array $issues, $id) {
                return array_filter($issues, function (Issue $issue) use ($id) {
                    return $issue->getStatusId() === (int) $id;
                });
            });

        $grid->addColumnText('priority', 'Priorita', 'priority.name')
            ->setFilterSelect([null => 'Vše'] + $issueService->getPrioritiesList())
            ->setCondition(function (array $issues, $id) {
                return array_filter($issues, function (Issue $issue) use ($id) {
                    return $issue->getPriorityId() === (int) $id;
                });
            });

        $grid->addColumnText('author', 'Autor', 'author.name')
            ->setFilterText()
            ->setCondition(function (array $issues, $text) {
                return array_filter($issues, function (Issue $issue) use ($text) {
                    return stripos($issue->getAuthor()->getName(), $text) !== false;
                });
            });

        $grid->addColumnText('assignedTo', 'Řeší', 'assignedTo.name')
            ->setFilterText()
            ->setCondition(function (array $issues, $text) {
                return array_filter($issues, function (Issue $issue) use ($text) {
                    if ($issue->getAssignedTo() === null) {
                        return false;
                    }

                    return stripos($issue->getAssignedTo()->getName(), $text) !== false;
                });
            });

        $grid->addColumnText('doneRatio', 'Hotovo', 'doneRatio')
            ->setRenderer(function (Issue $issue) {
                return $issue->getDoneRatio() . '%';
            });

        $grid->addAction('edit', 'Edit', 'edit');

        return $grid;
    }
}

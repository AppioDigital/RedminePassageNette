<?php
declare(strict_types=1);

namespace Appio\RedmineNette\UI\Grids;

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

        $grid->addColumnText('id', 'ID');

        $grid->addColumnText('tracker', 'Typ', 'tracker.name');

        $grid->addColumnText('subject', 'Název');

        $grid->addColumnText('status', 'Stav', 'status.name');

        $grid->addColumnText('priority', 'Priorita', 'priority.name');

        $grid->addColumnText('author', 'Autor', 'author.name');

        $grid->addColumnText('assignedTo', 'Řeší', 'assignedTo.name');

        $grid->addAction('edit', 'Edit', 'edit');

        return $grid;
    }
}

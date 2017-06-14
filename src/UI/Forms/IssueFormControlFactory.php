<?php
declare(strict_types=1);

namespace Appio\RedmineNette\UI\Forms;

use Appio\RedmineNette\Services\FileService;
use Appio\RedmineNette\Services\Providers\ProjectIssueServiceProvider;
use Nette\SmartObject;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class IssueFormControlFactory
{
    use SmartObject;

    /** @var ProjectIssueServiceProvider */
    private $issueServiceProvider;

    /** @var FileService */
    private $fileService;

    /**
     * @param ProjectIssueServiceProvider $issueServiceProvider
     * @param FileService $fileService
     */
    public function __construct(ProjectIssueServiceProvider $issueServiceProvider, FileService $fileService)
    {
        $this->issueServiceProvider = $issueServiceProvider;
        $this->fileService = $fileService;
    }

    /**
     * @param int|null $id
     * @param int|null $projectId
     * @return IssueFormControl
     */
    public function create(int $id = null, int $projectId = null): IssueFormControl
    {
        $issueService = $this->issueServiceProvider->getService($projectId);
        $issue = $id !== null ? $issueService->getIssue($id) : null;
        return new IssueFormControl($issueService, $this->fileService, $issue);
    }
}

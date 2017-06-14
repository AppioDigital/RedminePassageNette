<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Services\Providers;

use Appio\Redmine\Manager\IssueManager;
use Appio\Redmine\Manager\PriorityManager;
use Appio\Redmine\Manager\StatusManager;
use Appio\Redmine\Manager\TrackerManager;
use Appio\RedmineNette\Services\FileService;
use Appio\RedmineNette\Services\ProjectIssueService;
use Nette\SmartObject;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class ProjectIssueServiceProvider
{
    use SmartObject;

    /** @var array */
    private $defaults;

    /** @var IssueManager */
    private $issueManager;

    /** @var ProjectManagerProvider */
    private $projectManagerProvider;

    /** @var TrackerManager */
    private $trackerManager;

    /** @var PriorityManager */
    private $priorityManager;

    /** @var StatusManager */
    private $statusManager;

    /** @var FileService */
    private $fileService;

    /** @var ProjectIssueService[] */
    private $services;

    /**
     * @param array $defaults
     * @param IssueManager $issueManager
     * @param ProjectManagerProvider $projectManagerProvider
     * @param TrackerManager $trackerManager
     * @param PriorityManager $priorityManager
     * @param StatusManager $statusManager
     * @param FileService $fileService
     */
    public function __construct(
        array $defaults,
        IssueManager $issueManager,
        ProjectManagerProvider $projectManagerProvider,
        TrackerManager $trackerManager,
        PriorityManager $priorityManager,
        StatusManager $statusManager,
        FileService $fileService
    ) {
        $this->defaults = $defaults;
        $this->issueManager = $issueManager;
        $this->projectManagerProvider = $projectManagerProvider;
        $this->trackerManager = $trackerManager;
        $this->priorityManager = $priorityManager;
        $this->statusManager = $statusManager;
        $this->fileService = $fileService;
    }

    /**
     * @param int|null $id
     * @return ProjectIssueService
     */
    public function getService(int $id = null): ProjectIssueService
    {
        $key = $id ?? 'default';

        if (!isset($this->services[$key])) {
            $projectManager = $this->projectManagerProvider->getManager($id);
            $this->services[$key] = new ProjectIssueService(
                $this->defaults[$key] ?? [],
                $this->issueManager,
                $projectManager,
                $this->trackerManager,
                $this->priorityManager,
                $this->statusManager,
                $this->fileService
            );
        }

        return $this->services[$key];
    }
}

<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Services;

use Appio\Redmine\Entity\CustomField;
use Appio\Redmine\Entity\Issue;
use Appio\Redmine\DTO\Issue as IssueDTO;
use Appio\Redmine\Exception\EntityNotFoundException;
use Appio\Redmine\Manager\IssueManager;
use Appio\Redmine\Manager\PriorityManager;
use Appio\Redmine\Manager\ProjectManager;
use Appio\Redmine\Manager\StatusManager;
use Appio\Redmine\Manager\TrackerManager;
use Appio\RedmineNette\Utils\Helpers;
use Exception;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\SmartObject;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class ProjectIssueService
{
    use SmartObject;

    /** @var array */
    private $defaults;

    /** @var IssueManager */
    private $issueManager;

    /** @var ProjectManager */
    private $projectManager;

    /** @var TrackerManager */
    private $trackerManager;

    /** @var PriorityManager */
    private $priorityManager;

    /** @var StatusManager */
    private $statusManager;

    /** @var FileService */
    private $fileService;

    /**
     * @param array $defaults
     * @param IssueManager $issueManager
     * @param ProjectManager $projectManager
     * @param TrackerManager $trackerManager
     * @param PriorityManager $priorityManager
     * @param StatusManager $statusManager
     * @param FileService $fileService
     */
    public function __construct(
        array $defaults,
        IssueManager $issueManager,
        ProjectManager $projectManager,
        TrackerManager $trackerManager,
        PriorityManager $priorityManager,
        StatusManager $statusManager,
        FileService $fileService
    ) {
        $this->defaults = $defaults;
        $this->issueManager = $issueManager;
        $this->projectManager = $projectManager;
        $this->trackerManager = $trackerManager;
        $this->priorityManager = $priorityManager;
        $this->statusManager = $statusManager;
        $this->fileService = $fileService;
    }

    /**
     * @param int $id
     * @return Issue
     * @throws BadRequestException
     */
    public function getIssue(int $id): Issue
    {
        try {
            return $this->issueManager->get($id);
        } catch (EntityNotFoundException $exception) {
            throw new BadRequestException;
        }
    }

    /**
     * @return Issue[]
     */
    public function getAllIssues(): array
    {
        return $this->issueManager->findAllByProject($this->projectManager->getId(), $this->defaults['params']);
    }

    /**
     * @param IssueDTO $issueDto
     * @return Issue
     */
    public function saveIssue(IssueDTO $issueDto): Issue
    {
        if ($issueDto->hasProjectId() === false) {
            $issueDto->setProjectId($this->projectManager->getId());
        }

        if ($issueDto->hasTrackerId() === false) {
            $issueDto->setTrackerId(
                $this->defaults['trackerId'] ?? $this->trackerManager->getDefaultTracker()->getId()
            );
        }

        if ($issueDto->hasStatusId() === false) {
            $issueDto->setStatusId($this->defaults['statusId'] ?? $this->statusManager->getDefaultStatus()->getId());
        }

        if ($issueDto->hasAssignedToId() === false && isset($this->defaults['assignedToId'])) {
            $issueDto->setAssignedToId($this->defaults['assignedToId']);
        }

        return $this->issueManager->save($issueDto);
    }

    /**
     * @param Issue|null $issue
     * @param Form $form
     * @return Issue|null
     */
    public function saveIssueFromForm(?Issue $issue, Form $form): ?Issue
    {
        $values = $form->getValues();

        $issueDto = new IssueDTO;

        if ($issue !== null) {
            $issueDto->setId($issue->getId());
            $issueDto->setProjectId($issue->getProjectId());
            $issueDto->setTrackerId($issue->getTrackerId());
            $issueDto->setStatusId($issue->getStatusId());
            if ($issue->getAssignedToId() !== null) {
                $issueDto->setAssignedToId($issue->getAssignedToId());
            }
        }

        $issueDto->setPriorityId($values->priority);
        $issueDto->setSubject($values->subject);

        if ($values->description ?? false) {
            $issueDto->setDescription($values->description);
        }

        try {
            $issueDto->setStartDate(Helpers::convertDateStringToRedmineFormat($values->startDate));
            $issueDto->setDueDate(Helpers::convertDateStringToRedmineFormat($values->dueDate));
        } catch (Exception $exception) {
            $form->addError('Špatný formát datumu');
            return null;
        }

        // comment
        if ($values->journal ?? false) {
            $issueDto->setNotes($values->journal, true);
        }

        // files
        /** @var FileUpload $file */
        $file = $values->attachment->file;
        if ($file->isOk()) {
            $issueDto->setUploads([$this->fileService->uploadFile($file, $values->attachment->description)]);
        }

        // custom fields
        $customFields = [];
        foreach ($values->customFields as $customFieldId => $value) {
            if (is_bool($value)) {
                $value = $value === true ? '1' : '0';
            } else {
                $value = (string) $value;
            }

            $customFields[] = new CustomField((int) $customFieldId, '', $value);
        }
        $issueDto->setCustomFields($customFields);

        return $this->saveIssue($issueDto);
    }

    /* ======== select list providers ========= */

    /**
     * @return array
     */
    public function getProjectsList(): array
    {
        return [$this->projectManager->getId() => $this->projectManager->getName()];
    }

    /**
     * @return array
     */
    public function getTrackersList(): array
    {
        $trackers = [];
        foreach ($this->trackerManager->findAll() as $tracker) {
            $trackers[$tracker->getId()] = $tracker->getName();
        }

        return $trackers;
    }

    /**
     * @return array
     */
    public function getPrioritiesList(): array
    {
        $priorities = [];
        foreach ($this->priorityManager->findAll() as $priority) {
            $priorities[$priority->getId()] = $priority->getName();
        }

        return $priorities;
    }

    /**
     * @return array
     */
    public function getStatusesList(): array
    {
        $statuses = [];
        foreach ($this->statusManager->findAll() as $status) {
            $statuses[$status->getId()] = $status->getName();
        }

        return $statuses;
    }

    /* ======= config ======== */

    /**
     * @return array
     */
    public function getCustomFieldsConfig(): array
    {
        return $this->defaults['customFields'] ?? [];
    }
}

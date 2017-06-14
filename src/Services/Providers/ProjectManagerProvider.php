<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Services\Providers;

use Appio\Redmine\Fetcher\ObjectFetcher;
use Appio\Redmine\Manager\ProjectManager;
use Appio\Redmine\Normalizer\Entity\ProjectNormalizer;
use Nette\SmartObject;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class ProjectManagerProvider
{
    use SmartObject;

    /** @var int */
    private $defaultProjectId;

    /** @var ObjectFetcher */
    private $fetcher;

    /** @var ProjectNormalizer */
    private $denormalizer;

    /** @var ProjectManager[] */
    private $managers;

    /**
     * @param int $defaultProjectId
     * @param ObjectFetcher $fetcher
     * @param ProjectNormalizer $denormalizer
     */
    public function __construct(int $defaultProjectId, ObjectFetcher $fetcher, ProjectNormalizer $denormalizer)
    {
        $this->defaultProjectId = $defaultProjectId;
        $this->fetcher = $fetcher;
        $this->denormalizer = $denormalizer;
        $this->managers = [];
    }

    /**
     * Return project manager by projectId -> return default manager id is null
     * @param int|null $id
     * @return ProjectManager
     */
    public function getManager(int $id = null): ProjectManager
    {
        $id = $id ?? $this->defaultProjectId;

        if (!isset($this->managers[$id])) {
            $this->managers[$id] = new ProjectManager($this->fetcher, $this->denormalizer, $id);
        }

        return $this->managers[$id];
    }
}

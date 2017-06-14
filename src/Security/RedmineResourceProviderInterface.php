<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Security;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
interface RedmineResourceProviderInterface
{
    /**
     * @return RedmineResourceKeyInterface|null
     */
    public function getResource(): ?RedmineResourceKeyInterface;
}

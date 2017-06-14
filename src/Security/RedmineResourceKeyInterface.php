<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Security;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
interface RedmineResourceKeyInterface
{
    /**
     * @return string
     */
    public function getRedminApiKey(): string;
}

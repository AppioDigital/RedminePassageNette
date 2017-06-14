<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Httplug;

use Appio\RedmineNette\Security\RedmineResourceProviderInterface;
use Http\Client\Exception\RequestException;
use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class ApiKeyAuthentication implements Authentication
{
    /** @var RedmineResourceProviderInterface */
    private $redmineResourceProvider;

    /** @var string|null */
    private $apiKey;

    /**
     * @param RedmineResourceProviderInterface $redmineResourceProvider
     */
    public function __construct(RedmineResourceProviderInterface $redmineResourceProvider)
    {
        $this->redmineResourceProvider = $redmineResourceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(RequestInterface $request)
    {
        $apiKey = $this->getApiKey();

        if ($apiKey === null) {
            throw new RequestException('Missing api key', $request);
        }

        return $request->withAddedHeader('X-Redmine-API-Key', $apiKey);
    }

    /**
     * @return string|null
     */
    protected function getApiKey(): ?string
    {
        if ($this->apiKey === null) {
            $resource = $this->redmineResourceProvider->getResource();

            if ($resource !== null) {
                return $this->apiKey = $resource->getRedminApiKey();
            }
        }

        return $this->apiKey;
    }
}

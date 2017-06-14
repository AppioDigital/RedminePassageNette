<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Services;

use Appio\Redmine\Entity\Upload;
use Appio\Redmine\Manager\UploadManager;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class FileService
{
    use SmartObject;

    /** @var UploadManager */
    private $uploadManager;

    /** @var HttpClient */
    private $client;

    /** @var MessageFactory */
    private $messageFactory;

    /**
     * @param UploadManager $uploadManager
     * @param HttpClient $client
     * @param MessageFactory $messageFactory
     */
    public function __construct(UploadManager $uploadManager, HttpClient $client, MessageFactory $messageFactory)
    {
        $this->uploadManager = $uploadManager;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param string $url
     * @return ResponseInterface
     */
    public function downloadFile(string $url): ResponseInterface
    {
        return $this->client->sendRequest($this->messageFactory->createRequest('GET', $url));
    }

    /**
     * @param FileUpload $file
     * @param string $description
     * @return Upload
     */
    public function uploadFile(FileUpload $file, string $description): Upload
    {
        return $this->uploadManager->create(
            $file->getTemporaryFile(),
            $file->getName(),
            $file->getContentType() ?? '',
            $description
        );
    }
}

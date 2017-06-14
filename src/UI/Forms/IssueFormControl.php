<?php
declare(strict_types=1);

namespace Appio\RedmineNette\UI\Forms;

use Appio\Redmine\Entity\Issue;
use Appio\RedmineNette\Services\FileService;
use Appio\RedmineNette\Services\ProjectIssueService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 * @method void onSuccess(Issue $issue)
 */
class IssueFormControl extends Control
{
    /** @var \Closure[] */
    public $onSuccess = [];

    /** @var ProjectIssueService */
    private $issueService;

    /** @var FileService */
    private $fileService;

    /** @var Issue|null */
    private $issue;

    /**
     * @param ProjectIssueService $issueService
     * @param FileService $fileService
     * @param Issue|null $issue
     */
    public function __construct(ProjectIssueService $issueService, FileService $fileService, ?Issue $issue)
    {
        parent::__construct();
        $this->issueService = $issueService;
        $this->fileService = $fileService;
        $this->issue = $issue;
    }

    /**
     * @return Form
     */
    protected function createComponentForm(): Form
    {
        $form = new Form;

        $form->getElementPrototype()->setAttribute('id', 'redmine-form');

        $mainGroupName = $this->issue !== null ?
            $this->issue->getTracker()->getName() . ' # ' . $this->issue->getId() :
            '#';

        $form->addGroup($mainGroupName);

        $form->addSelect('priority', 'Priorita', $this->issueService->getPrioritiesList())
            ->setRequired();

        $form->addText('subject', 'Předmět')
            ->setRequired();

        $form->addTextArea('description', 'Popis')
            ->setRequired();

        $form->addText('startDate', 'Začátek');
        $form->addText('dueDate', 'Uzavřít do');

        $this->initCustomFields($form);

        $form->addGroup('Soubory');
        $attachmentContainer = $form->addContainer('attachment');
        $attachmentContainer->addText('description', 'Popis');
        $attachmentContainer->addUpload('file', 'Soubor');

        $form->addSubmit('save', 'Uložit')
            ->setHtmlId('redmine-form-submit')
            ->setHtmlAttribute('style', 'display: none');

        if ($this->issue !== null) {
            $form->setDefaults([
                'priority' => $this->issue->getPriorityId(),
                'subject' => $this->issue->getSubject(),
                'description' => $this->issue->getDescription(),
                'startDate' => $this->issue->getStartDate() !== null ?
                    $this->issue->getStartDate()->format('d.m.Y') : null,
                'dueDate' => $this->issue->getDueDate() !== null ?
                    $this->issue->getDueDate()->format('d.m.Y') : null
            ]);
        }

        $form->onSuccess[] = function (Form $form) {
            $issue = $this->issueService->saveIssueFromForm($this->issue, $form);
            if ($issue !== null) {
                $this->onSuccess($issue);
            }
        };

        return $form;
    }

    /**
     * @param Form $form
     */
    protected function initCustomFields(Form $form): void
    {
        $form->addGroup();
        $customFields = $form->addContainer('customFields');
        foreach ($this->issueService->getCustomFieldsConfig() as $customFieldId => $customFieldConfig) {
            switch ($customFieldConfig['type'] ?? 'text') {
                case 'hidden':
                    $customFields->addHidden($customFieldId, $customFieldConfig['defaultValue']);
                    break;
                case 'checkbox':
                    $customFields->addCheckbox($customFieldId, $customFieldConfig['label'] ?? '')
                        ->setDefaultValue($customFieldConfig['defaultValue'] ?? null);
                    break;
                case 'text':
                    $customFields->addText($customFieldId, $customFieldConfig['label'] ?? '')
                        ->setDefaultValue($customFieldConfig['defaultValue'] ?? null);
                    break;
                default:
                    throw new InvalidArgumentException('Unknown customField type "' . $customFieldConfig['type'] . '"');
            }
        }

        if ($this->issue !== null) {
            // fill defaults from existing issue
            foreach ($this->issue->getCustomFields() as $customField) {
                if ($customField->getValue() !== '') {
                    $form->setDefaults(['customFields' => [$customField->getId() => $customField->getValue()]]);
                }
            }
        }
    }

    /**
     * @param string $url
     */
    public function handleGetAttachment(string $url): void
    {
        $response = $this->fileService->downloadFile($url);
        $this->presenter->getHttpResponse()->setContentType($response->getHeader('Content-Type')[0]);
        echo $response->getBody();
        $this->presenter->terminate();
    }

    /**
     *
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/issueForm.latte');
        $this->template->issue = $this->issue;
        $this->template->render();
    }
}

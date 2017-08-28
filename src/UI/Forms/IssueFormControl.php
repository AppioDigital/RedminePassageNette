<?php
declare(strict_types=1);

namespace Appio\RedmineNette\UI\Forms;

use Appio\Redmine\Entity\Issue;
use Appio\RedmineNette\Services\FileService;
use Appio\RedmineNette\Services\ProjectIssueService;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;

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

        $trackerControl = $form->addSelect('tracker', 'Typ', $this->issueService->getTrackersList())
            ->setRequired();

        if ($this->issue !== null) {
            $trackerControl->setDisabled();
        } else {
            $trackerControl->setDefaultValue($this->issueService->getDefaultTrackerId());
        }

        $form->addSelect('priority', 'Priorita', $this->issueService->getPrioritiesList())
            ->setRequired();

        $form->addText('subject', 'Předmět')
            ->setRequired();

        $descriptionControl = $form->addTextArea('description', 'Popis', null, 10)
            ->setRequired();

        $form->addText('startDate', 'Začátek');
        $form->addText('dueDate', 'Uzavřít do');

        $this->initCustomFields($form);

        if ($this->issue !== null) {
            // disable editing base description
            $descriptionControl->setDisabled();

            // allow comments (journals)
            $journalsEl = Html::el('div');

            foreach ($this->issue->getJournals() as $journal) {
                if ($journal->isPrivateNotes() === false || $journal->getNotes() === '') {
                    continue;
                }

                $journalsEl->addHtml(sprintf(
                    '<div style="border-top: 1px solid #ccc">%s</div>' .
                    '<div style="text-align: right; margin-bottom: 20px; font-size: 70%%">(%s - %s)</div>',
                    $journal->getNotes(),
                    $journal->getUser()->getName(),
                    $journal->getCreatedOn()->format('j.n.Y H:i')
                ));
            }

            $journalsEl->addHtml('<br><br>');

            $form->addGroup('Komentáře')
                ->setOption('description', $journalsEl);
            $form->addTextArea('journal', 'Nový komentář', null, 10);


            $form->setDefaults([
                'tracker' => $this->issue->getTrackerId(),
                'priority' => $this->issue->getPriorityId(),
                'subject' => $this->issue->getSubject(),
                'description' => $this->issue->getDescription(),
                'startDate' => $this->issue->getStartDate() !== null ?
                    $this->issue->getStartDate()->format('d.m.Y') : null,
                'dueDate' => $this->issue->getDueDate() !== null ?
                    $this->issue->getDueDate()->format('d.m.Y') : null
            ]);
        }

        $form->addGroup('Soubory');
        $attachmentContainer = $form->addContainer('attachment');
        $attachmentContainer->addText('description', 'Popis');
        $attachmentContainer->addUpload('file', 'Soubor');

        $form->addSubmit('save', 'Uložit')
            ->setHtmlId('redmine-form-submit')
            ->setHtmlAttribute('style', 'display: none');

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
                    $control = $customFields->addHidden($customFieldId);
                    break;
                case 'checkbox':
                    $control = $customFields->addCheckbox($customFieldId, $customFieldConfig['label'] ?? '');
                    break;
                case 'text':
                    $control = $customFields->addText($customFieldId, $customFieldConfig['label'] ?? '');
                    break;
                default:
                    throw new InvalidArgumentException('Unknown customField type "' . $customFieldConfig['type'] . '"');
            }

            $disabledAction = $customFieldConfig['disabledAction'] ?? false;
            if ($disabledAction === 'all' ||
                ($disabledAction === 'add' && $this->issue === null) ||
                ($disabledAction === 'edit' && $this->issue !== null)
            ) {
                // disable editation
                $control->setDisabled();
            }

            if (isset($customFieldConfig['defaultValue'])) {
                $control->setDefaultValue($customFieldConfig['defaultValue']);
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

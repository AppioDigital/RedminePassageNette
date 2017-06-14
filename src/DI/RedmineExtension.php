<?php
declare(strict_types=1);

namespace Appio\RedmineNette\DI;

use FreezyBee\Httplug\DI\IClientProvider;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Utils\Validators;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class RedmineExtension extends CompilerExtension implements IClientProvider
{
    /** @var array */
    private static $defaults = [
        'defaultProjectId' => null,
        'defaults' => null,
        'baseUri' => null,
        'httplugFactory' => '@httplug.factory.guzzle6'
    ];

    /**
     *
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $this->config = $this->validateConfig(self::$defaults);

        Validators::assert($this->config['baseUri'], 'uri');
        Validators::assert($this->config['defaultProjectId'], 'int|null');

        // validate defaults structure
        foreach ($this->config['defaults'] ?? [] as $projectId => $projectDefaults) {
            if ($projectId === 'default') {
                continue;
            }

            if (isset($this->config['defaults']['default']) ?? false) {
                $this->config['defaults'][$projectId] += $this->config['defaults']['default'];
            }

            Validators::assert($projectId, 'int');
            if (isset($projectDefaults['assignedToId'])) {
                Validators::assert($projectDefaults['assignedToId'], 'int');
            }
        }

        $builder->parameters['redmine'] = $this->config;

        Compiler::loadDefinitions($builder, $this->loadFromFile(__DIR__ . '/common.neon'));
        Compiler::loadDefinitions($builder, $this->loadFromFile(__DIR__ . '/redmine.neon'), 'redmine');
        Compiler::loadDefinitions($builder, $this->loadFromFile(__DIR__ . '/extension.neon'), 'redmine');
    }

    /**
     * {@inheritdoc}
     */
    public function getClientConfigs(): array
    {
        return ['redmine' => [
            'factory' => $this->config['httplugFactory'],
            'plugins' => [
                'authentication' => [
                    'type' => 'service',
                    'service' => $this->prefix('@httplug.apiKeyAuthentication')
                ],
                'addHost' => [
                    'host' => $this->config['baseUri']
                ],
                'headerDefaults' => [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=utf-8'
                    ]
                ]
            ]
        ]];
    }
}

<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\ScenarioFeatureSet;

use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\Domain\MCP\ToolConstructor;
use SJS\Flow\MCP\FeatureSet\FeatureSetInterface;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;

class ListScenariosTool extends Tool implements ToolConstructor
{
    /**
     * @var array<string, array{file: string, title: string, description: string}>
     */
    private array $catalog = [];

    public function __construct(FeatureSetInterface $featureSet)
    {
        parent::__construct(
            name: 'list_scenarios',
            description: 'Returns a list of available scenarios — curated multi-shot examples '
                . 'showing correct tool call sequences for common CMS tasks. '
                . 'Use get_scenario to load a specific scenario.',
            inputSchema: new ObjectSchema(properties: []),
            annotations: new Annotations(
                title: 'List Scenarios',
                readOnlyHint: true
            ),
            featureSet: $featureSet
        );
    }

    /**
     * @param array<string, array{file: string, title: string, description: string}> $catalog
     */
    public function setCatalog(array $catalog): void
    {
        $this->catalog = $catalog;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $scenarios = [];
        foreach ($this->catalog as $name => $entry) {
            $scenarios[] = [
                'name' => $name,
                'title' => $entry['title'] ?? $name,
                'description' => $entry['description'] ?? '',
            ];
        }

        return Content::structuredWithFallback([
            'scenarios' => $scenarios,
        ]);
    }
}

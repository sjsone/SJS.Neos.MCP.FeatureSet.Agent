<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet;

use SJS\Flow\MCP\Domain\Connection\ServerContext;
use SJS\Flow\MCP\Domain\MCP\Tool\Annotations;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Flow\MCP\Domain\MCP\ToolConstructor;
use SJS\Flow\MCP\FeatureSet\FeatureSetInterface;
use SJS\Flow\MCP\JsonSchema\ObjectSchema;
use SJS\Flow\MCP\JsonSchema\StringSchema;
use SJS\Neos\MCP\FeatureSet\Agent\Tool\AbstractKnowledgeTool;

class GetContextTool extends AbstractKnowledgeTool implements ToolConstructor
{
    /**
     * @var array<string, string> topic name → resource:// URI or file path
     */
    private array $topics = [];

    public function __construct(FeatureSetInterface $featureSet)
    {
        $files = $featureSet->getOptions()['files'] ?? [];
        if (!\is_array($files) || \count($files) === 0) {
            throw new \InvalidArgumentException(
                'GetContextTool: no topics configured. Add files entries under knowledge.options.files in Settings.Server.yaml.'
            );
        }

        $this->topics = $files;

        $topicNames = \array_keys($this->topics);

        $description = 'Load detailed knowledge documentation about Neos CMS concepts. '
            . 'Use this to understand the architecture before operating on content. '
            . 'Valid topics: ' . \implode(', ', $topicNames) . '. '
            . 'For help choosing a topic, see also: get_site_landscape, get_available_children.';

        $inputSchema = new ObjectSchema(properties: [
            'topic' => (new StringSchema(
                description: 'The knowledge topic to load',
                enum: $topicNames,
            ))->required(),
        ]);

        parent::__construct(
            name: 'get_context',
            description: $description,
            inputSchema: $inputSchema,
            annotations: new Annotations(
                title: 'Get Context',
                readOnlyHint: true
            )
        );
    }

    /**
     * @param array<string,mixed> $input
     */
    public function run(ServerContext $serverContext, array $input): Content
    {
        $topic = $input['topic'] ?? '';

        if (!\array_key_exists($topic, $this->topics)) {
            $valid = \implode(', ', \array_keys($this->topics));
            return Content::text("Unknown topic '{$topic}'. Valid topics: {$valid}");
        }

        return Content::text($this->loadMarkdownFile($this->topics[$topic]));
    }
}

<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\Tool;

use SJS\Flow\MCP\Domain\MCP\Tool;
use SJS\Flow\MCP\Domain\MCP\Tool\Content;
use SJS\Neos\MCP\FeatureSet\Agent\Trait\KnowledgeResourceTrait;

/**
 * Base class for tools that load and return knowledge markdown content.
 */
abstract class AbstractKnowledgeTool extends Tool
{
    use KnowledgeResourceTrait;

    /**
     * Load a knowledge file and return it as text Content.
     */
    protected function knowledgeContent(string $filename): Content
    {
        return Content::text($this->loadKnowledgeFile($filename));
    }
}

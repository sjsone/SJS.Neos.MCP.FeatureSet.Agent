<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent;

use Neos\Flow\Annotations as Flow;
use SJS\Flow\MCP\FeatureSet\AbstractFeatureSet;
use SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet\GetContextTool;
use SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet\GetSiteLandscapeTool;
use SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet\GetAvailableChildrenTool;
use SJS\Neos\MCP\FeatureSet\Agent\KnowledgeFeatureSet\FindSimilarContentTool;

#[Flow\Scope("singleton")]
class KnowledgeFeatureSet extends AbstractFeatureSet
{
    public function initialize(): void
    {
        $this->addTool(GetContextTool::class);
        $this->addTool(GetSiteLandscapeTool::class);
        $this->addTool(GetAvailableChildrenTool::class);
        $this->addTool(FindSimilarContentTool::class);
    }
}

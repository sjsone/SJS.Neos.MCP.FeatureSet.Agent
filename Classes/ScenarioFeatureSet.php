<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent;

use Neos\Flow\Annotations as Flow;
use SJS\Flow\MCP\FeatureSet\AbstractFeatureSet;
use SJS\Neos\MCP\FeatureSet\Agent\ScenarioFeatureSet\ListScenariosTool;
use SJS\Neos\MCP\FeatureSet\Agent\ScenarioFeatureSet\GetScenarioTool;

#[Flow\Scope("singleton")]
class ScenarioFeatureSet extends AbstractFeatureSet
{
    public function initialize(): void
    {
        $listTool = $this->addTool(ListScenariosTool::class);
        $getTool = $this->addTool(GetScenarioTool::class);

        // Configure scenario tools with catalog from options
        $scenarios = $this->options['scenarios'] ?? [];
        if (\is_array($scenarios) && \count($scenarios) > 0) {
            $listTool->setCatalog($scenarios);
            $getTool->setCatalog($scenarios);
        }
    }
}

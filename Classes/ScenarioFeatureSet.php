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
        $this->addTool(ListScenariosTool::class);
        $this->addTool(GetScenarioTool::class);
    }
}

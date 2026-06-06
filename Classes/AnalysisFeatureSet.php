<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent;

use Neos\Flow\Annotations as Flow;
use SJS\Flow\MCP\FeatureSet\AbstractFeatureSet;
use SJS\Neos\MCP\FeatureSet\Agent\AnalysisFeatureSet\AuditSEOTool;
use SJS\Neos\MCP\FeatureSet\Agent\AnalysisFeatureSet\FindContentPatternsTool;

#[Flow\Scope("singleton")]
class AnalysisFeatureSet extends AbstractFeatureSet
{
    public function initialize(): void
    {
        $this->addTool(AuditSEOTool::class);
        $this->addTool(FindContentPatternsTool::class);
    }
}

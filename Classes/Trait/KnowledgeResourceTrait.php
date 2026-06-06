<?php

declare(strict_types=1);

namespace SJS\Neos\MCP\FeatureSet\Agent\Trait;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;

trait KnowledgeResourceTrait
{
    #[Flow\Inject]
    protected PackageManager $packageManager;

    /**
     * Load a markdown file from Resources/Private/{directory}/{filename}
     */
    protected function loadMarkdownFromDirectory(string $directory, string $filename): string
    {
        $package = $this->packageManager->getPackage('SJS.Neos.MCP.FeatureSet.Agent');
        $filePath = $package->getResourcesPath() . 'Private/' . $directory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(
                "File not found: {$directory}/{$filename}"
            );
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \InvalidArgumentException(
                "Could not read file: {$directory}/{$filename}"
            );
        }

        return $content;
    }

    /**
     * Load a knowledge markdown file from Resources/Private/Knowledge/
     */
    protected function loadKnowledgeFile(string $filename): string
    {
        return $this->loadMarkdownFromDirectory('Knowledge', $filename);
    }
}

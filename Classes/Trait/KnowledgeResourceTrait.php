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
     * Load a markdown file from a resource:// URI or filesystem path.
     *
     * Neos Flow handles resource:// URIs natively via its stream wrapper,
     * so \file_get_contents('resource://Package.Key/Private/.../file.md')
     * works directly.
     */
    protected function loadMarkdownFile(string $uri): string
    {
        $content = \file_get_contents($uri);
        if ($content === false) {
            throw new \InvalidArgumentException(
                "Could not read file: {$uri}"
            );
        }

        return $content;
    }

    /**
     * Load a markdown file from Resources/Private/{directory}/{filename}
     * (Legacy helper for non-resource-URI paths.)
     */
    protected function loadMarkdownFromDirectory(string $directory, string $filename): string
    {
        $package = $this->packageManager->getPackage('SJS.Neos.MCP.FeatureSet.Agent');
        $filePath = $package->getResourcesPath() . 'Private/' . $directory . '/' . $filename;

        if (!\file_exists($filePath)) {
            throw new \InvalidArgumentException(
                "File not found: {$directory}/{$filename}"
            );
        }

        $content = \file_get_contents($filePath);
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

<?php

declare(strict_types=1);

namespace Scafera\Frontend\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\InstalledPackages;

final class TemplateLocationValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Template location';
    }

    public function validate(string $projectDir): array
    {
        $resourcesDir = $projectDir . '/resources';
        if (!is_dir($resourcesDir)) {
            return [];
        }

        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $templatesDir = $architecture?->getTemplatesDir();

        if ($templatesDir === null) {
            return [];
        }

        $templatesPrefix = rtrim($templatesDir, '/') . '/';
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resourcesDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'twig') {
                continue;
            }

            $relative = str_replace($projectDir . '/', '', $file->getPathname());

            if (!str_starts_with($relative, $templatesPrefix)) {
                $violations[] = $relative . ' is outside ' . $templatesPrefix . ' — move it to ' . $templatesPrefix;
            }
        }

        return $violations;
    }
}

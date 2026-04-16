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
        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $templatesDir = $architecture?->getTemplatesDir();

        if ($templatesDir === null) {
            return [];
        }

        $templatesPrefix = rtrim($templatesDir, '/') . '/';

        // Scan ONE level above the templates dir so stray .twig files in
        // sibling folders are caught (e.g. for templatesDir='resources/templates',
        // scan 'resources/' — catches 'resources/stray.twig' and 'resources/emails/x.twig').
        //
        // Deliberate trade-off: strays more than one level outside the templates
        // parent (e.g. 'app/foo.twig' when templatesDir='app/views/twig') are not
        // scanned. Walking the whole project is too expensive for this check.
        //
        // Degenerate case: when templatesDir is at project root (e.g. 'templates/'),
        // there is no "upper" level to scan — fall back to scanning only the
        // templates dir itself.
        $parent = dirname($templatesDir);
        $scanRelative = ($parent === '.' || $parent === '') ? rtrim($templatesDir, '/') : rtrim($parent, '/');
        $scanRoot = $projectDir . '/' . $scanRelative;

        if (!is_dir($scanRoot)) {
            return [];
        }

        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanRoot, \FilesystemIterator::SKIP_DOTS),
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

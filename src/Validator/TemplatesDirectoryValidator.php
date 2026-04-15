<?php

declare(strict_types=1);

namespace Scafera\Frontend\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\InstalledPackages;

final class TemplatesDirectoryValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Templates directory';
    }

    public function validate(string $projectDir): array
    {
        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $templatesDir = $architecture?->getTemplatesDir();

        if ($templatesDir === null) {
            return [];
        }

        if (is_dir($projectDir . '/' . $templatesDir)) {
            return [];
        }

        return ['scafera/frontend is installed but ' . $templatesDir . '/ directory does not exist. Create it or remove the package.'];
    }
}

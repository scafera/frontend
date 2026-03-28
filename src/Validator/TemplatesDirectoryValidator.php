<?php

declare(strict_types=1);

namespace Scafera\Frontend\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class TemplatesDirectoryValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'Templates directory';
    }

    public function validate(string $projectDir): array
    {
        if (is_dir($projectDir . '/templates')) {
            return [];
        }

        return ['scafera/frontend is installed but templates/ directory does not exist. Create it or remove the package.'];
    }
}

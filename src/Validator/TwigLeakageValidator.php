<?php

declare(strict_types=1);

namespace Scafera\Frontend\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class TwigLeakageValidator implements ValidatorInterface
{
    public function getId(): string
    {
        return 'frontend.twig-leakage';
    }

    public function getName(): string
    {
        return 'No Twig imports in userland';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';
        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $contents = file_get_contents($file);
            $relative = 'src/' . str_replace($srcDir . '/', '', $file);

            if (preg_match('/^use\s+Twig\\\\[{A-Z]/m', $contents)) {
                $violations[] = $relative . ': imports Twig types directly — use Scafera\Kernel\Contract\ViewInterface instead';
            }
        }

        return $violations;
    }
}

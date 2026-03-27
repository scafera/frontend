<?php

declare(strict_types=1);

namespace Scafera\Frontend\Validator;

use Scafera\Kernel\Contract\ValidatorInterface;

final class TwigLeakageValidator implements ValidatorInterface
{
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

        foreach ($this->findPhpFiles($srcDir) as $file) {
            $contents = file_get_contents($file);
            $relative = 'src/' . str_replace($srcDir . '/', '', $file);

            if (preg_match('/^use\s+Twig\\\\/m', $contents)) {
                $violations[] = $relative . ': imports Twig types directly — use Scafera\Kernel\Contract\ViewInterface instead';
            }
        }

        return $violations;
    }

    /** @return list<string> */
    private function findPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

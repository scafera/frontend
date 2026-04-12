<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Frontend\Validator\TwigLeakageValidator;

class TwigLeakageValidatorTest extends TestCase
{
    private TwigLeakageValidator $validator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->validator = new TwigLeakageValidator();
        $this->tmpDir = sys_get_temp_dir() . '/scafera_twig_test_' . uniqid();
        mkdir($this->tmpDir . '/src', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenNoTwigImports(): void
    {
        file_put_contents($this->tmpDir . '/src/HomeController.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use Scafera\Kernel\Contract\ViewInterface;
        class HomeController {
            public function __construct(private readonly ViewInterface $view) {}
        }
        PHP);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenTwigEnvironmentImported(): void
    {
        file_put_contents($this->tmpDir . '/src/BadController.php', <<<'PHP'
        <?php
        namespace App\Controller;
        use Twig\Environment;
        class BadController {
            public function __construct(private readonly Environment $twig) {}
        }
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('BadController.php', $violations[0]);
        $this->assertStringContainsString('ViewInterface', $violations[0]);
    }

    public function testFailsWhenTwigExtensionImported(): void
    {
        file_put_contents($this->tmpDir . '/src/CustomExtension.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Twig\Extension\AbstractExtension;
        class CustomExtension extends AbstractExtension {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('CustomExtension.php', $violations[0]);
    }

    public function testFailsWhenTwigGroupedImportUsed(): void
    {
        file_put_contents($this->tmpDir . '/src/GroupedImport.php', <<<'PHP'
        <?php
        namespace App\Service;
        use Twig\{Environment, Loader\ArrayLoader};
        class GroupedImport {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('GroupedImport.php', $violations[0]);
    }

    public function testPassesWhenNoSrcDir(): void
    {
        $emptyDir = sys_get_temp_dir() . '/scafera_empty_' . uniqid();
        mkdir($emptyDir);

        $this->assertSame([], $this->validator->validate($emptyDir));

        rmdir($emptyDir);
    }

    public function testReportsMultipleViolations(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad1.php', <<<'PHP'
        <?php
        use Twig\Environment;
        class Bad1 {}
        PHP);

        mkdir($this->tmpDir . '/src/Sub', 0777, true);
        file_put_contents($this->tmpDir . '/src/Sub/Bad2.php', <<<'PHP'
        <?php
        use Twig\Loader\ArrayLoader;
        class Bad2 {}
        PHP);

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(2, $violations);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}

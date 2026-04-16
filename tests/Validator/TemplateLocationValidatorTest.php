<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Frontend\Validator\TemplateLocationValidator;

class TemplateLocationValidatorTest extends TestCase
{
    private string $tmpDir;
    private TemplateLocationValidator $validator;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/scafera_tplloc_test_' . uniqid();
        mkdir($this->tmpDir . '/vendor/composer', 0777, true);
        $this->writeInstalledJson();
        $this->validator = new TemplateLocationValidator();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenNoResourcesDir(): void
    {
        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testPassesWhenAllTwigInTemplatesDir(): void
    {
        mkdir($this->tmpDir . '/resources/templates/layouts', 0777, true);
        file_put_contents($this->tmpDir . '/resources/templates/home.html.twig', '');
        file_put_contents($this->tmpDir . '/resources/templates/layouts/base.html.twig', '');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsOnTwigAtResourcesRoot(): void
    {
        mkdir($this->tmpDir . '/resources', 0777, true);
        file_put_contents($this->tmpDir . '/resources/base.html.twig', '');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('resources/base.html.twig', $violations[0]);
        $this->assertStringContainsString('resources/templates/', $violations[0]);
    }

    public function testFailsOnTwigInSiblingDir(): void
    {
        mkdir($this->tmpDir . '/resources/emails', 0777, true);
        file_put_contents($this->tmpDir . '/resources/emails/welcome.html.twig', '');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('resources/emails/welcome.html.twig', $violations[0]);
    }

    public function testIgnoresNonTwigFiles(): void
    {
        mkdir($this->tmpDir . '/resources/translations', 0777, true);
        mkdir($this->tmpDir . '/resources/assets', 0777, true);
        file_put_contents($this->tmpDir . '/resources/translations/en.json', '{}');
        file_put_contents($this->tmpDir . '/resources/assets/app.css', 'body{}');

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testScanRootDerivedFromNonDefaultTemplatesDir(): void
    {
        require_once __DIR__ . '/../Fixtures/NestedTemplatesArchitecture.php';
        $this->writeInstalledJson(\Scafera\Frontend\Tests\Fixtures\NestedTemplatesArchitecture::class);

        mkdir($this->tmpDir . '/views/app/templates', 0777, true);
        file_put_contents($this->tmpDir . '/views/app/templates/home.html.twig', '');
        file_put_contents($this->tmpDir . '/views/app/stray.html.twig', '');

        $violations = $this->validator->validate($this->tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('views/app/stray.html.twig', $violations[0]);
        $this->assertStringContainsString('views/app/templates/', $violations[0]);
    }

    public function testPassesWhenScanRootParentDoesNotExist(): void
    {
        require_once __DIR__ . '/../Fixtures/NestedTemplatesArchitecture.php';
        $this->writeInstalledJson(\Scafera\Frontend\Tests\Fixtures\NestedTemplatesArchitecture::class);

        // No views/ dir at all — nothing to scan, no violations expected.
        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testRootLevelTemplatesDirScansOnlyTemplatesItself(): void
    {
        require_once __DIR__ . '/../Fixtures/RootTemplatesArchitecture.php';
        $this->writeInstalledJson(\Scafera\Frontend\Tests\Fixtures\RootTemplatesArchitecture::class);

        mkdir($this->tmpDir . '/templates', 0777, true);
        file_put_contents($this->tmpDir . '/templates/home.html.twig', '');

        // Valid: inside the templates dir → no violation.
        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testRootLevelTemplatesDirDoesNotWalkEntireProject(): void
    {
        require_once __DIR__ . '/../Fixtures/RootTemplatesArchitecture.php';
        $this->writeInstalledJson(\Scafera\Frontend\Tests\Fixtures\RootTemplatesArchitecture::class);

        mkdir($this->tmpDir . '/templates', 0777, true);
        mkdir($this->tmpDir . '/other', 0777, true);
        file_put_contents($this->tmpDir . '/other/stray.html.twig', '');

        // Documented blind spot: strays outside templates/ are NOT caught when
        // templatesDir is at project root. Test locks that behavior in.
        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    private function writeInstalledJson(string $architectureClass = 'Scafera\\Layered\\LayeredArchitecture'): void
    {
        @unlink($this->tmpDir . '/var/cache/installed_packages.php');

        file_put_contents(
            $this->tmpDir . '/vendor/composer/installed.json',
            json_encode(['packages' => [[
                'name' => 'scafera/layered',
                'type' => 'symfony-bundle',
                'extra' => ['scafera-architecture' => $architectureClass],
                'autoload' => ['psr-4' => []],
            ]]]),
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}

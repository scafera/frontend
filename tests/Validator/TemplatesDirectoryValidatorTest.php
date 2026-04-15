<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Frontend\Validator\TemplatesDirectoryValidator;

class TemplatesDirectoryValidatorTest extends TestCase
{
    private string $tmpDir;
    private TemplatesDirectoryValidator $validator;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/scafera_tpl_test_' . uniqid();
        mkdir($this->tmpDir . '/vendor/composer', 0777, true);
        $this->writeInstalledJson();
        $this->validator = new TemplatesDirectoryValidator();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPassesWhenTemplatesDirectoryExists(): void
    {
        mkdir($this->tmpDir . '/resources/templates', 0777, true);

        $this->assertSame([], $this->validator->validate($this->tmpDir));
    }

    public function testFailsWhenTemplatesDirectoryMissing(): void
    {
        $violations = $this->validator->validate($this->tmpDir);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('resources/templates/', $violations[0]);
    }

    public function testSkipsWhenNoArchitectureInstalled(): void
    {
        $noArchDir = sys_get_temp_dir() . '/scafera_tpl_noarch_' . uniqid();
        mkdir($noArchDir);

        $this->assertSame([], $this->validator->validate($noArchDir));

        rmdir($noArchDir);
    }

    private function writeInstalledJson(): void
    {
        @unlink($this->tmpDir . '/var/cache/installed_packages.php');

        file_put_contents(
            $this->tmpDir . '/vendor/composer/installed.json',
            json_encode(['packages' => [[
                'name' => 'scafera/layered',
                'type' => 'symfony-bundle',
                'extra' => ['scafera-architecture' => 'Scafera\\Layered\\LayeredArchitecture'],
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

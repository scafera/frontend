<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests\Fixtures;

use Scafera\Kernel\Contract\ArchitecturePackageInterface;

/**
 * Architecture fixture with templatesDir at the project root (just 'templates').
 * Exercises the degenerate fallback — no "upper" level to scan.
 */
final class RootTemplatesArchitecture implements ArchitecturePackageInterface
{
    public function getName(): string { return 'root-fixture'; }
    public function getServiceDiscovery(string $projectDir): array { return ['namespace' => 'App\\', 'resource' => 'src/', 'exclude' => []]; }
    public function getControllerPaths(): array { return []; }
    public function getStructure(): array { return []; }
    public function getValidators(): array { return []; }
    public function getGenerators(): array { return []; }
    public function getAdvisors(): array { return []; }
    public function getEntityMapping(): ?array { return null; }
    public function getTranslationsDir(): ?string { return null; }
    public function getStorageDir(): ?string { return null; }
    public function getAssetsDir(): ?string { return null; }
    public function getTemplatesDir(): ?string { return 'templates'; }
}

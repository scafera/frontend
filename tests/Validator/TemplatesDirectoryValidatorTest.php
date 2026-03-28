<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Scafera\Frontend\Validator\TemplatesDirectoryValidator;

class TemplatesDirectoryValidatorTest extends TestCase
{
    private TemplatesDirectoryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TemplatesDirectoryValidator();
    }

    public function testPassesWhenTemplatesDirectoryExists(): void
    {
        $tmpDir = sys_get_temp_dir() . '/scafera_tpl_test_' . uniqid();
        mkdir($tmpDir . '/templates', 0777, true);

        $this->assertSame([], $this->validator->validate($tmpDir));

        rmdir($tmpDir . '/templates');
        rmdir($tmpDir);
    }

    public function testFailsWhenTemplatesDirectoryMissing(): void
    {
        $tmpDir = sys_get_temp_dir() . '/scafera_tpl_test_' . uniqid();
        mkdir($tmpDir);

        $violations = $this->validator->validate($tmpDir);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('templates/', $violations[0]);

        rmdir($tmpDir);
    }
}

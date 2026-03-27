<?php

declare(strict_types=1);

namespace Scafera\Frontend;

use Scafera\Kernel\Contract\ViewInterface;
use Twig\Environment;

final class View implements ViewInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}

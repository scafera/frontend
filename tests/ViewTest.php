<?php

declare(strict_types=1);

namespace Scafera\Frontend\Tests;

use PHPUnit\Framework\TestCase;
use Scafera\Frontend\View;
use Twig\Environment;

class ViewTest extends TestCase
{
    public function testRenderDelegatesToTwigAndReturnsString(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->with('order/show.html.twig', ['id' => 42])
            ->willReturn('<h1>Order #42</h1>');

        $view = new View($twig);
        $result = $view->render('order/show.html.twig', ['id' => 42]);

        $this->assertSame('<h1>Order #42</h1>', $result);
    }
}

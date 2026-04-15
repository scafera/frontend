<?php

declare(strict_types=1);

namespace Scafera\Frontend;

use Scafera\Kernel\Contract\ViewInterface;
use Scafera\Kernel\InstalledPackages;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ScaferaFrontendBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $projectDir = $builder->getParameter('kernel.project_dir');
        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $templatesDir = $architecture?->getTemplatesDir();

        if ($templatesDir === null) {
            return;
        }

        $builder->prependExtensionConfig('twig', [
            'default_path' => '%kernel.project_dir%/' . $templatesDir,
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->set(View::class)
                ->autowire()
                ->public()
            ->alias(ViewInterface::class, View::class)
                ->public();

        $container->services()
            ->set(Validator\TwigLeakageValidator::class)
                ->tag('scafera.validator')
            ->set(Validator\TemplatesDirectoryValidator::class)
                ->tag('scafera.validator');
    }
}

<?php
namespace Application\Factory;

use Application\Controller\PrintController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\View\Renderer\RendererInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PrintControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tcpdf = $container->get(\TCPDF::class);
        $renderer = $container->get(RendererInterface::class);
        return new PrintController(
            $tcpdf,
            $renderer
        );
    }
}
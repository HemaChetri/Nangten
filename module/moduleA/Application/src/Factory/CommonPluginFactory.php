<?php
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
 
class CommonPluginFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return ((substr($requestedName, -6) === 'Plugin') && class_exists($requestedName));
    }
 
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $pluginName = '\\' . $requestedName;

        return new $pluginName($container);
    }
}
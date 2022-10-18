<?php
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
 
class CommonControllerFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return ((substr($requestedName, -10) === 'Controller') && class_exists($requestedName));
    }
 
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $controllerName = '\\' . $requestedName;

        return new $controllerName($container);
    }
}
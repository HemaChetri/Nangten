<?php
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
 
class CommonHelperFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return ((substr($requestedName, -6) === 'Helper') && class_exists($requestedName));
    }
 
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $helperName = '\\' . $requestedName;
		
        return new $helperName($container);
    }
}
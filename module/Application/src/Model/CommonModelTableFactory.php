<?php
namespace Application\Model;
 
use ArrayObject;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
 
class CommonModelTableFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return ((substr($requestedName, -5) === 'Table') && class_exists($requestedName));
    }
 
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $tableModel = '\\' . $requestedName;
 
       $adapter = $container->get('Laminas\Db\Adapter\Adapter');
       
        return new $tableModel($adapter);
    }
}
<?php
/**
 * Notification Helper Factory
 * chophel@athang.com
 */
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Acl\Model\NotifyTable;

class NotificationHelperFactory implements FactoryInterface
{
    /**
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return Getresourcehelper
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, NotificationHelper::class);
    }
    /**
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return Getresourcehelper
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $class = $requestedName ? '\\' .$requestedName : NotificationHelper::class;
        $Table = $container->get(NotifyTable::class);
        $viewHelper = new $class($Table);
        
        return $viewHelper;
		//return new Getresourcehelper($container->get('Acl\attachTable'));
		
    }

}



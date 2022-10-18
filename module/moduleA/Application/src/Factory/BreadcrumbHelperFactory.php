<?php
/*
 * 
 * Helper -- Get Resoucre with given route format
 * 
 * 
 */
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Application\View\Helper\Getresourcehelper;
use Acl\Model\AclTable;

class BreadcrumbHelperFactory implements FactoryInterface
{
    /**
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return Getresourcehelper
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, GetresourceHelper::class);
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
        $class = $requestedName ? $requestedName : GetresourceHelper::class;
        $aclTable = $container->get(AclTable::class);
        $viewHelper = new $class($aclTable, $container);
        
        return $viewHelper;
		
    }

}



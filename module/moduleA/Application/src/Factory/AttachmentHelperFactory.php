<?php
/*
 * Helper -- Get Attachment
 * chophel@athang.com
 */
namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Application\View\Helper\GetresourceHelper;
use Acl\Model\AttachmentTable;

class AttachmentHelperFactory implements FactoryInterface
{
    /**
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return Getresourcehelper
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AttachmentHelper::class);
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
        $class = $requestedName ? '\\' .$requestedName : AttachmentHelper::class;
        $attachTable = $container->get(AttachmentTable::class);
        $viewHelper = new $class($attachTable);
        
        return $viewHelper;
    }

}



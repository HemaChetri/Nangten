<?php
/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Application\Factory\PrintControllerFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'print' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/print[/:action]',
                    'defaults' => [
                        'controller' => Controller\PrintController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'abstract_factories' => [
            Factory\CommonControllerFactory::class,
        ],
        'factories' => [
            //Controller\IndexController::class => InvokableFactory::class,
            Controller\PrintController::class => PrintControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'abstract_factories' => [
            Factory\CommonPluginFactory::class,
        ],
        'factories' => [
            //Controller\Plugin\SafeDataPlugin::class =>  Controller\Factory\CommonPluginFactory::class,
        ],
        'aliases' => [
            'safedata' => Controller\Plugin\SafeDataPlugin::class,
            'password' => Controller\Plugin\PasswordPlugin::class,
            'sessionArray' => Controller\Plugin\SessionArrayPlugin::class,
        ],
    ], 
    'service_manager' => [
        'abstract_factories' => [
            Model\CommonModelTableFactory::class,
        ],
        'factories' => [
            AuthenticationService::class => InvokableFactory::class,
            AuthenticationServiceInterface::class => Auth\Factory\AuthenticationServiceFactory::class,
            \TCPDF::class => Factory\TCPDFFactory::class,
        ],
        'shared' => [
            \TCPDF::class => false
        ]
    ],
    'view_helpers' => [
        'invokables'=> [
            'flashmessage' => View\Helper\FlashMessage::class,
        ],
		'abstract_factories' => [
            Factory\CommonHelperFactory::class,
        ],
		'factories' => [
			View\Helper\GetresourceHelper::class 	=> Factory\GetresourceHelperFactory::class, // Or use your own factory
			View\Helper\MenuHelper::class 			=> Factory\GetresourceHelperFactory::class, // Or use your own factory
			View\Helper\ButtonHelper::class 		=> Factory\ButtonHelperFactory::class, // Or use your own factory
			View\Helper\BreadcrumbHelper::class 	=> Factory\BreadcrumbHelperFactory::class, 
			View\Helper\StatusHelper::class 		=> Factory\StatusHelperFactory::class, 
			View\Helper\NotificationHelper::class 	=> Factory\NotificationHelperFactory::class,
			View\Helper\TabsHelper::class 	        => Factory\BreadcrumbHelperFactory::class,
			View\Helper\AttachmentHelper::class 	=> Factory\AttachmentHelperFactory::class,
		],
		'aliases' => [
			'getresource_helper' 	=> View\Helper\GetresourceHelper::class,
			'getroute_helper' 		=> View\Helper\GetrouteHelper::class,
			'menu_helper' 			=> View\Helper\MenuHelper::class,
			'button' 				=> View\Helper\ButtonHelper::class,
			'breadcrumb' 			=> View\Helper\BreadcrumbHelper::class,
			'status' 				=> View\Helper\StatusHelper::class,
			'notification' 			=> View\Helper\NotificationHelper::class,
			'currency' 				=> View\Helper\CurrencyHelper::class,
			'decimal' 				=> View\Helper\DecimalHelper::class,
			'numtoWords' 			=> View\Helper\NumbertoWords::class,
			'sessioncontent' 		=> View\Helper\SessionarrayHelper::class,
			'tabs_helper' 		    => View\Helper\TabsHelper::class,
			'dateformat'            => View\Helper\DateFormatHelper::class,
			'attachment' 			=> View\Helper\AttachmentHelper::class,
            'canvas'                => View\Helper\CanvasHelper::class,
            'leaflet' 		        => View\Helper\LeafletHelper::class,
		]
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
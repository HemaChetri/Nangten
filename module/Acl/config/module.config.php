<?php
namespace Acl;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Session\SessionManager;

return [
		
    'router' => [
    		'routes' => [
				'setup' => [
					'type'    => Literal::class,
					'options' => [
							'route'    => '/acl/setup',
							//'constraints' => array(
							//		'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
							//		'id'     	 => '[a-zA-Z0-9_-]*',
							//		'role'     	 => '[a-zA-Z0-9_-]*',
							//),
							'defaults' => [
									'controller' => Controller\IndexController::class,
									'action'     => 'index',
							],
					],
				],
				'acl' => [
					'type'    =>  Segment::class,
					'options' => [
							'route'       => '/acl/acl[/:action][/:id][/:role][/:task]',
							'constraints' => [
									'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'     	 => '[a-zA-Z0-9_-]*',
									'role'     	 => '[a-zA-Z0-9_-]*',
									'task'     	 => '[a-zA-Z0-9_-]*',
							],        		
							'defaults' => [
									'controller' => Controller\AclController::class,
									'action'     => 'index',
							],
					],
					'may_terminate' => true,
					'child_routes'  => [
						'defaults' => [
							'type'      => 'Segment',
							'options'   => [
								'route' => '/[:controller[/:action][/:id][/:role][/:task]]',
								'constraints' => [
									'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*',
									'role'      => '[0-9]*',
									'task'      => '[0-9]*',
								],
								'defaults' => [
								],
							],
						],
						'paginator' => [
							'type' => Segment::class,
							'options' => [
								'route' => '/[page/:page]',
								'constraints' => [
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*', 
									'role'      => '[0-9]*', 
									'task'      => '[0-9]*', 
								],
								'defaults' => [
									'__NAMESPACE__' => 'Acl\Controller',
									'controller' => Controller\AclController::class,
								],
							],
						],
					],
				],
				'button' => [
					'type'    => Segment::class,
					'options' => [
							'route'       => '/acl/btn[/:action][/:id][/:role][/:task]',
							'constraints' => [
									'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'     	 => '[a-zA-Z0-9_-]*',
									'role'     	 => '[a-zA-Z0-9_-]*',
									'task'     	 => '[a-zA-Z0-9_-]*',
							],        		
							'defaults' => [
									'controller' => Controller\ButtonController::class,
									'action'     => 'index',
							],
					],
					'may_terminate' => true,
					'child_routes'  => [
						'defaults' => [
							'type'      => Segment::class,
							'options'   => [
								'route' => '/[:controller[/:action][/:id][/:role][/:task]]',
								'constraints' => [
									'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*',
									'role'      => '[0-9]*',
									'task'      => '[0-9]*',
								],
								'defaults' => [
								],
							],
						],
						'paginator' => [
							'type' => 'Segment',
							'options' => [
								'route' => '/[page/:page]',
								'constraints' => [
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*', 
									'role'      => '[0-9]*', 
									'task'      => '[0-9]*', 
								],
								'defaults' => [
									'__NAMESPACE__' => 'Acl\Controller',
									'controller' => Controller\ButtonController::class,
								],
							],
						],
					],
				],
				'upload' => [
					'type'    => Segment::class,
					'options' => [
							'route'    => '/up[/:action[/:id]]',
							'constraints' => [
									'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'     	 => '[a-zA-Z0-9_-]*',
							],
							'defaults' => [
									'controller' => Controller\UploadController::class,
									'action'        => 'upload-form',
							],
					],
				],
				'logs' => [
					'type'    => Segment::class,
					'options' => [
							'route'       => '/acl/logs[/:action][/:id][/:role][/:task]',
							'constraints' => [
									'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'     	 => '[a-zA-Z0-9_-]*',
									'role'     	 => '[a-zA-Z0-9_-]*',
									'task'     	 => '[a-zA-Z0-9_-]*',
							],        		
							'defaults' => [
									'controller' => Controller\LogsController::class,
									'action'     => 'index',
							],
					],
					'may_terminate' => true,
					'child_routes'  => [
						'defaults' => [
							'type'      => Segment::class,
							'options'   => [
								'route' => '/[:controller[/:action][/:id][/:role][/:task]]',
								'constraints' => [
									'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*',
									'role'      => '[0-9]*',
									'task'      => '[0-9]*',
								],
								'defaults' => [
								],
							],
						],
						'paginator' => [
							'type' => 'Segment',
							'options' => [
								'route' => '/[page/:page]',
								'constraints' => [
									'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
									'id'      => '[0-9]*', 
									'role'      => '[0-9]*', 
									'task'      => '[0-9]*', 
								],
								'defaults' => [
									'__NAMESPACE__' => 'Acl\Controller',
									'controller' => Controller\LogsController::class,
								],
							],
						],
					],
				],
    		],
    ],
	'view_manager' => [
    		'template_path_stack' => [
    				'acl' => __DIR__ . '/../view'
    		],
    		'display_exceptions' => true,
    ],
	
	// added for Acl   ###################################
	'controller_plugins' => [
	    'factories' => [
			Controller\Plugin\AclPlugin::class 			=> \Application\Controller\Factory\CommonPluginFactory::class,
			Controller\Plugin\PermissionPlugin::class 	=> \Application\Controller\Factory\CommonPluginFactory::class,
			Controller\Plugin\EmailPlugin::class 		=> \Application\Controller\Factory\CommonPluginFactory::class,
			Controller\Plugin\ApiPlugin::class 			=> \Application\Controller\Factory\CommonPluginFactory::class,
	     ],
        'aliases' => [
            'AclPlugin'        => Controller\Plugin\AclPlugin::class,
            'PermissionPlugin' => Controller\Plugin\PermissionPlugin::class,
			'EmailPlugin'      => Controller\Plugin\EmailPlugin::class,
			'ApiPlugin'        => Controller\Plugin\ApiPlugin::class,
        ],
	 ],
];

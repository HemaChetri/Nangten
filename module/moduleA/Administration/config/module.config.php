<?php
namespace Administration;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
	
    'router' => [
        'routes' => [      
            'adm' => [
            		'type'    => Segment::class,
            		'options' => [ 
            				'route'    => '/adm[/:action[/:id]]',
            				'constraints' => [
            						'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            						'id'     	 => '[a-zA-Z0-9_-]*',
            				],
            				'defaults' => [
            						'controller' => Controller\IndexController::class,
            						'action'   => 'index',
            				],
            		],
            ],
			'user' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/adm/user[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\UserController::class,
								'action'     => 'users',
						],
				],
				'may_terminate' => true,
				'child_routes'  => [
					'defaults' => [
						'type'      => Segment::class,
						'options'   => [
							'route' => '/[:controller[/:action][/:id]]',
							'constraints' => [
								'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'      => '[0-9]*',
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
							],
							'defaults' => [
								'__NAMESPACE__' => 'Administration\Controller',
								'controller' => Controller\UserController::class,
							],
						],
					],
				],
			],
			'setmaster' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/adm/setmaster[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\MasterController::class,
								'action'     => 'district',
						],
				],
				'may_terminate' => true,
				'child_routes'  => [
					'defaults' => [
						'type'      => Segment::class,
						'options'   => [
							'route' => '/[:controller[/:action][/:id]]',
							'constraints' => [
								'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'      => '[0-9]*',
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
							],
							'defaults' => [
								'__NAMESPACE__' => 'Administration\Controller',
								'controller' => Controller\MasterController::class,
							],
						],
					],
				],
			],
			'lhakhang' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/adm/lhakhang[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\LhakhangController::class,
								'action'     => 'lhakhang',
						],
				],
				'may_terminate' => true,
				'child_routes'  => [
					'defaults' => [
						'type'      => Segment::class,
						'options'   => [
							'route' => '/[:controller[/:action][/:id]]',
							'constraints' => [
								'controller'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'  => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'      => '[0-9]*',
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
							],
							'defaults' => [
								'__NAMESPACE__' => 'Administration\Controller',
								'controller' => Controller\LhakhangController::class,
							],
						],
					],
				],
			],
		],
	],	
	'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view/',
        ],
		'display_exceptions' => true,
    ],
];

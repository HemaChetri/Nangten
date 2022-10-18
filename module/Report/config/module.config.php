<?php
namespace Report;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [      
            'report' => [
				'type'    => Segment::class,
				'options' => [ 
					'route'    => '/report[/:action[/:id]]',
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
			'ngreport' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/report/ngreport[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\NgreportController::class,
								'action'     => 'nangtenreport',
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
								'__NAMESPACE__' => 'Report\Controller',
								'controller' => Controller\NgreportController::class,
							],
						],
					],
				],
			],
			'awpbreport' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/report/awpbreport[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\AwpbreportController::class,
								'action'     => 'workplanreport',
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
								'__NAMESPACE__' => 'Report\Controller',
								'controller' => Controller\AwpbreportController::class,
							],
						],
					],
				],
			],
			'implreport' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/report/implreport[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\ImplreportController::class,
								'action'     => 'beneficiaryreport',
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
								'__NAMESPACE__' => 'Report\Controller',
								'controller' => Controller\ImplreportController::class,
							],
						],
					],
				],
			],
			'expensereport' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/report/expensereport[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\ExpensereportController::class,
								'action'     => 'expensereport',
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
								'__NAMESPACE__' => 'Report\Controller',
								'controller' => Controller\ExpensereportController::class,
							],
						],
					],
				],
			],
			'resultreport' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/report/resultreport[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\ResultreportController::class,
								'action'     => 'resultreport',
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
								'__NAMESPACE__' => 'Report\Controller',
								'controller' => Controller\ResultreportController::class,
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

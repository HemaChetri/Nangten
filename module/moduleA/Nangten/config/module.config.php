<?php
namespace Nangten;

use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'ng' => [
                'type'    => Segment::class,
                'options' => [ 
                        'route'    => '/ng[/:action[/:id]]',
                        'constraints' => [
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'     	 => '[a-zA-Z0-9_-]*',
                        ],
                        'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action'   => 'index',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\IndexController::class,
							],
						],
					],
				],
            ],
            'ngmaster' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/ngmaster[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\MasterController::class,
								'action'     => 'group',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\MasterController::class,
							],
						],
					],
				],
			],
            'statue' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/statue[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\StatueController::class,
								'action'     => 'statue',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\StatueController::class,
							],
						],
					],
				],
			],
			'thanka' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/thanka[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\ThankaController::class,
								'action'     => 'thanka',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\ThankaController::class,
							],
						],
					],
				],
			],
			'ritual' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/ritual[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\RitualController::class,
								'action'     => 'ritual',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\RitualController::class,
							],
						],
					],
				],
			],
			'pecha' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/pecha[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\PechaController::class,
								'action'     => 'pecha',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\PechaController::class,
							],
						],
					],
				],
			],
			'debri' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/debri[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\DebriController::class,
								'action'     => 'debri',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\DebriController::class,
							],
						],
					],
				],
			],
			'stupa' => [
				'type'    => Segment::class,
				'options' => [
						'route'       => '/ng/stupa[/:action][/:id]',
						'constraints' => [
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'     	 => '[a-zA-Z0-9_-]*',
						],        		
						'defaults' => [
								'controller' => Controller\StupaController::class,
								'action'     => 'stupa',
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
								'__NAMESPACE__' => 'Nangten\Controller',
								'controller' => Controller\StupaController::class,
							],
						],
					],
				],
			],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'album' => __DIR__ . '/../view',
        ],
    ],
];
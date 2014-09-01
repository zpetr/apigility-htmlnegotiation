<?php
return array(
    'view_manager' => array(
        'template_map' => array(
            'zf/rest/get'			  	=> __DIR__ . '/../view/zf/rest/get.phtml',
        	'zf/rest/get-list'		  	=> __DIR__ . '/../view/zf/rest/get_list.phtml',
        	'htmlnegotiation/layout'	=> __DIR__ . '/../view/layout.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
	'zf-content-negotiation' => array(
		'selectors' => array(
			'HTML-HalJson' => array(
				'ZF\\Hal\\View\\HalJsonModel' => array(
					0 => 'application/json',
					1 => 'application/*+json',
				),
				'HtmlNegotiation\\View\\HtmlModel' => array(
					0 => 'text/html',
					1 => 'text/*+html',
				),
			),
		),
	),
);

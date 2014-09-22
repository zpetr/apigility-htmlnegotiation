<?php
namespace zPetr\HtmlNegotiation;

use Zend\Mvc\MvcEvent;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }
    
    /**
     * Retrieve Service Manager configuration
     *
     * Defines HtmlNegotiation\HtmlStrategy service factory.
     *
     * @return array
     */
    public function getServiceConfig()
    {
    	return array('factories' => array(
    			'zPetr\HtmlNegotiation\HtmlRenderer' => function ($services) {
    				$helpers            = $services->get('ViewHelperManager');
    				$apiProblemRenderer = $services->get('ZF\ApiProblem\ApiProblemRenderer');
    				$config             = $services->get('Config');
    
    				$renderer = new View\HtmlRenderer($apiProblemRenderer,$config);
    				$renderer->setHelperPluginManager($helpers);
    
    				return $renderer;
    			},
    			'zPetr\HtmlNegotiation\HtmlStrategy' => function ($services) {
    				$renderer = $services->get('zPetr\HtmlNegotiation\HtmlRenderer');
    				return new View\HtmlStrategy($renderer);
    			},
    	));
    }
    
    /**
     * Listener for bootstrap event
     *
     * Attaches a render event.
     *
     * @param  \Zend\Mvc\MvcEvent $e
     */
    public function onBootstrap($e)
    {
    	$app      = $e->getTarget();
    	$services = $app->getServiceManager();
    	$events   = $app->getEventManager();
    	$events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 190);
    }
    
    /**
     * Listener for the render event
     *
     * Attaches a rendering/response strategy to the View.
     *
     * @param  \Zend\Mvc\MvcEvent $e
     */
    public function onRender($e)
    {
    	$result = $e->getResult();
    	if (!$result instanceof View\HtmlModel) {
    		return;
    	}
    
    	$app                 = $e->getTarget();
    	$services            = $app->getServiceManager();
    	$view                = $services->get('View');
    	$events              = $view->getEventManager();

    	$events->attach($services->get('zPetr\HtmlNegotiation\HtmlStrategy'), 190);
    }
}

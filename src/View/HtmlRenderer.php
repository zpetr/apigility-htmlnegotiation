<?php
namespace HtmlNegotiation\View;

use Zend\View\HelperPluginManager;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\ViewEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\View\ApiProblemModel;
use ZF\ApiProblem\View\ApiProblemRenderer;
use Zend\View\Resolver;
use Zend\View\Model\ViewModel;

/**
 * Handles rendering of the following:
 *
 * - API-Problem
 * - HAL collections
 * - HAL resources
 */
class HtmlRenderer extends PhpRenderer
{
    /**
     * @var ApiProblemRenderer
     */
    protected $apiProblemRenderer;

    /**
     * @var HelperPluginManager
     */
    protected $helpers;

    /**
     * @var ViewEvent
     */
    protected $viewEvent;
    
    /**
     * @var Config
     */
    protected $config;
    
    private $_entity,
    		$_collection,
    		$_payload;

    /**
     * @param ApiProblemRenderer $apiProblemRenderer
     */
    public function __construct(ApiProblemRenderer $apiProblemRenderer,$config)
    {
        $this->apiProblemRenderer = $apiProblemRenderer;
        $this->config = $config;
    }

    /**
     * Set helper plugin manager instance.
     *
     * Also ensures that the 'Hal' helper is present.
     *
     * @param  HelperPluginManager $helpers
     */
    public function setHelperPluginManager($helpers)
    {
    	if (!$helpers->has('Hal')) {
    		$this->injectHalHelper($helpers);
    	}
    	$this->helpers = $helpers;
    }

    /**
     * @param  ViewEvent $event
     * @return self
     */
    public function setViewEvent(ViewEvent $event)
    {
        $this->viewEvent = $event;
        return $this;
    }
    
    /**
     * Lazy-loads a helper plugin manager if none available.
     *
     * @return HelperPluginManager
     */
    public function getHelperPluginManager()
    {
    	if (!$this->helpers instanceof HelperPluginManager) {
    		$this->setHelperPluginManager(new HelperPluginManager());
    	}
    	return $this->helpers;
    }

    /**
     * @return ViewEvent
     */
    public function getViewEvent()
    {
        return $this->viewEvent;
    }
    
    protected function setPayload($payload)
    {
    	$this->_payload = $payload;
    	return $this;
    }
    
    public function getPayload()
    {
    	return $this->_payload;
    }
    
    protected function setEntity($entity)
    {
    	$this->_entity = $entity;
    	return $this;
    }
    
    public function getEntity()
    {
    	return $this->_entity;
    }
    
    protected function setCollection($collection)
    {
    	$this->_collection = $collection;
    	return $this;
    }
    
    public function getCollection()
    {
    	return $this->_collection;
    }

    /**
     * Render a view model
     *
     * If the view model is a HtmlRenderer, determines if it represents
     * a Collection or Entity, and, if so, creates a custom
     * representation appropriate to the type.
     *
     * If not, it passes control to the parent to render.
     *
     * @param  mixed $nameOrModel
     * @param  mixed $values
     * @return string
     */
    public function render($nameOrModel, $values = null)
    {
    	if (!$nameOrModel instanceof HtmlModel) {
            return parent::render($nameOrModel, $values);
        }

        $resolver = new Resolver\AggregateResolver();
        
        $this->setResolver($resolver);
        $resolverMap = $this->config['view_manager']['template_map'];
                
        $entityReflection = ($nameOrModel->isEntity()) ? new \ReflectionClass($nameOrModel->getPayload()->entity):new \ReflectionClass($nameOrModel->getPayload()->getCollection());
        $entityDir = dirname($entityReflection->getFileName());
        $viewDir = $entityDir.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR;
        if (!isset($payload) && $nameOrModel->isEntity()) {
        	$helper  = $this->helpers->get('Hal');
        	$payload = $helper->renderEntity($nameOrModel->getPayload());
        	
        	$this->setPayload($payload);
        	$this->setEntity($nameOrModel->getPayload()->entity);
        	if(file_exists($viewDir) && file_exists($viewDir.'get.phtml')){
        		$resolverMap['zf/rest/get'] = $viewDir.'get.phtml';	 
        	}
        }
        
        if (!isset($payload) && $nameOrModel->isCollection()) {
        	$helper  = $this->helpers->get('Hal');
        	$payload = $helper->renderCollection($nameOrModel->getPayload());
        
        	if ($payload instanceof ApiProblem) {
        		return $this->renderApiProblem($payload);
        	}
        	
        	
        	$this->setPayload($payload);
        	$this->setCollection($nameOrModel->getPayload()->getCollection());
        	if(file_exists($viewDir) && file_exists($viewDir.'get_list.phtml')){
        		$resolverMap['zf/rest/get-list'] = $viewDir.'get_list.phtml';
        	}
        }
        
        $map = new Resolver\TemplateMapResolver($resolverMap);
        $resolver->attach($map);
        $content = (isset($payload)) ? parent::render($nameOrModel, $payload):parent::render($nameOrModel, $values);
        
        // Layout
        $view = new ViewModel();
        
        $rendererLayout = new PhpRenderer();
        $resolverLayout = new Resolver\AggregateResolver();
        
        $rendererLayout->setResolver($resolverLayout);
        
        $map = new Resolver\TemplateMapResolver($this->config['view_manager']['template_map']);
        
        $resolverLayout->attach($map);
        $view->setTemplate("htmlnegotiation/layout");
        
        $view->setVariable('content',$content);
        
        return $rendererLayout->render($view);
    }

    /**
     * Inject the helper manager with the Hal helper
     *
     * @param  HelperPluginManager $helpers
     */

    protected function injectHalHelper(HelperPluginManager $helpers)
    {
        $helper = new HalHelper();
        $helper->setView($this);
        $helper->setServerUrlHelper($helpers->get('ServerUrl'));
        $helper->setUrlHelper($helpers->get('Url'));
        $helpers->setService('Hal', $helper);
    }


    /**
     * Render an API-Problem result
     *
     * Creates an ApiProblemModel with the provided ApiProblem, and passes it
     * on to the composed ApiProblemRenderer to render.
     *
     * If a ViewEvent is composed, it passes the ApiProblemModel to it so that
     * the ApiProblemStrategy can be invoked when populating the response.
     *
     * @param  ApiProblem $problem
     * @return string
     */
    protected function renderApiProblem(ApiProblem $problem)
    {
        $model = new ApiProblemModel($problem);
        $event = $this->getViewEvent();
        if ($event) {
            $event->setModel($model);
        }
        return $this->apiProblemRenderer->render($model);
    }
    
    public function getValue($value)
    {
    	$html = '';
    	if(is_array($value)){
    		$html .= "<table class=\"list item\">";
    		foreach($value as $k=>$v){
    			$html .= "<tr><td>".$k."</td><td>".$this->getValue($v)."</td></tr>";
    		}
    		$html .= "</table>";
    	} else
    		$html .= $value;
    	return $html;
    }
}

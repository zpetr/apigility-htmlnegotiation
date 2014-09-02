<?php
namespace HtmlNegotiation\View;

use Zend\View\Strategy\PhpRendererStrategy;
use Zend\View\ViewEvent;

/**
 * Extension of the HTML strategy to handle the HtmlModel and provide
 * a Content-Type header appropriate to the response it describes.
 *
 * This will give the following content types:
 *
 * - text/hal+html for a result that contains HAL-compliant links
 * - text/html for all other responses
 */
class HtmlStrategy extends PhpRendererStrategy
{
    protected $contentType = 'text/html';

    public function __construct(HtmlRenderer $renderer)
    {
    	$this->renderer = $renderer;
    }

    /**
     * Detect if we should use the HalJsonRenderer based on model type.
     *
     * @param  ViewEvent $e
     * @return null|HalJsonRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (!$model instanceof HtmlModel) {
            // unrecognized model; do nothing
            return;
        }

        // HtmlModel found
        $this->renderer->setViewEvent($e);
        return $this->renderer;
    }

    /**
     * Inject the response
     *
     * Injects the response with the rendered content, and sets the content
     * type based on the detection that occurred during renderer selection.
     *
     * @param  ViewEvent $e
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result   = $e->getResult();
        if (!is_string($result)) {
            // We don't have a string
            return;
        }

        $model       = $e->getModel();
        $contentType = $this->contentType;
        $response    = $e->getResponse();

        if ($model instanceof HtmlModel
            && ($model->isCollection() || $model->isEntity())
        ) {
            $contentType = 'text/hal+html';
        }

        // Populate response
        $response->setContent($result);
        $headers = $response->getHeaders();
        $headers->addHeaderLine('content-type', $contentType);
    }
}

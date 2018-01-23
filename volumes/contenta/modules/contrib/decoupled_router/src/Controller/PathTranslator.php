<?php

namespace Drupal\decoupled_router\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\decoupled_router\PathTranslatorEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller that receives the path to inspect.
 */
class PathTranslator extends ControllerBase {

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * EventInfoController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, HttpKernelInterface $http_kernel) {
    $this->eventDispatcher = $event_dispatcher;
    $this->httpKernel = $http_kernel;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('http_kernel')
    );
  }

  /**
   * Responds with all the information about the path.
   */
  public function translate(Request $request) {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to translate empty path. Please send a ?path query string parameter with your request.');
    }
    // Now that we have the path, let's fire an event for translations.
    $event = new PathTranslatorEvent(
      $this->httpKernel,
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      sprintf('/%s', ltrim($path, '/'))
    );
    // Event subscribers are in charge of setting the appropriate response,
    // including cacheability metadata.
    $this->eventDispatcher->dispatch(PathTranslatorEvent::TRANSLATE, $event);
    $response = $event->getResponse();
    $response = $response ?: CacheableResponse::create(
      Json::encode([
        'message' => $this->t('Unable to resolve path @path.', ['@path' => $path]),
        'details' => $this->t('None of the available methods were able to find a match for this path.'),
      ]),
      404,
      [
        'Content-Type' => 'application/json',
      ]
    );
    $response->headers->add(['Content-Type' => 'application/json']);
    $response->getCacheableMetadata()->addCacheContexts(['url.query_args:path']);
    return $response;
  }

}

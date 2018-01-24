<?php

namespace Drupal\decoupled_router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Path translation event.
 */
class PathTranslatorEvent extends GetResponseEvent {

  const TRANSLATE = 'decoupled_router.translate_path';

  /**
   * The path that needs translation.
   *
   * @var string
   */
  protected $path;

  /**
   * PathTranslatorEvent constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The kernel.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int $requestType
   *   The type of request: master or subrequest.
   * @param string $path
   *   The path to process.
   */
  public function __construct(HttpKernelInterface $kernel, Request $request, $requestType, $path) {
    parent::__construct($kernel, $request, $requestType);
    $this->path = $path;
  }

  /**
   * Get the path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set the path.
   *
   * @param string $path
   *   The path.
   */
  public function setPath($path) {
    $this->path = $path;
  }

}

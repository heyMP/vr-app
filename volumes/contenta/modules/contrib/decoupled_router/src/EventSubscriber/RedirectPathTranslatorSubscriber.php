<?php

namespace Drupal\decoupled_router\EventSubscriber;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\decoupled_router\PathTranslatorEvent;

/**
 * Event subscriber that processes a path translation with the redirect info.
 */
class RedirectPathTranslatorSubscriber extends RouterPathTranslatorSubscriber {

  /**
   * {@inheritdoc}
   */
  public function onPathTranslation(PathTranslatorEvent $event) {
    if (!$this->moduleHandler->moduleExists('redirect')) {
      return;
    }
    // Find the redirected path. Bear in mind that we need to go through several
    // redirection levels before handing off to the route translator.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $redirect_storage = $entity_type_manager->getStorage('redirect');
    $destination = $event->getPath();
    $traced_urls = [];
    while (TRUE) {
      $destination = $this->cleanSubdirInPath($destination, $event->getRequest());
      // Find if there is a redirect for this path.
      $results = $redirect_storage
        ->getQuery()
        // Redirects are stored without the leading slash :-(.
        ->condition('redirect_source.path', ltrim($destination, '/'))
        ->execute();
      $rid = reset($results);
      if (!$rid) {
        break;
      }
      /** @var \Drupal\redirect\Entity\Redirect $redirect */
      $redirect = $redirect_storage->load($rid);
      $uri = $redirect->get('redirect_redirect')->uri;
      $url = Url::fromUri($uri)->toString(TRUE);
      $destination = $url->getGeneratedUrl();

      // Detect infinite loops and break if there is one.
      $infinite_loop = in_array($destination, array_map(function (GeneratedUrl $url) {
        return $url->getGeneratedUrl();
      }, $traced_urls));
      // Accumulate all the URLs we go through to add the necessary cacheability
      // metadata at the end.
      $traced_urls[] = $url;
      if ($infinite_loop) {
        break;
      }
    }
    // At this point we should be pointing to a system route or path alias.
    $event->setPath($destination);

    // Now call the route level.
    parent::onPathTranslation($event);

    // If there is a response object, add the cacheability metadata necessary
    // for the traced URLs.
    /** @var \Drupal\Core\Cache\CacheableResponse $response */
    if ($response = $event->getResponse()) {
      array_walk($traced_urls, function ($traced_url) use ($response) {
        $response->addCacheableDependency($traced_url);
      });
    }
  }

}

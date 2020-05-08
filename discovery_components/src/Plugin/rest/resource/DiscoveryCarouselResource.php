<?php

namespace Drupal\discovery_components\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * It gives us a resource to get the results of a carousel by REST.
 *
 * @RestResource(
 *   id = "discovery_carousel",
 *   label = @Translation("Discovery Carousel"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/discovery_carousel",
 *   }
 * )
 */
class DiscoveryCarouselResource extends ResourceBase {

  /**
   * {@inheritdoc}
   *
   * Endpoint example
   * /discovery_carousel?carousel=98&content=4797&start=0&langcode=es&region=ec&t=123
   *
   */
  public function get() {
    // Get carousel data.
    $component_manager = \Drupal::service('plugin.manager.component');
    $nodo_array = $component_manager->createInstance('carousel')->getCarouselRestResponse();

    // Add cache to response.
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheContexts(['url.query_args:t']);
    $response = new ResourceResponse($nodo_array);
    $response->addCacheableDependency($cache_metadata);
    $response->addCacheableDependency($nodo_array);
    return $response;
  }

}

<?php

/**
 * @file
 * Definition of Drupal\killbill\Plugin\rest\resource\CatalogResource.
 */

namespace Drupal\killbill\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "killbill_catalog_resource",
 *   label = @Translation("Catalog resource"),
 *   uri_paths = {
 *     "canonical" = "/api/killbill/catalog",
 *   }
 * )
 *
 */
class CatalogResource extends ResourceBase {

  /**
   * Get
   *
   * @return ResourceResponse
   */
  public function get() {
    $catalog = array(
      'product_1' => array(
        'price' => '11'
      ),
      'product_2' => array(
        'price' => '22'
      ),
    );
    return new ResourceResponse($catalog);
  }

}

<?php
/**
 * Created by PhpStorm.
 * User: e0ipso
 * Date: 04/09/2017
 * Time: 21:53
 */

namespace Drupal\consumer_image_styles\Normalizer\Value;


use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;

class ImageVariantItemNormalizerValue extends FieldItemNormalizerValue {

  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    return $this->rasterizeValueRecursive($this->raw);
  }

}

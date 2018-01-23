<?php

namespace Drupal\consumer_image_styles\Normalizer\Value;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\ValueExtractorInterface;

class ImageNormalizerValue implements ValueExtractorInterface, RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * @var \Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue
   */
  protected $subject;

  /**
   * The values.
   *
   * @param array
   */
  protected $variants;

  /**
   * ImageNormalizerValue constructor.
   */
  public function __construct(ValueExtractorInterface $variants, EntityNormalizerValue $subject) {
    $this->subject = $subject;
    $this->variants = $variants;
    $this->addCacheableDependency($variants);
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    $rasterized = $this->subject->rasterizeValue();
    $derivatives = $this->variants->rasterizeValue();
    if (empty($derivatives)) {
      return $rasterized;
    }
    $rasterized['meta'] = empty($rasterized['meta'])
      ? []
      : $rasterized['meta'];
    $rasterized['meta']['derivatives'] = $derivatives;
    return $rasterized;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeIncludes() {
    return $this->subject->rasterizeIncludes();
  }

  /**
   * Gets the values.
   *
   * @return mixed
   *   The values.
   */
  public function getValues() {
    return $this->subject->getValues();
  }

  /**
   * Gets a flattened list of includes in all the chain.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue[]
   *   The array of included relationships.
   */
  public function getIncludes() {
    return $this->subject->getIncludes();
  }

}

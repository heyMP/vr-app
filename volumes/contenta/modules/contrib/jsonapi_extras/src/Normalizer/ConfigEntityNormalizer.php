<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\jsonapi\Normalizer\ConfigEntityNormalizer as JsonapiConfigEntityNormalizer;

/**
 * Override ConfigEntityNormalizer to prepare input.
 */
class ConfigEntityNormalizer extends JsonapiConfigEntityNormalizer {

  use EntityNormalizerTrait;

}

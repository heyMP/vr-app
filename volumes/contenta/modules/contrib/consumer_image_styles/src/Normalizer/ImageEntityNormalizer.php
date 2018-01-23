<?php

namespace Drupal\consumer_image_styles\Normalizer;

use Drupal\consumer_image_styles\Normalizer\Value\ImageVariantItemNormalizerValue;
use Drupal\consumer_image_styles\Normalizer\Value\ImageNormalizerValue;
use Drupal\consumers\Negotiator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Normalizer\ContentEntityNormalizer;
use Drupal\jsonapi\Normalizer\Value\NullFieldNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

class ImageEntityNormalizer extends ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = File::class;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = ['api_json'];

  /**
   * The link manager.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\consumers\Negotiator
   */
  protected $consumerNegotiator;

  /**
   * Constructs an EntityNormalizer object.
   *
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The link manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\consumers\Negotiator $consumer_negotiator
   *   The consumer negotiator.
   */
  public function __construct(LinkManager $link_manager, ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager, Negotiator $consumer_negotiator) {
    $this->linkManager = $link_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->consumerNegotiator = $consumer_negotiator;
    parent::__construct($link_manager, $resource_type_repository, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // It is very tricky to detect if a file entity is an image or not. This is
    // typically done using a special field type to point to this entity.
    // However we don't have access to that in here. Besides we want this to
    // apply when requesting a listing of file entities as well, not only via
    // includes. For all this we'll do string matching against the mimetype.
    return parent::supportsNormalization($data, $format) &&
      strpos($data->get('filemime')->value, 'image/') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    // We do not need to do anything special about denormalization. Passing here
    // will have the serializer use the normal content entity normalizer.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $file_entity_values = parent::normalize($entity, $format, $context);
    $variants = $this->buildVariantValues($entity, $context);

    return new ImageNormalizerValue($variants, $file_entity_values);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // This should never be called.
    throw new \Exception('Unsupported denormalizer.');
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $context
   *
   * @return \Drupal\jsonapi\Normalizer\Value\ValueExtractorInterface
   */
  protected function buildVariantValues(EntityInterface $entity, array $context = []) {
    $request = empty($context['request']) ? NULL : $context['request'];
    $consumer = $this->consumerNegotiator->negotiateFromRequest($request);
    $output = new NullFieldNormalizerValue();
    if ($consumer) {
      // Get the image styles from the consumer.
      $image_style_ids = array_map(function ($field_value) {
        return $field_value['target_id'];
      }, $consumer->get('image_styles')->getValue());
      $uri = $entity->get('uri')->value;
      $image_style_storage = $this->entityTypeManager->getStorage('image_style');
      $urls = array_map(function ($image_style_id) use ($image_style_storage, $uri) {
        /** @var \Drupal\image\Entity\ImageStyle $image_style */
        $image_style = $image_style_storage->load($image_style_id);
        return file_create_url($image_style->buildUrl($uri));
      }, $image_style_ids);
      $value = array_combine($image_style_ids, $urls);
      $output = new ImageVariantItemNormalizerValue($value);
      $output->addCacheableDependency($consumer);
    }

    return $output;
  }

}

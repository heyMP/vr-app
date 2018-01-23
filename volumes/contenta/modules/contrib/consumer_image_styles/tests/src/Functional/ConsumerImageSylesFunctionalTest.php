<?php

namespace Drupal\Tests\consumer_image_styles\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\consumers\Entity\Consumer;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @group consumer_image_styles
 */
class ConsumerImageSylesFunctionalTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use ImageFieldCreationTrait;

  public static $modules = [
    'consumers',
    'consumer_image_styles',
    'jsonapi',
    'serialization',
    'node',
    'image',
  ];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The name of the image field.
   *
   * @var string
   */
  protected $imageFieldName;

  /**
   * The content type to attach the fields to test.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

  /**
   * @var \Drupal\consumers\Entity\Consumer
   */
  protected $consumer;

  protected function setUp() {
    parent::setUp();
    $this->contentType = $this->createContentType();
    $this->imageFieldName = $this->getRandomGenerator()->word(8);
    $this->user = $this->drupalCreateUser();
    $this->createImageField($this->imageFieldName, $this->contentType->id());
    drupal_flush_all_caches();
  }

  /**
   * Creates default content to test the API.
   *
   * @param int $num_nodes
   *   Number of articles to create.
   */
  protected function createDefaultContent($num_nodes) {
    $random = $this->getRandomGenerator();
    for ($created_nodes = 0; $created_nodes < $num_nodes; $created_nodes++) {
      $file = File::create([
        'uri' => 'public://' . $random->name() . '.png',
      ]);
      $file->setPermanent();
      $file->save();
      $this->files[] = $file;
      $values = [
        'uid' => ['target_id' => $this->user->id()],
        'type' => $this->contentType->id(),
      ];
      $values[$this->imageFieldName] = [
        'target_id' => $file->id(),
        'alt' => 'alt text'
      ];
      $node = $this->createNode($values);
      $this->nodes[] = $node;
    }
    // Create the image styles.
    $image_styles = array_map(function ($name) {
      $image_style = ImageStyle::create(['name' => $name]);
      $image_style->save();
      return $image_style;
    }, ['foo', 'bar']);

    // Create the consumer.
    $this->consumer = Consumer::create([
      'owner_id' => '',
      'label' => $this->getRandomGenerator()->name(),
      'image_styles' => array_map(function (ImageStyle $image_style) {
        return ['target_id' => $image_style->id()];
      }, $image_styles),
    ]);
    $this->consumer->save();
  }

  /**
   * Test the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(1);

    // 1. Check the request for the image directly.
    $path = sprintf('/jsonapi/file/file/%s', $this->files[0]->uuid());
    $query = ['_consumer_id' => $this->consumer->uuid()];
    $raw = $this->drupalGet($path, ['query' => $query]);
    $output = Json::decode($raw);
    $this->assertSession()->statusCodeEquals(200);
    $derivatives = $output['data']['meta']['derivatives'];
    $this->assertContains('/files/styles/foo/public/', $derivatives['foo']);
    $this->assertContains('/files/styles/bar/public/', $derivatives['bar']);
    $this->assertContains('itok=', $derivatives['foo']);
    $this->assertContains('itok=', $derivatives['bar']);

    // 2. Check the request via the node.
    $path = sprintf(
      '/jsonapi/node/%s/%s',
      $this->contentType->id(),
      $this->nodes[0]->uuid()
    );
    $query = [
      '_consumer_id' => $this->consumer->uuid(),
      'include' => $this->imageFieldName,
    ];
    $raw = $this->drupalGet($path, ['query' => $query]);
    $output = Json::decode($raw);
    $this->assertSession()->statusCodeEquals(200);
    $derivatives = $output['included'][0]['meta']['derivatives'];
    $this->assertContains(file_create_url('public://styles/foo/public/'), $derivatives['foo']);
    $this->assertContains(file_create_url('public://styles/bar/public/'), $derivatives['bar']);
    $this->assertContains('itok=', $derivatives['foo']);
    $this->assertContains('itok=', $derivatives['bar']);

    // 3. Check the request for the image directly without consumer.
    $path = sprintf('/jsonapi/file/file/%s',$this->files[0]->uuid());
    $raw = $this->drupalGet($path);
    $output = Json::decode($raw);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue(empty($output['data']['meta']['derivatives']));
  }

}

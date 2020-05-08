<?php

namespace Drupal\discovery_components\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\discovery_helper\Utility\DiscoveryContent;
use Drupal\discovery_helper\Utility\DiscoveryRegions;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\discovery_components\MigrateComponentBatch;

/**
 * Defines a base component implementation that most component plugins will extend.
 */
abstract class ComponentBase extends PluginBase implements ComponentPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Entity type manager.
   *
   * @var \Drupal\core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The langcode parameter query.
   */
  protected $langcode;

  /**
   * The region parameter query.
   */
  protected $region;

  /**
   * The current request.
   */
  protected $currentRequest;

  /**
   * Creates a ComponentBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->serializer = $serializer;
    $this->currentRequest = $this->requestStack->getCurrentRequest();
    // Get queries parameters.
    $this->langcode = $this->requestStack->getCurrentRequest()->query->get('langcode');
    $this->region = $this->requestStack->getCurrentRequest()->query->get('region');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('serializer')
    );
  }

  /**
   * Get paragraph/component default data.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   The default data of current component($paragraph).
   */
  protected function getDefaultData(ParagraphInterface $entity) {
    // Convert $entity into array with default data of entity.
    return $this->serializer->normalize($entity, 'array');
  }

  /**
   * See \Drupal\discovery_helper\Utility\DiscoveryContent::content()
   */
  protected function getContent($dest, $langcode, $estilo, $region = '', $poster = FALSE, $playlist = FALSE) {
    return DiscoveryContent::content($dest, $langcode, $estilo, $region, $poster, $playlist);
  }

  /**
   * See \Drupal\discovery_helper\Utility\DiscoveryRegions::isInRegion()
   */
  protected function isInRegion(Node $node, $region) {
    return DiscoveryRegions::isInRegion($node, $region);
  }

  /**
   * Migrate a component/paragraph.
   *
   * @param array $fields.
   *   The fields defiend in yml file for each component/paragraph.
   * @param array $nodes
   *   Array with each nodes entity translated.
   */
  public function processMigrate(array $fields, array $nodes) {
    if (!isset($fields['field_destination']) || empty($fields['field_destination'])) {
      throw new \Exception($this->t('The field_destination is required for migrate the component.'));
    }
    $this->migrateComponent($fields, $nodes);
  }

  /**
   * Start the migrate process.
   *
   * @param string $node_type.
   *   Migrating components for node of this $node_type.
   */
  public function migrate(string $node_type) {
    // Get migration file.
    $migration_file = $this->getMigrationFile();
    if (isset($migration_file[$node_type])) {
      // Execute migrate.
      $this->processContent($node_type, $migration_file[$node_type]);
    }
    else {
      throw new \Exception($this->t('There are no defined migrations of :component for node :node in :path.', [
        ':component' => $this->getPluginId(),
        ':node' => $node_type,
        ':path' => $this->getMigrationFilePath(),
       ]));
    }
  }

  /**
   * Get migration file path for current component.
   *
   * @return string
   *   The migration file path.
   */
  public function getMigrationFilePath() {
    $this_module = drupal_get_path('module', 'discovery_components');
    // Get the migration file path.
    $migration_file = "$this_module/components_migration/{$this->getPluginId()}.yml";
    return $migration_file;
  }

  /**
   * Get the migration file content decoded in array format.
   *
   * @return array
   *   The migration file content.
   */
  public function getMigrationFile() {
    // Get migration file path.
    $file_path = $this->getMigrationFilePath();
    // Decode migration .yml file as array.
    if ($migration_file = file_get_contents($file_path)) {
      $file_decoded = Yaml::decode($migration_file);
      return $file_decoded;
    }
    throw new \Exception($this->t('Migration file :file not found in :path', [
      ':file' => "{$this->getPluginId()}.yml",
      ':path' => $file_path,
    ]));
  }

  /**
   * Migrating components/paragraphs in batch operations.
   *
   * @param string $node_type.
   *   Migrating components for node of this $node_type.
   * @param array $old_components
   *   The old components/paragraphs to migrate.
   */
  private function processContent(string $node_type, array $old_components) {
    // Get nids of $node_type.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $node_type)
      ->condition('status', 1)

      //->range(0, 5) // @TODO Remove range() for prod when all its OK!!.
      ->execute();

    if ($nids) {
      // Build batch operations, one by node.
      $operations = [];
      foreach ($nids as $nid) {
        $data = [
          'nid' => $nid,
          'old_components' => $old_components,
          'plugin_id' => $this->getPluginId(),
        ];
        $operations[] = [
          [MigrateComponentBatch::class, 'migrateComponentBatchProcess'],
          [$data],
        ];
      }
      $batch = [
        'title' => $this->t('Migrating new components'),
        'operations' => $operations,
        'init_message' => $this->t('Starting migration the new components for content.'),
        'progress_message' => $this->t('Migrating @current contents of @total. Estimated time: @estimate.'),
        'finished' => [MigrateComponentBatch::class, 'finish'],
      ];
      batch_set($batch);
    }
    else {
      throw new \Exception($this->t('There is not nodes for migrate of type :node_type', [
        ':node_type' => $node_type,
       ]));
    }
  }

  /**
   * Get the nids and vids of nodes entities.
   *
   * @param array $nodes
   *   Array of nodes object implementing \Drupal\node\NodeInterface.
   * @param array $node_types
   *   If are values defiend, return just value of this node types.
   *
   * @return array
   *   Multidimensional array with nid and revision_id of nodes.
   */
  public function getNodesid(array $nodes, array $node_types = []) {
    $values = [];
    foreach ($nodes as $node) {
      if ($node instanceof NodeInterface) {
        if (empty($node_types)) {
          $values[] = [
            'target_id' => $node->id(),
            'target_revision_id' => $node->getRevisionId(),
          ];
        }
        elseif(in_array($node->getType(), $node_types)) {
          $values[] = [
            'target_id' => $node->id(),
            'target_revision_id' => $node->getRevisionId(),
          ];
        }
      }
    }
    return $values;
  }

}

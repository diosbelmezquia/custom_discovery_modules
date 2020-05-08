<?php

namespace Drupal\discovery_components\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Manages discovery and instantiation of component plugins.
 *
 * @see \Drupal\discovery_components\Plugin\ComponentPluginInterface
 */
class ComponentManager extends DefaultPluginManager {

  /**
   * Constructs a new ComponentManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Component', $namespaces, $module_handler, 'Drupal\discovery_components\Plugin\ComponentPluginInterface', 'Drupal\discovery_components\Annotation\Component');

    // Hook for alter the plugins, hook_discovery_component_alter(&definitions).
    $this->alterInfo('discovery_component');
  }

  /**
   * Get the candidate paragraphs to get the data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @param array $result
   *   Returns the paragraphs data by reference.
   */
  public function getDataComponents(ContentEntityInterface $entity, array &$result) {
    if ($entity) {
      // Get only the candidate fields to obtain the paragraphs.
      $fields_reference_revisions = array_filter($entity->getFields(),
        function ($field) {
          return $field instanceof EntityReferenceRevisionsFieldItemList;
        });

      // Get all paragraphs of the $entity.
      $paragraphs = [];
      foreach ($fields_reference_revisions as $field) {
        $paragraphs = array_merge($paragraphs, $field->referencedEntities());
      }

      // Get data for each paragraph defiend as component.
      $this->getParagraphsData($paragraphs, $result);
    }
  }

  /**
   * Get data of paragraphs defiend as plugin/component.
   *
   * @param array $entities
   *   The paragraphs to get the data.
   *
   * @param array $result
   *   Returns the paragraphs data by reference.
   */
  public function getParagraphsData(array $paragraphs, array &$result) {
    if (empty($paragraphs)) {
      return;
    }
    // Get this components just one time, not in array.
    $one_time = ['content_style', 'embed_code', 'highlight', 'live_streaming', 'graphic_opening'];
    // Unset position.
    $without_position = ['social_links'];
    // An array of the content already shown in other carousels
    $ids_to_exclude = [];
    // Get data for each paragraph defiend as plugin/component.

    foreach ($paragraphs as $position => $paragraph) {
      if ($paragraph instanceof ParagraphInterface) {
        $plugin_id = $paragraph->getType();
        // If there is any plugin/component created.
        if ($this->hasDefinition($plugin_id)) {
          // Get the plugin/component data defined in the getData() function of
          // each plugin/component.
          if ($plugin_id == 'carousel') {
            $component = $this->createInstance($plugin_id, $ids_to_exclude)->getData($paragraph);
          }
          else {
            $component = $this->createInstance($plugin_id, $ids_to_exclude)->getData($paragraph);
          }

          if (!empty($component)) {
            $component['position'] = $position;
            // Unset position.
            if (in_array($plugin_id, $without_position)) {
              unset($component['position']);
            }
            if (in_array($plugin_id, $one_time)) {
              $result['discovery_components'][$plugin_id] = $component;
            }
            else {
              $result['discovery_components'][$plugin_id][] = $component;
            }

            if ($plugin_id == 'carousel') {
              foreach ($component['content'] as $content) {
                $ids_to_exclude[] = $content['nid'];
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get preview link for each component/paragraph.
   *
   * @param array $result
   *   Current Paragraph.
   *
   * @param string $plugin_id
   *   The plugin_id for current paragraph.
   */
  public function getPreviewLink(array &$result, $plugin_id) {
    $image_name = drupal_get_path('module', 'discovery_components') . "/images/$plugin_id.png";

    if (file_exists($image_name)) {
      $url = file_create_url($image_name);
      // Show markup for image.
      $id = $plugin_id;
      $output = "<div class='img_popop' id='$id' class='modal'><img src='$url'></div>
      <a href='#$id' rel='modal:open'>Preview</a>";
      // Render array markup.
      $result['image_preview'] = [
        '#type' => 'item',
        '#markup' => $output,
      ];
    }
  }

}

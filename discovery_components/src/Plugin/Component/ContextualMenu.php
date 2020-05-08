<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\discovery_components\MigrateComponentBatch;
use Drupal\discovery_helper\Utility\DiscoveryContent;

/**
 * Provides a 'Contextual Menu' component.
 *
 * @Component(
 *   id = "contextual_menu",
 *   label = @Translation("Contextual Menu"),
 *   background_color = "#34bb6f42"
 * )
 */
class ContextualMenu extends ComponentBase {

  /**
   * Get the node parent for current paragraph.
   */
  protected $parent_entity = NULL;

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $elements = $paragraph->field_contextual_elements->referencedEntities();
    $elements_default = $paragraph->field_contextual_default->value;

    // Get the node parent for current paragraph.
    $this->parent_entity = $paragraph->getParentEntity();
    $main_category_id = $this->parent_entity->field_main_category->target_id;
    $content  = $this->getContextualMenu($elements, $elements_default, $this->langcode, $main_category_id);

    // Get values.
    $contextual_menu = [];
    if (!empty($content)) {
      $contextual_menu['content'] = $content;
    }
    if (isset($contextual_menu['content']) && $paragraph->field_label->value) {
      $contextual_menu['label'] = $paragraph->field_label->value;
    }
    return $contextual_menu;
  }

  /**
   * Add contextual menu data to the response.
   *
   * @param array $entity
   *   Array of entities.
   *
   * @param array
   *  The content.
   */
  private function getContextualMenu(array $entities, string $content, $langcode = 'es', $main_category_id = '') {
    $image_service = \Drupal::service('discovery_helper.get_image');

    // Get content to fill out or to display by default for the $content.
    $current_entities = [];
    foreach ($entities as $entity) {
      $current_entities[] = $entity->id();
    }
    $default_entities = $this->getDefaultContentForContexualMenu($content, $current_entities, $langcode, $main_category_id);

    // Get/plus all items for contextual menu.
    $entities = $entities += $default_entities;

    // Get just the first 24 elements.
    $entities = array_slice($entities, 0, 24);

    $data = [];
    $current_categories = [];
    foreach ($entities as $element) {
      $logo = '';
      // Get logo and URL
      $node_type = $element->getType();
      $alias_service = \Drupal::service('discovery_helper.get_alias');
      $title = mb_convert_encoding($element->title->value, 'UTF-8', 'UTF-8');
      if ($content == 'categories') {
        $logo = $image_service->get_contextual_menu_image($element, TRUE);
        $url = $alias_orig = \Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$element->field_category->target_id,$langcode);
        $title = $element->field_category->entity->name->value;
      }
      else {
        if ($node_type == 'channel') {
          $logo = $image_service->get_contextual_menu_image($element);
          if (!is_null($element->field_channel_url->uri)) {
            $url = $element->field_channel_url->uri;
          }
          else {
            $url = $alias_service->get_alias($element->id(), $langcode);
          }
          $name = id_channel($element);
          if($channel_title = DiscoveryContent::channelTitle($name, $langcode)) {
            $title = $channel_title;
          }
        }
        elseif ($node_type == 'programa') {
          $logo = $image_service->get_contextual_menu_image($element);
          $url = $alias_service->get_alias($element->id(), $langcode);
        }
        else {
          $logo = $image_service->get_contextual_menu_image($element, TRUE);
          $url = $alias_service->get_alias($element->id(), $langcode);
        }
      }

      // Use the title as key to order the array alphabetically.
      $data[$title] = [
        'logo'  => $logo,
        'title' => $title,
        'url'   => $url,
      ];
      // Add shortcode for channels.
      if ($node_type == 'channel') {
        $data[$title]['shortcode'] = id_channel($element);
      }
      // Select the current category this component belong.
      if ($node_type == 'categories' && $this->parent_entity) {
        if($this->checkCurrentCategory($element)) {
          $data[$title]['current_category'] = 'true';
          // Get current categories this component belong.
          $current_categories[] = $data[$title];
          // Unset this categories to add it later in first places.
          unset($data[$title]);
        }
      }
    }

    // Get data for response.
    if (!empty($data)) {
      // Order data alphabetically.
      ksort($data);

      // Reset the numeric array indexes again.
      $data_ordered = [];
      foreach ($data as $d) {
        $data_ordered[] = $d;
      }
      // Plus current categories this component belong.
      $data_ordered = array_merge($current_categories, $data_ordered);
      return $data_ordered;
    }

    return [];
  }

  // Select the current category this component belong.
  private function checkCurrentCategory(Node $element) {
    if (isset($this->parent_entity->field_category)) {
      $parent_node_terms = $this->parent_entity->field_category->referencedEntities();
      $element_categories = $element->field_category_term->referencedEntities();
      // Get nodes terms.
      $parent_node_terms = $this->getTerms($parent_node_terms);
      $element_categories = $this->getTerms($element_categories);
      // Si no esta vacio, enotnces coinciden las categorias.
      if (!empty(array_intersect($element_categories, $parent_node_terms))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  // Get Terms.
  private function getTerms(array $entity_terms) {
    $terms = [];
    foreach ($entity_terms as $term) {
      $terms[] = $term->getName();
    }
    return $terms;
  }

  /**
   * Get $node_type content to fill in or to display by default for the
   * $content.
   *
   * @return array
   *   The content objects.
   */
  private function getDefaultContentForContexualMenu(string $content, array $current_entities, string $langcode, $main_category_id) {
    $default_entities = [];
    if (!empty($content)) {
      switch ($content) {
        // A request to the home and search, show channels by default.
        case 'channel':
          $node_type = 'channel';
          break;
        // A request to some channel, show shows by default.
        case 'show_special':
          $node_type = 'programa';
          break;
        // Any other request, show categories by default.
        // For example for show, video, etc.
        default:
          $node_type = 'categories';
          break;
      }
      // Get $node_type content to fill in or to display by default for
      // the $content.
      if ($node_type == 'categories') {
        $tax_ids = \Drupal::entityQuery('taxonomy_term')
          ->condition('field_main_category', $main_category_id)
          ->execute();
        foreach ($tax_ids as $content_id) {
          $nid = $this->getNodeImagebyCategory($content_id);
          if ($nid) {
            $content_ids[] = $nid;
          }
        }
        // Get data from another exe.
        $tax_ids = \Drupal::entityQuery('taxonomy_term')
          ->condition('field_main_category', $main_category_id, '<>')
          ->execute();
        foreach ($tax_ids as $content_id) {
          $nid = $this->getNodeImagebyCategory($content_id);
          if ($nid) {
            $content_ids[] = $nid;
          }
        }
      }
      // Get just show of current channel.
      elseif($node_type == 'programa' && $this->parent_entity->getType() == 'channel') {
        $content_ids = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', $node_type)
          ->condition('field_channel', $this->parent_entity->id())
          ->range(0,24)
          ->sort('changed', DESC);
        if (!empty($current_entities)) {
          $content_ids->condition('nid', $current_entities, 'NOT IN');
        }
        $content_ids = $content_ids->execute();
      }
      else {
        $content_ids = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', $node_type)
          ->range(0,24)
          ->sort('changed', DESC);
        if ($node_type == 'channel') {
          $content_ids->condition('field_visible', '0');
        }
        if (!empty($current_entities)) {
          $content_ids->condition('nid', $current_entities, 'NOT IN');
        }
        $content_ids = $content_ids->execute();
      }

      // Load the entities.
      foreach ($content_ids as $content_id) {
        /**
         * @var Node $content
         */
        $content = Node::load($content_id);
        if ($content->hasTranslation($langcode)) {
          $content = $content->getTranslation($langcode);
          $default_entities[] = $content;
        }
        else {
          if ($content->language()->getId() == $langcode) {
            $default_entities[] = $content;
          }
        }
      }
    }
    return $default_entities;
  }

  // Get node by category, with image.
  private function getNodeImagebyCategory($category) {
    $nids = \Drupal::entityQuery('node')
      ->condition('field_category', $category)
      ->condition('status', 1)
      ->execute();
    // Get image service.
    $image_service = \Drupal::service('discovery_helper.get_image');
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      // Call get_image() for contextual menu.
      $image_url = $image_service->get_contextual_menu_image($node, TRUE);
      if (!preg_match('/1_1|image_default/', $image_url)) {
        return $nid;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    foreach ($nodes as $langcode => $node) {
      if ($node) {
        // Dont add Contextual menu for show special.
        if ($node->getType() == 'programa' && $node->field_layout->value == 'special') {
          continue;
        }
        switch ($node->getType()) {
          case 'home':
          case 'search':
            $default_elements = 'channel';
            break;
          case 'channel':
            $default_elements = 'show_special';
            break;
          default:
            $default_elements = 'categories';
            break;
        }
        // Create new Component/Paragraph.
        $paragraph = $this->createMigrateParagraph($default_elements, $langcode);
        //  Update destination node.
        $this->updateDestinationNode($node, $paragraph, $langcode);
      }
    }
  }

  /**
   * Create component/paragraph for migrate process.
   */
  private function createMigrateParagraph(string $default_elements, string $langcode) {
    // Create new Component/Paragraph.
    $values = [
      'type' => $this->getPluginId(),
      'langcode' => $langcode,
      'field_label' => '',
      'field_contextual_default' => [
        // categories, channel, show_special.
        ['value' => $default_elements],
      ]
    ];
    // Set contextual menu label.
    switch ($default_elements) {
      case 'channel':
        $values['field_label'] = t('Channels');
        break;
      case 'show_special':
        $values['field_label'] = t('Shows');
        break;
      default:
        $values['field_label'] = t('Categories');
        break;
    }
    $paragraph = Paragraph::create($values);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Start the migrate process.
   */
  public function migrate(string $node_type) {
    // Execute migrate.
    $this->processContent();
  }


  private function processContent() {
    // Get nids of $node_type.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', ['home', 'channel', 'video', 'programa', 'search'], 'IN')
      ->condition('status', 1)
      //->range(0, 5) // @TODO Remove range() for prod when all its OK!!.
      ->execute();

    if ($nids) {
      // Build batch operations, one by node.
      $operations = [];
      foreach ($nids as $nid) {
        $data = [
          'nid' => $nid,
        ];
        $operations[] = [
          [MigrateComponentBatch::class, 'migrateContextualMenuBatchProcess'],
          [$data],
        ];
      }
      $batch = [
        'title' => $this->t('Migrating new Contextual menu components'),
        'operations' => $operations,
        'init_message' => $this->t('Starting migration the new Contextual menu components for content.'),
        'progress_message' => $this->t('Migrating @current contents of @total. Estimated time: @estimate.'),
        'finished' => [MigrateComponentBatch::class, 'finish'],
      ];
      batch_set($batch);
    }
  }

  /**
   * Update destination node.
   */
  private function updateDestinationNode(NodeInterface $node, $paragraph, $langcode) {
    // Add the new Component/Paragraph to $node.
    $field_destination = 'field_contextual_menu_component';
    if ($node && $node->{$field_destination} && $node->{$field_destination} instanceof EntityReferenceFieldItemListInterface) {
      $node_last_modification = $node->changed->value;
      if ($node->{$field_destination}->isEmpty()) {
        $node->{$field_destination}->appendItem($paragraph);
        $node->save();
      }
      else {
        $node->{$field_destination}->removeItem(0)->appendItem($paragraph);
        $node->save();
      }
      $connection = \Drupal::database();
      $connection->update('node_field_data')
        ->fields([
          'changed' => $node_last_modification,
        ])
        ->condition('nid', $node->id())
        ->condition('langcode', $langcode)
        ->execute();

       $connection->update('node_field_revision')
        ->fields([
          'changed' => $node_last_modification,
        ])
        ->condition('nid', $node->id())
        ->condition('langcode', $langcode)
        ->execute();
    }
  }

}

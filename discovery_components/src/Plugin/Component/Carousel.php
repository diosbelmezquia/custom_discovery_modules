<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\discovery_helper\Utility\DiscoveryContent;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\discovery_adjustments\Utility\DiscoveryHelper;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides a 'Carousel' component.
 *
 * @Component(
 *   id = "carousel",
 *   label = @Translation("Carousel"),
 *   background_color = "#ffc30f42"
 * )
 */
class Carousel extends ComponentBase {

  /**
   * The amount of content to display in each carousel for each swipe.
   */
  const CANT_CONTENT = 12;

  /**
   * The carousel content.
   */
  protected $carousel_content;

  /**
   * The paragraph id.
   */
  protected $pid;

  /**
   * The number for where the carousel begin.
   */
  protected $start;

  /**
   * The node id.
   */
  protected $nid;

  /**
   * Content to exclude
   */
  protected $exclude;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $entity_type_manager, $serializer);

    // Get queries parameters for carousel.
    $this->pid = $this->requestStack->getCurrentRequest()->query->get('carousel');
    $this->nid = $this->requestStack->getCurrentRequest()->query->get('content');
    $this->start = $this->requestStack->getCurrentRequest()->query->get('start');
    $this->exclude = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph, $start = 0) {
    // Save the carousel data.
    $carousel_data = [];

    // Get date format
    $date_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    // Get current datetime drupal server.
    $current_date = DiscoveryHelper::getCurrentServerDate($date_format);
    // Unpublish current component for today.
    if ($paragraph->field_unpublish_date->getValue()[0]['value'] == $current_date) {
      return $carousel_data;
    }

    if ($this->publishScheduledContent($paragraph)) {
      // Get scheduled content.
      $field = 'field_carousel_scheduled_content';
      //$this->carousel_content = $this->getContentByInterval($field, $paragraph->id(), $start);
      $this->getCarouselContent($paragraph, $carousel_data, $field);
    }
    else {
      // Get default content.
      $field = 'field_carousel_content';
      // $this->carousel_content = $this->getContentByInterval($field, $paragraph->id(), $start);
      $this->getCarouselContent($paragraph, $carousel_data, $field);
    }
    // Get extra data if there is content.
    $this->getExtraData($paragraph, $carousel_data);

    return $carousel_data;
  }

  /**
   * Get the data from a carousel for a REST response.
   */
  public function getCarouselRestResponse() {
    $nodo_array = 'NOT_FOUND';
    // If there is the necessary data by the url.
    if (isset($this->pid) && isset($this->nid) && isset($this->start) &&
      isset($this->langcode) && isset($this->region)) {
      $node = Node::load($this->nid);
      // If the parent node exists and is published.
      if ($node && $node->isPublished()) {
        if ($node->hasTranslation($this->langcode)) {
          $nodo = $node->getTranslation($this->langcode);
        }
        if ($node->language()->getId() == $this->langcode) {
          $nodo = $node;
        }
        $paragraph = Paragraph::load($this->pid);
        // If the paragraph belongs to the parent node.
        if ($paragraph && $paragraph->getParentEntity()->id() == $nodo->id()) {
          $nodo_array = $this->getData($paragraph, $this->start);
          if (empty($nodo_array)) {
            // Avoid return data to front on empty response.
            $nodo_array = '[]';
          }
        }
      }
    }
    return $nodo_array;
  }

  /**
   * Get content by interval.
   *
   * @param string $field
   *   La columna que tiene los ids de los contenidos del carousel.
   *
   * @param int $pid
   *   The component($paragraph) to search for content.
   *
   * @param $start
   *   Show carousel content starting at $start.
   *
   * @return array
   *   The contents of the $pid component($paragraph) from $start
   *   to cont CANT_CONTENT.
   */
  private function getContentByInterval($field, $pid, $start = 0) {
    // Get column.
    $column = $field . '_target_id';
    // Get table.
    $table = 'paragraph__' . $field;
    // Get pagesize.
    $pagesize = self::CANT_CONTENT;
    // Query to get the referenced content by interval.
    $carousel_content = \Drupal::database()->query("
      SELECT $column as node_id
      FROM {$table}
      WHERE entity_id = :nid
      LIMIT $start, $pagesize", [':nid' => $pid])->fetchAll();
    return $carousel_content;
  }

  /**
   * Get the carousel content.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @param array
   *   The content for current component($paragraph).
   */
  private function getCarouselContent(ParagraphInterface $paragraph, array &$carousel_data, $field) {
    // Get all carousel content data.
    $existing_content_count = 0;
    if (!empty($paragraph->{$field})) {
      foreach ($paragraph->{$field} as $node) {
        $nodo = Node::load($node->target_id);
        // If the content shown on the carousel is the same language as the parent content.
        if ($nodo->isPublished()) {
          $parent_langcode = $paragraph->getParentEntity()
            ->language()
            ->getId();
          if ($nodo->hasTranslation($parent_langcode)) {
            $node_trans = $nodo->getTranslation($parent_langcode);
          }
          if ($nodo->language()->getId() == $parent_langcode) {
            $node_trans = $nodo;
          }
          $inRegion = $this->isInRegion($node_trans, $this->region);
          if ($inRegion['in_region']) {
            $carousel_data['content'][] = $this->getContent($node_trans, $paragraph->language()
              ->getId(), 'destacados', $this->region);
            $existing_content_count++;
          }
        }
      }
    }
    {

      $content_types = [];
      foreach ($paragraph->field_show_content_of_type as $type) {
        $content_types[] = $type->value;
      }
      if (count($content_types) == 0) {
        $content_types = ['video', 'programa'];
      }

      $parent_type = $paragraph->getParentEntity()->bundle();
      if ((!$paragraph->field_show_content_from->isEmpty()) || ($parent_type == 'home')) {
        if ($parent_type != 'home') {
          $type = $paragraph->field_show_content_from->entity->getType();
        }
        else {
          $type = $parent_type;
        }
        if ($type == 'home') {
          $show_id = NULL;
          $channel_id = NULL;
        }
        if ($type == 'programa') {
          $show_id = $paragraph->field_show_content_from->target_id;
          $channel_id = $paragraph->field_show_content_from->entity->field_channel->target_id;
        }
        else {
          $show_id = NULL;
          $channel_id = $paragraph->field_show_content_from->target_id;
        }
        $except = $this->exclude;
        foreach ($paragraph->{$field} as $content) {
          $except[] = $content->target_id;
        }
        $pagesize = 12-$existing_content_count;
        if ($paragraph->field_content_to_show->value == 'recommended') {
          $pagesize = 5-$existing_content_count;
        }
        if ($pagesize>0) {
          if ($paragraph->field_cross_content->value) {
            DiscoveryContent::generateArray($carousel_data, $show_id, $channel_id, $this->langcode, $this->region, '2_3', ['programa'], $category_id = NULL, $except, 0, $pagesize, FALSE, TRUE);
          }
          else {
            DiscoveryContent::generateArray($carousel_data, $show_id, $channel_id, $this->langcode, $this->region, 'destacados', $content_types, $category_id = $paragraph->field_show_content_from_category->target_id, $except, 0, $pagesize);
          }
        }

      }

      /** HOME */
    }

  }

  /**
   * To know if there is content scheduled to be published today.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @param boolean
   *   TRUE if there is scheduled contend for today, FALSE otherwise.
   */
  private function publishScheduledContent(ParagraphInterface $paragraph) {
    // Get scheduled content date in Unix/timestamp.
    $scheduled_date = $paragraph->field_scheduled_date->value;
    // Get date format
    $date_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    // Get current datetime drupal server.
    $current_date = DiscoveryHelper::getCurrentServerDate($date_format);
    // If the scheduled content is to be published today.
    if ($scheduled_date && strtotime($scheduled_date) <= strtotime($current_date)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get extra data related to the carousel.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @param array
   *   Default data for current component($paragraph).
   */
  private function getExtraData(ParagraphInterface $paragraph, array &$carousel_data) {
    // Show the label only if there is content in the carousel.
    if ((isset($carousel_data['content'])) || ($paragraph->field_content_to_show->value) || ($paragraph->field_cross_content->value)) {
      $display = $paragraph->field_display_options->value;
      $parent_content = $paragraph->getParentEntity();
      // Get data.
      $carousel_data['label'] = $paragraph->field_label->value == "" ? "" : t($paragraph->field_label->value, [], ['langcode' => $this->langcode]);
      if (!is_null($paragraph->field_label_link)) {
        $carousel_data['label_link_uri'] = $paragraph->field_label_link->uri;
        $carousel_data['label_link_title'] = $paragraph->field_label_link->title;
      }
      $carousel_data['show_content_as'] = $display;
      $carousel_data['carousel_id'] = $paragraph->id();
      $carousel_data['parent_content'] = $parent_content->id();
      $carousel_data['full_width'] = (boolean) $paragraph->field_full_width->value;
      // Show in cross content just for carousel display.
      if ($display == 'carousel') {
        $carousel_data['cross_content'] = $paragraph->field_cross_content->value == 1 ? 'true' : 'false';
      }
      if ($display == 'slider' && !$carousel_data['full_width']) {
        // Add ad to the response.
        $paragraph_ad = $paragraph->field_insert_ad->entity;
        if ($paragraph_ad) {
          $plugin_manager = \Drupal::service('plugin.manager.component');
          $carousel_data['ad'] = $plugin_manager->createInstance('ad_code')
            ->getData($paragraph_ad, $parent_content);
          $carousel_data['ad_position'] = $paragraph->field_ad_location->value == 0 ? 'right' : 'left';
        }
      }
      if ($display == 'slider') {
        // Get just the 5 first elements for slider with ads(recommended carosules).
        $carousel_data['content'] = array_slice($carousel_data['content'], 0, 5);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    foreach ($nodes as $langcode => $node) {
      if ($node) {
        // Validate the fields.
        $fields_validated = $this->validateMigrationFields($fields, $node);
        // Create new Component/Paragraph.
        $paragraph = $this->createMigrateParagraph($fields_validated, $langcode);
        //  Update destination node.
        $this->updateDestinationNode($fields_validated, $node, $paragraph);
      }
    }
  }

  /**
   * Validate the fields to migrate a component/paragraph.
   *
   * @param array $fields .
   *   The fields defiend in yml file for each component/paragraph.
   * @param \Drupal\node\NodeInterface $node
   *   Create a component/paragraph for the node $node.
   *
   * @return array
   *   The fields validated for create the component.
   */
  private function validateMigrationFields(array $fields, NodeInterface $node) {
    if (!empty($fields)) {

      $fields_validated = [
        'label' => '',
        'content' => [],
        'scheduled_content' => [],
        'scheduled_publication_date' => '',
        'display_as' => 'carousel',
        'cross_content' => 0,
        'field_destination' => $fields['field_destination'],
        'show_content_from' => $node->id(),
        'show_content_from_category' => '',
        'show_content_of_type' => $fields['field_show_content_of_type'],
        'field_content_to_show' => $fields['field_content_to_show'],
      ];

      // Get just data for this node types.
      $get_node_types = [
        'video',
        'article',
        'programa',
        'external_content',
        'galeria_de_imagenes',
      ];

      // Validate label field from yml file.
      if (isset($fields['label']) && !empty($fields['label'])) {
        $fields_validated['label'] = $fields['label'];
      }

      // Validate content field from yml file.
      if (isset($fields['content']) && !empty($fields['content'])) {
        $field_content = $fields['content'];
        if ($node && $node->{$field_content} && $node->{$field_content} instanceof EntityReferenceFieldItemListInterface) {
          $nodes = $node->{$field_content}->referencedEntities();
          // Get the nids and vids of these nodes.
          $fields_validated['content'] = $this->getNodesid($nodes, $get_node_types);
        }
      }
      else {
        throw new \Exception($this->t('The "content" field is required for migrate :plugin_id component, check the file in :file_path', [
          ':plugin_id' => $this->getPluginId(),
          ':file_path' => $this->getMigrationFilePath(),
        ]));
      }

      // Validate scheduled_content field from yml file.
      if (isset($fields['scheduled_content']) && !empty($fields['scheduled_content'])) {
        $field_scheduled_content = $fields['scheduled_content'];
        if ($node && $node->{$field_scheduled_content} && $node->{$field_scheduled_content} instanceof EntityReferenceFieldItemListInterface) {
          $nodes = $node->{$field_scheduled_content}->referencedEntities();
          // Get the nids and vids of these nodes.
          $fields_validated['$field_scheduled_content'] = $this->getNodesid($nodes, $get_node_types);
        }
      }

      // Validate display_as field from yml file.
      if (isset($fields['scheduled_publication_date']) && !empty($fields['scheduled_publication_date'])) {
        $fields_validated['scheduled_publication_date'] = $fields['scheduled_publication_date'];
      }

      // Validate display_as field from yml file.
      if (isset($fields['display_as']) && !empty($fields['display_as'])) {
        $fields_validated['display_as'] = $fields['display_as'];
      }

      // Validate label cross_content from yml file.
      if (isset($fields['cross_content'])) {
        $fields_validated['cross_content'] = 1;
      }

      // Validate label cross_content from yml file.
      if (isset($fields['field_show_content_of_type'])) {
        $fields_validated['field_show_content_of_type'] = $fields['field_show_content_of_type'];
      }

      return $fields_validated;
    }
    else {
      throw new \Exception($this->t('There are not fields to validate current migration.'));
    }
  }

  /**
   * Create component/paragraph for migrate process.
   */
  private function createMigrateParagraph(array $fields, string $langcode) {
    // Create new Component/Paragraph.
    $paragraph = NULL;
    if (!empty($fields)) {

      $paragraph = Paragraph::create([
        'type' => $this->getPluginId(),
        'langcode' => $langcode,
        'field_label' => [
          ['value' => $fields['label']],
        ],
        'field_carousel_content' => $fields['content'],
        'field_carousel_scheduled_content' => $fields['scheduled_content'],
        'field_scheduled_date' => [
          ['value' => $fields['scheduled_publication_date']],
        ],
        'field_display_options' => [
          ['value' => $fields['display_as']],
        ],
        'field_cross_content' => [
          ['value' => $fields['cross_content']],
        ],
        'field_show_content_from' => [
          ['target_id' => $fields['show_content_from']],
        ],
        'field_content_to_show' => [
          ['value' => $fields['field_content_to_show']],
        ],
      ]);
      if (isset($fields['field_show_content_of_type'])) {
        foreach ($fields['field_show_content_of_type'] as $type) {
          $values[] = $type;
        }
        $paragraph->set('field_show_content_of_type', $values);
      }
      $paragraph->save();
    }
    return $paragraph;
  }

  /**
   * Update destination node.
   */
  private function updateDestinationNode(array $fields, NodeInterface $node, $paragraph) {
    // Add the new Component/Paragraph to $node.
    $field_destination = $fields['field_destination'];
    if ($node && $node->{$field_destination} && $node->{$field_destination} instanceof EntityReferenceFieldItemListInterface) {
      $node->{$field_destination}->appendItem($paragraph);
      $node->save();
    }
  }

}

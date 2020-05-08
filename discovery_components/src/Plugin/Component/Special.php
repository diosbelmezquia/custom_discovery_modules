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
use Drupal\Component\Utility\Color;

/**
 * Provides a 'Special' component.
 *
 * @Component(
 *   id = "special",
 *   label = @Translation("Carousel"),
 *   background_color = "#2898ab63"
 * )
 */
class Special extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $image_service = \Drupal::service('discovery_helper.get_image');
    $special = $highlighted_array = [];

    foreach ($paragraph->field_content_special as $highlighted) {
      $content = DiscoveryContent::content($highlighted->entity, $this->langcode, 'slider', $this->region);
      if (!empty($content)) {
        $highlighted_array[] = $content;
      }
    }
    if (!empty($highlighted_array)) {
      $special['content'] = $highlighted_array;
    }

    if (isset($special['content']) && count($special['content']) > 0) {
      // Get logo image.
      if ($paragraph->field_logo_kids->entity) {
        $special['logo_image'] = file_create_url($paragraph->field_logo_kids->entity->getFileUri());
      }
      // Show logo url.
      $logo_url = $paragraph->field_link_special->value;
      if ($logo_url) {
        $special['logo_url'] = $logo_url;
      }
      // Get background image.
      if ($paragraph->field_imagen->entity) {
        $special['background_image'] = file_create_url($paragraph->field_imagen->entity->getFileUri());
      }
      // Get font color.
      if ($paragraph->field_content_color->color) {
        $rgb_color = Color::hexToRgb($paragraph->field_content_color->color);
        $special['font_color'] = $rgb_color;
      }
    }
    return $special;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

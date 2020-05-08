<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Component\Utility\Color;

/**
 * Provides a 'Graphic Opening' component.
 *
 * @Component(
 *   id = "graphic_opening",
 *   label = @Translation("Graphic Opening"),
 *   background_color = "#00bcd43d"
 * )
 */
class GraphicOpening extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $image_service = \Drupal::service('discovery_helper.get_image');
    $graphic_opening = [];
    // Get title.
    $title_type = $paragraph->field_title_type->value;
    if ($title_type == 'graphic') {
      if ($paragraph->field_graphic_title->entity) {
        $graphic_opening['image_title'] = $image_service->get_image_estilo_imgix($paragraph->field_graphic_title, 'header');
      }
    }
    else {
      if ($paragraph->field_text_title->value) {
        $graphic_opening['text_title'] = $paragraph->field_text_title->value;
      }
    }
    // Get background image.
    if ($paragraph->field_back_image_desktop->entity) {
      $graphic_opening['background_image'] = $image_service->get_image_estilo($paragraph->field_back_image_desktop, 'slider');
    }
    // Get gradient color.
    if ($paragraph->field_background_color->color) {
      $rgb_color = Color::hexToRgb($paragraph->field_background_color->color);
      $graphic_opening['gradient'] = $rgb_color;
    }
    return $graphic_opening;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

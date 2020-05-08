<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Content Style' component.
 *
 * @Component(
 *   id = "content_style",
 *   label = @Translation("Content Style"),
 *   background_color = "#f504044d"
 * )
 */
class ContentStyle extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
    $content_style = [
      'font_color' => $paragraph->field_content_color->getValue()[0]['color'],
      'details_color' => $paragraph->field_color_details->getValue()[0]['color'],
      'background_color'=> $paragraph->field_background_color->color,
      'repeat_background' => $paragraph->field_repeat_background->value,
    ];

    // Get images.
    if ($paragraph->field_back_image_desktop->entity) {
      $content_style['background_img_desktop'] = file_create_url($paragraph->field_back_image_desktop->entity->getFileUri());
    }
    if ($paragraph->field_back_image_movil->entity) {
      $content_style['background_img_mobile'] = file_create_url($paragraph->field_back_image_movil->entity->getFileUri());
    }

    return $content_style;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

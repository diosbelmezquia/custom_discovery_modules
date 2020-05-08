<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Highlight' component.
 *
 * @Component(
 *   id = "highlight",
 *   label = @Translation("Highlight"),
 *   background_color = "#ff869823"
 * )
 */
class Highlight extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
    $highlight = [];
    $image_service = \Drupal::service('discovery_helper.get_image');
    if ($paragraph->field_label->value) {
      $highlight['label'] = $paragraph->field_label->value;
    }
    if ($paragraph->field_show->entity) {
      $highlight['show_title'] = $paragraph->field_show->entity->title->value;
    }
    if (!$paragraph->field_cta->isEmpty()) {
      $highlight['cta']['url'] = $paragraph->field_cta->uri;
      $highlight['cta']['title'] = $paragraph->field_cta->title;
    }
    if ($paragraph->field_back_image_desktop->entity) {
      $highlight['background_image'] = $image_service->get_image_estilo($paragraph->field_imagen, 'slider');
    }
    if ($paragraph->field_imagen->entity) {
      $highlight['thumbnail_image'] = $image_service->get_image_estilo($paragraph->field_imagen, 'slider');
    }
    if ($paragraph->field_button_color->color) {
      $highlight['button_color'] = $paragraph->field_button_color->color;
    }
    if ($paragraph->field_dark_or_light_text_color->value) {
      $highlight['text_color'] = $paragraph->field_dark_or_light_text_color->value;
    }
    else {
      $highlight['text_color'] = 'light';
    }
    return $highlight;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

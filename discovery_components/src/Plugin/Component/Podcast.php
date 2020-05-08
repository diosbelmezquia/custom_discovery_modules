<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Podcast' component.
 *
 * @Component(
 *   id = "podcast",
 *   label = @Translation("Podcast"),
 *   background_color = "#ff869823"
 * )
 */
class Podcast extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
    $embed_codes = [];
    foreach ($paragraph->field_embed_code as $embed_code) {
      $embed_codes[] = $embed_code->value;
    }
    $podcast = [
      'label' => $paragraph->field_label->value,
      'embed_code' => $embed_codes
    ];
    return $podcast;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

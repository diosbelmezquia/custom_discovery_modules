<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Ad code' component.
 *
 * @Component(
 *   id = "embed_code",
 *   label = @Translation("Embed code component"),
 *   background_color = "#ff869823"
 * )
 */
class EmbedCode extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
      if ($paragraph->field_label->value) {
        $embed_code['label'] = $paragraph->field_label->value;
      }
      $embed_code = [
        'code' => $paragraph->field_embed_code->value,
      ];

    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

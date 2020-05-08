<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'IFrame' component.
 *
 * @Component(
 *   id = "iframe",
 *   label = @Translation("IFrame component"),
 *   background_color = "#ff869823"
 * )
 */
class IFrame extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
      if ($paragraph->field_iframe_embed->value) {
        $embed_code['iframe_embed_code'] = $paragraph->field_iframe_embed->value;
      }
    if ($paragraph->field_iframe_url->value) {
      $embed_code['iframe_url'] = $paragraph->field_iframe_url->value;
    }

    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

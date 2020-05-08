<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Airtime' component.
 *
 * @Component(
 *   id = "airtime",
 *   label = @Translation("Airtime"),
 *   background_color = "#ff869823"
 * )
 */
class Airtime extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
    $airtime = [
      'show_name' => $paragraph->field_show->entity->title->value,
      'label' => $paragraph->field_label->value,
    ];
    return $airtime;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

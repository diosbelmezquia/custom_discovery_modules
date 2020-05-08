<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Panoramic Opening' component.
 *
 * @Component(
 *   id = "panoramic",
 *   label = @Translation("Panoramic Opening"),
 *   background_color = "#33dc204d"
 * )
 */
class Panoramic extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $panoramic = $this->getContent($paragraph->field_panoramic->entity, $this->langcode, 'slider');
    return $panoramic;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

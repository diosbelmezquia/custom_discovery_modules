<?php

namespace Drupal\discovery_components\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\node\NodeInterface;

/**
 * Defines an interface for component plugins.
 */
interface ComponentPluginInterface extends PluginInspectionInterface {

  /**
   * Get paragraph/component data.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   The data of current component($paragraph).
   */
  public function getData(ParagraphInterface $paragraph);

  /**
   * Migrate a component/paragraph.
   *
   * @param array $fields.
   *   The fields defiend in yml file for each component/paragraph.
   * @param array $nodes
   *   Array with each nodes entity translated.
   */
  public function migrateComponent(array $fields, array $nodes);

}

<?php

namespace Drupal\discovery_components\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Component annotation object.
 *
 * @see \Drupal\discovery_components\Plugin\ComponentPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Component extends Plugin {

  /**
   * The Paragraph type.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The background color for the component/paragraph widget.
   *
   * @var string
   */
  public $background_color;
}

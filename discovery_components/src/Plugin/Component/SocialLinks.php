<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Social Links' component.
 *
 * @Component(
 *   id = "social_links",
 *   label = @Translation("Social Links"),
 *   background_color = "#673ab71a"
 * )
 */
class SocialLinks extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $social_links = [];
    // If there is exist.
    if ($social_user = $paragraph->field_social_network_url->value) {
      // Get social network name.
      $social_name = $paragraph->field_social_name->value;
      // Get user for social network.
      $social_links[$social_name] = $social_user;
    }
    return $social_links;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

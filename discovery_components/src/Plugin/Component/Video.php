<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\discovery_helper\Utility\DiscoveryContent;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Provides a 'Single Video' component.
 *
 * @Component(
 *   id = "video",
 *   label = @Translation("Video component"),
 *   background_color = "#ff869823"
 * )
 */
class Video extends ComponentBase {
   /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get values.
    if (!$paragraph->field_video->isEmpty()) {
      $embed_code = DiscoveryContent::content($paragraph->field_video->entity, $this->langcode, 'destacados', $this->region, FALSE, TRUE);
      return $embed_code;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

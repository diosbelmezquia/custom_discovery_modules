<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Component\Utility\Color;

/**
 * Provides a 'LiveStream' component.
 *
 * @Component(
 *   id = "live_streaming",
 *   label = @Translation("Live Streaming"),
 *   background_color = "#ff573354"
 * )
 */
class LiveStream extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    $image_service = \Drupal::service('discovery_helper.get_image');
    // Get regions to show the live video.
    $regions = [];
    $terms = $paragraph->field_live_region->referencedEntities();
    if ($terms) {
      foreach ($terms as $term) {
        $regions[] = strtolower($term->field_countries->value);
      }
    }
    // Get values.
    $live_streaming = [
      'title' => $paragraph->field_title->value,
      'embed_code' => $paragraph->field_embed_code->value,
      'tag' => $paragraph->field_tag->value,
      'regions' => $regions,
    ];

    // Get background option.
    $this->getBackground($paragraph, $live_streaming, $image_service);

    return $live_streaming;
  }

  /**
   * Get background option.
   */
  private function getBackground($paragraph, &$live_streaming, $image_service) {
    switch ($paragraph->field_background->value) {
      case 'image':
        if ($paragraph->field_imagen->entity) {
          $live_streaming['background']['image'] = $image_service->get_image_estilo_imgix($paragraph->field_imagen, 'wallpaper');
            // Add opacity.
            if ($paragraph->field_opacity->value) {
              $live_streaming['background']['opacity'] = 50;
            }
        }
        break;
      case 'color':
        if ($paragraph->field_background_color->color) {
          $rgb_color = Color::hexToRgb($paragraph->field_background_color->color);
          $live_streaming['background']['color'] = $rgb_color;
          // Add opacity.
          if ($paragraph->field_opacity->value) {
            $live_streaming['background']['opacity'] = 50;
          }
        }
        break;
      case 'gradient':
        if ($paragraph->field_background_gradient->color) {
          $rgb_color = Color::hexToRgb($paragraph->field_background_gradient->color);
          $live_streaming['background']['gradient'] = $rgb_color;
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

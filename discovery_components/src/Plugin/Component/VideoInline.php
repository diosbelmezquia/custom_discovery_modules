<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\color_field\ColorHex;
use Drupal\color_field\ColorRGB;
use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Color;

/**
 * Provides a 'Video Inline' component.
 *
 * @Component(
 *   id = "video_inline",
 *   label = @Translation("Video Inline"),
 *   background_color = "#00bcd43d"
 * )
 */
class VideoInline extends ComponentBase {

  /**
   * {@inheritdoc}
   *
   * @TODO Review this component better.
   */
  public function getData(ParagraphInterface $paragraph) {
    $image_service = \Drupal::service('discovery_helper.get_image');
    $video_inline = [];

    // Get all videos.
    $videos = $paragraph->field_video->referencedEntities();

    // Get video attributes.
    $video_fields = $this->getVideo($videos);

    if (isset($video_fields['videoid'])) {
      // Add title for current video inline.
      if ($label = $paragraph->field_label->value) {
        $video_inline['video_inline_title'] = $label;
      }
      // Get video.
      $video_inline['video'] = $video_fields;
      // Get Playlist.
      $video = Node::load($video_fields['videoid']);
      $playlist = $this->getPlayList($video);
      if (count($playlist) > 0) {
        $video_inline['playlist'] = $playlist;
      }
      $video_inline['is_active'] = $paragraph->field_es_nuevo->value == 1 ? 'true' : 'false';
      $video_inline['is_featured'] = $paragraph->field_featured->value == 1 ? 'true' : 'false';

      if ($paragraph->field_header_image->entity) {
        $video_inline['header_image'] = $image_service->get_image_estilo_imgix($paragraph->field_header_image, 'header');
      }
      $video_inline['autoplay'] = $paragraph->field_autoplay->value==1?'true':'false';
      $video_inline['playlist_direction'] = $paragraph->field_playlist_direction->value;

      // Add background playlist in RGB.
      $rgb_color = Color::hexToRgb($paragraph->field_color_details->color);
      $video_inline['color_block'] = $rgb_color;

      // Add opacity for background color of playlist.
      if ($paragraph->field_add_opacity_playlist->value) {
        $video_inline['color_block_opacity'] = 50;
      }
      // Get background option.
      $this->getBackground($paragraph, $video_inline, $image_service);
    }

    return $video_inline;
  }

  /**
   * Get video inline.
   */
  private function getVideo(array $videos) {
    $video_inline = [];
    foreach ($videos as $video) {
      if ($video && $video->isPublished()) {
        $inRegionOpening = $this->isInRegion($video, $this->region);
        if ($inRegionOpening['exact_match']) {
          $video_inline = $this->getContent($video, $this->langcode, 'destacados', $this->region, $poster = FALSE, $playlist = TRUE);
          $video_inline['videoid'] = $video->id();
          break;
        }
        elseif($inRegionOpening['in_region']) {
          $video_inline = $this->getContent($video, $this->langcode, 'destacados', $this->region, $poster = FALSE, $playlist = TRUE);
          $video_inline['videoid'] = $video->id();
        }

      }
    }
    return $video_inline;
  }

  /**
   * Get play list.
   */
  private function getPlayList(Node $video) {
    if (!$video) {
      return NULL;
    }

    $videos_programa = \Drupal::entityQuery('node')
      ->condition('type', 'video')
      ->condition('field_show', $video->field_show->target_id)
      ->condition('langcode', $this->langcode)
      ->condition('status', 1)
      ->condition('nid', $video->id(), '<>')
      ->range(0, 5)
      ->sort('field_video_type', 'ASC')
      ->sort('changed', 'DESC')
      ->execute();

      //return $videos_programa
    if (count($videos_programa)>0) {
      $playlist_array = [];
      foreach ($videos_programa as $video_programa) {
        $nodo_playlist = Node::load($video_programa);
        if ($nodo_playlist) {
          $inRegionPlaylist = $this->isInRegion($nodo_playlist, $this->region);
          if ($inRegionPlaylist['in_region']) {
            $playlist_array[] = $this->getContent($nodo_playlist, $this->langcode, 'destacados', $this->region, $poster = FALSE, $playlist = TRUE);
          }
        }
      }
      return $playlist_array;
    }
  }

  /**
   * Get background option.
   */
  private function getBackground($paragraph, &$video_inline, $image_service) {
    switch ($paragraph->field_background->value) {
      case 'image':
        if ($paragraph->field_imagen->entity) {
          $video_inline['background']['image'] = $image_service->get_image_estilo_imgix($paragraph->field_imagen, 'wallpaper');
        }
        break;
      case 'color':
        if ($paragraph->field_background_color->color) {
          $rgb_color = Color::hexToRgb($paragraph->field_background_color->color);
          $video_inline['background']['color'] = $rgb_color;
          // Add opacity.
          if ($paragraph->field_opacity->value) {
            $video_inline['background']['opacity'] = 50;
          }
        }
        break;
      case 'gradient':
        if ($paragraph->field_background_gradient->color) {
          $rgb_color = Color::hexToRgb($paragraph->field_background_gradient->color);
          $video_inline['background']['gradient'] = $rgb_color;
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

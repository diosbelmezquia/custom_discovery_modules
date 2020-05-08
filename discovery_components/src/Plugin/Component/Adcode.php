<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\color_field\ColorHex;
use Drupal\Component\Utility\Color;
use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a 'Ad code' component.
 *
 * @Component(
 *   id = "ad_code",
 *   label = @Translation("Ad code"),
 *   background_color = "#ff869823"
 * )
 */
class Adcode extends ComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph, $parent_content = NULL) {
    // Get values.
    if (is_null($paragraph->field_ad_slot_id->value)) {
      $parent = $paragraph->getParentEntity();
      if ($parent_content) {
        $parent = $parent_content;
      }
      return get_ad_codes($parent, $parent->language()->getId());
    }
    else {
      $ad_code = [
        'ad_slot_id' => $paragraph->field_ad_slot_id->value,
        'ad_unit_text' => $paragraph->field_ad_unit_text->value,
        'ad_size' => $paragraph->field_ad_size->value,
        'ad_slot_id_mobile' => $paragraph->field_ad_slot_id_mobile->value,
        'ad_unit_text_mobile' => $paragraph->field_ad_unit_text_mobile->value,
        'ad_size_mobile' => $paragraph->ad_size_mobile->value,
      ];
    }
    $image_service = \Drupal::service('discovery_helper.get_image');
    if ($paragraph->field_dark_or_light_background->value == 'dark') {
      $ad_code['background_lightness'] = 'dark';
    }
    else {
      $ad_code['background_lightness'] = 'light';
    }
    return $ad_code;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

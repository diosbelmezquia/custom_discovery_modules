<?php

namespace Drupal\discovery_adjustments\Utility;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use yedincisenol\DynamicLinks\DynamicLinks;
use Drupal\node\Entity\Node;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\discovery_adjustments\GenerateShorterUrlBatch;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides discovery internal helper methods.
 *
 * @ingroup utility
 */
class DiscoveryHelper {

  /**
   * Get the remaining days between two day.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $future_time
   *  The future date/time object to get the diff.
   *
   * @return string
   *  The remaining days in format (1 day) (5 days).
   */
  public static function getRemainingTime(DrupalDateTime $future_time) {
    // Get current time.
    $server_timezone = static::getServerTimezone();
    $now = new DrupalDateTime('now', $server_timezone);
    // Get diff between days.
    $difference = $now->diff($future_time);

    $number = $difference->format('%d');
    $date_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    // Set the plural for days.
    $day = t('day');
    $days = t('days');
    $date_equals = $now->format($date_format) == $future_time->format($date_format);
    if ($number == 0 && $date_equals) {
      return 'today';
    }
    else {
      $number++;
      $remaining_time = ngettext("$number $day", "$number $days", $number);
      return $remaining_time;
    }
  }

  /**
   * Add SEO values to de Response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @param array $result
   *  The REST response data result.
   */
  public static function addDataSEO(EntityInterface $entity, array &$result) {
    if (isset($entity->field_seo->value)) {
      $seo_values = unserialize($entity->field_seo->value);
      $result['seo'] = $seo_values;
    }
  }

  /**
   * Add url shorter to all content to the REST Response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @param array $result
   *  The REST response data result.
   */
  public static function addUrlShorter(EntityInterface $entity, array &$result) {
    if (isset($entity->url_shorter->value)) {
      $result['url_shorter'] = $entity->url_shorter->value;
    }
  }

  /**
   * Add Live Streaming values to de Response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @param array $result
   *  The REST response data result.
   */
  public static function addLiveStreamingData(EntityInterface $entity, array &$result) {
    if ($live = $entity->field_live_streaming->entity) {
      // Get regions for show the live video.
      $regiones = [];
      $terms = $live->field_region_term->referencedEntities();
      if ($terms) {
        foreach ($terms as $term) {
          $regiones[] = strtolower($term->field_countries->value);
        }
      }
      // Get values.
      $live_streaming = [
        'title' => $live->title->value,
        'embed_code' => $live->field_ad_long_code->value,
        'tag' => $live->field_tag->value,
        'regions' => $regiones,
      ];
      $result['live_streaming'] = $live_streaming;
    }
  }

  /**
   * Constructs an translated array of month names in Portuguese.
   *
   * @return array
   *   An array of month names.
   */
  public static function monthNamesPortuguese() {
    // Force the key to use the correct month value, rather than
    // starting with zero.
    return [
      1 => 'Janeiro',
      2 => 'Fevereiro',
      3 => 'Março',
      4 => 'Abril',
      5 => 'Maio',
      6 => 'Junho',
      7 => 'Julho',
      8 => 'Agosto',
      9 => 'Setembro',
      10 => 'Outubro',
      11 => 'Novembro',
      12 => 'Dezembro',
    ];
  }

  /**
   * Constructs an translated array of month names in format MM.
   *
   * @return array
   *   An array of month names.
   */
  public static function monthNamesTranslated() {
    return [
      '01' => t('January'),
      '02' => t('February'),
      '03' => t('March'),
      '04' => t('April'),
      '05' => t('May'),
      '06' => t('June'),
      '07' => t('July'),
      '08' => t('August'),
      '09' => t('September'),
      '10' => t('October'),
      '11' => t('November'),
      '12' => t('December'),
    ];
  }

  /**
   * Get the long url for production environment from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return $url_long
   *   The long url form entity o NULL otherwise.
   */
  public static function getProductionLongUrl(EntityInterface $entity) {
    $domain_prod = [
      'es' => 'https://www.tudiscovery.com',
      'pt-br' => 'https://www.discoverybrasil.com',
    ];

    $url_long = NULL;
    $langcode = $entity->language()->getId();
    if ($langcode && isset($domain_prod[$langcode])) {
      // Get entity url.
      $entity_url = $entity->toUrl()->toString();
      // Remove first occurrence of langcode from url.
      $entity_url = preg_replace("#/$langcode/#", '/', $entity_url, 1);
      // Concat url with production domain.
      $url_long = $domain_prod[$langcode] . $entity_url;
    }
    return $url_long;
  }

  /**
   * Generate short link with Firebase.
   *
   * @param string $long_link
   *   The large link for generate the short link.
   *
   * @return short link
   *   The short link of $link.
   */
  public static function getShortLink(string $long_link, $input_token = '', $input_domain = '') {
    $config = \Drupal::config('system.site');
    // Api key from Firebase.
    $apiKey = $config->get('api_key');
    // Domain from Firebase.
    $dynamicLinkDomain = $config->get('domain');

    if ($input_token) {
      // Api key from Firebase.
      $apiKey = $input_token;
    }
    if ($input_domain) {
      // Domain from Firebase.
      $dynamicLinkDomain = $input_domain;
    }

    $longLink = "$dynamicLinkDomain/?link=$long_link";

    try {
      $dynamicLink = new DynamicLinks([
        'api_key' => $apiKey,
        'dynamic_link_domain' => $dynamicLinkDomain,
      ]);
      // Short a long link.
      $short_url = $dynamicLink->shorten($longLink);
      // Get string short link.
      $short_url = $short_url->getShortLink();
    } catch (\Exception $e) {
      // Do nothing.
    }

    return $short_url;
  }

  /**
   * Get all channels.
   *
   * @return array
   *   The channles keyed by nid/title.
   */
  public static function getChannels() {
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'channel');
    $nids = $query->execute();
    $channels = array_flip($nids);

    foreach ($channels as $nid => $revision) {
      $node = Node::load($nid);
      if (isset($node)) {
        $title = $node->title->value;
        $channels[$nid] = $title;
      }
    }
    return $channels;
  }

  /**
   * Get Drupal Timezone.
   *
   * @return string
   *   The Timezone on Drupal server.
   */
  public static function getServerTimezone() {
    $config = \Drupal::config('system.date');
    $server_timezone = $config->get('timezone.default');
    return $server_timezone;
  }

  /**
   * Get current Drupal server date/time.
   *
   * @param string $format
   *   The format date.
   *
   * @return string
   *   The current drupal server date/time.
   */
  public static function getCurrentServerDate(string $format) {
    $server_timezone = static::getServerTimezone();
    $now = new DrupalDateTime('now', $server_timezone);
    // Get Drupal server date.
    $current_date = $now->format($format);
    return $current_date;
  }

  /**
   * Generate short url for nodes in batch operations.
   */
  public static function generateShortUrl() {
    // Get nids.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', [
        'programa',
        'special',
        'article',
        'video',
        'galeria_de_imagenes',
      ], 'IN')
      ->condition('status', 1)
      ->execute();
    if ($nids) {
      // Build batch operations, one per revision.
      $operations = [];
      foreach ($nids as $nid) {
        $operations[] = [
          [GenerateShorterUrlBatch::class, 'generateShorterUrlBatchProcess'],
          [$nid],
        ];
      }
      $batch = [
        'title' => t('Generating url shorter for nodes'),
        'operations' => $operations,
        'init_message' => t('Starting to generate shorter url for nodes.'),
        'progress_message' => t('Generated @current url shorter out of @total. Estimated time: @estimate.'),
        'finished' => [GenerateShorterUrlBatch::class, 'finish'],
      ];
      batch_set($batch);
    }
  }

  /**
   * Get $node_type content to fill in or to display by default for the
   * $content.
   *
   * @return array
   *   The content objects.
   */
  private function getDefaultContentForContexualMenu(string $content, array $current_entities, string $langcode) {
    $default_entities = [];
    if (!empty($content)) {
      switch ($content) {
        // A request to the home and search, show channels by default.
        case 'home':
        case 'search':
          $node_type = 'channel';
          break;
        // A request to some channel, show shows by default.
        case 'channel':
          $node_type = 'programa';
          break;
        // Any other request, show categories by default.
        // For example for show, video, etc.
        // For example for show, video, search, etc.
        default:
          $node_type = 'categories';
          break;
      }
      // Get $node_type content to fill in or to display by default for
      // the $content.
      $content_ids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', $node_type);
      if (!empty($current_entities)) {
        $content_ids->condition('nid', $current_entities, 'NOT IN');
      }

      $content_ids = $content_ids->execute();
      // Load the entities.
      foreach ($content_ids as $content_id) {
        /**
         * @var Node $content
         */
        $content = Node::load($content_id);
        if ($content->hasTranslation($langcode)) {
          $content = $content->getTranslation($langcode);
          $default_entities[] = $content;
        }
        else {
          if ($content->language()->getId() == $langcode) {
            $default_entities[] = $content;
          }
        }
      }
    }
    return $default_entities;
  }

  /**
   * @TODO Remove this when all contexual menu are migrated.
   *
   * Add contextual menu data to the response.
   *
   * @param array $entity
   *   Array of entities.
   *
   * @param array $result
   *  The REST response data result.
   */
  public function getContextualMenu(array $entities, array &$result, string $content, $langcode = '', $node_id = NULL) {
    $image_service = \Drupal::service('discovery_helper.get_image');

    // Get content to fill out or to display by default for the $content.
    $current_entities = [];
    foreach ($entities as $entity) {
      $current_entities[] = $entity->id();
    }
    $default_entities = $this->getDefaultContentForContexualMenu($content, $current_entities, $langcode);

    // Get/plus all items for contextual menu.
    $entities = $entities += $default_entities;

    // Get just the first 50 elements.
    $entities = array_slice($entities, 0, 50);

    $data = [];
    foreach ($entities as $element) {
      $logo = '';
      // Get logo and URL
      $node_type = $element->getType();
      $alias_service = \Drupal::service('discovery_helper.get_alias');
      if ($node_type == 'channel') {
        $logo = $image_service->get_contextual_menu_image($element);
        if (!is_null($element->field_channel_url->uri)) {
          $url = $element->field_channel_url->uri;
        }
        else {
          $url = $alias_service->get_alias($element->id(), $langcode);
        }
      }
      elseif ($node_type == 'programa') {
        $logo = $image_service->get_contextual_menu_image($element);
        $url = $alias_service->get_alias($element->id(), $langcode);
      }
      elseif ($node_type == 'categories') {
        $logo = $image_service->get_contextual_menu_image($element);
        $url = $alias_service->get_alias($element->id(), $langcode);
      }

      $title = mb_convert_encoding($element->title->value, 'UTF-8', 'UTF-8');
      // Use the title as key to order the array alphabetically.
      $data[$title] = [
        'logo'  => $logo,
        'title' => $title,
        'url'   => $url,
      ];
    }

    // Get data for response.
    if (!empty($data)) {
      // Order data alphabetically.
      ksort($data);

      // Reset the numeric array indexes again.
      $data_ordered = [];
      foreach ($data as $d) {
        $data_ordered[] = $d;
      }

      $result['contextual_menu']['content'] = $data_ordered;
      if (isset($node_id)) {
        $node = Node::load($node_id);
        if (isset($node->field_label_contextual_menu->value)) {
          $label = $node->field_label_contextual_menu->value;
          $result['contextual_menu']['label'] = $label;
        }
      }
    }
  }

  /**
   * Add all paragraphs/components data to the Response.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity object.
   *
   * @param array $result
   *  The REST response data result.
   */
  public static function addDataComponents(ContentEntityInterface $entity, array &$result) {
    \Drupal::service('plugin.manager.component')->getDataComponents($entity, $result);
  }

}


<?php

namespace Drupal\discovery_adjustments;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\discovery_adjustments\Utility\DiscoveryHelper;

/**
 * Defines a video manager.
 */
class VideoManager extends ControllerBase {

  /**
   * Remove label from videos if there is a defined date for deletion.
   */
  public function removeLabel() {
    // Get all candidate videos to remove the Label.
    // Getting the entity query instance.
    $query = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'video')
      ->exists('field_label_new')
      ->exists('field_label_expire');

    // Check date to remove the Label.
    $now = new DrupalDateTime();
        // Get Drupal server date.
    $server_timezone = DiscoveryHelper::getServerTimezone();
    $now->setTimezone(new \DateTimeZone($server_timezone));
    $query->condition('field_label_expire', $now->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), '<');
    $nids = $query->execute();

    // Reset field label and date to empty.
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        $node->set('field_label_new', NULL);
        $node->set('field_label_expire', NULL);
        $node->save();
      }
    }
  }

}

<?php

namespace Drupal\discovery_adjustments;

use Drupal\discovery_adjustments\Utility\DiscoveryHelper;

/**
 * Methods for generate short url in a batch.
 */
class GenerateShorterUrlBatch {

  /**
   * Implements callback_batch_operation().
   *
   * Generate short url for the node with $nid.
   *
   * @param $nid
   *   The node ID.
   * @param array $context
   *   An array of contextual key/values.
   */
  public static function generateShorterUrlBatchProcess($nid, &$context) {
    if (empty($context['results'])) {
      $context['results']['short_url'] = 0;
    }
    // Just load and save the node so that the url_shorter is updated.
    // See discovery_adjustments_node_presave.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($node) {
      $node->save();
      // Count the number of short url.
      $context['results']['short_url']++;
    }
  }

  /**
   * Finish batch for url shorter.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of the operations that had not been completed by the batch API.
   */
  public static function finish($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    $logger = \Drupal::logger('short_url');

    if ($success) {
      $message_seccess = $results['short_url'] .' '. t('urls shorter generated for nodes');
      $logger->notice($message_seccess);
      $messenger->addMessage($message_seccess);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE)
      ]);
      $logger->error($message);
      $messenger->addError($message);
    }
  }

}

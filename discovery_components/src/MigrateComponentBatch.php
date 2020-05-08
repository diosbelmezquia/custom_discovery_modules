<?php

namespace Drupal\discovery_components;

use Drupal\node\NodeInterface;

/**
 * Methods for migrate components in batch.
 */
class MigrateComponentBatch {

  /**
   * Implements callback_batch_operation().
   *
   * Migrate new components/paragraphs in the node with $nid.
   *
   * @param $data
   *   Data with nid, old_components and plugin_id.
   * @param array $context
   *   An array of contextual key/values.
   */
  public static function migrateComponentBatchProcess($data, &$context) {
    if (empty($context['results'])) {
      $context['results']['component'] = 0;
    }
    try {
      // Migrate components.
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($data['nid']);
      if ($node) {
        $component_manager = \Drupal::service('plugin.manager.component');
        // Get translated node for current node.
        $nodes = [
          'es' => self::getTranslatedNode($node, 'es'),
          'pt-br' => self::getTranslatedNode($node, 'pt-br'),
        ];
        // Reset values.
        foreach ($nodes as $langcode => $node_translated) {
          if ($node_translated) {
            // Get all fields detinations.
            foreach ($data['old_components'] as $old_component) {
              $field_destination = $old_component['field_destination'];
              //$node_translated->{$field_destination}->setValue([]);
            }
            $nodes[$langcode] = $node_translated;
          }
        }
        // Migrate all new components based on old components.
        foreach ($data['old_components'] as $old_component) {
          // Migrate $old_component for new $plugin_id component in $node.
          $component_manager->createInstance($data['plugin_id'])->processMigrate($old_component, $nodes);
          // Count the number of component migrated.
          $context['results']['component']++;
        }
        $context['results']['title'][] = $node->title->value;
      }
    } catch (\Exception $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }


  public static function migrateContextualMenuBatchProcess($data, &$context) {
    if (empty($context['results'])) {
      $context['results']['component'] = 0;
    }
    try {
      // Migrate components.
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($data['nid']);
      if ($node) {
        $component_manager = \Drupal::service('plugin.manager.component');
        // Get translated node for current node.
        $nodes = [
          'es' => self::getTranslatedNode($node, 'es'),
          'pt-br' => self::getTranslatedNode($node, 'pt-br'),
        ];
        // Migrate contextual_menu in $node.
        $component_manager->createInstance('contextual_menu')->migrateComponent([], $nodes);
        // Count the number of component migrated.
        $context['results']['component']++;
        $context['results']['title'][] = $node->title->value;
      }
    } catch (\Exception $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
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
    $logger = \Drupal::logger('component');

    if ($success) {
      $message_seccess = $results['component'].' '. t('components migrated for this content:');
      $logger->notice($message_seccess);
      $messenger->addMessage($message_seccess);

      // Get node titles.
      $node_titles = implode(', ', $results['title']);
      $messenger->addMessage($node_titles);
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

  /**
   * Get Node traduction.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get traduction.
   * @param string $langcode
   *   The languages.
   *
   * @return $node.
   *   The node translated.
   */
  public static function getTranslatedNode(NodeInterface $node, string $langcode) {
    if ($node->hasTranslation($langcode)) {
      return $node->getTranslation($langcode);
    }
    return NULL;
  }

}

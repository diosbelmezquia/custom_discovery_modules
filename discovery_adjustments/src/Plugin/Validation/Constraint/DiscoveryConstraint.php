<?php

namespace Drupal\discovery_adjustments\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for discovery site.
 *
 * @Constraint(
 *   id = "discovery_constraint",
 *   label = @Translation("Discovery Constraint.", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class DiscoveryConstraint extends Constraint {

  /**
   * The violation message for search content creations.
   *
   * @var string
   */
  public $search_message = 'Only two search contents can be created, edit the current contents if you need to make changes.';

  /**
   * The violation message for content field duplicate.
   * Add entities fields as array key.
   */
  public $content_duplicate = [
    'langcode' => 'There is already a search content for this language, select another language for this content.',
    'field_category_term' =>  'There is already a category content with this category, select another category for this content.',
  ];

}

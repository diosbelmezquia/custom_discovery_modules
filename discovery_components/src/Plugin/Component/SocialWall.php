<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Provides a 'Social Wall' component.
 *
 * @Component(
 *   id = "social_wall",
 *   label = @Translation("Social Wall"),
 *   background_color = "#77e01036"
 * )
 */
class SocialWall extends ComponentBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $entity_type_manager, $serializer);
    $this->region = $this->requestStack->getCurrentRequest()->query->get('region');
  }

  /**
   * {@inheritdoc}
   */
  public function getData(ParagraphInterface $paragraph) {
    // Get the referenced content.

    $social_networks_service = \Drupal::service('discovery_social_networks.get_social_networks_by_region');
    $social_networks = $social_networks_service->get_social_networks_from_paragraph($paragraph, $this->region);
    $social_wall['label'] = $paragraph->field_label->value;
    $social_wall['content'][] = $social_networks;

    return $social_wall;
  }

  /**
   * {@inheritdoc}
   */
  public function migrateComponent(array $fields, array $nodes) {
    return;
  }

}

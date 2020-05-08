<?php

namespace Drupal\discovery_components\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * MigrateComponentForm
 */
class MigrateComponentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'discovery_components_migrate_component';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['component'] = [
      '#type' => 'select',
      '#title' => $this->t('Migrate component'),
      '#options' => $this->getComponents(),
    ];

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => $this->getNodeTypes(),
      '#states' => [
        'invisible' => [
          ':select[name="component"]' => ['value' => 'contextual_menu'],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $component = $form_state->getValue('component');
    $node_type = $form_state->getValue('node_type');

    $component_manager = \Drupal::service('plugin.manager.component');
    try {
      $result = $component_manager->createInstance($component)->migrate($node_type);
    } catch (\Exception $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }

  public function getComponents() {
    $paragraphs_type = \Drupal\paragraphs\Entity\ParagraphsType::loadMultiple();

    // Get the component plugin manager.
    $component_manager = \Drupal::service('plugin.manager.component');

    $components = [];
    // Get the paragraphs type defiend as component.
    foreach ($paragraphs_type as $paragraph_type) {
      if ($component_manager->hasDefinition($paragraph_type->id())) {
        if(in_array($paragraph_type->id(), ['carousel', 'contextual_menu'])) {
          $components[$paragraph_type->id()] = $paragraph_type->label();
        }
      }
    }
    return $components;
  }

  public function getNodeTypes() {
    // Get all node types
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();

    $node_types_title = [];
    foreach ($node_types as $node_type) {
      if(in_array($node_type->id(), ['home', 'programa', 'channel'])) {
        $node_types_title[$node_type->id()] = $node_type->label();
      }
    }
    return $node_types_title;
  }

}

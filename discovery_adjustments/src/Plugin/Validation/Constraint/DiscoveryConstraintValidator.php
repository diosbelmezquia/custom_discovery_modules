<?php

namespace Drupal\discovery_adjustments\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Constraint validator for discovery site.
 */
class DiscoveryConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current request.
   */
  protected $currentRequest;

  /**
   * Creates a new DiscoveryConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->currentRequest = $this->requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }
    // Validate media on save/update operations.
    if ($entity instanceof MediaInterface) {
      switch ($entity->bundle()) {
        case 'image':
          // @TODO Do something on media image saved.
          break;
      }
      return;
    }
    // Validate nodes on save/update operations.
    elseif ($entity instanceof NodeInterface) {
      switch ($entity->getType()) {
        case 'categories':
          $this->validateCategoriesContent($entity, $constraint);
          break;
      }
      return;
    }
  }

  /**
   * Verify that only category type content is created with only 1 related category field.
   */
  private function validateCategoriesContent($entity, Constraint $constraint) {
    // Find the categories content have already been created.
    $categories = $this->getEntities('node', 'categories');

    // Prevent saving the same content categories with the same category field.
    $this->checkDuplicateField($categories, $entity, $constraint, 'field_category_term');
  }

  /**
   * Get the ids of the content entities defined by parameters.
   */
  private function getEntities(string $entity_type, string $content_type) {
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $query_result = $entity_storage->getQuery()
      ->condition('type', $content_type)
      ->execute();
    return $query_result;
  }

  /**
   * Verify that only 1 content with only one field $field_to_check is created.
   */
  private function checkDuplicateField(array $elements, $entity, Constraint $constraint, $field_to_check = NULL) {
    if ($elements && $field_to_check) {
      foreach ($elements as $nid) {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        $node_value   = $node->{$field_to_check}->value;
        $entity_value = $entity->{$field_to_check}->value;

        if ($node->{$field_to_check} instanceof EntityReferenceFieldItemListInterface) {
            $node_value   = $node->get($field_to_check)->target_id;
            $entity_value = $entity->get($field_to_check)->target_id;
        }
        if ($node_value == $entity_value) {
          // Do nothing if it is the same entity with the same field that is
          // being saved. Operation edit.
          if ($entity->id() && $entity->id() == $nid) {
            return;
          }
          // Show field message for $field_to_check.
          // See DiscoveryConstraint constraint.
          if (isset($constraint->content_duplicate[$field_to_check])) {
            $this->context->addViolation($constraint->content_duplicate[$field_to_check]);
          }
        }
      }
    }
  }

}

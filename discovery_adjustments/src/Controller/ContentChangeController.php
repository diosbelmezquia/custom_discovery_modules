<?php

namespace Drupal\discovery_adjustments\Controller;

use Drupal\node\Controller\NodeController;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\diff\Controller\NodeRevisionController;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
 * Class ContentChangeController.
 */
class ContentChangeController extends NodeController {

  /**
   * The previous revision for current node.
   */
  protected $previous_revision;

  /**
   * The last revision for current node.
   */
  protected $last_revision;

  /**
   * Define how show the fields/result.
   */
  const FILTER = 'split_fields';

  /**
   * Constructs a ContentChangeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\diff\Controller\NodeRevisionController $node_diff
   *   The node changes service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, NodeRevisionController $node_diff) {
    parent::__construct($date_formatter, $renderer);
    $this->node_diff = $node_diff;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('discovery_adjustments.diff_render')
    );
  }

  /**
   * Returns a table which shows the differences between two node revisions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose revisions are compared.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function getNodesDiff(NodeInterface $node) {
    $node_storage   = $this->entityTypeManager()->getStorage('node');
    // Set the revisions for $node.
    $this->getRevisionIds($node, $node_storage);

    // Get function of diff module to get the changes.
    $build = $this->node_diff->compareNodeRevisions($node, $this->previous_revision, $this->last_revision, self::FILTER);
    return $build;
  }

  /**
   * Return the last two revisions for current $node.
   */
  public function getRevisionIds(NodeInterface $node, NodeStorageInterface $node_storage) {
    // Get all revisions for current node.
    $all_revisions = parent::getRevisionIds($node, $node_storage);

    // Set the latest revisions vid.
    $this->last_revision = $all_revisions[0];
    $this->previous_revision = (count($all_revisions) == 1) ? $all_revisions[0] : $all_revisions[1];
  }

  /**
   * Get the preview image for media entity autocomplete.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A Json response containing the image element to show.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if no input value is specified.
   */
  public function getImgMediaAutocomplete(Request $request) {
    // Get queries from AJAX Request.
    $input = $request->request->get('input');
    $name = $request->request->get('name');
    if (!isset($input)) {
      throw new NotFoundHttpException();
    }
    // Get media id from title string.
    $mid = EntityAutocomplete::extractEntityIdFromAutocompleteInput($input);
    // Get media object.
    $media = Media::load($mid);
    // Return image just for media entities.
    if ($media && $media instanceof \Drupal\media\MediaInterface) {
      $fid = $media->field_media_image->target_id;
      $file = File::load($fid);

      if ($file) {
        // Get image with image style.
        $image_style = ImageStyle::load('media_library');
        $image_url = $image_style->buildUrl($file->getFileUri());
        $data = [
          'field_name' => $name,
          'image' => "<img src='" . $image_url . "'>",
        ];
        return new JsonResponse($data);
      }
    }
    // Return nothing.
    return new JsonResponse();
  }

  public function getNodeData(Request $request) {
    // Get queries from AJAX Request.
    $node_title = $request->request->get('node_title');
    if (!isset($node_title)) {
      throw new NotFoundHttpException();
    }
    // Get node id from title string.
    $nid = EntityAutocomplete::extractEntityIdFromAutocompleteInput($node_title);
    // Get node object.
    $node = Node::load($nid);
    // Return data for node entities.
    if ($node && $node instanceof NodeInterface) {
      $state = (boolean) $node->status->value == TRUE ? t('Published') : t('No Published');
      // Get regions for current content.
      $regiones = '';
      $regiones_array = [];
      if (is_null($node->field_region_term)) {
        $regiones = t('All');
      }
      else {
        foreach ($node->field_region_term as $region) {
          $regiones_array[] = $region->entity->name->value;
        }
        $regiones = implode(',', $regiones_array);
      }
      $data = [
        'status'  => $state,
        'type' => $node->getType(),
        'regions' => $regiones,
        'edit' => \Drupal\Core\Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], ['absolute' => TRUE])->toString(),
      ];
      return new JsonResponse($data);
    }
    // Return nothing.
    return new JsonResponse();
  }

}

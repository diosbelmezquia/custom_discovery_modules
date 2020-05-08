<?php

namespace Drupal\discovery_components\Plugin\Component;

use Drupal\discovery_components\Plugin\ComponentBase;
use Drupal\discovery_helper\Utility\DiscoveryContent;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 * Provides a 'Container Food' component.
 *
 * @Component(
 *   id = "container_food",
 *   label = @Translation("Container Food"),
 *   background_color = "#ff869823"
 * )
 */
class ContainerFood extends ComponentBase {

    /**
     * The amount of content to display in each carousel for each swipe.
     */
    const CANT_CONTENT = 12;

    /**
     * The carousel content.
     */
    protected $carousel_content;

    /**
     * The paragraph id.
     */
    protected $pid;

    /**
     * The number for where the carousel begin.
     */
    protected $start;

    /**
     * The node id.
     */
    protected $nid;

    /**
     * Content to exclude
     */
    protected $exclude;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $request_stack, $entity_type_manager, $serializer);

        // Get queries parameters for carousel.
        $this->pid = $this->requestStack->getCurrentRequest()->query->get('carousel');
        $this->nid = $this->requestStack->getCurrentRequest()->query->get('content');
        $this->start = $this->requestStack->getCurrentRequest()->query->get('start');
        $this->exclude = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ParagraphInterface $paragraph, $start = 0) {
        // Save the carousel data.
        $carousel_data = [];
        $this->getCarouselContent($paragraph, $carousel_data);
        // Get extra data if there is content.
        $this->getExtraData($paragraph, $carousel_data);

        return $carousel_data;
    }

    /**
     * Get the carousel content.
     *
     * @param \Drupal\paragraphs\ParagraphInterface $paragraph
     *   The paragraph entity.
     *
     * @param array
     *   The content for current component($paragraph).
     */
    private function getCarouselContent(ParagraphInterface $paragraph, array &$carousel_data) {
        // Get all carousel content data.
        if (!empty($paragraph->field_carousel_content)) {
            foreach ($paragraph->field_carousel_content as $node) {
                $node = Node::load($node->target_id);
                // If the content shown on the carousel is the same language as the parent content.
                if ($node->isPublished()) {
                    if ($node->language()->getId() == $paragraph->getParentEntity()
                            ->language()
                            ->getId()) {
                        $inRegion = $this->isInRegion($node, $this->region);
                        if ($inRegion['in_region']) {
                            $carousel_data['content'][] = $this->getContent($node, $paragraph->language()
                                ->getId(), 'destacados', $this->region);
                        }
                    }
                }
            }
        }


    }

    /**
     * Get extra data related to the carousel.
     *
     * @param \Drupal\paragraphs\ParagraphInterface $paragraph
     *   The paragraph entity.
     *
     * @param array
     *   Default data for current component($paragraph).
     */
    private function getExtraData(ParagraphInterface $paragraph, array &$carousel_data) {
        // Show the label only if there is content in the carousel.
        if ((isset($carousel_data['content'])) || ($paragraph->field_content_to_show->value) || ($paragraph->field_cross_content->value)) {
            $display = $paragraph->field_display_options->value;
            $parent_content = $paragraph->getParentEntity();
            // Get data.
            $carousel_data['label'] = $paragraph->field_label->value == "" ? "" : t($paragraph->field_label->value, [], ['langcode' => $this->langcode]);
            $carousel_data['see_more_url'] = $paragraph->field_see_more_url->value == "" ? "" : t($paragraph->field_see_more_url->value, [], ['langcode' => $this->langcode]);
            $carousel_data['show_content_as'] = $display;
            $carousel_data['carousel_id'] = $paragraph->id();
            $carousel_data['parent_content'] = $parent_content->id();
            $carousel_data['full_width'] = (boolean) $paragraph->field_full_width->value;
            // Show in cross content just for carousel display.
            if ($display == 'carousel') {
                $carousel_data['cross_content'] = $paragraph->field_cross_content->value == 1 ? 'true' : 'false';
            }
            if ($display == 'slider' && !$carousel_data['full_width']) {
                // Add ad to the response.
                $paragraph_ad = $paragraph->field_insert_ad->entity;
                if ($paragraph_ad) {
                    $plugin_manager = \Drupal::service('plugin.manager.component');
                    $carousel_data['ad'] = $plugin_manager->createInstance('ad_code')
                        ->getData($paragraph_ad, $parent_content);
                    $carousel_data['ad_position'] = $paragraph->field_ad_location->value == 0 ? 'right' : 'left';
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function migrateComponent(array $fields, array $nodes) {
        foreach ($nodes as $langcode => $node) {
            if ($node) {
                // Validate the fields.
                $fields_validated = $this->validateMigrationFields($fields, $node);
                // Create new Component/Paragraph.
                $paragraph = $this->createMigrateParagraph($fields_validated, $langcode);
                //  Update destination node.
                $this->updateDestinationNode($fields_validated, $node, $paragraph);
            }
        }
    }

}

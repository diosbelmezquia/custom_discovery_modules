<?php

namespace Drupal\discovery_adjustments\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle responses.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * Change the csv files to xls on Response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    // If there is some response returning csv files.
    //@TODO Check by requested url better.
    if ($response->headers->contains('Content-type', 'text/csv')) {
      // Change the file to Excel(xls) format.
      $response->headers->set('Content-Disposition', "attachment; filename=br-data.xls");
      $response->headers->set('Content-type', 'application/vnd.ms-excel');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }

}

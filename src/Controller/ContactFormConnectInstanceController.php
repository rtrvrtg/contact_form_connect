<?php
/**
 * @file
 * Contains \Drupal\contact\Controller\ContactController.
 */

namespace Drupal\contact_form_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\contact\ContactFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for contact routes.
 */
class ContactFormConnectInstanceController extends ControllerBase {
  /**
   * Form constructor for the contact form connect.
   *
   * @param \Drupal\contact\ContactFormInterface $contactForm
   *   The contact form to be modified.
   *
   * @return array
   *   The personal contact form as render array as expected by drupal_render().
   */
  public function contactFormConnectInstanceForm($contact_form = NULL) {
    $contactFormEntity = NULL;
    $contactFormName = NULL;

    if (empty($contact_form)) {
      // Use the default form if no form has been passed.
      $contactFormName = $this->config('contact.settings')
        ->get('default_form');
    }
    else {
      // Load the contact form by name.
      $contactFormName = $contact_form;
    }
    $contactFormEntity = $this->entityManager()
      ->getStorage('contact_form')
      ->load($contactFormName);

    // If there are no forms, do not display the form.
    if (empty($contactFormEntity)) {
      if ($this->currentUser()->hasPermission('administer contact forms')) {
        drupal_set_message($this->t('The contact form has not been configured. <a href=":add">Add one or more forms</a> .', array(
          ':add' => $this->url('contact.form_add'))), 'error');
        return array();
      }
      else {
        throw new NotFoundHttpException();
      }
    }

    $form = \Drupal::formBuilder()->getForm(
      'Drupal\contact_form_connect\Form\ContactFormConnectInstanceForm',
      ['contactFormEntity' => $contactFormEntity]
    );
    return $form;
  }
}

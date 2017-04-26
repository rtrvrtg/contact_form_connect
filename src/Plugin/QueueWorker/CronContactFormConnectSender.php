<?php
/**
 * @file
 * A Contact Form Connector that sends messages on CRON run.
 */
namespace Drupal\contact_form_connect\Plugin\QueueWorker;

/**
 * A Contact Form Connector that sends messages on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_contact_form_connect_send",
 *   title = @Translation("Contact Form Connect Sender"),
 *   cron = {"time" = 60}
 * )
 */
class CronContactFormConnectSender extends ContactFormConnectSendBase {}

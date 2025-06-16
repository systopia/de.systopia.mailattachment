<?php
/*-------------------------------------------------------+
| SYSTOPIA Mail Attachments Extension                    |
| Copyright (C) 2022 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

namespace Civi\Mailattachment\Form;

use Civi\Core\Event\GenericHookEvent;
use Civi\Mailattachment\AttachmentType\ContributionInvoice;
use Civi\Mailattachment\AttachmentType\FileOnServer;
use CRM_Mailattachment_ExtensionUtil as E;

class Attachments {

  /**
   * @param \CRM_Core_Form $form
   * @param array{prefix?: string, defaults?: array<string, mixed>} $context
   *
   * @return void
   * @throws \Exception
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public static function addAttachmentElements(&$form, $context = []) {
    // phpcs: enable
    $prefix = $context['prefix'] ?? '';
    $attachment_types = self::attachmentTypes($context);
    foreach ($attachment_types as &$attachment_type) {
      $controller = $attachment_type['controller'];
      if (is_callable([$controller, 'getAttachmentFormTemplate'])) {
        $attachment_type['form_template'] = $controller::getAttachmentFormTemplate();
        $attachment_type['help_template'] = $controller::getAttachmentFormTemplate('hlp');
      }
    }
    unset($attachment_type);

    /** @phpstan-var array<string, array<int, array<string, mixed>>> $attachments */
    $attachments = $form->get('attachments') ?? [];
    if (!isset($attachments[$prefix])) {
      $attachments[$prefix] = $context['defaults'] ?? [];
      $form->set('attachments', $attachments);
    }

    $ajax_action = \CRM_Utils_Request::retrieve('ajax_action', 'String');
    if (\CRM_Utils_Request::retrieve('ajax_attachments_prefix', 'String') === $prefix) {
      if ('remove_attachment' === $ajax_action) {
        $attachment_id = \CRM_Utils_Request::retrieve('ajax_attachment_id', 'String');
        unset($attachments[$prefix][$attachment_id]);
      }
      if ('add_attachment' === $ajax_action) {
        $attachment_type = \CRM_Utils_Request::retrieve('ajax_attachment_type', 'String');
        $attachments[$prefix][] = ['type' => $attachment_type];
      }
      $form->set('attachments', $attachments);
    }

    $attachment_forms = (
        (method_exists($form, 'getTemplateVars'))
            ? $form->getTemplateVars('attachment_forms')
            // @phpstan-ignore method.deprecated
            : $form->get_template_vars('attachment_forms')
    ) ?? [];
    foreach ($attachments[$prefix] as $attachment_id => $attachment) {
      $attachment_type = $attachment_types[$attachment['type']] ?? NULL;
      if (!isset($attachment_type)) {
        throw new \RuntimeException(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
      }
      $controller = $attachment_type['controller'];
      $attachment_forms[$prefix][$attachment_id]['title'] = $attachment_type['label'];
      $attachment_forms[$prefix][$attachment_id]['elements'] = $controller::buildAttachmentForm(
      $form,
        $attachment_id,
        $prefix,
        $attachment
      );
      $attachment_forms[$prefix][$attachment_id]['form_template'] = $attachment_type['form_template'] ?? NULL;
      $attachment_forms[$prefix][$attachment_id]['help_template'] = $attachment_type['help_template'] ?? NULL;
      $form->add(
        'button',
        $prefix . 'attachments--' . $attachment_id . '_remove',
        E::ts('Remove attachment'),
        [
          'data-attachment_id' => $attachment_id,
          'class' => 'crm-mailattachment-attachment-remove',
        ]
      );
    }
    $form->assign('attachment_forms', $attachment_forms);

    $form->add(
        'select',
        $prefix . 'attachments_more_type',
        E::ts('Attachment type'),
        array_map(function ($attachment_type) {
            return $attachment_type['label'];
        }, $attachment_types),
        FALSE,
        [
          'class' => 'crm-mailattachment-attachment-more-type',
        ]
    );
    $form->add(
        'button',
        $prefix . 'attachments_more',
        E::ts('Add attachment'),
        [
          'class' => 'crm-mailattachment-attachment-more',
        ]
    );
    \Civi::resources()->addScriptFile(E::LONG_NAME, 'js/attachments.js');
    $formClasses = explode(' ', $form->getAttribute('class') ?? '');
    if (!in_array('crm-mailattachment-attachments-form', $formClasses, TRUE)) {
      $form->addClass('crm-mailattachment-attachments-form');
    }
    $form->assign('supports_attachments', TRUE);
  }

  /**
   * @param \CRM_Core_Form $form
   *
   * @phpstan-param array{prefix?: string} $context
   *
   * @return array<int, array<string, mixed>>
   * @throws \Exception
   */
  public static function processAttachments(&$form, $context = []) {
    $attachment_values = [];
    $prefix = $context['prefix'] ?? '';
    /** @phpstan-var array<string, array<int, array<string, mixed>>> $attachments */
    $attachments = $form->get('attachments');
    $attachment_types = self::attachmentTypes();
    foreach ($attachments[$prefix] as $attachment_id => $attachment) {
      $attachment_type = $attachment_types[$attachment['type']] ?? NULL;
      if (!isset($attachment_type)) {
        throw new \RuntimeException(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
      }
      $controller = $attachment_type['controller'];
      $attachment_values[$attachment_id] = $controller::processAttachmentForm(
        $form,
        $attachment_id,
        $prefix
      ) + ['type' => $attachment['type']];
    }
    return $attachment_values;
  }

  /**
   * Builds a list of registered attachment types.
   *
   * @phpstan-param array{
   *   entity_type?: string,
   *   entity_types?: list<string>
   * } $context
   *
   * @phpstan-return array<string, array{
   *   label: string,
   *   controller: string,
   *   context: ?array<string, mixed>
   * }>
   *   The list of registered attachment types, indexed by their internal name.
   *
   */
  public static function attachmentTypes($context = []) {
    $attachment_types = [];

    $attachment_types['file_on_server'] = [
      'label' => E::ts('File on Server'),
      'controller' => FileOnServer::class,
      'context' => [
        'entity_types' => ['contact', 'contribution', 'participant'],
      ],
    ];
    $attachment_types['invoice'] = [
      'label' => E::ts('Contribution Invoice'),
      'controller' => ContributionInvoice::class,
      'context' => [
        'entity_types' => ['contribution'],
      ],
    ];

    // Let other extensions provide attachment types.
    $event = GenericHookEvent::create(['attachment_types' => &$attachment_types]);
    \Civi::dispatcher()->dispatch('civi.mailattachment.attachmentTypes', $event);

    // Add supported entity types to context for not allowing e.g. generating invoices for contacts.
    return isset($context['entity_type']) ? array_filter($attachment_types, function($attachment_type) use ($context) {
      // @phpstan-ignore isset.offset
      return !isset($attachment_type['context']['entity_types'])
        || in_array($context['entity_type'], $attachment_type['context']['entity_types'], TRUE);
    }) : $attachment_types;
  }

  /**
   * get the mime type of the given file
   *
   * @param string $path
   *
   * @return string mime type
   */
  public static function getMimeType($path) {
    static $known_files = [];
    if (!isset($known_files[$path])) {
      $known_files[$path] = mime_content_type($path);
    }
    return $known_files[$path];
  }

}

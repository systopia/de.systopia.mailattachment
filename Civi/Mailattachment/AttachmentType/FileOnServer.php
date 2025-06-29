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

namespace Civi\Mailattachment\AttachmentType;

use Civi\Mailattachment\Form\Attachments;
use CRM_Mailattachment_ExtensionUtil as E;

class FileOnServer implements AttachmentTypeInterface {

  /**
   * {@inheritDoc}
   */
  public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = []) {
    $form->add(
        'text',
        $prefix . 'attachments--' . $attachment_id . '--path',
        E::ts('Attachment Path/URL'),
        ['class' => 'huge'],
        FALSE
    );

    $form->add(
        'text',
        $prefix . 'attachments--' . $attachment_id . '--name',
        E::ts('Attachment Name'),
        ['class' => 'huge'],
        FALSE
    );

    $form->setDefaults(
        [
          $prefix . 'attachments--' . $attachment_id . '--path' => $defaults['path'] ?? '',
          $prefix . 'attachments--' . $attachment_id . '--name' => $defaults['name'] ?? '',
        ]
    );

    return [
      $prefix . 'attachments--' . $attachment_id . '--path' => 'attachment-file_on_server-path',
      $prefix . 'attachments--' . $attachment_id . '--name' => 'attachment-file_on_server-name',
    ];
  }

  /**
   * @param string $type
   *
   * @return string|null
   */
  public static function getAttachmentFormTemplate($type = 'tpl') {
    return $type === 'hlp' ? 'Civi/Mailattachment/AttachmentType/FileOnServer.' . $type : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public static function processAttachmentForm(&$form, $attachment_id, $prefix = '') {
    $values = $form->exportValues();
    return [
      'path' => $values[$prefix . 'attachments--' . $attachment_id . '--path'],
      'name' => $values[$prefix . 'attachments--' . $attachment_id . '--name'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function buildAttachment($context, $attachment_values) {
    $file_context = [
      $context['entity_type'] => $context['entity_id'],
    ];
    switch ($context['entity_type']) {
      case 'contribution':
        $file_context['contact'] = \Civi\Api4\Contribution::get(FALSE)
          ->addWhere('id', '=', $context['entity_id'])
          ->addSelect('contact_id')
          ->execute()
          ->single()['contact_id'];
        break;

      case 'participant':
        $file_context['contact'] = \Civi\Api4\Participant::get(FALSE)
          ->addWhere('id', '=', $context['entity_id'])
          ->addSelect('contact_id')
          ->execute()
          ->single()['contact_id'];
        break;
    }
    $attachment_file = self::findAttachmentFile($file_context, $attachment_values['path']);
    if (is_string($attachment_file)) {
      $name_parts = explode('.', basename($attachment_file));
      $file_extension = end($name_parts);
      $name_parts = explode('.', $attachment_values['name']);
      if ([] !== $name_parts && end($name_parts) !== $file_extension) {
        $attachment_values['name'] .= '.' . $file_extension;
      }
      $attachment = [
        'fullPath' => $attachment_file,
        'mime_type' => Attachments::getMimeType($attachment_file),
        'cleanName' => '' === $attachment_values['name'] ? basename($attachment_file) : $attachment_values['name'],
      ];
    }
    return $attachment ?? NULL;
  }

  /**
   * Try to find the attachment #{$index} based on the file path
   *   and the contact
   *
   * @param array<string, string> $context
   *
   * @param string $path
   *
   * @return string|null
   *   full file path or null
   */
  protected static function findAttachmentFile($context, $path) {
    if ('' !== $path) {
      foreach ($context as $entity_type => $entity_id) {
        $path = preg_replace("/[{]{$entity_type}_id[}]/", $entity_id, $path) ?? '';
      }
      if (is_string($path) && is_readable($path) && !is_dir($path)) {
        return $path;
      }
    }
    return NULL;
  }

}

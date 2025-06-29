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

use CRM_Mailattachment_ExtensionUtil as E;

interface AttachmentTypeInterface {

  /**
   * TODO: Document what needs to be returned.
   *
   * @param \CRM_Core_Form $form
   * @param int $attachment_id
   * @param string $prefix
   * @param array<string, mixed> $defaults
   *
   * @return mixed
   */
  public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = []);

  /**
   * TODO: Document what needs to be returned.
   *
   * @param \CRM_Core_Form $form
   * @param int $attachment_id
   * @param string $prefix
   *
   * @return mixed
   */
  public static function processAttachmentForm(&$form, $attachment_id, $prefix = '');

  /**
   * @phpstan-param array{
   *   // The lowercase name of the CiviCRM entity type
   *   entity_type: string,
   *   // The ID of the CiviCRM entity
   *   entity_id: string,
   *   // An array of all entity IDs involved (e.g. in a batch)
   *   entity_ids: ?list<int>,
   *   // An array representation of the CiviCRM entity, e.g. as returned by the API
   *   entity: ?array<string, mixed>,
   *   // An array with extra information, e.g. related CiviCRM entities as an entity type - entity ID mapping
   *   extra: ?array<string, mixed>
   * } $context
   *
   * @param array{path: string, name: string, extra?: mixed} $attachment_values
   *
   * @return mixed
   */
  public static function buildAttachment($context, $attachment_values);

  /**
     * TODO: Optional pre-caching of attachments for a batch of entities to be
     *       used in self::buildAttachment() instead of slow generation
     *       one-by-one.
     *
     * @param $context
     * @param $attachment_values
     *
     * @return bool
     *   Whether the caching was successful.
     */
  // phpcs: disable Squiz.PHP.CommentedOutCode.Found
  // public static function preCacheAttachments($context, $attachment_values);
  // phpcs:enable

  /**
     * TODO: Inform attachment providers that things are done:
     *       - a batch of contacts
     *       - the entire task
     *       so that generated attachments can be cleaned up.
     *
     * Optional
     *
     * @param $context
     *
     * @return mixed
     */
  // phpcs: disable Squiz.PHP.CommentedOutCode.Found
  // public static function cleanUpAttachments($context);
  // phpcs:enable
}

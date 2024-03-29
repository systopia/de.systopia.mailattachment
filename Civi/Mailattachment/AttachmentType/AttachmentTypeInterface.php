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

namespace Civi\Mailattachment\AttachmentType;

use CRM_Mailattachment_ExtensionUtil as E;

interface AttachmentTypeInterface
{
    /**
     * TODO: Document what needs to be returned.
     *
     * @param \CRM_Core_Form $form
     * @param int $attachment_id
     * @param string $prefix
     * @param array $defaults
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
     * @param $context
     *   An array with the following keys:
     *   - "entity_type": The lowercase name of the CiviCRM entity type
     *   - "entity_id": The ID of the CiviCRM entity
     *   - "entity_ids": (optional) An array of all entity IDs involved (e.g. in a
     *       batch)
     *   - "entity": (optional) An array representation of the CiviCRM entity,
     *       e.g. as returned by the API
     *   - "extra": (optional) An array with extra information, e.g. related
     *       CiviCRM entities as an entity type - entity ID mapping, e.g.
     *       `['contact_id' => $contact_id]`
     *
     * @param $attachment_values
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
//    public static function preCacheAttachments($context, $attachment_values);

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
//    public static function cleanUpAttachments($context);
}

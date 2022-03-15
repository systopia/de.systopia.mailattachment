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

use Civi\Mailattachment\Form\Task\AttachmentsTrait;
use CRM_Mailattachment_ExtensionUtil as E;

class FileOnServer implements AttachmentTypeInterface
{

    /**
     * @param \CRM_Core_Form_Task $form
     *
     * @param int $attachment_id
     *
     * @return array
     */
    public static function buildAttachmentForm(&$form, $attachment_id)
    {
        $form->add(
            'text',
            'attachments--' . $attachment_id . '--path',
            E::ts('Attachment Path/URL'),
            ['class' => 'huge'],
            false
        );

        $form->add(
            'text',
            'attachments--' . $attachment_id . '--name',
            E::ts('Attachment Name'),
            ['class' => 'huge'],
            false
        );
        return [
            'attachments--' . $attachment_id . '--path' => 'attachment-file_on_server-path',
            'attachments--' . $attachment_id . '--name' => 'attachment-file_on_server-name',
        ];
    }

    public static function getAttachmentFormTemplate($type = 'tpl')
    {
        return $type == 'hlp' ? 'Civi/Mailattachment/AttachmentType/FileOnServer.' . $type : null;
    }

    public static function processAttachmentForm(&$form, $attachment_id)
    {
        $values = $form->exportValues();
        return [
            'path' => $values['attachments--' . $attachment_id . '--path'],
            'name' => $values['attachments--' . $attachment_id . '--name'],
        ];
    }

    public static function buildAttachment($context, $attachment_values)
    {
        $attachment_file = self::findAttachmentFile($context['entity_id'], $attachment_values['path']);
        if ($attachment_file) {
            $file_name = empty($attachment_values['name']) ? basename($attachment_file) : $attachment_values['name'];
            $attachment = [
                'fullPath' => $attachment_file,
                'mime_type' => AttachmentsTrait::getMimeType($attachment_file),
                'cleanName' => $file_name,
            ];
        }
        return $attachment ?? null;
    }

    /**
     * Try to find the attachment #{$index} based on the file path
     *   and the contact
     *
     * @param integer $contact_id
     *   contact ID
     *
     *   index
     *
     * @return string|null
     *   full file path or null
     */
    protected static function findAttachmentFile($contact_id, $path)
    {
        if (!empty($path)) {
            // replace {contact_id} token
            $path = preg_replace('/[{]contact_id[}]/', $contact_id, $path);
            if (is_readable($path) && !is_dir($path)) {
                return $path;
            }
        }
        return null;
    }

}
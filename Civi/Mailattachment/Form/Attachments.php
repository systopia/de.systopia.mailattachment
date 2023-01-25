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

namespace Civi\Mailattachment\Form;

use Civi\Core\Event\GenericHookEvent;
use Civi\FormProcessor\API\Exception;
use CRM_Mailattachment_ExtensionUtil as E;

class Attachments
{
    /**
     * @param \CRM_Core_Form $form
     * @param $context
     *
     * @return void
     * @throws \CRM_Core_Exception
     * @throws \Civi\FormProcessor\API\Exception
     */
    public static function addAttachmentElements(&$form, $context = [])
    {
        $prefix = $context['prefix'] ?? '';
        $attachment_types = self::attachmentTypes($context);
        foreach ($attachment_types as &$attachment_type) {
            /* @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
            $controller = $attachment_type['controller'];
            if (is_callable([$controller, 'getAttachmentFormTemplate'])) {
                $attachment_type['form_template'] = $controller::getAttachmentFormTemplate();
                $attachment_type['help_template'] = $controller::getAttachmentFormTemplate('hlp');
            }
        }
        unset($attachment_type);

        $defaults = $context['defaults'] ?? [];
        $attachments = $form->get('attachments') ?? [];
        if (!isset($attachments[$prefix])) {
          $attachments[$prefix] = !empty($defaults) ? $defaults : [];
          $form->set('attachments', $attachments);
        }

        $ajax_action = \CRM_Utils_Request::retrieve('ajax_action', 'String');
        if (\CRM_Utils_Request::retrieve('ajax_attachments_prefix', 'String') == $prefix) {
          if ($ajax_action == 'remove_attachment') {
            $attachment_id = \CRM_Utils_Request::retrieve('ajax_attachment_id', 'String');
            unset($attachments[$prefix][$attachment_id]);
          }
          if ($ajax_action == 'add_attachment') {
            $attachment_type = \CRM_Utils_Request::retrieve('ajax_attachment_type', 'String');
            $attachments[$prefix][] = ['type' => $attachment_type];
          }
          $form->set('attachments', $attachments);
        }

        $attachment_forms = $form->get_template_vars('attachment_forms') ?? [];
        foreach ($attachments[$prefix] as $attachment_id => $attachment) {
            if (!$attachment_type = $attachment_types[$attachment['type']] ?? null) {
                throw new Exception(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
            }
            /* @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
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
        if (!in_array('crm-mailattachment-attachments-form', $formClasses)) {
          $form->addClass('crm-mailattachment-attachments-form');
        }
        $form->assign('supports_attachments', true);
    }

    /**
     * @param \CRM_Core_Form $form
     *
     * @return array
     * @throws \Civi\FormProcessor\API\Exception
     */
    public static function processAttachments(&$form, $context = [])
    {
        $attachment_values = [];
        $prefix = $context['prefix'] ?? '';
        $attachments = $form->get('attachments');
        $attachment_types = self::attachmentTypes();
        foreach ($attachments[$prefix] as $attachment_id => $attachment) {
            if (!$attachment_type = $attachment_types[$attachment['type']] ?? null) {
                throw new Exception(E::ts('Unregistered attachment type %1', [1 => $attachment['type']]));
            }
            /* @var \Civi\Mailattachment\AttachmentType\AttachmentTypeInterface $controller */
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
     * @return array
     *   The list of registered attachment types, indexed by their internal name.
     *
     */
    public static function attachmentTypes($context = [])
    {
        $attachment_types = [];

        $attachment_types['file_on_server'] = [
            'label' => E::ts('File on Server'),
            'controller' => '\Civi\Mailattachment\AttachmentType\FileOnServer',
            'context' => [
                'entity_types' => ['contact', 'contribution', 'participant'],
            ],
        ];
        $attachment_types['invoice'] = [
            'label' => E::ts('Contribution Invoice'),
            'controller' => '\Civi\Mailattachment\AttachmentType\ContributionInvoice',
            'context' => [
                'entity_types' => ['contribution'],
            ],
        ];

        // Let other extensions provide attachment types.
        $event = GenericHookEvent::create(['attachment_types' => &$attachment_types]);
        \Civi::dispatcher()->dispatch('civi.mailattachment.attachmentTypes', $event);

      // Add supported entity types to context for not allowing e.g. generating invoices for contacts.
        return !empty($context['entity_type']) ? array_filter($attachment_types, function($attachment_type) use ($context) {
            return
                empty($attachment_type['context']['entity_types'])
                || in_array($context['entity_type'], $attachment_type['context']['entity_types']);
        }) : $attachment_types;
    }

    /**
     * get the mime type of the given file
     *
     * @param string $path
     *
     * @return string mime type
     */
    public static function getMimeType($path)
    {
        static $known_files = [];
        if (!isset($known_files[$path])) {
            $known_files[$path] = mime_content_type($path);
        }
        return $known_files[$path];
    }

}

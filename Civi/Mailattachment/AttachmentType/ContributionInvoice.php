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

use Civi\Mailattachment\Form\Attachments;
use CRM_Mailattachment_ExtensionUtil as E;

class ContributionInvoice implements AttachmentTypeInterface {

  /**
   * {@inheritDoc}
   */
  public static function buildAttachmentForm(&$form, $attachment_id, $prefix = '', $defaults = []) {
    $form->add(
        'text',
        $prefix . 'attachments--' . $attachment_id . '--name',
        E::ts('Attachment Name'),
        ['class' => 'huge'],
        FALSE
    );

    $form->setDefaults(
        [
          $prefix . 'attachments--' . $attachment_id . '--name' => $defaults['name'] ?? '',
        ]
    );

    return [
      $prefix . 'attachments--' . $attachment_id . '--name' => 'attachment-contribution_invoice-name',
    ];
  }

  public static function getAttachmentFormTemplate($type = 'tpl') {
    return $type == 'hlp' ? 'Civi/Mailattachment/AttachmentType/ContributionInvoice.' . $type : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public static function processAttachmentForm(&$form, $attachment_id, $prefix = '') {
    $values = $form->exportValues();
    return [
      'name' => $values[$prefix . 'attachments--' . $attachment_id . '--name'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function buildAttachment($context, $attachment_values) {
    // Generate an invoice.
    $params = ['output' => 'pdf_invoice', 'forPage' => 'confirmpage'];
    $invoice_html = \CRM_Contribute_Form_Task_Invoice::printPDF(
        [$context['entity_id']],
        $params,
        [$context['extra']['contact_id']]
    );
    $invoice_pdf = \CRM_Utils_PDF_Utils::html2pdf($invoice_html, 'invoice.pdf', TRUE);
    $tmp_file_path = tempnam(sys_get_temp_dir(), 'invoice-') . '.pdf';
    file_put_contents($tmp_file_path, $invoice_pdf);
    return [
      'fullPath' => $tmp_file_path,
      'mime_type' => Attachments::getMimeType($tmp_file_path),
      'cleanName' => $attachment_values['name'] ?: basename($tmp_file_path),
    ];
  }

}

<?php declare(strict_types = 1);

namespace Civi\Mailattachment\Tests\Support;

/**
 * Dummy controller used by tests via hook-provided attachment type.
 */
final class DummyAttachmentController {

  /**
   * @return array<string, mixed>
   */
  public static function processAttachmentForm(object $form, int $attachmentId, string $prefix): array {
    return [
      'processed' => TRUE,
      'attachment_id' => $attachmentId,
      'prefix' => $prefix,
    ];
  }

  public static function getAttachmentFormTemplate(string $suffix = ''): string {
    return $suffix === 'hlp' ? 'dummy-help.tpl' : 'dummy-form.tpl';
  }

  /**
   * @return array<empty>
   */
  public static function buildAttachmentForm(): array {
    return [];
  }

}

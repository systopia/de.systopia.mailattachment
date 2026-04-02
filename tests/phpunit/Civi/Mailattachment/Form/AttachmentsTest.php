<?php
declare(strict_types = 1);

namespace Civi\MailAttachment\Tests\Civi\Mailattachment\Form;

use Civi\Core\Event\GenericHookEvent;
use Civi\Mailattachment\Form\Attachments;
use Civi\MailAttachment\Tests\HeadlessSetup;
use Civi\MailAttachment\Tests\Support\DummyAttachmentController;
use Civi\MailAttachment\Tests\Support\DummyForm;
use Civi\MailAttachment\Tests\Support\DummyFormForAdd;
use Civi\Test\TransactionalInterface;

/**
 * @covers Civi\Mailattachment\Form\Attachments
 * @group headless
 */
final class AttachmentsTest extends HeadlessSetup implements TransactionalInterface {

  /**
   * @var (\Closure(\Civi\Core\Event\GenericHookEvent): void)|null
   */
  private $listener = NULL;

  public function setUp(): void {
    parent::setUp();

    // Register a dummy attachment type through the hook so we don't depend on real controllers.
    $this->listener = function (GenericHookEvent $event): void {
      // 1) magic property
      if (isset($event->attachment_types) && is_array($event->attachment_types)) {
        $event->attachment_types['dummy'] = [
          'label' => 'Dummy',
          'controller' => DummyAttachmentController::class,
          'context' => ['entity_types' => ['contact']],
        ];
        return;
      }

      $values = $event->getHookValues();
      if (isset($values['attachment_types']) && is_array($values['attachment_types'])) {
        $values['attachment_types']['dummy'] = [
          'label' => 'Dummy',
          'controller' => DummyAttachmentController::class,
          'context' => ['entity_types' => ['contact']],
        ];
        return;
      }

      if (method_exists($event, 'getParam')) {
        $types = $event->getParam('attachment_types');
        if (is_array($types)) {
          $types['dummy'] = [
            'label' => 'Dummy',
            'controller' => DummyAttachmentController::class,
            'context' => ['entity_types' => ['contact']],
          ];
          // If there is a setter, persist it
          if (method_exists($event, 'setParam')) {
            $event->setParam('attachment_types', $types);
          }
        }
      }
    };

    \Civi::dispatcher()->addListener('civi.mailattachment.attachmentTypes', $this->listener);
  }

  public function tearDown(): void {
    if (
    $this->listener !== NULL) {
      \Civi::dispatcher()->removeListener('civi.mailattachment.attachmentTypes', $this->listener);
    }
    parent::tearDown();
  }

  /**
   * @covers \Civi\Mailattachment\Form\Attachments::addAttachmentElements
   */
  public function testAddAttachmentElements_InitializesAttachments(): void {
    $form = new DummyFormForAdd();

    //@phpstan-ignore-next-line
    Attachments::addAttachmentElements($form, [
      'prefix' => 'p_',
      'defaults' => [
        ['type' => 'dummy'],
      ],
    ]);

    $attachments = $form->get('attachments');
    self::assertIsArray($attachments);
    self::assertArrayHasKey('p_', $attachments);
    self::assertSame([['type' => 'dummy']], $attachments['p_']);

    self::assertTrue($form->getTemplateVars('supports_attachments'));
    self::assertStringContainsString('crm-mailattachment-attachments-form', (string) $form->getAttribute('class'));
  }

  public function testProcessAttachments_ReturnsControllerValues(): void {
    $form = new DummyForm();

    $form->set('attachments', [
      '' => [
        0 => ['type' => 'dummy'],
        1 => ['type' => 'dummy'],
      ],
    ]);

    $result = Attachments::processAttachments($form, ['prefix' => '']);

    self::assertArrayHasKey(0, $result);
    self::assertArrayHasKey(1, $result);

    self::assertSame('dummy', $result[0]['type']);
    self::assertTrue($result[0]['processed']);
    self::assertSame(0, $result[0]['attachment_id']);
    self::assertSame('', $result[0]['prefix']);

    self::assertSame('dummy', $result[1]['type']);
    self::assertTrue($result[1]['processed']);
    self::assertSame(1, $result[1]['attachment_id']);
    self::assertSame('', $result[1]['prefix']);
  }

  public function testProcessAttachments_WithInvalidType_ThrowsException(): void {
    $form = new DummyForm();

    $form->set('attachments', [
      '' => [
        0 => ['type' => 'does_not_exist'],
      ],
    ]);

    $this->expectException(\RuntimeException::class);
    Attachments::processAttachments($form, ['prefix' => '']);
  }

  public function testAttachmentTypes_FiltersByEntityType(): void {
    $typesForContact = Attachments::attachmentTypes(['entity_type' => 'contact']);

    self::assertArrayHasKey('file_on_server', $typesForContact, 'file_on_server should support contact');
    self::assertArrayHasKey('dummy', $typesForContact, 'dummy type should be injected via hook and support contact');
    self::assertArrayNotHasKey('invoice', $typesForContact, 'invoice should not be available for contact');
  }

  public function testGetMimeType_ReturnsAString(): void {
    $tmp = tempnam(sys_get_temp_dir(), 'mime');
    file_put_contents($tmp, "hello\n");

    $mime1 = Attachments::getMimeType($tmp);
    $mime2 = Attachments::getMimeType($tmp);

    self::assertIsString($mime1);
    self::assertNotSame('', $mime1);
    self::assertSame($mime1, $mime2);

    unlink($tmp);
  }

}

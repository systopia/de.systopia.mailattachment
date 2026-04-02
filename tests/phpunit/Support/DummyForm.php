<?php declare(strict_types = 1);

namespace Civi\MailAttachment\Tests\Support;

/**
 * Minimal form stub
 */
final class DummyForm extends \CRM_Core_Form {

  /**
   * @var array<string, mixed> */
  private array $store = [];

  public function set($name, $value): void {
    $this->store[$name] = $value;
  }

  public function get($name): mixed {
    return $this->store[$name] ?? NULL;
  }

}

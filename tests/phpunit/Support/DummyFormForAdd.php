<?php declare(strict_types = 1);

namespace Civi\MailAttachment\Tests\Support;

use HTML_QuickForm_element;

final class DummyFormForAdd extends \CRM_Core_Form {

  /**
   * @var array<string, mixed>
   */
  private array $store = [];

  /**
   * @var array<string, mixed>
   */
  private array $tpl = [];

  /**
   * @var array<int, array<string, mixed>> $added
   */
  public array $added = [];

  /**
   * @var array<string, string|null>
   */
  private array $attr = ['class' => NULL];

  public function set($name, mixed $value): void {
    $this->store[(string) $name] = $value;
  }

  public function get($name): mixed {
    return $this->store[(string) $name] ?? NULL;
  }

  public function getTemplateVars($name = NULL): mixed {
    if ($name === NULL) {
      return $this->tpl;
    }

    return $this->tpl[(string) $name] ?? NULL;
  }

  public function assign($var, $value = NULL): void {
    $this->tpl[(string) $var] = $value;
  }

  /**
   * @param mixed $type
   * @param mixed $name
   * @param mixed $label
   * @param array $attributes
   * @param mixed $required
   * @param array $extra
   *
   * @return \HTML_QuickForm_element
   *
   * @phpstan-ignore missingType.iterableValue, missingType.iterableValue
   */
  public function &add(
    $type,
    $name,
    $label = '',
    $attributes = [],
    $required = FALSE,
    $extra = []
  ) {
    $this->added[] = [
      'type' => $type,
      'name' => $name,
      'label' => $label,
      'attrs' => $attributes,
    ];

    $element = new HTML_QuickForm_element();
    return $element;
  }

  public function getAttribute($attr) {
    return $this->attr[(string) $attr] ?? NULL;
  }

  public function addClass($className): void {
    $existing = $this->attr['class'] ?? '';
    $this->attr['class'] = trim($existing . ' ' . (string) $className);
  }

}

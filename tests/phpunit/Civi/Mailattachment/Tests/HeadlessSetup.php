<?php
declare(strict_types = 1);

namespace Civi\Mailattachment\Tests;

use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

class HeadlessSetup extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

}

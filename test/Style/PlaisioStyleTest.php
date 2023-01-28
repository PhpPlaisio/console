<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Style;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Application\PlaisioApplication;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test cases for class PlaisioStyle.
 */
class PlaisioStyleTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @return void
   */
  public function testLogging(): void
  {
    putenv(sprintf('PLAISIO_CONFIG_DIR=%s', __DIR__));

    $application = new PlaisioApplication();
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $tester->run(['command' => 'plaisio:style-test']);

    self::assertSame("level: debug
level: very verbose
level: verbose
level: info
 ! [NOTE] note
%s", trim($tester->getDisplay()));
    self::assertSame(0, $tester->getStatusCode());
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

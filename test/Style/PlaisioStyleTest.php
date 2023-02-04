<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Style;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Application\PlaisioApplication;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Path;

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
    copy(Path::join(__DIR__, 'plaisio-commands.xml'), 'plaisio-commands.xml');

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

    unlink('plaisio-commands.xml');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

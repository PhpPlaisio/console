<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Helper;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Application\PlaisioApplication;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Path;

/**
 * Test cases for class TwoPhaseWrite
 */
class TwoPhaseWriteTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case for method write.
   */
  public function testWrite(): void
  {
    copy(Path::join(__DIR__, 'TwoPhaseWriteTest', __FUNCTION__, 'plaisio-commands.xml'), 'plaisio-commands.xml');

    $application = new PlaisioApplication();
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $tester->run(['command'  => 'plaisio:two-phase-write-test',
                  'filename' => 'hello.txt']);

    self::assertSame("Wrote hello.txt
 File hello.txt is up-to-date
 Wrote hello.txt", trim($tester->getDisplay()));
    self::assertSame(0, $tester->getStatusCode());

    unlink('plaisio-commands.xml');
    unlink('hello.txt');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

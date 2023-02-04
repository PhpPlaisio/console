<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Command;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Application\PlaisioApplication;
use Plaisio\Console\Test\TestPlaisioKernel;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Path;

/**
 * Test cases for trait PlaisioKernelCommand.
 */
class PlaisioKernelCommandTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test kernel is set correctly.
   */
  public function testSetPlaisioKernel(): void
  {
    copy(Path::join(__DIR__, 'PlaisioKernelCommandTest', __FUNCTION__, 'plaisio-commands.xml'), 'plaisio-commands.xml');
    copy(Path::join(__DIR__, 'PlaisioKernelCommandTest', __FUNCTION__, 'plaisio-console.xml'), 'plaisio-console.xml');

    $application = new PlaisioApplication();
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $tester->run(['command' => 'plaisio:kernel-command-test']);

    self::assertSame(TestPlaisioKernel::class, $tester->getDisplay());
    self::assertSame(0, $tester->getStatusCode());

    unlink('plaisio-commands.xml');
    unlink('plaisio-console.xml');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

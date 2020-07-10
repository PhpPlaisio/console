<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Helper\TypeScript;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Application\PlaisioApplication;
use Symfony\Component\Console\Tester\ApplicationTester;
use Webmozart\PathUtil\Path;

/**
 * Test cases for class TypeScriptFixHelper.
 */
class TypeScriptFixHelperTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test fixing defining deps with global packages and local packages.
   */
  public function testFixDefineDeps(): void
  {
    putenv(sprintf('PLAISIO_CONFIG_DIR=%s', Path::join(__DIR__, __FUNCTION__)));

    $jsPath = Path::makeRelative(Path::join(__DIR__, __FUNCTION__, 'js', 'Test', 'Foo.js'), getcwd());
    $orgPath = Path::changeExtension($jsPath, 'org.js');
    $expectedPath = Path::changeExtension($jsPath, 'expected.js');
    copy($orgPath, $jsPath);

    $application = new PlaisioApplication();
    $application->setAutoExit(false);

    $tester = new ApplicationTester($application);
    $tester->run(['command' => 'plaisio:type-script-fixer',
                  'path'    => $jsPath]);

    self::assertSame(0, $tester->getStatusCode());
    self::assertFileEquals($expectedPath, $jsPath);

    echo $tester->getDisplay();

    unlink($jsPath);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

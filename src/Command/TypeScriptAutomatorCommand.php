<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\Assets\AssetsPlaisioXmlHelper;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Plaisio\Console\Helper\TypeScript\TypeScriptAutomatorHelper;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Command for automatically compiling and fixing of TypeScript files.
 */
class TypeScriptAutomatorCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The directory under root asset directory for JS files.
   *
   * @var string
   */
  public $jsDir = 'js';

  /**
   * The path to the JavScript asset directory.
   *
   * @var string
   */
  private $jsPath;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:type-script-automator')
         ->setDescription('Automatically compiles and fixes TypeScript files')
         ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the recompilation of all TypeScript files');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: TypeScript Automator');

    $force = Cast::toManBool($input->getOption('force'));

    $this->readResourceDir();
    $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
    $helper->automate($force);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the asset root directory (a.k.a. the resource directory).
   */
  private function readResourceDir(): void
  {
    $path         = PlaisioXmlUtility::plaisioXmlPath('assets');
    $helper       = new AssetsPlaisioXmlHelper($path);
    $rootAssetDir = $helper->queryAssetsRootDir();

    $this->jsPath = Path::join($rootAssetDir, $this->jsDir);
    if (!file_exists($this->jsPath))
    {
      throw new RuntimeException("JavaScript asset directory '%s' does not exists", $this->jsPath);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

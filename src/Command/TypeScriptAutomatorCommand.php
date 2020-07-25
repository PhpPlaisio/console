<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\Assets\AssetsPlaisioXmlHelper;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Plaisio\Console\Helper\TypeScript\TypeScriptAutomatorHelper;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Helper\DescriptorHelper;
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
         ->addOption('auto', 'a', InputOption::VALUE_NONE, 'Monitors the filesystem and automatically compiles and fixes TypeScript files')
         ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Removes compiled JavaScript files')
         ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the recompilation of all TypeScript files');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $auto   = Cast::toManBool($input->getOption('auto'));
    $delete = Cast::toManBool($input->getOption('delete'));
    $force  = Cast::toManBool($input->getOption('force'));
    if (!$auto && !$delete && !$force)
    {
      $helper = new DescriptorHelper();
      $helper->describe($output, $this);
    }
    else
    {
      $this->readResourceDir();

      $this->io->title('Plaisio: TypeScript Automator');

      if ($delete)
      {
        $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
        $helper->delete();
      }

      if ($force)
      {
        $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
        $helper->force();
      }

      if ($auto)
      {
        $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
        $helper->automate();
      }
    }

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
    if (!is_dir($this->jsPath))
    {
      throw new RuntimeException("JavaScript asset directory '%s' does not exists", $this->jsPath);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

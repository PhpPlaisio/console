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

/**
 * Command for automatically transpiling and fixing of TypeScript files.
 */
class TypeScriptAutomatorCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The path to the JavScript asset directory.
   *
   * @var string
   */
  private string $jsPath;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:type-script-automator')
         ->setDescription('Automatically transpiles and fixes TypeScript files')
         ->addOption('auto', 'a', InputOption::VALUE_NONE, 'Monitors the filesystem and automatically transpiles and fixes TypeScript files')
         ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Removes transpiled JavaScript files')
         ->addOption('force', 'f', InputOption::VALUE_NONE, 'Transpiles all TypeScript files')
         ->addOption('once', 'o', InputOption::VALUE_NONE, 'Transpiles TypeScript files when required only');
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
    $once   = Cast::toManBool($input->getOption('once'));
    if (!$auto && !$delete && !$force && !$once)
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
        $helper->once(true);
      }

      if ($auto)
      {
        $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
        $helper->automate();
      }

      if ($once)
      {
        $helper = new TypeScriptAutomatorHelper($this->io, $this->jsPath);
        $helper->once();
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
    $this->jsPath = $helper->queryAssetDir('js');
    if (!is_dir($this->jsPath))
    {
      throw new RuntimeException("JavaScript asset directory '%s' does not exists", $this->jsPath);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

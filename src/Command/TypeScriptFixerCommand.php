<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\Assets\AssetsPlaisioXmlHelper;
use Plaisio\Console\Helper\ConfigException;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Plaisio\Console\Helper\TypeScript\TypeScriptFixHelper;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Command for fixing from TypeScript generated JavaScript files as a proper AMD module according to Plaisio standards.
 */
class TypeScriptFixerCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The directory under root asset directory for JS files.
   *
   * @var string
   */
  public string $jsDir = 'js';

  /**
   * The file extension of JavaScript files.
   *
   * @var string
   */
  private string $jsExtension = 'js';

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
    $this->setName('plaisio:type-script-fixer')
         ->setDescription('Fixes from TypeScript generated JavaScript files as a proper AMD module according to Plaisio standards')
         ->addArgument('path', InputArgument::REQUIRED, 'The path to a JavaScript file or directory for recursive traversal');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   *
   * @throws ConfigException
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: TypeScript Fixer');

    $this->readResourceDir();
    $helper = new TypeScriptFixHelper($this->io, $this->jsPath);

    $path = Cast::toManString($input->getArgument('path'));
    if (is_file($path) && Path::hasExtension($path, $this->jsExtension))
    {
      $helper->fixJavaScriptFile($path);
    }
    elseif (is_dir($path))
    {
      $helper->fixJavaScriptFiles($path);
    }
    else
    {
      $this->io->error(sprintf("Path '%s' is not JavaScript file nor a directory", $path));
    }

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the asset root directory (a.k.a. the resource directory).
   *
   * @throws ConfigException
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

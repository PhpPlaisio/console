<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\Assets\AssetsPlaisioXmlHelper;
use Plaisio\Console\Helper\Assets\AssetsStore;
use Plaisio\Console\Helper\ConfigException;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Command for coping web assets from packages to the asset (a.k.a. resources) directory.
 */
class AssetsCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * File count.
   *
   * @var int[]
   */
  private array $count = ['new' => 0, 'current' => 0, 'old' => 0];

  /**
   * The asset root directory (a.k.a. teh resource directory). The parent directory for all asset types.
   *
   * @var string
   */
  private string $rootAssetDir;

  /**
   * The volatile asset store.
   *
   * @var AssetsStore
   */
  private AssetsStore $store;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:assets')
         ->setDescription('Copy web assets from packages to the asset (a.k.a. resource) directory');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: Assets');

    try
    {
      $this->readResourceDir();
      $this->createStore();
      $this->findAssets();
      $this->readCurrentAssets();
      $this->assetsRemoveObsolete();
      $this->assetsUpdateCurrent();
      $this->assetsAddNew();
      $this->writeCurrentAssets();

      $this->io->text(sprintf('Removed %d, updated %d, and added %d assets',
                              $this->count['old'],
                              $this->count['current'],
                              $this->count['new']));
    }
    catch (ConfigException $exception)
    {
      $this->io->error($exception->getMessage());
      $this->io->logVerbose($exception->getTraceAsString());

      return -1;
    }

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new assets.
   */
  private function assetsAddNew(): void
  {
    $assets = $this->store->plsDiffNew();
    foreach ($assets as $asset)
    {
      $pathSource = $this->composeSourcePath($asset);
      $pathDest   = $this->composeDestPath($asset);

      $this->io->logVerbose('Adding asset <fso>%s</fso>', $pathDest);

      $this->copyFile($pathSource, $pathDest);

      $this->store->insertRow('PLS_CURRENT', ['cur_id'       => null,
                                              'cur_type'     => $asset['ass_type'],
                                              'cur_base_dir' => $asset['ass_base_dir'],
                                              'cur_to_dir'   => $asset['ass_to_dir'],
                                              'cur_path'     => $asset['ass_path']]);
      $this->count['new']++;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes obsolete assets.
   */
  private function assetsRemoveObsolete(): void
  {
    $assets = $this->store->plsDiffObsolete();
    foreach ($assets as $asset)
    {
      $path = $this->composeDestPath($asset);
      if (file_exists($path))
      {
        $this->io->logVerbose('Removing obsolete asset <fso>%s</fso>', $path);

        unlink($path);

        $this->count['old']++;
      }

      $this->store->plsCurrentAssetsDeleteAsset($asset['ass_type'],
                                                $asset['ass_base_dir'],
                                                $asset['ass_to_dir'],
                                                $asset['ass_path']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates current assets.
   */
  private function assetsUpdateCurrent(): void
  {
    $assets = $this->store->plsDiffCurrent();
    foreach ($assets as $asset)
    {
      $pathSource = $this->composeSourcePath($asset);
      $pathDest   = $this->composeDestPath($asset);

      $source = file_get_contents($pathSource);
      if (file_exists($pathDest))
      {
        $dest = file_get_contents($pathDest);
      }
      else
      {
        $dest = null;
      }

      if ($dest!==$source)
      {
        $this->io->logVerbose('Updating asset <fso>%s</fso>', $pathDest);

        $this->copyFile($pathSource, $pathDest);

        $this->count['current']++;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Composes the destination path of an asset.
   *
   * @param array $asset The details of the asset.
   *
   * @return string
   */
  private function composeDestPath(array $asset): string
  {
    return Path::join($this->rootAssetDir, $asset['ass_type'], $asset['ass_to_dir'] ?? '', $asset['ass_path']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Composes the source path of an asset.
   *
   * @param array $asset The details of the asset.
   *
   * @return string
   */
  private function composeSourcePath(array $asset): string
  {
    return Path::join($asset['ass_base_dir'], $asset['ass_path']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Copies a file.
   *
   * @param string $pathSource The path the the source file.
   * @param string $pathDest   The path to the destination file.
   */
  private function copyFile(string $pathSource, string $pathDest): void
  {
    $dir = Path::getDirectory($pathDest);
    if (!file_exists($dir))
    {
      mkdir($dir, 0777, true);
    }

    copy($pathSource, $pathDest);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates the SQLite store.
   */
  private function createStore(): void
  {
    $this->store = new AssetsStore(null, __DIR__.'/../../lib/ddl/assets/0100_create_tables.sql');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds all assets all packages (using plaisio-assets.xml files).
   *
   * @throws ConfigException
   */
  private function findAssets(): void
  {
    // Assets supplied by packages.
    $plaisioXmlList = PlaisioXmlUtility::findPlaisioXmlPackages('assets');
    $collections    = [];
    foreach ($plaisioXmlList as $plaisioConfigPath)
    {
      $helper      = new AssetsPlaisioXmlHelper($plaisioConfigPath);
      $tmp         = $helper->queryAssetFileList();
      $collections = array_merge($collections, $tmp);
    }

    // Third party assets.
    $plaisioConfigPath = PlaisioXmlUtility::plaisioXmlPath('assets');
    $helper            = new AssetsPlaisioXmlHelper($plaisioConfigPath);
    $tmp               = $helper->queryOtherAssetFileList();
    $collections       = array_merge($collections, $tmp);

    foreach ($collections as $collection)
    {
      foreach ($collection['files'] as $file)
      {
        $this->io->logVerbose('Found asset <fso>%s</fso>', Path::join($collection['base-dir'], $file));

        $this->store->insertRow('PLS_ASSET', ['ass_id'       => null,
                                              'ass_type'     => $collection['type'],
                                              'ass_base_dir' => $collection['base-dir'],
                                              'ass_to_dir'   => $collection['to-dir'],
                                              'ass_path'     => $file]);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the current assets from the current assets metadata file.
   */
  private function readCurrentAssets(): void
  {
    $path = PlaisioXmlUtility::plaisioXmlPath('assets');
    $path = Path::changeExtension($path, 'csv');
    if (file_exists($path))
    {
      $handle = fopen($path, 'r');
      while (($data = fgetcsv($handle))!==false)
      {
        $this->store->insertRow('PLS_CURRENT', ['cur_id'       => null,
                                                'cur_type'     => $data[0],
                                                'cur_base_dir' => $data[1],
                                                'cur_to_dir'   => $data[2],
                                                'cur_path'     => $data[3]]);
      }
      fclose($handle);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the asset root directory (a.k.a. the resource directory).
   */
  private function readResourceDir(): void
  {
    $path               = PlaisioXmlUtility::plaisioXmlPath('assets');
    $helper             = new AssetsPlaisioXmlHelper($path);
    $this->rootAssetDir = $helper->queryAssetsRootDir();

    if (!file_exists($this->rootAssetDir))
    {
      throw new ConfigException("Asset root directory '%s' does not exists", $this->rootAssetDir);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes the current assets metadata to the filesystem.
   */
  private function writeCurrentAssets(): void
  {
    $path  = PlaisioXmlUtility::plaisioXmlPath('assets');
    $path1 = Path::changeExtension($path, 'tmp');
    $path2 = Path::changeExtension($path, 'csv');

    $assets = $this->store->plsCurrentAssetsGetAll();
    $handle = fopen($path1, 'w');
    foreach ($assets as $asset)
    {
      fputcsv($handle, [$asset['cur_type'], $asset['cur_base_dir'], $asset['cur_to_dir'], $asset['cur_path']]);
    }

    $new = file_get_contents($path1);
    if (file_exists($path2))
    {
      $old = file_get_contents($path2);
    }
    else
    {
      $old = null;
    }

    if ($new!==$old)
    {
      $this->io->logVerbose('Updating <fso>%s</fso>', $path2);

      rename($path1, $path2);
    }
    else
    {
      unlink($path1);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

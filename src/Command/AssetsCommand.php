<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\Assets\AssetsPlaisioXmlHelper;
use Plaisio\Console\Helper\Assets\AssetsStore;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use SetBased\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Command for coping web assets from packages to the asset (a.k.a. resources) directory.
 */
class AssetsCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * All possible assets types.
   *
   * @var string[]
   */
  private $assetTypes = ['css', 'images', 'js'];

  /**
   * File count.
   *
   * @var int[]
   */
  private $count = ['new' => 0, 'current' => 0, 'old' => 0];

  /**
   * The asset root directory (a.k.a. teh resource directory). The parent directory for all asset types.
   *
   * @var string
   */
  private $rootAssetDir;

  /**
   * The volatile asset store.
   *
   * @var AssetsStore
   */
  private $store;

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
      $pathSource = Path::join($asset['ass_base_dir'], $asset['ass_path']);
      $pathDest   = Path::join($this->rootAssetDir, $asset['ass_type'], $asset['ass_path']);

      $this->io->logVerbose('Adding asset <fso>%s</fso>', $pathDest);

      $dir = Path::getDirectory($pathDest);
      if (!file_exists($dir))
      {
        mkdir($dir, 0777, true);
      }
      copy($pathSource, $pathDest);

      $this->store->insertRow('PLS_CURRENT', ['cur_id'       => null,
                                              'cur_type'     => $asset['ass_type'],
                                              'cur_base_dir' => $asset['ass_base_dir'],
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
      $path = Path::join($this->rootAssetDir, $asset['ass_type'], $asset['ass_path']);
      if (file_exists($path))
      {
        $this->io->logVerbose('Removing obsolete asset <fso>%s</fso>', $path);

        unlink($path);

        $this->count['old']++;
      }

      $this->store->plsCurrentAssetsDeleteAsset($asset['ass_type'], $asset['ass_path'], $asset['ass_path']);
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
      $pathSource = Path::join($asset['ass_base_dir'], $asset['ass_path']);
      $pathDest   = Path::join($this->rootAssetDir, $asset['ass_type'], $asset['ass_path']);

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

        copy($pathSource, $pathDest);

        $this->count['current']++;
      }
    }
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
   */
  private function findAssets(): void
  {
    $plaisioXmlList = PlaisioXmlUtility::findPlaisioXmlAll('assets');
    foreach ($plaisioXmlList as $plaisioConfigPath)
    {
      $helper = new AssetsPlaisioXmlHelper($plaisioConfigPath);
      foreach ($this->assetTypes as $assetType)
      {
        $files = $helper->queryFileList($assetType);
        if (!empty($files))
        {
          foreach ($files['files'] as $file)
          {
            $this->io->logVerbose('Found asset <fso>%s</fso>', Path::join($files['base-dir'], $file));

            $this->store->insertRow('PLS_ASSET', ['ass_id'       => null,
                                                  'ass_type'     => $files['type'],
                                                  'ass_base_dir' => $files['base-dir'],
                                                  'ass_path'     => $file]);
          }
        }
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
                                                'cur_path'     => $data[2]]);
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
      throw new RuntimeException("Asset root directory '%s' does not exists", $this->rootAssetDir);
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
      fputcsv($handle, [$asset['cur_type'], $asset['cur_base_dir'], $asset['cur_path']]);
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

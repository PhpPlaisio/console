<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\Assets;

use Plaisio\Console\Helper\SourceFinderHelper;
use SetBased\Exception\RuntimeException;
use Webmozart\PathUtil\Path;

/**
 * Helper class for retrieving information from plaisio-assets.xml files.
 */
class AssetsPlaisioXmlHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The path to the plaisio.xml file.
   *
   * @var string
   */
  protected $path;

  /**
   * The XML of the plaisio.xml.
   *
   * @var \DOMDocument
   */
  protected $xml;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * PlaisioXmlHelper constructor.
   *
   * @param string $path Path to the PhpPlaisio configuration file.
   */
  public function __construct(string $path)
  {
    $this->path = $path;

    try
    {
      $this->xml = new \DOMDocument();
      $this->xml->load($this->path, LIBXML_NOWARNING);
    }
    catch (\Throwable $exception)
    {
      throw new RuntimeException([$exception], 'Failed to read Plaisio config %s', $this->path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the file lists of an asset type.
   *
   * @param string $type The asset type (js, css, or image).
   *
   * @return array
   */
  public function queryFileList(string $type): array
  {
    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query(sprintf('/assets/asset[@type="%s"]', $type));
    $files = [];
    if ($list->length==1)
    {
      $dir = $list->item(0)->getAttribute('dir');
      if ($dir==='')
      {
        throw new RuntimeException("Attribute '%s' of '/assets/asset/%s' in '%s' not set", 'dir', $type, $this->path);
      }

      $files = $this->queryFileListHelper($type, $dir);
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the pattern lists of an asset type.
   *
   * @param string $type The asset type (js, css, or image).
   * @param string $dir
   *
   * @return array
   */
  public function queryFileListHelper(string $type, string $dir): array
  {
    $baseDir  = Path::join(dirname($this->path), $dir);
    $patterns = [];
    $xpath    = new \DOMXpath($this->xml);
    $list     = $xpath->query(sprintf('/assets/asset[@type="%s"]/include', $type));
    foreach ($list as $item)
    {
      $name = $item->getAttribute('name');
      if ($name==='')
      {
        throw new RuntimeException("Attribute '%s' of '/assets/asset[@type=%s]/include' in '%s' not set",
                                   'name',
                                   $type,
                                   $this->path);
      }

      $patterns[] = $name;
    }

    $helper = new SourceFinderHelper($baseDir);
    $files  = $helper->findFiles($patterns);
    foreach ($files as $key => $file)
    {
      $files[$key] = Path::makeRelative($file, $baseDir);
    }

    return ['type'     => $type,
            'base-dir' => $baseDir,
            'files'    => $files];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the root asset directory (a.k.a. resource directory).
   *
   * @return string
   */
  public function queryAssetsRootDir(): string
  {
    $xpath = new \DOMXpath($this->xml);
    $node  = $xpath->query('/assets/root')->item(0);

    if ($node===null)
    {
      throw new RuntimeException('Root asset directory not defined in %s', $this->path);
    }

    return $node->nodeValue;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------


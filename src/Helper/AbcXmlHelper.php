<?php

namespace SetBased\Abc\Console\Helper;

use Composer\Composer;

/**
 * Helper class for retrieving information about abc.xml files.
 */
class AbcXmlHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The path to the abc.xml file.
   *
   * @var string
   */
  private $path;

  /**
   * The XML of the abc.xml.
   *
   * @var \DOMDocument
   */
  private $xml;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * AbcXmlHelper constructor.
   *
   * @param string $path The path to the abc.xml file.
   */
  public function __construct($path)
  {
    $this->path = $path;

    $this->xml = new \DOMDocument();
    $success   = $this->xml->load($path, LIBXML_NOWARNING);
    if (!$success)
    {
      throw new \RuntimeException('Unable to parse the XML file.');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a list of abc.xml files of all installed packages.
   *
   * @param Composer $composer The composer object.
   *
   * @return string[]
   */
  public static function getAbcXmlOfInstalledPackages($composer)
  {
    $list = [];

    $cwd = getcwd();

    $repositoryManager   = $composer->getRepositoryManager();
    $installationManager = $composer->getInstallationManager();
    $localRepository     = $repositoryManager->getLocalRepository();

    $packages = $localRepository->getPackages();
    foreach ($packages as $package)
    {
      $installPath = $installationManager->getInstallPath($package);
      $path        = $installPath.'/abc.xml';
      if (is_file($path))
      {
        if (strncmp($path, $cwd, strlen($cwd))===0)
        {
          $path = ltrim(substr($path, strlen($cwd)), DIRECTORY_SEPARATOR);
        }
        $list[] = $path;
      }
    }

    sort($list);

    return $list;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @return string
   */
  public function getStratumConfigFilename()
  {
    $xpath = new \DOMXpath($this->xml);
    $node  = $xpath->query('/abc/stratum/config')->item(0);

    if ($node===null)
    {
      throw new \RuntimeException(sprintf('Stratum configuration file not defined in %s', $this->path));
    }

    return $node->nodeValue;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the Stratum source patterns for finding store routines.
   *
   * @return string[]
   */
  public function getStratumSourcePatterns()
  {
    $patterns = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/abc/stratum/includes/include');
    foreach ($list as $item)
    {
      $patterns[] = $item->nodeValue;
    }

    return $patterns;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

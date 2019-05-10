<?php
declare(strict_types=1);

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
  protected $path;

  /**
   * The XML of the abc.xml.
   *
   * @var \DOMDocument
   */
  protected $xml;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * AbcXmlHelper constructor.
   *
   * @param string $path The path to the abc.xml file.
   */
  public function __construct(string $path)
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
  public static function getAbcXmlOfInstalledPackages(Composer $composer): array
  {
    $list = [];

    $repositoryManager   = $composer->getRepositoryManager();
    $installationManager = $composer->getInstallationManager();
    $localRepository     = $repositoryManager->getLocalRepository();

    $packages = $localRepository->getPackages();
    foreach ($packages as $package)
    {
      $installPath = $installationManager->getInstallPath($package);
      $path        = $installPath.DIRECTORY_SEPARATOR.'abc.xml';
      if (is_file($path))
      {
        $list[$package->getName()] = self::relativePath($path);
      }
    }

    asort($list);

    return $list;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the relative path of path if the path is below the cwd. Otherwise returns the path unmodified.
   *
   * @param string $path The path.
   *
   * @return string
   */
  private static function relativePath(string $path): string
  {
    $cwd = getcwd();

    if (strncmp($path, $cwd, strlen($cwd))===0)
    {
      return ltrim(substr($path, strlen($cwd)), DIRECTORY_SEPARATOR);
    }

    return $path;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns all commands found in any abc.xml under the current project.
   *
   * @return string[]
   */
  public function findAbcCommands(): array
  {
    $commands = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/abc/commands/command');
    foreach ($list as $item)
    {
      /** @var \DOMElement $item */
      $name            = $item->getAttribute('name');
      $class           = $item->nodeValue;
      $commands[$name] = function () use ($class) {
        return new $class;
      };
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @return string
   */
  public function getStratumConfigFilename(): string
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
  public function getStratumSourcePatterns(): array
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

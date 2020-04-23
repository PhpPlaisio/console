<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use DirectoryIterator;

/**
 * Helper class for retrieving information about plaisio.xml files.
 */
class PlaisioXmlHelper
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
   * @param string $path The path to the plaisio.xml file.
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
   * Returns a list of plaisio.xml files of all installed packages and of the current project.
   *
   * @param string $vendorDir Path to vendor directory.
   *
   * @return string[]
   */
  public static function getAllPlaisioXml(string $vendorDir): array
  {
    $list = self::getPlaisioXmlOfInstalledPackages($vendorDir);

    $plaisioConfigPath = $_ENV['PLAISIO_CONFIG'] ?? dirname($vendorDir).'/plaisio.xml';
    if (is_file($plaisioConfigPath))
    {
      $list[] = $plaisioConfigPath;
    }

    return $list;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a list of plaisio.xml files of all installed packages.
   *
   * @param string $vendorDir Path to vendor directory.
   *
   * @return string[]
   */
  public static function getPlaisioXmlOfInstalledPackages(string $vendorDir): array
  {
    $list = [];

    $directories1 = new DirectoryIterator($vendorDir);
    foreach ($directories1 as $item1)
    {
      if ($item1->isDir() && !$item1->isDot())
      {
        $directories2 = new DirectoryIterator($item1->getPathname());
        foreach ($directories2 as $item2)
        {
          if ($item2->isDir() && !$item2->isDot())
          {
            $path = $item2->getPathname().DIRECTORY_SEPARATOR.'plaisio.xml';
            if (is_file($path))
            {
              $list[] = self::relativePath($path);
            }
          }
        }
      }
    }

    sort($list);

    return $list;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the path to the vendor directory.
   *
   * @return string
   */
  public static function vendorDir(): string
  {
    return dirname(dirname(dirname(dirname(__DIR__))));
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
   * Returns all commands found in any plaisio.xml under the current project.
   *
   * @return array
   */
  public function findPlaisioCommands(): array
  {
    $commands = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/plaisio/commands/command');
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
   * The path to the plaisio.xml file.
   *
   * @return string
   */
  public function getPathOfPlaisioXml(): string
  {
    return $this->path;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @return string
   */
  public function getStratumConfigFilename(): string
  {
    $xpath = new \DOMXpath($this->xml);
    $node  = $xpath->query('/plaisio/stratum/config')->item(0);

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
    $list  = $xpath->query('/plaisio/stratum/includes/include');
    foreach ($list as $item)
    {
      $patterns[] = $item->nodeValue;
    }

    return $patterns;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

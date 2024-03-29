<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use DirectoryIterator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility class for querying paths to plaisio.xml files.
 */
class PlaisioXmlPathHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a list of paths to plaisio-*.xml files of all installed packages and of the current project.
   *
   * @param string $section The section of PhpPlaisio configuration.
   *
   * @return string[]
   */
  public static function findPlaisioXmlAll(string $section): array
  {
    $list = self::findPlaisioXmlPackages($section);

    $plaisioConfigPath = self::plaisioXmlPath($section);
    if (is_file($plaisioConfigPath))
    {
      $list[] = $plaisioConfigPath;
    }

    return $list;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a list of paths to plaisio.xml files of all installed packages.
   *
   * @param string $section The section of PhpPlaisio configuration.
   *
   * @return string[]
   */
  public static function findPlaisioXmlPackages(string $section): array
  {
    $list = [];

    $directories1 = new DirectoryIterator(self::vendorDir());
    foreach ($directories1 as $item1)
    {
      if ($item1->isDir() && !$item1->isDot())
      {
        $directories2 = new DirectoryIterator($item1->getPathname());
        foreach ($directories2 as $item2)
        {
          if ($item2->isDir() && !$item2->isDot())
          {
            $path = sprintf('%s%splaisio-%s.xml', $item2->getPathname(), DIRECTORY_SEPARATOR, $section);
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
   * Returns the path to plaisio.xml of the current project.
   *
   * @param string $section The section of PhpPlaisio configuration.
   *
   * @return string
   */
  public static function plaisioXmlPath(string $section): string
  {
    return sprintf('%s%s%s-%s.xml', dirname(self::vendorDir()), DIRECTORY_SEPARATOR, 'plaisio', $section);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the relative path of path if the path is below the cwd. Otherwise, returns the path unmodified.
   *
   * @param string $path The path.
   *
   * @return string
   */
  public static function relativePath(string $path): string
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
   * Returns the path to the vendor directory.
   *
   * @return string
   */
  public static function vendorDir(): string
  {
    $reflection = new \ReflectionClass(Filesystem::class);

    return self::relativePath(dirname($reflection->getFileName(), 3));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

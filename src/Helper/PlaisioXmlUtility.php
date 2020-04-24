<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use DirectoryIterator;

/**
 * Utility class for retrieving information about plaisio.xml files.
 */
class PlaisioXmlUtility
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a list of paths to plaisio.xml files of all installed packages and of the current project.
   *
   * @return string[]
   */
  public static function findPlaisioXmlAll(): array
  {
    $list = self::findPlaisioXmlPackages();

    $plaisioConfigPath = self::plaisioXmlPath();
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
   * @return string[]
   */
  public static function findPlaisioXmlPackages(): array
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
   * Returns the path to plaisio.xml of the current project.
   *
   * @return string
   */
  public static function plaisioXmlPath(): string
  {
    $path = getenv('PLAISIO_CONFIG');

    if ($path===false)
    {
      $path = dirname(self::vendorDir()).'/plaisio.xml';
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
    return self::relativePath(dirname(dirname(dirname(dirname(__DIR__)))));
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
}

//----------------------------------------------------------------------------------------------------------------------

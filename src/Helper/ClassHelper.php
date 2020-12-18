<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use Composer\Autoload\ClassLoader;
use SetBased\Exception\RuntimeException;

/**
 * Utility class for classes.
 */
class ClassHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the first line of the class declaration in the source code of a class.
   *
   * @param string[] $lines The source code of the class.
   * @param string   $class The name of the class.
   *
   * @return int
   */
  public static function classDeclarationLine(array $lines, string $class): int
  {
    $pattern = sprintf('/(\w\s+)?class\s+%s/', preg_quote($class));
    $line    = self::searchPattern($lines, $pattern);

    if ($line===null)
    {
      throw new RuntimeException('Unable to find declaration of class %s', $class);
    }

    return $line;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the path to the source file of a class.
   *
   * @param string $class The fully qualified name of the class.
   *
   * @return string
   */
  public static function classPath(string $class): string
  {
    /** @var ClassLoader $loader */
    $loader = spl_autoload_functions()[0][0];

    // Find the source file of the constant class.
    $filename = $loader->findFile(ltrim($class, '\\'));
    if ($filename===false)
    {
      throw new RuntimeException("ClassLoader can not find class '%s'", $class);
    }

    return realpath($filename);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the line of a property declaration in the source code of a class.
   *
   * @param string[] $lines The source code of the class.
   * @param string   $name  The name of the property.
   *
   * @return int
   */
  public static function propertyDeclarationLine(array $lines, string $name): int
  {
    $pattern = sprintf('/^.*@property(-read)? (.+) (%s) (.*)$/',
                       preg_quote($name));
    $line    = self::searchPattern($lines, $pattern);

    if ($line===null)
    {
      throw new RuntimeException('Unable to find property %s', $name);
    }

    return $line;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the first line matching a regular expression.
   *
   * @param array  $lines   The source code of the class.
   * @param string $pattern The regular expression.
   *
   * @return int|null
   */
  private static function searchPattern(array $lines, string $pattern): ?int
  {
    foreach ($lines as $key => $line)
    {
      if (preg_match($pattern, $line)==1)
      {
        return $key;
      }
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

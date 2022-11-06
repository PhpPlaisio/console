<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\TypeScript;

/**
 * Utility class for handling markers in source files.
 */
class TypeScriptMarkHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends the hash of a TypeScript source file.
   *
   * @param string[] $lines The TypeScript source as lines.
   *
   * @return string[]
   */
  public static function appendHashToLines(array $lines): array
  {
    $lines = self::removeHashFromLines($lines);
    $hash  = md5(implode(PHP_EOL, $lines));
    $mark  = self::getMarkMd5();

    if (empty($lines) || !str_starts_with(end($lines), '//'))
    {
      $lines[] = '';
    }
    $lines[] = $mark.$hash;

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Computes the hash of a TypeScript source file.
   *
   * @param string[] $lines The TypeScript source as lines.
   *
   * @return string
   */
  public static function computeHashFromLines(array $lines): string
  {
    $lines = self::removeHashFromLines($lines);

    return md5(implode(PHP_EOL, $lines));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Computes the hash of a TypeScript source file.
   *
   * @param string $path The path to the TypeScript source.
   *
   * @return string
   */
  public static function computeHashFromSource(string $path): string
  {
    $lines = self::sourceAsLines($path);

    return self::computeHashFromLines($lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the logged hash from a JavaScript or TypeScript source file.
   *
   * @param string $path The path to the source.
   *
   * @return string|null
   */
  public static function extractHashFromSource(string $path): ?string
  {
    $lines   = self::sourceAsLines($path);
    $mark    = self::getMarkMd5();
    $pattern = sprintf('/^%s(?<hash>.*)$/', preg_quote($mark, '/'));
    [$key, $matches] = self::findMark($pattern, $lines);
    unset($key);

    return ($matches!==null) ? $matches['hash'] : null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a comment that must be appended to the JavScript source file to indicate that the file has been processed.
   */
  public static function getMarkUpdated(): string
  {
    return sprintf('// %s::updated', self::class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes the trailing line with the hash (if any) from a TypeScript source.
   *
   * @param string[] $lines The TypScript source as lines.
   *
   * @return string[]
   */
  public static function removeHashFromLines(array $lines): array
  {
    $mark    = self::getMarkMd5();
    $pattern = sprintf('/^%s(?<hash>.*)$/', preg_quote($mark, '/'));
    if (!empty($lines) && preg_match($pattern, end($lines))===1)
    {
      array_pop($lines);
      $lines = self::removeEmptyTrainingLines($lines);
    }

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the source of a JavaScript or TypeScript source file as lines. Empty trailing lines are removed.
   *
   * @param string $path The path to the source.
   *
   * @return string[]
   */
  public static function sourceAsLines(string $path): array
  {
    $lines = explode(PHP_EOL, file_get_contents($path));

    return self::removeEmptyTrainingLines($lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds a mark ina source file.
   *
   * @param string $pattern The regexp pattern for find the mark.
   * @param array  $lines   The source as lines.
   *
   * @return array
   */
  private static function findMark(string $pattern, array $lines): array
  {
    $tail = array_reverse(array_slice($lines, -5, 5, true), true);
    foreach ($tail as $key => $line)
    {
      if (preg_match($pattern, $line, $matches)===1)
      {
        return [$key, $matches];
      }
    }

    return [null, null];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @return string
   */
  private static function getMarkMd5(): string
  {
    return sprintf('// %s::md5: ', self::class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes trailing empty lines.
   *
   * @param string[] $lines The lines.
   *
   * @return string[]
   */
  private static function removeEmptyTrainingLines(array $lines): array
  {
    while (!empty($lines) && trim(end($lines))==='')
    {
      array_pop($lines);
    }

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

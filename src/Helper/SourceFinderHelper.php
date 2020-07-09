<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Helper class for finding files.
 */
class SourceFinderHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The directory of the stratum configuration file.
   *
   * @var string
   */
  private $basedir;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * SourceFinderHelper constructor.
   *
   * @param string $basedir The directory of the stratum configuration file.
   */
  public function __construct(string $basedir)
  {
    $this->basedir = $basedir;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds files matching a pattern.
   *
   * @param array $patterns A list of patterns. When a file matches a pattern the file is included in the set of files.
   *
   * @return string[]
   */
  public function findFiles(array $patterns): array
  {
    $files = [];
    foreach ($patterns as $pattern)
    {
      $tmp   = $this->findFilesInPattern($pattern);
      $files = array_merge($files, $tmp);
    }

    $files = array_unique($files);
    sort($files);

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds sources of stored routines in a pattern.
   *
   * @param string $pattern The pattern of the sources.
   *
   * @return string[]
   */
  private function findFilesInPattern(string $pattern): array
  {
    $files = [];

    $directory = new RecursiveDirectoryIterator($this->basedir);
    $directory->setFlags(RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
    $tmp = new RecursiveIteratorIterator($directory);
    foreach ($tmp as $fullPath => $file)
    {
      if ($file->isFile() && SelectorHelper::matchPath($pattern, $fullPath))
      {
        $files[] = $fullPath;
      }
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

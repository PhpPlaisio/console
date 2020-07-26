<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\TypeScript;

use Plaisio\Console\Style\PlaisioStyle;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Webmozart\PathUtil\Path;

/**
 * Fixes from a TypeScript file generated JavaScript file as a proper AMD module according to Plaisio standards.
 */
class TypeScriptFixHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The name of the class or interface in the current JS file.
   *
   * @var string
   */
  private $className;

  /**
   * The fully qualified name of the class or interface of the current JS file.
   *
   * @var string
   */
  private $fullyQualifiedName;

  /**
   * The output decorator.
   *
   * @var PlaisioStyle
   */
  private $io;

  /**
   * The path to the JavaScript assets directory.
   *
   * @var string
   */
  private $jsAssetPath;

  /**
   * The file extension of JavaScript files.
   *
   * @var string
   */
  private $jsExtension = 'js';

  /**
   * The source code of the current JS file as lines.
   *
   * @var string[]
   */
  private $lines;

  /**
   * A comment that must be appended to the file to indicate that the file has been processed.
   *
   * @var string
   */
  private $marker;

  /**
   * The namespace of the class or interface in the current JS file.
   *
   * @var string
   */
  private $namespace;

  /**
   * The file extension of TypeScript files.
   *
   * @var string
   */
  private $tsExtension = 'ts';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param PlaisioStyle $io     The output decorator.
   * @param string       $jsPath The path to the JS assert directory.
   */
  public function __construct(PlaisioStyle $io, string $jsPath)
  {
    $this->io          = $io;
    $this->jsAssetPath = $jsPath;
    $this->marker      = sprintf('// Modified by %s', self::class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fixes from a TypeScript file generated JavaScript file as a proper AMD module according to Plaisio standards.
   *
   * @param string $path The path to the from a TypeScript file generated JavaScript file.
   */
  public function fixJavaScriptFile(string $path): void
  {
    if (file_exists(Path::changeExtension($path, $this->tsExtension)))
    {
      $this->deriveNaming($path);
      $this->readJsSource($path);

      if (!$this->hasBeenProcessed())
      {
        if ($this->requiresFixing($path))
        {
          $this->io->logInfo('TypeScript fixing: <fso>%s</fso>', $path);

          $this->fixDefine();
          $this->fixExports1('Object.defineProperty(exports, "__esModule", { value: true });');
          $this->fixExports1(sprintf('exports.%1$s = %1$s;', $this->className));
          $this->fixExports2();
          $this->fixReferences();
        }
        else
        {
          $this->io->logVeryVerbose("Main file doesn't require fixing: <fso>%s</fso>", $path);
        }

        $this->writeJsSource($path);
      }
      else
      {
        $this->io->logVeryVerbose('Has been TypeScript fixed already: <fso>%s</fso>', $path);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fixes from all TypeScript file generated JavaScript file as a proper AMD module according to Plaisio standards
   * recursively under a directory.
   *
   * @param string $dir The path directory
   */
  public function fixJavaScriptFiles(string $dir): void
  {
    $paths = $this->collectJavaScriptFiles($dir);
    foreach ($paths as $path)
    {
      $this->fixJavaScriptFile($path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Collects recursively all JavaScript files under a directory.
   *
   * @param string $root The directory.
   *
   * @return array
   */
  private function collectJavaScriptFiles(string $root): array
  {
    $files = [];

    $directory = new RecursiveDirectoryIterator($root);
    $directory->setFlags(RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
    $iterator = new RecursiveIteratorIterator($directory);
    foreach ($iterator as $path => $file)
    {
      if ($file->isFile() && Path::hasExtension($file->getFilename(), $this->jsExtension))
      {
        $files[] = $path;
      }
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Derives the namespace, name, and fully qualified name of the class or interface in a JS file based on its path.
   *
   * @param string $path The path to JS file.
   */
  private function deriveNaming(string $path): void
  {
    $tmp                      = Path::join(Path::getDirectory($path), Path::getFilenameWithoutExtension($path));
    $this->fullyQualifiedName = Path::makeRelative($tmp, $this->jsAssetPath);
    $this->namespace          = Path::getDirectory($this->fullyQualifiedName);
    $this->className          = Path::getFilename($this->fullyQualifiedName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fix the arguments of the call of define().
   */
  private function fixDefine()
  {
    $matches = null;
    $key     = null;
    foreach ($this->lines as $i => $line)
    {
      if (preg_match('/^define\(\[(?<deps>[^]]*)],function\((?<args>[^)]*)\){$/',
                     str_replace(' ', '', $line),
                     $matches))
      {
        $key = $i;
        break;
      }
    }

    if (!empty($matches))
    {
      $deps = $this->fixDefineDeps($matches['deps']);
      $args = $this->fixDefineArgs($matches['args']);

      $this->lines[$key] = sprintf('define("%s", [%s], function (%s) {', $this->fullyQualifiedName, $deps, $args);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Return the correct arguments for the callback for calling define.
   *
   * @param string $args The comma separated arguments.
   *
   * @return string
   */
  private function fixDefineArgs(string $args): string
  {
    $args = explode(',', $args);

    return implode(', ', $args);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Return the correct dependencies for calling define.
   *
   * @param string $deps The comma separated dependencies.
   *
   * @return string
   */
  private function fixDefineDeps(string $deps): string
  {
    $deps = explode(',', $deps);
    foreach ($deps as $key => $dep)
    {
      $dep = trim($dep, '"');

      if (substr($dep, 0, 1)==='.')
      {
        $depPath = Path::join($this->namespace, $dep);

        $deps[$key] = '"'.$depPath.'"';
      }
    }

    return implode(', ', $deps);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a line from the code.
   *
   * @param string $search the line to be removed.
   */
  private function fixExports1(string $search): void
  {
    $search = str_replace(' ', '', $search);

    foreach ($this->lines as $key => $line)
    {
      $line = str_replace(' ', '', $line);
      if ($line===$search)
      {
        $this->lines[$key] = '';
        break;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds the return statement for classes.
   */
  private function fixExports2(): void
  {
    $pattern = sprintf('/^\s*class\s+%s/', preg_quote($this->className));
    if (preg_match($pattern, implode(PHP_EOL, $this->lines)))
    {
      $return = sprintf('    return %s;', $this->className);

      $lines = array_reverse($this->lines);
      $key   = null;
      foreach ($lines as $i => $line)
      {
        $line = str_replace(' ', '', $line);
        if ($line==='});')
        {
          $key = $i;
          break;
        }
      }

      if ($key!==null)
      {
        array_splice($lines, $key + 1, 0, [$return, '']);
      }

      $this->lines = array_reverse($lines);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fixes references to dependencies in the code.
   */
  private function fixReferences()
  {
    $matches = null;
    foreach ($this->lines as $i => $line)
    {
      if (preg_match('/^define\((?<name>[^\[]*),\[(?<deps>[^]]*)],function\((?<args>[^)]*)\){$/',
                     str_replace(' ', '', $line),
                     $matches))
      {
        break;
      }
    }

    if (!empty($matches))
    {
      $deps = explode(',', $matches['deps']);
      $args = explode(',', $matches['args']);

      $replace = [];
      foreach ($args as $key => $arg)
      {
        $dep = trim($deps[$key], '"');

        if (!in_array($dep, ['require', 'exports']))
        {
          $name                                   = Path::getFilename($dep);
          $replace[sprintf('%s.%s', $arg, $name)] = $arg;
        }
      }

      foreach ($this->lines as $key => $line)
      {
        $this->lines[$key] = strtr($line, $replace);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if and only if the JS source has been processed already.
   *
   * @return bool
   */
  private function hasBeenProcessed(): bool
  {
    return (end($this->lines)===$this->marker);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Reads the JS source.
   *
   * @param string $path The path to JS file.
   */
  private function readJsSource(string $path)
  {
    $source      = file_get_contents($path);
    $this->lines = explode(PHP_EOL, $source);

    // Remove trailing empty lines.
    while (end($this->lines)==='')
    {
      array_pop($this->lines);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if and only if the JS source requires fixing
   *
   * @param string $path The path to JS file.
   *
   * @return bool
   */
  private function requiresFixing($path): bool
  {
    return (!str_ends_with($path, '.main.js'));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes the modified JS source.
   *
   * @param string $path The path to JS file.
   */
  private function writeJsSource(string $path)
  {
    if ($this->marker!==null)
    {
      // Add marker.
      $this->lines[] = $this->marker;
      $this->lines[] = '';
    }
    $source = implode(PHP_EOL, $this->lines);

    file_put_contents($path, $source);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

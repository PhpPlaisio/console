<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\TypeScript;

use Plaisio\Console\Style\PlaisioStyle;
use Webmozart\PathUtil\Path;

/**
 * Fixes from a TypeScript file generated JavaScript file as a proper AMD module according to Plaisio standards.
 */
class TypeScriptFixHelper
{
  //--------------------------------------------------------------------------------------------------------------------
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
  private $jsPath;

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
   * The namespace of the current JS file.
   *
   * @var string
   */
  private $namespace;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param PlaisioStyle $io     The output decorator.
   * @param string       $jsPath The path to the JS assert directory.
   */
  public function __construct(PlaisioStyle $io, string $jsPath)
  {
    $this->io     = $io;
    $this->jsPath = realpath($jsPath);
    $this->marker = sprintf('// Modified by %s ', self::class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fixes from a TypeScript file generated JavaScript file as a proper AMD module according to Plaisio standards.
   *
   * @param string $path The path to the from a TypeScript file generated JavaScript file.
   */
  public function fixJavaScriptFile(string $path): void
  {
    $this->deriveNamespace($path);
    $this->readJsSource($path);

    if (!$this->hasBeenProcessed())
    {
      $this->io->logInfo('TypeScript fixing: <fso>%s</fso>', $path);

      $this->fixDefine();
      $this->fixExports1('Object.defineProperty(exports, "__esModule", { value: true });');
      $this->fixExports1(sprintf('exports.%1$s = %1$s;', Path::getFilename($this->namespace)));
      $this->fixExports2();
      $this->fixReferences();
      $this->writeJsSource($path);
    }
    else
    {
      $this->io->logVeryVerbose('Has been TypeScript fixed already: <fso>%s</fso>', $path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Derives the namespace of a JS file based on its path.
   *
   * @param string $path The path to JS file.
   */
  private function deriveNamespace(string $path): void
  {
    $tmp             = Path::join(Path::getDirectory($path), Path::getFilenameWithoutExtension($path));
    $this->namespace = Path::makeRelative($tmp, $this->jsPath);
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
      if (preg_match('/^define\(\[(?<deps>[^\]]*)\],function\((?<args>[^)]*)\){$/',
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

      $this->lines[$key] = sprintf('define("%s", [%s], function (%s) {', $this->namespace, $deps, $args);
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

      if (!in_array($dep, ['require', 'exports']))
      {
        $depPath = Path::join(Path::getDirectory($this->namespace), $dep);

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
   * Adds the return statement.
   */
  private function fixExports2(): void
  {
    $return = sprintf('    return %s;', Path::getFilename($this->namespace));

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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Fixes references to dependencies in the code.
   */
  private function fixReferences()
  {
    $matches = null;
    foreach ($this->lines as $i => $line)
    {
      if (preg_match('/^define\((?<name>[^\[]*),\[(?<deps>[^\]]*)\],function\((?<args>[^\)]*)\){$/',
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
      foreach ($deps as $key => $dep)
      {
        $dep = trim($dep, '"');
        $arg = $args[$key];

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

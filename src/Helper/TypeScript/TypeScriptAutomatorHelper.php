<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\TypeScript;

use Plaisio\Console\Style\PlaisioStyle;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SetBased\Helper\ProgramExecution;
use Webmozart\PathUtil\Path;

/**
 *  Watch the asset root directory for file events related to TypeScript files.
 */
class TypeScriptAutomatorHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  private static array $masks = [1    => ['IN_ACCESS', 'File was accessed (read)'],
                                 2    => ['IN_MODIFY', 'File was modified'],
                                 4    => ['IN_ATTRIB', 'Metadata changed (e.g. permissions, mtime, etc.)'],
                                 8    => ['IN_CLOSE_WRITE', 'File opened for writing was closed'],
                                 16   => ['IN_CLOSE_NOWRITE', 'File not opened for writing was closed'],
                                 32   => ['IN_OPEN', 'File was opened'],
                                 128  => ['IN_MOVED_TO', 'File moved into watched directory'],
                                 64   => ['IN_MOVED_FROM', 'File moved out of watched directory'],
                                 256  => ['IN_CREATE', 'File or directory created in watched directory'],
                                 512  => ['IN_DELETE', 'File or directory deleted in watched directory'],
                                 1024 => ['IN_DELETE_SELF', 'Watched file or directory was deleted'],
                                 2048       => ['IN_MOVE_SELF', 'Watch file or directory was moved'],
                                 24         => ['IN_CLOSE', 'Equals to IN_CLOSE_WRITE | IN_CLOSE_NOWRITE'],
                                 192        => ['IN_MOVE', 'Equals to IN_MOVED_FROM | IN_MOVED_TO'],
                                 4095       => ['IN_ALL_EVENTS', 'Bitmask of all the above constants'],
                                 8192       => ['IN_UNMOUNT', 'File system containing watched object was unmounted'],
                                 16384      => ['IN_Q_OVERFLOW', 'Event queue overflowed (wd is -1 for this event)'],
                                 32768      => ['IN_IGNORED',
                                          'Watch was removed (explicitly by inotify_rm_watch() or because file was removed or filesystem unmounted'],
                                 1073741824 => ['IN_ISDIR', 'Subject of this event is a directory'],
                                 1073741840 => ['IN_CLOSE_NOWRITE', 'High-bit: File not opened for writing was closed'],
                                 1073741856 => ['IN_OPEN', 'High-bit: File was opened'],
                                 1073742080 => ['IN_CREATE', 'High-bit: File or directory created in watched directory'],
                                 1073742336 => ['IN_DELETE', 'High-bit: File or directory deleted in watched directory'],
                                 16777216   => ['IN_ONLYDIR',
                                          'Only watch pathname if it is a directory (Since Linux 2.6.15)'],
                                 33554432   => ['IN_DONT_FOLLOW',
                                          'Do not dereference pathname if it is a symlink (Since Linux 2.6.15)'],
                                 536870912  => ['IN_MASK_ADD',
                                          'Add events to watch mask for this pathname if it already exists (instead of replacing mask).'],
                                 2147483648 => ['IN_ONESHOT',
                                          'Monitor pathname for one event, then remove from watch list.']];

  /**
   * Map from watch descriptor to directory name.
   *
   * @var array<int, string>
   */
  private array $directories;

  /**
   * The output decorator.
   *
   * @var PlaisioStyle
   */
  private PlaisioStyle $io;

  /**
   * The path to the JavScript asset directory.
   *
   * @var string
   */
  private string $jsAssetPath;

  /**
   * The file extension of JavaScript files.
   *
   * @var string
   */
  private string $jsExtension = 'js';

  /**
   * The file extension of map files.
   *
   * @var string
   */
  private string $mapExtension = 'map';

  /**
   * Events that we handle.
   *
   * @var int[]
   */
  private array $myEvents = [IN_CLOSE_WRITE,
                             IN_MOVED_TO,
                             IN_MOVED_FROM,
                             IN_CREATE,
                             IN_DELETE,
                             IN_DELETE_SELF];

  /**
   * Timeout waiting for inotify and start "manual" inspections of TypeScript and JavaScript files.
   *
   * @var int
   */
  private int $timeout = 10;

  /**
   * The file extension of TypeScript files.
   *
   * @var string
   */
  private string $tsExtension = 'ts';

  /**
   * The inotify instance.
   *
   * @var resource
   */
  private $watcher;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Object constructor.
   *
   * @param PlaisioStyle $io     The output decorator.
   * @param string       $jsPath The path to the JavScript asset directory.
   */
  public function __construct(PlaisioStyle $io, string $jsPath)
  {
    $this->io          = $io;
    $this->jsAssetPath = $jsPath;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the automator.
   */
  public function automate(): void
  {
    $this->initWatchers();
    $this->once();
    $this->watch();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes all corresponding JavaScript and map files of TypeScript files.
   */
  public function delete(): void
  {
    $tsPaths = $this->collectTypeScriptFiles();
    foreach ($tsPaths as $tsPath)
    {
      $paths = $this->associated($tsPath);
      foreach ($paths as $path)
      {
        $this->removeFile($path);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Transpiles TypeScript files of which the corresponding JavScript file is out of sync.
   *
   * @param bool $force Whether to transpile TypeScript source files unconditionally.
   */
  public function once(bool $force = false): void
  {
    $tsPaths = $this->collectTypeScriptFiles();
    foreach ($tsPaths as $tsPath)
    {
      if ($this->mustTranspile($tsPath) || $force)
      {
        $this->runTypeScriptTranspiler($tsPath);
      }
    }

    $helper = new TypeScriptFixHelper($this->io, $this->jsAssetPath);
    $helper->fixJavaScriptFiles($this->jsAssetPath);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a watcher on a directory.
   *
   * @param string $path The directory.
   */
  private function addWatcher(string $path): void
  {
    $this->io->logVerbose('Watching directory %s', $path);

    $mask = 0;
    foreach ($this->myEvents as $event)
    {
      $mask = $mask | $event;
    }

    $wd = inotify_add_watch($this->watcher, $path, $mask);

    $this->directories[$wd] = $path;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns all associated files of a TypeScript file.
   *
   * @param string $path The path to the TypeScript file.
   *
   * @return string[]
   */
  private function associated(string $path): array
  {
    $jsPath  = Path::changeExtension($path, $this->jsExtension);
    $mapPath = $jsPath.'.'.$this->mapExtension;

    return [$jsPath, $mapPath];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Collects recursively all TypeScript files.
   *
   * @return array
   */
  private function collectTypeScriptFiles(): array
  {
    $files = [];

    $directory = new RecursiveDirectoryIterator($this->jsAssetPath);
    $directory->setFlags(RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
    $iterator = new RecursiveIteratorIterator($directory);
    foreach ($iterator as $path => $file)
    {
      if ($file->isFile() && Path::hasExtension($file->getFilename(), $this->tsExtension))
      {
        $files[] = $path;
      }
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles a IN_CLOSE_WRITE of a path.
   *
   * @param string $path The path.
   */
  private function handleCloseWrite(string $path): void
  {
    if (Path::hasExtension($path, $this->tsExtension))
    {
      if ($this->mustTranspile($path))
      {
        $this->runTypeScriptTranspiler($path);
      }
    }

    if (Path::hasExtension($path, $this->jsExtension) &&
      is_file($path) &&
      is_file(Path::changeExtension($path, $this->tsExtension)))
    {
      $this->runTypeScriptFixer($path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles a IN_CREATE of a path.
   *
   * @param string $path The path.
   */
  private function handleCreate(string $path): void
  {
    if (is_dir($path) && !in_array($path, $this->directories))
    {
      $this->addWatcher($path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles a IN_DELETE and IN_MOVE_FROM of a path.
   *
   * @param string $path The path.
   */
  private function handleDelete(string $path): void
  {
    if (Path::hasExtension($path, $this->tsExtension))
    {
      $this->removeFile(Path::changeExtension($path, $this->jsExtension));
      $this->removeFile(Path::changeExtension($path, $this->mapExtension));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles a IN_DELETE_SELF of a path.
   *
   * @param string $path The path.
   * @param int    $wd   The watch descriptor.
   */
  private function handleDeleteSelf(string $path, int $wd): void
  {
    $this->io->logVerbose('Stop watching directory %s', $path);

    unset($this->directories[$wd]);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles all filesystem events.
   */
  private function handleEvents(): void
  {
    $events = inotify_read($this->watcher);
    if (!empty($events))
    {
      foreach ($events as $event)
      {
        try
        {
          $path = Path::join($this->directories[$event['wd']] ?? '-', $event['name']);
          $this->logEvent($event['mask'], $path);
          switch (true)
          {
            case ($event['mask'] & IN_CLOSE_WRITE)!==0:
              $this->handleCloseWrite($path);
              break;

            case ($event['mask'] & IN_MOVED_TO)!==0:
              $this->handleMoveTo($path);
              break;

            case ($event['mask'] & IN_CREATE)!==0:
              $this->handleCreate($path);
              break;

            case ($event['mask'] & IN_MOVED_FROM)!==0:
            case ($event['mask'] & IN_DELETE)!==0:
              $this->handleDelete($path);
              break;

            case ($event['mask'] & IN_DELETE_SELF)!==0:
              $this->handleDeleteSelf($path, $event['wd']);
              break;

            case ($event['mask'] & IN_IGNORED)!==0:
              // nothing to do.
              break;

            default:
              // throw new FallenException('mask', $event['mask']);
          }
        }
        catch (\Throwable $exception)
        {
          $this->io->error($exception->getMessage());
          $this->io->error($exception->getTraceAsString());
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles a IN_MOVED_TO of a path.
   *
   * @param string $path The path.
   */
  private
  function handleMoveTo(string $path): void
  {
    if (is_dir($path) && !in_array($path, $this->directories))
    {
      $this->addWatcher($path);
    }

    if (is_file($path) && Path::hasExtension($path, $this->tsExtension))
    {
      $this->runTypeScriptTranspiler($path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Initializes watchers for all directories recursively under the asset root.
   */
  private
  function initWatchers(): void
  {
    $dirs = $this->initWatchersFetchDirectories();

    $this->watcher = inotify_init();
    stream_set_blocking($this->watcher, false);

    $this->directories = [];
    foreach ($dirs as $dir)
    {
      $this->addWatcher($dir);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Collects recursively all directories under the asset root.
   *
   * @return array All found directories.
   */
  private
  function initWatchersFetchDirectories(): array
  {
    $dirs = [];

    $directory = new RecursiveDirectoryIterator($this->jsAssetPath);
    $directory->setFlags(RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
    $iterator = new RecursiveIteratorIterator($directory);
    foreach ($iterator as $path => $file)
    {
      if ($file->isDir() && $file->getFilename()!=='..')
      {
        $dirs[] = Path::canonicalize($path);
      }
    }

    return $dirs;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs an event.
   *
   * @param int    $mask The INOTIFY constant.
   * @param string $path The path.
   */
  private
  function logEvent(int $mask, string $path): void
  {
    foreach ($this->myEvents as $tmp)
    {
      if ($tmp & $mask)
      {
        $this->io->logVeryVerbose("Handling %s: %s (%d & %d) on %s\n",
                                  self::$masks[$tmp][0] ?? '-',
                                  self::$masks[$tmp][1] ?? '-',
                                  $mask,
                                  $tmp,
                                  $path);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Return whether a TypeScript file must e transpiled.
   *
   * @param string $tsPath The path to the TypeScript file.
   *
   * @return bool
   */
  private
  function mustTranspile(string $tsPath): bool
  {
    $jsPath = Path::changeExtension($tsPath, $this->jsExtension);
    if (!file_exists($jsPath))
    {
      return true;
    }

    $tsHash = TypeScriptMarkHelper::addHashToTypeScriptSource($tsPath);
    $jsHash = TypeScriptMarkHelper::extractHashFromSource($jsPath);

    return ($tsHash!==$jsHash);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a file.
   *
   * @param string $path The path to the file.
   */
  private
  function removeFile(string $path): void
  {
    if (is_file($path))
    {
      $this->io->logVerbose("Removing file: %s", $path);

      @unlink($path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the TypeScript Fixer on a JavaScript file.
   *
   * @param string $path The path to the JavScript file.
   **/
  private
  function runTypeScriptFixer(string $path): void
  {
    $helper = new TypeScriptFixHelper($this->io, $this->jsAssetPath);
    $helper->fixJavaScriptFile($path);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Runs the TypeScript transpiler (tsc) on a TypeScript file.
   *
   * @param string $path The path to the JavScript file.
   **/
  private
  function runTypeScriptTranspiler(string $path): void
  {
    $command = ['node_modules/typescript/bin/tsc',
                '-m',
                'amd',
                '-t',
                'ES2015',
                '--strict',
                '--sourceMap',
                $path];

    $this->io->logInfo('Running: %s', implode(' ', $command));

    [$lines, $status] = ProgramExecution::exec1($command, null);
    if ($status!==0)
    {
      echo implode(PHP_EOL, $lines);

      $paths = $this->associated($path);
      foreach ($paths as $name)
      {
        $this->removeFile($name);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Watch the asset root directory for file events.
   */
  private
  function watch(): void
  {
    do
    {
      $read   = [$this->watcher];
      $write  = null;
      $except = null;

      stream_select($read, $write, $except, $this->timeout);

      if (inotify_queue_len($this->watcher)>0)
      {
        $this->handleEvents();
      }
      else
      {
        // Handle possible changed files not notified by inotify (e.g. nfs mounted filesystems).
        $this->once();
      }
    } while (true);
  }
  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

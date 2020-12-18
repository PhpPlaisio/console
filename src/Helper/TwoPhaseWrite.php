<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use Plaisio\Console\Style\PlaisioStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Helper class for writing a file to the filesystem in two phases.
 */
class TwoPhaseWrite
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator.
   *
   * @var PlaisioStyle
   */
  private PlaisioStyle $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param PlaisioStyle $io The output decorator.
   */
  public function __construct(PlaisioStyle $io)
  {
    $this->io = $io;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a file in two phase to the filesystem.
   *
   * First write the data to a temporary file (in the same directory) and than renames the temporary file. If the file
   * already exists and its content is equal to the data that must be written no action  is taken. This has the
   * following advantages:
   * * In case of some write error (e.g. disk full) the original file is kept in tact and no file with partially data
   *   is written.
   * * Renaming a file is atomic. Hence, a running process will never read a partially written data.
   *
   * @param string $filename The name of the file were the content must be stored.
   * @param string $content  The content that must be written.
   *
   * @return bool True if the file was saved. False the file is up to date
   */
  public function write(string $filename, string $content): bool
  {
    $flag  = true;
    $perms = null;

    if (file_exists($filename))
    {
      $currentContent = file_get_contents($filename);
      $flag           = ($content!==$currentContent);

      if ($flag)
      {
        $perms = fileperms($filename);
      }
    }

    if ($flag)
    {
      $tempName = sprintf('%s.tmp', $filename);
      file_put_contents($tempName, $content);
      rename($tempName, $filename);

      if ($perms!==null)
      {
        chmod($filename, $perms);
      }

      $this->io->text(sprintf('Wrote <fso>%s</fso>', OutputFormatter::escape($filename)));
    }
    else
    {
      $this->io->text(sprintf('File <fso>%s</fso> is up to date', OutputFormatter::escape($filename)));
    }

    return $flag;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

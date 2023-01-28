<?php
declare(strict_types=1);

namespace Plaisio\Console\Style;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Output decorator helpers based on Symfony Style Guide.
 */
class PlaisioStyle extends SymfonyStyle
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param InputInterface  $input  The input interface.
   * @param OutputInterface $output The output interface.
   */
  public function __construct(InputInterface $input, OutputInterface $output)
  {
    parent::__construct($input, $output);

    // Create style notes.
    $style = new OutputFormatterStyle('yellow');
    $output->getFormatter()->setStyle('note', $style);

    // Create style for database objects.
    $style = new OutputFormatterStyle('green', null, ['bold']);
    $output->getFormatter()->setStyle('dbo', $style);

    // Create style for file and directory names.
    $style = new OutputFormatterStyle(null, null, ['bold']);
    $output->getFormatter()->setStyle('fso', $style);

    // Create style for SQL statements.
    $style = new OutputFormatterStyle('magenta', null, ['bold']);
    $output->getFormatter()->setStyle('sql', $style);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a debug message.
   *
   * @param string $format    The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param mixed  ...$values The values to be used in the format string.
   */
  public function logDebug(string $format, mixed ...$values): void
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_DEBUG)
    {
      $message = $this->composeMessage($format, $values);
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes an information message.
   *
   * @param string $format    The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param mixed  ...$values The values to be used in the format string.
   */
  public function logInfo(string $format, mixed ...$values): void
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_NORMAL)
    {
      $message = $this->composeMessage($format, $values);
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a notification message.
   *
   * @param string $format    The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param mixed  ...$values The values to be used in the format string.
   */
  public function logNote(string $format, mixed ...$values): void
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_NORMAL)
    {
      $message = $this->composeMessage($format, $values);
      $this->writeln('<note> ! [NOTE] '.$message.'</note>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a verbose message.
   *
   * @param string $format    The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param mixed  ...$values The values to be used in the format string.
   */
  public function logVerbose(string $format, mixed ...$values): void
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERBOSE)
    {
      $message = $this->composeMessage($format, $values);
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a very verbose message.
   *
   * @param string $format    The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param mixed  ...$values The values to be used in the format string.
   */
  public function logVeryVerbose(string $format, mixed ...$values): void
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
      $message = $this->composeMessage($format, $values);
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Composes the message based on the arguments.
   *
   * @param string $format The format string, see https://www.php.net/manual/en/function.vsprintf.php
   * @param array  $values The values to be used in the format string.
   *
   * @return string
   */
  private function composeMessage(string $format, array $values): string
  {
    if (empty($values))
    {
      $message = $format;
    }
    else
    {
      $message = vsprintf($format, $values);
    }

    return $message;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

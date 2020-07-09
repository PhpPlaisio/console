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
   */
  public function logDebug()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_DEBUG)
    {
      $message = $this->composeMessage(func_get_args());
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes an infomation message.
   */
  public function logInfo()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_NORMAL)
    {
      $message = $this->composeMessage(func_get_args());
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a notification message.
   */
  public function logNote()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_NORMAL)
    {
      $message = $this->composeMessage(func_get_args());
      $this->writeln('<note> ! [NOTE] '.$message.'</note>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a verbose message.
   */
  public function logVerbose()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERBOSE)
    {
      $message = $this->composeMessage(func_get_args());
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes a very verbose message.
   */
  public function logVeryVerbose()
  {
    if ($this->getVerbosity()>=OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
      $message = $this->composeMessage(func_get_args());
      $this->writeln('<info>'.$message.'</info>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Composes the message based on the arguments.
   *
   * @param array $args The arguments.
   *
   * @return string
   */
  private function composeMessage(array $args): string
  {
    $format = array_shift($args);

    if ($format===null)
    {
      $message = '';
    }
    elseif (empty($args))
    {
      $message = $format;
    }
    else
    {
      $message = vsprintf($format, $args);
    }

    return $message;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

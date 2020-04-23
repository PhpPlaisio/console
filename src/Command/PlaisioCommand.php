<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Style\PlaisioStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract parent command for all Plaisio commands.
 */
abstract class PlaisioCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The output decorator.
   *
   * @var PlaisioStyle
   */
  protected $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Initializes the output decorator and console IO object.
   *
   * @param InputInterface  $input  An InputInterface instance.
   * @param OutputInterface $output An OutputInterface instance.
   */
  protected function initialize(InputInterface $input, OutputInterface $output)
  {
    $this->io = new PlaisioStyle($input, $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

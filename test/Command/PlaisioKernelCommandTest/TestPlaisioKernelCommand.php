<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Command\PlaisioKernelCommandTest;

use Plaisio\Console\Command\PlaisioCommand;
use Plaisio\Console\Command\PlaisioKernelCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for testing purposes.
 */
class TestPlaisioKernelCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  use PlaisioKernelCommand;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:kernel-command-test');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $output->write(get_class($this->nub));

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

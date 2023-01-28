<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Helper\TwoPhaseWriteTest;

use Plaisio\Console\Command\PlaisioCommand;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for testing class TwoPhaseWrite.
 */
class TestTwoFaseWriteCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:two-phase-write-test')
         ->addArgument('filename');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $helper = new TwoPhaseWrite($this->io);

    $helper->write($input->getArgument('filename'), 'Hello world!');
    $helper->write($input->getArgument('filename'), 'Hello world!');
    $helper->write($input->getArgument('filename'), 'Hello, world!');

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

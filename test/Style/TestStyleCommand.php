<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Style;

use Plaisio\Console\Command\PlaisioCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for testing class TwoPhaseWrite.
 */
class TestStyleCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure(): void
  {
    $this->setName('plaisio:style-test');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->io->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

    $this->io->logDebug('level: %s', 'debug');
    $this->io->logVeryVerbose('level: %s', 'very verbose');
    $this->io->logVerbose('level: %s', 'verbose');
    $this->io->logInfo('level: %s', 'info');
    $this->io->logNote('note');
    $this->io->logDebug('%s');

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

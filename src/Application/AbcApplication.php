<?php

namespace SetBased\Abc\Console\Application;

use SetBased\Abc\Console\Command\StratumSourcesCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * The ABC application.
 */
class AbcApplication extends Application
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * AbcApplication constructor.
   */
  public function __construct()
  {
    parent::__construct('ABC', '0.0.0');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets the default commands that should always be available.
   *
   * @return Command[] An array of default Command instances.
   */
  protected function getDefaultCommands()
  {
    // Keep the core default commands to have the HelpCommand which is used when using the --help option
    $defaultCommands = parent::getDefaultCommands();

    $defaultCommands[] = new StratumSourcesCommand();

    return $defaultCommands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

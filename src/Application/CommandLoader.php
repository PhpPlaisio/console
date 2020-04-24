<?php
declare(strict_types=1);

namespace Plaisio\Console\Application;

use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

/**
 * Command loader for Plaisio commands.
 */
class CommandLoader extends FactoryCommandLoader
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   */
  public function __construct()
  {
    parent::__construct($this->findPlaisioCommands());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the Plaisio commands in this project.
   *
   * @return array
   */
  private function findPlaisioCommands(): array
  {
    $plaisioXmlList = PlaisioXmlUtility::findPlaisioXmlAll();

    $commands = [];
    foreach ($plaisioXmlList as $path)
    {
      $helper   = new PlaisioXmlHelper($path);
      $commands = array_merge($commands, $helper->queryPlaisioCommands());
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

<?php
declare(strict_types=1);

namespace Plaisio\Console\Application;

use Plaisio\Console\Exception\ConfigException;
use Plaisio\Console\Helper\PlaisioXmlQueryHelper;
use Plaisio\Console\Helper\PlaisioXmlPathHelper;
use Symfony\Component\Console\Command\Command;
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
   * Loads a command.
   *
   * @param string $name The name of the command.
   *
   * @return Command
   */
  public function get(string $name): Command
  {
    $command = parent::get($name);

    if (method_exists($command, 'setPlaisioKernel'))
    {
      $path   = PlaisioXmlPathHelper::plaisioXmlPath('console');
      $kernel = null;
      if (file_exists($path))
      {
        $helper  = new PlaisioXmlQueryHelper($path);
        $factory = $helper->queryConsoleKernelFactory();

        if ($factory!==null)
        {
          $kernel = $factory($name);
          $command->setPlaisioKernel($kernel);
        }
      }

      if ($kernel===null)
      {
        throw new ConfigException(sprintf('Kernel factory not found in %s.', $path));
      }
    }

    return $command;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the Plaisio commands in this project.
   *
   * @return array
   */
  private function findPlaisioCommands(): array
  {
    $plaisioXmlList = PlaisioXmlPathHelper::findPlaisioXmlAll('commands');

    $commands = [];
    foreach ($plaisioXmlList as $path)
    {
      $helper   = new PlaisioXmlQueryHelper($path);
      $commands = array_merge($commands, $helper->queryPlaisioCommands());
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

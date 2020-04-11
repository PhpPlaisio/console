<?php
declare(strict_types=1);

namespace Plaisio\Console\Application;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

/**
 * Command loader for Plaisio commands.
 */
class CommandLoader extends FactoryCommandLoader
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The IO object.
   *
   * @var ConsoleIO
   */
  private $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param ConsoleIO $io The IO object.
   */
  public function __construct($io)
  {
    $this->io = $io;

    parent::__construct($this->findPlaisioCommands());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the Plaisio commands in this projects.
   *
   * @return string[]
   */
  private function findPlaisioCommands(): array
  {
    $composer       = Factory::create($this->io);
    $plaisioXmlList = PlaisioXmlHelper::getPlaisioXmlOfInstalledPackages($composer);

    if (is_file('plaisio.xml'))
    {
      $plaisioXmlList[] = 'plaisio.xml';
    }

    $commands = [];
    foreach ($plaisioXmlList as $plaisioXmlPath)
    {
      $helper   = new PlaisioXmlHelper($plaisioXmlPath);
      $commands = array_merge($commands, $helper->findPlaisioCommands());
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

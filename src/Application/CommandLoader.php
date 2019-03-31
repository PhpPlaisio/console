<?php
declare(strict_types=1);

namespace SetBased\Abc\Console\Application;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use SetBased\Abc\Console\Helper\AbcXmlHelper;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

/**
 * Command loader for ABC commands.
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

    parent::__construct($this->findAbcCommands());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ABC commands in this projects.
   *
   * @return string[]
   */
  private function findAbcCommands(): array
  {
    $composer   = Factory::create($this->io);
    $abcXmlList = AbcXmlHelper::getAbcXmlOfInstalledPackages($composer);

    if (is_file('abc.xml'))
    {
      $abcXmlList[] = 'abc.xml';
    }

    $commands = [];
    foreach ($abcXmlList as $abcXmlPath)
    {
      $helper   = new AbcXmlHelper($abcXmlPath);
      $commands = array_merge($commands, $helper->findAbcCommands());
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

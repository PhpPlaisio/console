<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Plaisio\Console\Style\PlaisioStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for collecting source patterns for finding stored routines provided by packages.
 */
class StratumSourcesCommand extends Command
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The Console IO object.
   *
   * @var ConsoleIO
   */
  private $consoleIo;

  /**
   * The output decorator.
   *
   * @var PlaisioStyle
   */
  private $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:stratum-sources')
         ->setDescription('Sets the stratum patterns for finding sources of stored routines');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io        = new PlaisioStyle($input, $output);
    $this->consoleIo = new ConsoleIO($input, $output, $this->getHelperSet());

    $this->io->section('Plaisio: Stratum Sources');

    $patterns        = $this->findStratumSourcePatterns();
    $configFilename  = $this->stratumConfigFilename();
    $sourcesFilename = $this->sourcesListFilename($configFilename);

    $this->saveSourcePatterns($sourcesFilename, $patterns);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Saves the Stratum sources patterns to a file.
   *
   * @param string   $sourcesFilename The name of the file.
   * @param string[] $patterns        The Stratum sources patterns.
   */
  protected function saveSourcePatterns(string $sourcesFilename, array $patterns): void
  {
    $content = implode(PHP_EOL, $patterns);
    $content .= PHP_EOL;

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($sourcesFilename, $content);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the Stratum sources patterns for this project.
   *
   * @return string[]
   */
  private function findStratumSourcePatterns(): array
  {
    $composer = Factory::create($this->consoleIo);

    $plaisioXmlList = PlaisioXmlHelper::getPlaisioXmlOfInstalledPackages($composer);

    if (is_file('plaisio.xml'))
    {
      $plaisioXmlList[] = 'plaisio.xml';
    }

    $patterns = [];
    foreach ($plaisioXmlList as $plaisioXmlPath)
    {
      $packageRoot = dirname($plaisioXmlPath);
      $helper      = new PlaisioXmlHelper($plaisioXmlPath);
      $list        = $helper->getStratumSourcePatterns();
      foreach ($list as $item)
      {
        $patterns[] = (($packageRoot!='.') ? $packageRoot.'/' : '').$item;
      }
    }

    sort($patterns);

    return $patterns;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of the file for storing the list of patterns for sources of stored routines.
   *
   * @param string $configFilename The name Stratum configuration file.
   *
   * @return string
   */
  private function sourcesListFilename(string $configFilename): string
  {
    $settings = parse_ini_file($configFilename, true);

    if (!isset($settings['loader']['sources']))
    {
      throw new \RuntimeException(sprintf("Setting '%s' not found in section '%s' in file '%s'",
                                          'sources',
                                          'loader',
                                          $configFilename));
    }

    $sources = $settings['loader']['sources'];

    if (substr($sources, 0, 5)!='file:')
    {
      throw new \RuntimeException(sprintf("Setting '%s' in section '%s' in file '%s' must have format 'file:<filename>'",
                                          'sources',
                                          'loader',
                                          $configFilename));
    }

    $basedir = dirname($configFilename);
    $path    = substr($sources, 5);

    return $basedir.'/'.$path;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of the Stratum configuration file.
   *
   * @return string
   */
  private function stratumConfigFilename(): string
  {
    $plaisioXmlPath = 'plaisio.xml';
    if (!is_file($plaisioXmlPath))
    {
      throw new \RuntimeException(sprintf('File %s not found', $plaisioXmlPath));
    }

    $helper = new PlaisioXmlHelper($plaisioXmlPath);

    return $helper->getStratumConfigFilename();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

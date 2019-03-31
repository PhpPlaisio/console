<?php

namespace SetBased\Abc\Console\Command;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use SetBased\Abc\Console\Helper\AbcXmlHelper;
use SetBased\Abc\Console\Style\AbcStyle;
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
   * @var AbcStyle
   */
  private $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('abc:stratum-sources')
         ->setDescription('Sets the stratum patterns for finding sources of stored routines');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io        = new AbcStyle($input, $output);
    $this->consoleIo = new ConsoleIO($input, $output, $this->getHelperSet());

    $patterns        = $this->findStratumSourcePatterns();
    $configFilename  = $this->stratumConfigFilename();
    $sourcesFilename = $this->sourcesListFilename($configFilename);

    $this->saveSourcePatterns($sourcesFilename, $patterns);
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
    $this->io->writeln(sprintf("Writing sources patterns to <fso>%s</fso>", $sourcesFilename));

    $content = implode(PHP_EOL, $patterns);
    $content .= PHP_EOL;
    file_put_contents($sourcesFilename, $content);
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

    $abcXmlList = AbcXmlHelper::getAbcXmlOfInstalledPackages($composer);

    if (is_file('abc.xml'))
    {
      $abcXmlList[] = 'abc.xml';
    }

    $patterns = [];
    foreach ($abcXmlList as $abcXmlPath)
    {
      $packageRoot = dirname($abcXmlPath);
      $helper      = new AbcXmlHelper($abcXmlPath);
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
    $abcXmlPath = 'abc.xml';
    if (!is_file($abcXmlPath))
    {
      throw new \RuntimeException(sprintf('File %s not found', $abcXmlPath));
    }

    $helper         = new AbcXmlHelper($abcXmlPath);
    $configFilename = $helper->getStratumConfigFilename();

    return $configFilename;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

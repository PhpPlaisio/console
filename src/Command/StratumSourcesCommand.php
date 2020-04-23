<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for collecting source patterns for finding stored routines provided by packages.
 */
class StratumSourcesCommand extends PlaisioCommand
{
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
    $this->io->title('Plaisio: Stratum Sources');

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
    $plaisioXmlList = PlaisioXmlHelper::findPlaisioXmlAll();

    $patterns = [];
    foreach ($plaisioXmlList as $plaisioConfigPath)
    {
      $packageRoot = dirname($plaisioConfigPath);
      $helper      = new PlaisioXmlHelper($plaisioConfigPath);
      $list        = $helper->queryStratumSourcePatterns();
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
    $plaisioConfigPath = 'plaisio.xml';
    if (!is_file($plaisioConfigPath))
    {
      throw new \RuntimeException(sprintf('File %s not found', $plaisioConfigPath));
    }

    $helper = new PlaisioXmlHelper($plaisioConfigPath);

    return $helper->queryStratumConfigFilename();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

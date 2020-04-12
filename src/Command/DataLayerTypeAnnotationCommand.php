<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Composer\Autoload\ClassLoader;
use Noodlehaus\Config;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use SetBased\Config\TypedConfig;
use SetBased\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for collecting source patterns for finding stored routines provided by packages.
 */
class DataLayerTypeAnnotationCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Class name of the kernel.
   */
  const PLAISIO_KERNEL_NUB = '\\Plaisio\\Kernel\\Nub';

  /**
   * The declaration of the DataLayer.
   */
  const PUBLIC_STATIC_DL = 'public static $DL;';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:data-layer-type-annotation')
         ->setDescription(sprintf('Sets the type annotation of the DataLayer in %s', self::PLAISIO_KERNEL_NUB))
         ->addArgument('class', InputArgument::OPTIONAL, 'The class of the DataLayer');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->section('Plaisio: DataLayer Type Annotation');

    $wrapperClass = $input->getArgument('class');
    if ($wrapperClass===null)
    {
      $configFilename = $this->stratumConfigFilename();
      $wrapperClass   = $this->wrapperClass($configFilename);
    }
    $nubPath = $this->classPath(self::PLAISIO_KERNEL_NUB);
    $source  = $this->fixAnnotation($nubPath, $wrapperClass);

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($nubPath, $source);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the path to the source file of a class.
   *
   * @param string $class The name of the class.
   *
   * @return string
   */
  private function classPath(string $class): string
  {
    /** @var ClassLoader $loader */
    $loader = spl_autoload_functions()[0][0];

    // Find the source file of the constant class.
    $filename = $loader->findFile(ltrim($class, '\\'));
    if ($filename===false)
    {
      throw new RuntimeException("ClassLoader can not find class '%s'.", $class);
    }

    return realpath($filename);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the index of the line with de declaration of the DataLayer.
   *
   * @param string[] $lines The source of \Plaisio\Kernel\Nub as lines.
   *
   * @return int
   */
  private function declarationOfDataLayer(array $lines): int
  {
    foreach ($lines as $index => $line)
    {
      if (trim($line)==self::PUBLIC_STATIC_DL)
      {
        return $index;
      }
    }

    throw new RuntimeException('Declaration of the DataLayer not found');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Replaces the type annotation of the DataLayer with the actual wrapper class.
   *
   * @param string $nubPath      The path to the source of \Plaisio\Kernel\Nub.
   * @param string $wrapperClass The name of the class of the DataLayer.
   *
   * @return string
   */
  private function fixAnnotation(string $nubPath, string $wrapperClass): string
  {
    $source = file_get_contents($nubPath);
    $lines  = explode(PHP_EOL, $source);
    $index  = $this->declarationOfDataLayer($lines);

    if ($index<=4 || substr(trim($lines[$index - 2]), 0, 6)!=='* @var')
    {
      throw new \RuntimeException(sprintf('Annotation of the DataLayer not found in %s', $nubPath));
    }

    $lines[$index - 2] = sprintf('%s@var %s',
                                 strstr($lines[$index - 2], '@var', true),
                                 '\\'.ltrim($wrapperClass, '\\'));

    return implode(PHP_EOL, $lines);
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
  /**
   * Extracts the wrapper class name from the Stratum configuration file.
   *
   * @param string $configFilename The name of the Stratum configuration file.
   *
   * @return string
   */
  private function wrapperClass(string $configFilename): string
  {
    $config = new TypedConfig(new Config($configFilename));

    return $config->getManString('wrapper.wrapper_class');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

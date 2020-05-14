<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Noodlehaus\Config;
use Plaisio\Console\Helper\ClassHelper;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Plaisio\PlaisioKernel;
use SetBased\Config\TypedConfig;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for setting the type of the DataLayer in the kernel.
 */
class KernelDataLayerTypeCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The declaration of the DataLayer.
   */
  const PUBLIC_STATIC_DL = '/(?P<property>.*@property-read) (?P<class>.+) (?P<dl>\$DL) (?P<comment>.*)$/';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:kernel-data-layer-type')
         ->setDescription(sprintf('Sets the type of the DataLayer in %s', PlaisioKernel::class))
         ->addArgument('class', InputArgument::OPTIONAL, 'The class of the DataLayer');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: DataLayer Type Annotation');

    $wrapperClass = Cast::toOptString($input->getArgument('class'));
    if ($wrapperClass===null)
    {
      $configFilename = $this->phpStratumConfigFilename();
      $wrapperClass   = $this->wrapperClass($configFilename);
    }
    $nubPath = ClassHelper::classPath(PlaisioKernel::class);
    $source  = $this->fixAnnotation($nubPath, $wrapperClass);

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($nubPath, $source);

    return 0;
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
      if (preg_match(self::PUBLIC_STATIC_DL, $line)==1)
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

    preg_match(self::PUBLIC_STATIC_DL, $lines[$index], $parts);
    $lines[$index] = sprintf('%s %s %s %s',
                             $parts['property'],
                             '\\'.ltrim($wrapperClass, '\\'),
                             $parts['dl'],
                             trim($parts['comment']));

    return implode(PHP_EOL, $lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of the PhpStratum configuration file.
   *
   * @return string
   */
  private function phpStratumConfigFilename(): string
  {
    $path1   = PlaisioXmlUtility::plaisioXmlPath('stratum');
    $helper = new PlaisioXmlHelper($path1);

    $path2 = $helper->queryPhpStratumConfigFilename();

    return PlaisioXmlUtility::relativePath(dirname($path1).DIRECTORY_SEPARATOR.$path2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the wrapper class name from the PhpStratum configuration file.
   *
   * @param string $configFilename The name of the PhpStratum configuration file.
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

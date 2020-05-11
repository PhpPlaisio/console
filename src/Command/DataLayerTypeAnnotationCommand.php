<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Noodlehaus\Config;
use Plaisio\Console\Helper\ClassHelper;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use SetBased\Config\TypedConfig;
use SetBased\Exception\FallenException;
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
   * The declaration of the DataLayer, kernel 1.x.
   */
  const PUBLIC_STATIC_DL1 = 'public static $DL;';

  /**
   * The declaration of the DataLayer, kernel 2.x.
   */
  const PUBLIC_STATIC_DL2 = '/(?P<property>.*@property-read) (?P<class>.+) (?P<dl>\$DL) (?P<comment>.*)$/';

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:data-layer-type-annotation')
         ->setDescription(sprintf('Sets the type annotation of the DataLayer in %s', ClassHelper::PLAISIO_KERNEL_NUB))
         ->addArgument('class', InputArgument::OPTIONAL, 'The class of the DataLayer');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: DataLayer Type Annotation');

    $wrapperClass = $input->getArgument('class');
    if ($wrapperClass===null)
    {
      $configFilename = $this->phpStratumConfigFilename();
      $wrapperClass   = $this->wrapperClass($configFilename);
    }
    $nubPath = ClassHelper::classPath(ClassHelper::PLAISIO_KERNEL_NUB);
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
   * @return array
   */
  private function declarationOfDataLayer(array $lines): array
  {
    foreach ($lines as $index => $line)
    {
      if (preg_match(self::PUBLIC_STATIC_DL2, $line)==1)
      {
        return ['2.x', $index];
      }

      if (trim($line)==self::PUBLIC_STATIC_DL1)
      {
        return ['1.x', $index];
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
    [$version, $index] = $this->declarationOfDataLayer($lines);

    switch ($version)
    {
      case '1.x':
        if ($index<=4 || substr(trim($lines[$index - 2]), 0, 6)!=='* @var')
        {
          throw new RuntimeException('Annotation of the DataLayer not found in %s', $nubPath);
        }

        $lines[$index - 2] = sprintf('%s@var %s',
                                     strstr($lines[$index - 2], '@var', true),
                                     '\\'.ltrim($wrapperClass, '\\'));
        break;

      case '2.x':
        preg_match(self::PUBLIC_STATIC_DL2, $lines[$index], $parts);
        $lines[$index] = sprintf('%s %s %s %s',
                                 $parts['property'],
                                 '\\'.ltrim($wrapperClass, '\\'),
                                 $parts['dl'],
                                 trim($parts['comment']));
        break;

      default:
        throw new FallenException('version', $version);
    }

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
    $helper = new PlaisioXmlHelper();

    return $helper->queryPhpStratumConfigFilename();
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

<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Noodlehaus\Config;
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
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->io->title('Plaisio: DataLayer Type Annotation');

    $wrapperClass = Cast::toOptString($input->getArgument('class'));
    if ($wrapperClass===null)
    {
      $configFilename = $this->phpStratumConfigFilename();
      $wrapperClass   = $this->wrapperClass($configFilename);
    }

    $configPath = PlaisioXmlUtility::vendorDir().DIRECTORY_SEPARATOR.'plaisio/kernel/plaisio-kernel.xml';
    $config     = $this->updateDataLayerType($configPath, $wrapperClass);

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($configPath, $config);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of the PhpStratum configuration file.
   *
   * @return string
   */
  private function phpStratumConfigFilename(): string
  {
    $path1  = PlaisioXmlUtility::plaisioXmlPath('stratum');
    $helper = new PlaisioXmlHelper($path1);

    $path2 = $helper->queryPhpStratumConfigFilename();

    return PlaisioXmlUtility::relativePath(dirname($path1).DIRECTORY_SEPARATOR.$path2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Replaces the type annotation of the DataLayer with the actual wrapper class.
   *
   * @param string $path         The path to the config file plaisio-kernel.xml of package plaisio/kernel.
   * @param string $wrapperClass The name of the class of the DataLayer.
   *
   * @return string
   */
  private function updateDataLayerType(string $path, string $wrapperClass): string
  {
    $config = new PlaisioXmlHelper($path);
    $xml    = $config->xml();

    $xpath = new \DOMXpath($xml);
    $query = "/kernel/properties/property/name[text()='DL']";
    $list  = $xpath->query($query);
    if ($list->length!==1)
    {
      throw new RuntimeException('Unable to find the DataLayer in %s', $path);
    }

    $parent = $list->item(0)->parentNode;
    $query  = 'type';
    $list   = $xpath->query($query, $parent);
    if ($list->length!==1)
    {
      throw new RuntimeException('Unable to find the type of the DataLayer in %s', $path);
    }

    $list->item(0)->nodeValue = '\\'.ltrim($wrapperClass, '\\');

    return $xml->saveXML($xml);
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

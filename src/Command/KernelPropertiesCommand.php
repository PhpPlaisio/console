<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\ClassHelper;
use Plaisio\Console\Helper\PlaisioXmlHelper;
use Plaisio\Console\Helper\PlaisioXmlUtility;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Plaisio\PlaisioKernel;
use SetBased\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for adding a property to the kernel (i.e. class \Plaisio\Kernel\Nub).
 */
class KernelPropertiesCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:kernel-properties')
         ->setDescription('Adds properties to the kernel');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->io->title('Plaisio: Kernel Properties');

    $nubPath = ClassHelper::classPath(PlaisioKernel::class);
    $source  = $this->addProperties($nubPath);

    $this->io->text('');

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($nubPath, $source);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a property to the source of a class.
   *
   * @param string $path The path to the source of the class.
   *
   * @return string
   */
  private function addProperties(string $path): string
  {
    $source = file_get_contents($path);
    $lines  = explode(PHP_EOL, $source);

    $properties = $this->collectProperties();
    $lines      = $this->removeProperties($lines);
    $lines      = $this->appendProperties($lines, $properties);

    return implode(PHP_EOL, $lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds properties to the kernel.
   *
   * @param string[] $lines      The source of the class.
   * @param array    $properties The details of the properties.
   *
   * @return string[]
   */
  private function appendProperties(array $lines, array $properties): array
  {
    $key = ClassHelper::classDeclarationLine($lines, 'PlaisioKernel');

    if ($key<2 || $lines[$key - 1]!=' */')
    {
      throw new RuntimeException('Unable to add property');
    }

    if ($lines[$key - 2]!==' *')
    {
      array_splice($lines, $key - 1, 0, [' *']);
      $key++;
    }

    $code = [];
    foreach ($properties as $property)
    {
      $name = '$'.ltrim($property['name'], '$');

      $this->io->text(sprintf('Adding property %s', $name));

      $code[] = rtrim(sprintf(' * @property-read %s %s %s',
                              $property['type'],
                              $name,
                              $property['description']));
    }

    array_splice($lines, $key - 1, 0, $code);

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Collects all kernel properties defined in all plaisio-kernel.xml files under current project.
   *
   * @return array
   */
  private function collectProperties(): array
  {
    $files = PlaisioXmlUtility::findPlaisioXmlAll('kernel');

    $properties = [];

    foreach ($files as $file)
    {
      $config = new PlaisioXmlHelper($file);

      $properties = array_merge($properties, $config->queryKernelProperties());
    }

    usort($properties, function ($a, $b) {
      return $a['name']<=>$b['name'];
    });

    return $properties;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a property from the source of the kernel.
   *
   * @param string[] $lines The source of the class.
   *
   * @return string[]
   */
  private function removeProperties(array $lines): array
  {
    $ret = [];

    foreach ($lines as $line)
    {
      if (preg_match('/^(.*@property(-read)?)/', $line)===0)
      {
        $ret[] = $line;
      }
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

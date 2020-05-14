<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\ClassHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use Plaisio\PlaisioKernel;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for make a property in the kernel read/write (i.e. class \Plaisio\Kernel\Nub).
 */
class KernelPropertyReadWriteCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:kernel-property-read-write')
         ->setDescription('Makes a property of the kernel read/write')
         ->addArgument('name', InputArgument::REQUIRED, 'The name of the property');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: Read/Write Kernel Property');

    $name = Cast::toManString($input->getArgument('name'));

    $nubPath = ClassHelper::classPath(PlaisioKernel::class);
    $source  = $this->readWriteProperty($nubPath, $name);

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($nubPath, $source);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a property to the source of a class.
   *
   * @param string $path The path to the source of the class.
   * @param string $name The name of the property.
   *
   * @return string
   */
  private function readWriteProperty(string $path, string $name): string
  {
    $name = '$'.ltrim($name, '$');

    $source = file_get_contents($path);
    $lines  = explode(PHP_EOL, $source);

    $key = ClassHelper::propertyDeclarationLine($lines, $name);

    $lines[$key] = str_replace('@property-read', '@property     ', $lines[$key]);

    return implode(PHP_EOL, $lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

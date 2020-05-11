<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\Console\Helper\ClassHelper;
use Plaisio\Console\Helper\TwoPhaseWrite;
use SetBased\Exception\RuntimeException;
use SetBased\Helper\Cast;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for adding a property to the kernel (i.e. class \Plaisio\Kernel\Nub).
 */
class KernelPropertyAddCommand extends PlaisioCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function configure()
  {
    $this->setName('plaisio:kernel-property-add')
         ->setDescription('Adds a property to the kernel')
         ->addArgument('class', InputArgument::REQUIRED, 'The fully qualified name of the class of the property')
         ->addArgument('name', InputArgument::REQUIRED, 'The name of the property')
         ->addArgument('description', InputArgument::OPTIONAL, 'The description of the property');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io->title('Plaisio: Add Kernel Property');

    $class       = Cast::toManString($input->getArgument('class'));
    $name        = Cast::toManString($input->getArgument('name'));
    $description = Cast::toOptString($input->getArgument('description'));

    $nubPath = ClassHelper::classPath(ClassHelper::PLAISIO_KERNEL_NUB);
    $source  = $this->addProperty($nubPath, $class, $name, $description);

    $helper = new TwoPhaseWrite($this->io);
    $helper->write($nubPath, $source);

    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a property to the source of a class.
   *
   * @param string      $path        The path to the source of the class.
   * @param string      $class       The class of the property.
   * @param string      $name        The name of the property.
   * @param string|null $description The description of the property.
   *
   * @return string
   */
  private function addProperty(string $path, string $class, string $name, ?string $description): string
  {
    $class       = '\\'.ltrim($class, '\\');
    $name        = '$'.ltrim($name, '$');
    $description = preg_replace('/\s+/', ' ', $description ?? '');

    $source = file_get_contents($path);
    $lines  = explode(PHP_EOL, $source);

    $lines = $this->removeProperty($lines, $name);
    $lines = $this->appendProperty($lines, $class, $name, $description);

    return implode(PHP_EOL, $lines);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Appends a property to the kernel.
   *
   * @param array  $lines       The source of the class.
   * @param string $class       The fully qualified name of the class of the property,
   * @param string $name        The name of the property.
   * @param string $description The description of the property.
   *
   * @return string[]
   */
  private function appendProperty(array $lines, string $class, string $name, string $description): array
  {
    $key = ClassHelper::classDeclarationLine($lines, 'Nub');

    if ($key<2 || $lines[$key - 1]!=' */')
    {
      throw new RuntimeException('Unable to add property');
    }

    $code = rtrim(sprintf(' * @property-read %s %s %s', $class, $name, $description));

    array_splice($lines, $key - 1, 0, [$code]);

    return $lines;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Removes a property from the source of the kernel.
   *
   * @param string[] $lines The source of the class.
   * @param string   $name  The name of the property.
   *
   * @return string[]
   */
  private function removeProperty(array $lines, string $name): array
  {
    $ret = [];

    $pattern = sprintf('/^(.*@property(-read)?) (.+) (%s)(.*)$/', preg_quote($name));
    foreach ($lines as $line)
    {
      if (preg_match($pattern, $line)===0)
      {
        $ret[] = $line;
      }
    }

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

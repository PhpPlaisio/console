<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper;

use SetBased\Exception\RuntimeException;

/**
 * Helper class for retrieving information from plaisio.xml files.
 */
class PlaisioXmlHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The path to the plaisio.xml file.
   *
   * @var string
   */
  protected string $path;

  /**
   * The XML of the plaisio.xml.
   *
   * @var \DOMDocument
   */
  protected \DOMDocument $xml;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * PlaisioXmlHelper constructor.
   *
   * @param string $path Path to the PhpPlaisio configuration file.
   */
  public function __construct(string $path)
  {
    $this->path = $path;

    try
    {
      $this->xml = new \DOMDocument();
      $this->xml->load($this->path, LIBXML_NOWARNING);
    }
    catch (\Throwable $exception)
    {
      throw new RuntimeException([$exception], 'Failed to read Plaisio config %s', $this->path);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the factory for creating an instance of PlaisioKernel.
   *
   * @return string|null
   */
  public function queryConsoleKernelFactory(): ?string
  {
    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/console/kernel/factory');
    if ($list->length===1)
    {
      $factory = trim($list->item(0)->nodeValue);

      return ($factory==='') ? null : $factory;
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns all commands found in any plaisio.xml under the current project.
   *
   * @return array
   */
  public function queryPlaisioCommands(): array
  {
    $commands = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/commands/command');
    foreach ($list as $item)
    {
      /** @var \DOMElement $item */
      $name            = $item->getAttribute('name');
      $class           = trim($item->nodeValue);
      $commands[$name] = function () use ($class) {
        return new $class;
      };
    }

    return $commands;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

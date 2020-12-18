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
   * Returns the path to plaisio.xml.
   *
   * @return string
   */
  public function path(): string
  {
    return $this->path;
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
    if ($list->length==1)
    {
      $factory = trim($list->item(0)->nodeValue);

      return ($factory==='') ? null : $factory;
    }

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns all kernel properties in this PhpPlaisio config file.
   *
   * @return array
   */
  public function queryKernelProperties(): array
  {
    $properties = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/kernel/properties/property');
    foreach ($list as $item)
    {
      $properties[] = ['type'        => $xpath->query('type', $item)[0]->nodeValue ?? null,
                       'name'        => $xpath->query('name', $item)[0]->nodeValue ?? null,
                       'description' => $xpath->query('description', $item)[0]->nodeValue ?? null];
    }

    return $properties;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the path to the config file of PhpStratum.
   *
   * @return string
   */
  public function queryPhpStratumConfigFilename(): string
  {
    $xpath = new \DOMXpath($this->xml);
    $node  = $xpath->query('/stratum/config')->item(0);

    if ($node===null)
    {
      throw new RuntimeException('PhpStratum configuration file not defined in %s', $this->path);
    }

    return $node->nodeValue;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the PhpStratum source patterns for finding stored routines.
   *
   * @return string[]
   */
  public function queryPhpStratumSourcePatterns(): array
  {
    $patterns = [];

    $xpath = new \DOMXpath($this->xml);
    $list  = $xpath->query('/stratum/includes/include');
    foreach ($list as $item)
    {
      $patterns[] = $item->nodeValue;
    }

    return $patterns;
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
  /**
   * Returns the content of the configuration file as a DOMDocument.
   *
   * @return \DOMDocument
   */
  public function xml(): \DOMDocument
  {
    return $this->xml;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\Fileset;

use Plaisio\Console\Helper\ConfigException;

/**
 * Parses the XML definition of a fileset.
 *
 * The definition of a fileset look like this:
 * <fileset dir="...">
 *    <include name="..."/>
 *    <include name="..."/>
 *    ...
 *    <exclude name="..."/>
 *    <exclude name="..."/>
 * </fileset>
 */
class FilesetXmlParser
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The directory of the fileset.
   *
   * @var string
   */
  private $dir = null;

  /**
   * The exclude patterns.
   *
   * @var array
   */
  private $excludes = [];

  /**
   * The include patterns.
   *
   * @var array
   */
  private $includes = [];

  /**
   * The DOM node with the fileset.
   *
   * @var \DOMNode
   */
  private $node;

  /**
   * The path to the XML file.
   *
   * @var string
   */
  private $path;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * FilesetXmlParser constructor.
   *
   * @param string   $path The path to the XML file.
   * @param \DOMNode $node The DOM node with the fileset.
   */
  public function __construct(string $path, \DOMNode $node)
  {
    $this->path = $path;
    $this->node = $node;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Parses the XML definition of fileset.
   *
   * @return array
   *
   * @throws ConfigException
   */
  public function parse(): array
  {
    $this->parseElement();
    $this->parseChildNodes();

    return ['dir'      => $this->dir,
            'includes' => $this->includes,
            'excludes' => $this->excludes];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Parses the attributes of a include or exclude child node of the fileset.
   *
   * @param \DOMNode $node  The child node.
   * @param array    $array The includes or exclude array.
   *
   * @throws ConfigException
   */
  private function parsePatternNodeAttributes(\DOMNode $node, array &$array): void
  {
    foreach ($node->attributes as $name => $value)
    {
      switch ($name)
      {
        case 'name':
          $array[] = $value->value;
          break;

        default:
          throw new ConfigException("Unexpected attribute '%s' at %s:%s'", $name, $this->path, $node->getLineNo());
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Parses the child nodes of the fileset.
   *
   * @throws ConfigException
   */
  private function parseChildNodes(): void
  {
    foreach ($this->node->childNodes as $node)
    {
      /** @var \DOMNode $node */
      switch ($node->nodeName)
      {
        case 'include':
          $this->parsePatternNodeAttributes($node, $this->includes);
          break;

        case 'exclude':
          $this->parsePatternNodeAttributes($node, $this->excludes);
          break;

        case '#text';
          break;

        default:
          throw new ConfigException("Unexpected child node '%s' found at %s:%d",
                                    $node->nodeName,
                                    $this->path,
                                    $node->getLineNo());
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Parses the open tag of a fileset.
   *
   * @throws ConfigException
   */
  private function parseElement(): void
  {
    if ($this->node->nodeName!=='fileset')
    {
      throw new ConfigException('Expecting a fileset at %s:%s', $this->path, $this->node->getLineNo());
    }

    foreach ($this->node->attributes as $name => $value)
    {
      switch ($name)
      {
        case 'dir':
          $this->dir = $value->value;
          break;

        default:
          throw new ConfigException("Unexpected attribute '%s' at %s:%s'", $name, $this->path, $this->node->getLineNo());
      }
    }

    if ($this->dir===null)
    {
      throw new ConfigException("Mandatory attribute 'dir' not set %s:%s'", $this->path, $this->node->getLineNo());
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

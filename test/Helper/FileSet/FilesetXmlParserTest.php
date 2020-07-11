<?php
declare(strict_types=1);

namespace Plaisio\Console\Test\Helper\FileSet;

use PHPUnit\Framework\TestCase;
use Plaisio\Console\Helper\Fileset\FilesetXmlParser;

/**
 *
 */
class FilesetXmlParserTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test against a valid XML definition: empty fileset.
   */
  public function testValid1(): void
  {
    $xml = <<< EOT
<fileset dir="src">
</fileset>
EOT;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $node0 = $document->childNodes->item(0);

    $helper  = new FilesetXmlParser('test.xml', $node0);
    $fileset = $helper->parse();

    self::assertEquals('src', $fileset['dir']);
    self::assertSame([], $fileset['includes']);
    self::assertEquals([], $fileset['excludes']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test against a valid XML definition: with includes and no excludes.
   */
  public function testValid2(): void
  {
    $xml = <<< EOT
<fileset dir="src">
   <include name="**/*.php"/>
   <include name="**/*.txt"/>
</fileset>
EOT;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $node0 = $document->childNodes->item(0);

    $helper  = new FilesetXmlParser('test.xml', $node0);
    $fileset = $helper->parse();

    self::assertEquals('src', $fileset['dir']);
    self::assertEquals(['**/*.php', '**/*.txt'], $fileset['includes']);
    self::assertEquals([], $fileset['excludes']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test against a valid XML definition: with no includes and excludes.
   */
  public function testValid3(): void
  {
    $xml = <<< EOT
<fileset dir="src">
   <exclude name="**/*Test.php"/>
   <exclude name="README.txt"/>
</fileset>
EOT;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $node0 = $document->childNodes->item(0);

    $helper  = new FilesetXmlParser('test.xml', $node0);
    $fileset = $helper->parse();

    self::assertEquals('src', $fileset['dir']);
    self::assertEquals([], $fileset['includes']);
    self::assertEquals(['**/*Test.php', 'README.txt'], $fileset['excludes']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test against a valid XML definition: with includes and excludes.
   */
  public function testValid4(): void
  {
    $xml = <<< EOT
<fileset dir="src">
   <include name="**/*.php"/>
   <include name="**/*.txt"/>
   <exclude name="**/*Test.php"/>
   <exclude name="README.txt"/>
</fileset>
EOT;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $node0 = $document->childNodes->item(0);

    $helper  = new FilesetXmlParser('test.xml', $node0);
    $fileset = $helper->parse();

    self::assertEquals('src', $fileset['dir']);
    self::assertEquals(['**/*.php', '**/*.txt'], $fileset['includes']);
    self::assertEquals(['**/*Test.php', 'README.txt'], $fileset['excludes']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test against a invalid XML definition.
   */
  public function testInvalidAttribute1(): void
  {
    $xml = <<< EOT
<fileset dir="src">
   <include name="**/*.php"/>
   <include name="**/*.txt"/>
   <exclude name="**/*Test.php" spam="eggs"/>
   <exclude name="README.txt"/>
</fileset>
EOT;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $node0 = $document->childNodes->item(0);

    $helper  = new FilesetXmlParser('test.xml', $node0);
    $this->expectExceptionMessageMatches("/Unexpected attribute 'spam' at test.xml:4/");
    $helper->parse();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

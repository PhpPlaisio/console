<?php
declare(strict_types=1);

namespace Plaisio\Console\Command;

use Plaisio\PlaisioKernel;

/**
 * Trait for commands that require access to the kernel of PhpPlaisio.
 */
trait PlaisioKernelCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The kernel of PhpPlaisio.
   *
   * @var PlaisioKernel
   */
  protected PlaisioKernel $nub;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets kernel of PhpPlaisio.
   *
   * @param PlaisioKernel $nub The kernel of PhpPlaisio.
   */
  public function setPlaisioKernel(PlaisioKernel $nub): void
  {
    $this->nub = $nub;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

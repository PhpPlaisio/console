<?php
declare(strict_types=1);

namespace Plaisio\Console\Helper\Assets;

use SetBased\Stratum\SqlitePdo\SqlitePdoDataLayer;

/**
 * The data layer.
 */
class AssetsStore extends SqlitePdoDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Deletes an asset from the current assets.
   *
   * @param string|null $pCurType    The asset type (css, images, js).
   * @param string|null $pCurBaseDir The directory relative to project root to base dir of the asset.
   * @param string|null $pCurToDir   The directory under the asset dir (assert root + asset type).
   * @param string|null $pCurPath    The relative path of the asset.
   */
  public function plsCurrentAssetsDeleteAsset(?string $pCurType, ?string $pCurBaseDir, ?string $pCurToDir, ?string $pCurPath): void
  {
    $replace = [':p_cur_type' => $this->quoteVarchar($pCurType), ':p_cur_base_dir' => $this->quoteVarchar($pCurBaseDir), ':p_cur_to_dir' => $this->quoteVarchar($pCurToDir), ':p_cur_path' => $this->quoteVarchar($pCurPath)];
    $query   = <<< EOT
delete from PLS_CURRENT
where cur_type     = :p_cur_type
and   cur_base_dir = :p_cur_base_dir
and   cur_to_dir   = :p_cur_to_dir
and   cur_path     = :p_cur_path
EOT;
    $query = str_repeat(PHP_EOL, 10).$query;

    $this->executeNone($query, $replace);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all current assets.
   *
   * @return array[]
   */
  public function plsCurrentAssetsGetAll(): array
  {
    $query = <<< EOT
select cur_type
,      cur_base_dir
,      cur_to_dir
,      cur_path
from   PLS_CURRENT
order by cur_type
,        cur_base_dir
,        cur_to_dir
,        cur_path
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all actual assets.
   *
   * @return array[]
   */
  public function plsDiffCurrent(): array
  {
    $query = <<< EOT
select ass_type
,      ass_base_dir
,      ass_to_dir
,      ass_path
,      sum(case src when 1 then 1 else 0 end) cnt1
,      sum(case src when 2 then 1 else 0 end) cnt2
from
(
  select ass_type
  ,      ass_base_dir
  ,      ass_to_dir
  ,      ass_path
  ,      1               src
  from   PLS_ASSET

  union all

  select cur_type
  ,      cur_base_dir
  ,      cur_to_dir
  ,      cur_path
  ,      2               src
  from   PLS_CURRENT
) t
group by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
having cnt1 = 1
and    cnt2 = 1
order by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all new assets.
   *
   * @return array[]
   */
  public function plsDiffNew(): array
  {
    $query = <<< EOT
select ass_type
,      ass_base_dir
,      ass_to_dir
,      ass_path
,      sum(case src when 1 then 1 else 0 end) cnt1
,      sum(case src when 2 then 1 else 0 end) cnt2
from
(
  select ass_type
  ,      ass_base_dir
  ,      ass_to_dir
  ,      ass_path
  ,      1               src
  from   PLS_ASSET

  union all

  select cur_type
  ,      cur_base_dir
  ,      cur_to_dir
  ,      cur_path
  ,      2               src
  from   PLS_CURRENT
) t
group by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
having cnt1 = 1
and    cnt2 = 0
order by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all obsolete assets.
   *
   * @return array[]
   */
  public function plsDiffObsolete(): array
  {
    $query = <<< EOT
select ass_type
,      ass_base_dir
,      ass_to_dir
,      ass_path
,      sum(case src when 1 then 1 else 0 end) cnt1
,      sum(case src when 2 then 1 else 0 end) cnt2
from
(
  select ass_type
  ,      ass_base_dir
  ,      ass_to_dir
  ,      ass_path
  ,      1               src
  from   PLS_ASSET

  union all

  select cur_type
  ,      cur_base_dir
  ,      cur_to_dir
  ,      cur_path
  ,      2               src
  from   PLS_CURRENT
) t
group by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
having cnt1 = 0
and    cnt2 = 1
order by ass_type
,        ass_base_dir
,        ass_to_dir
,        ass_path
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------

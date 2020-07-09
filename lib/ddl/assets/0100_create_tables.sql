create table PLS_ASSET
(
  ass_type     varchar not null, -- The asset type (css, images, js).
  ass_base_dir varchar not null, -- The directory relative to project root to base dir of the asset.
  ass_path     varchar not null  -- The relative path of the asset.
);

create unique index idx_pls_asset_01 on PLS_ASSET(ass_type, ass_base_dir, ass_path);

create table PLS_CURRENT
(
  cur_type     varchar not null, -- The asset type (css, images, js).
  cur_base_dir varchar not null, -- The directory relative to project root to base dir of the asset.
  cur_path     varchar not null  -- The relative path of the asset.
);

create unique index idx_pls_current_01 on PLS_CURRENT(cur_type, cur_base_dir, cur_path);

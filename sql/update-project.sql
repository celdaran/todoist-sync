UPDATE project
SET name           = '$name',
    child_order    = $child_order,
    collapsed      = $collapsed,
    color          = '$color',
    inbox_project  = $inbox_project,
    is_archived    = $is_archived,
    is_deleted     = $is_deleted,
    is_favorite    = $is_favorite,
    parent_id      = $parent_id,
    shared         = $shared,
    sync_id        = '$sync_id',
    view_style     = '$view_style'
WHERE id = '$id';

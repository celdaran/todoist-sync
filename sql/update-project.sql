UPDATE project
SET name           = '$name',
    child_order    = $child_order,
    collapsed      = $collapsed,
    color          = $color,
    has_more_notes = $has_more_notes,
    inbox_project  = $inbox_project,
    is_archived    = $is_archived,
    is_deleted     = $is_deleted,
    is_favorite    = $is_favorite,
    parent_id      = $parent_id,
    modified       = CURRENT_TIMESTAMP,
    last_synced    = CURRENT_TIMESTAMP
WHERE id = $id;

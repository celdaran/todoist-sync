UPDATE section
SET name          = '$name',
    project_id    = $project_id,
    collapsed     = $collapsed,
    added_at      = '$added_at',
    archived_at   = '$archived_at',
    is_archived   = $is_archived,
    is_deleted    = $is_deleted,
    section_order = $section_order,
    sync_id       = '$sync_id',
    user_id       = $user_id
WHERE id = $id;

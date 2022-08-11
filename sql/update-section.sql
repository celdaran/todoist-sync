UPDATE section
SET name          = '$name',
    project_id    = $project_id,
    collapsed     = $collapsed,
    date_added    = '$date_added',
    date_archived = '$date_archived',
    is_archived   = $is_archived,
    is_deleted    = $is_deleted,
    section_order = $section_order,
    user_id       = $user_id,
    modified      = CURRENT_TIMESTAMP
WHERE id = $id;

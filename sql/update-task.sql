UPDATE task
SET content         = '$content',
    added_at        = '$added_at',
    added_by_uid    = '$added_by_uid',
    assigned_by_uid = '$assigned_by_uid',
    checked         = $checked,
    collapsed       = $collapsed,
    completed_at    = '$completed_at',
    description     = '$description',
    parent_id       = '$parent_id',
    priority        = $priority,
    project_id      = '$project_id',
    section_id      = '$section_id',
    sync_id         = '$sync_id',
    user_id         = '$user_id'
WHERE id = '$id';

UPDATE comment
SET task_id    = $item_id,
    project_id = $project_id,
    content    = '$content',
    is_deleted = $is_deleted,
    posted     = '$posted',
    modified   = CURRENT_TIMESTAMP
WHERE id = $id;

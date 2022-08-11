UPDATE label
SET name        = '$name',
    color       = $color,
    is_deleted  = $is_deleted,
    is_favorite = $is_favorite,
    task_order  = $task_order,
    modified    = CURRENT_TIMESTAMP
WHERE id = $id;

INSERT INTO label (
    id,
    name,
    color,
    is_deleted,
    is_favorite,
    task_order)
VALUES (
           $id,
           '$name',
           $color,
           $is_deleted,
           $is_favorite,
           $task_order)
;

SELECT name,
       child_order,
       collapsed,
       color,
       inbox_project,
       is_archived,
       is_deleted,
       is_favorite,
       parent_id,
       shared,
       sync_id,
       view_style
FROM project
WHERE id = '$id';

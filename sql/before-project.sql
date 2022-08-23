SELECT
    name,
    child_order,
    collapsed,
    color,
    has_more_notes,
    inbox_project,
    is_archived,
    is_deleted,
    is_favorite,
    parent_id
FROM project
WHERE id = $id;

SELECT
    name,
    project_id,
    collapsed,
    date_added,
    date_archived,
    is_archived,
    is_deleted,
    section_order,
    user_id
FROM section
WHERE id = $id;

SELECT name,
       project_id,
       collapsed,
       added_at,
       archived_at,
       is_archived,
       is_deleted,
       section_order,
       sync_id,
       user_id
FROM section
WHERE id = '$id';

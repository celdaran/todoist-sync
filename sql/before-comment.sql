SELECT item_id,
       content,
       is_deleted,
       posted_at,
       posted_uid
FROM comment
WHERE id = '$id';

SELECT name,
       color,
       is_deleted,
       is_favorite,
       item_order
FROM label
WHERE id = '$id';

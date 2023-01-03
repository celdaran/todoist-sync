UPDATE label
SET name        = '$name',
    color       = '$color',
    is_deleted  = $is_deleted,
    is_favorite = $is_favorite,
    item_order  = $item_order
WHERE id = '$id';

UPDATE comment
SET item_id    = '$item_id',
    content    = '$content',
    is_deleted = $is_deleted,
    posted_at  = '$posted_at',
    posted_uid = '$posted_uid'
WHERE id = '$id';

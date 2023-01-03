INSERT INTO comment (id,
                     item_id,
                     content,
                     is_deleted,
                     posted_at,
                     posted_uid)
VALUES ('$id',
        '$item_id',
        '$content',
        $is_deleted,
        '$posted_at',
        '$posted_uid')
;

INSERT INTO comment (id,
                  task_id,
                  project_id,
                  content,
                  is_deleted,
                  posted)
VALUES ($id,
        $item_id,
        $project_id,
        '$content',
        $is_deleted,
        '$posted')
;

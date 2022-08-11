DELETE FROM task_label
WHERE task_id = $task_id
  and label_id = $label_id
;

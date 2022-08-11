<?php namespace App\Service;

use SQLite3;

/**
 * Data wrapper
 */
class Data
{
    private SQLite3 $db;

    public function __construct()
    {
        $db = $_ENV['TODOIST_DATABASE'];
        $this->db = new SQLite3($db);
    }

    //------------------------------------------------------------------
    // Return table data
    //------------------------------------------------------------------

    /**
     * @return array
     */
    public function getProjects(): array
    {
        return $this->getRows('project');
    }

    /**
     * @return array
     */
    public function getSections(): array
    {
        return $this->getRows('section');
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->getRows('task');
    }

    /**
     * @param int $taskId
     * @return array
     */
    public function getComments(int $taskId): array
    {
        return $this->getRows('', 'SELECT * FROM comment WHERE task_id = ' . $taskId);
    }

    /**
     * @param int $taskId
     * @return array
     */
    public function getTaskLabels(int $taskId): array
    {
        return $this->getRows('', 'SELECT * FROM task_label WHERE task_id = ' . $taskId);
    }

    //------------------------------------------------------------------
    // Primary update methods
    //------------------------------------------------------------------

    /**
     * @param array $label
     * @return Result
     */
    public function updateLabel(array $label): Result
    {
        return $this->execUpsert('label', $label);
    }

    /**
     * @param array $project
     * @return Result
     */
    public function updateProject(array $project): Result
    {
        return $this->execUpsert('project', $project);
    }

    /**
     * @param array $user
     * @return Result
     */
    public function updateUser(array $user): Result
    {
        return $this->execUpsert('user', $user);
    }

    /**
     * @param array $user
     * @return Result
     */
    public function insertUserHistory(array $user): Result
    {
        return $this->execInsert('user-history', $user);
    }

    /**
     * @param array $section
     * @return Result
     */
    public function updateSection(array $section): Result
    {
        return $this->execUpsert('section', $section);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTask(array $task): Result
    {
        return $this->execUpsert('task', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTaskDue(array $task): Result
    {
        return $this->execUpsert('task-due', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function updateTaskDeleted(array $task): Result
    {
        return $this->execUpsert('task-deleted', $task);
    }

    /**
     * @param array $task
     * @return Result
     */
    public function deleteTaskLabel(array $task): Result
    {
        return $this->execDelete('task-label', $task);
    }

    /**
     *
     * @param array $task
     * @return Result
     */
    public function insertTaskLabel(array $task): Result
    {
        return $this->execInsert('task-label', $task);
    }

    /**
     * @param array $import
     * @return Result
     */
    public function insertImportHistory(array $import): Result
    {
        return $this->execInsert('import-history', $import);
    }

    /**
     * @param array $comment
     * @return Result
     */
    public function updateComment(array $comment): Result
    {
        return $this->execUpsert('comment', $comment);
    }

    //------------------------------------------------------------------
    // Internal sqlite data access layer
    //------------------------------------------------------------------

    /**
     * @param string $table
     * @param ?string $query
     * @return array
     */
    private function getRows(string $table, ?string $query = null): array
    {
        $rows = [];

        if ($query === null) {
            $query = 'SELECT * FROM ' . $table;
        }

        $result = $this->db->query($query);

        $row = true;
        while ($row !== false) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row !== false) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     */
    private function execUpsert(string $table, array $row): Result
    {
        $result = new Result();

        // Get the update statement
        $sql = $this->doSubs($this->getSql($table, 'update'), $row);

        // Run it and check results
        $succeeded = $this->db->exec($sql);
        if ($succeeded) {

            // If it succeeded, find out how many rows were affected
            $changes = $this->db->querySingle('SELECT changes()');

            // If no rows, then nothing updated, so let's insert
            if ($changes === 0) {
                $sql = $this->doSubs($this->getSql($table, 'insert'), $row);
                $succeeded = $this->db->exec($sql);
                if ($succeeded) {
                    $result->setSucceeded();
                    $result->setInserted();
                    $result->setMessage("Inserted $table row {$row['id']}");
                } else {
                    $msg = $this->db->lastErrorMsg();
                    $result->setMessage("Insert failed: $msg");
                }

                // If one change, then we're good: upsert complete
            } else {
                if ($changes === 1) {
                    $result->setSucceeded();
                    $result->setUpdated();
                    $result->setMessage("Updated $table row {$row['id']}");

                    // Otherwise, this is alarming
                } else {
                    $msg = $this->db->lastErrorMsg();
                    $result->setMessage("Unexpected value returned from changes(): $changes, msg: $msg");
                }
            }

            // The update itself didn't work
        } else {
            $msg = $this->db->lastErrorMsg();
            $result['message'] = "Error running initial update: $msg";
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     */
    private function execDelete(string $table, array $row): Result
    {
        $result = new Result();

        $sql = $this->doSubs($this->getSql($table, 'delete'), $row);

        $succeeded = $this->db->exec($sql);
        if ($succeeded) {
            $result->setSucceeded();
            $result->setDeleted();
            $result->setMessage("Rows deleted");
        } else {
            $msg = $this->db->lastErrorMsg();
            $result->setMessage("Error executing delete: $msg");
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $row
     * @return Result
     */
    private function execInsert(string $table, array $row): Result
    {
        $result = new Result();

        $sql = $this->doSubs($this->getSql($table, 'insert'), $row);

        $succeeded = $this->db->exec($sql);
        if ($succeeded) {
            $result->setSucceeded();
            $result->setInserted();
            $result->setMessage("Rows inserted");
        } else {
            $msg = $this->db->lastErrorMsg();
            $result->setMessage("Error executing delete: $msg");
        }

        return $result;
    }

    //------------------------------------------------------------------
    // File I/O
    //------------------------------------------------------------------

    private function getSql(string $object, string $verb) : string
    {
        return file_get_contents(__DIR__."/../sql/$verb-$object.sql");
    }

    //------------------------------------------------------------------
    // Helpers
    //------------------------------------------------------------------

    /**
     * @param string $str
     * @param array $array
     * @return string
     */
    private function doSubs(string $str, array $array): string
    {
        if ($str === '') {
            $str = 'QUERY-NOT-FOUND';
        }

        foreach ($array as $name => $value) {
            $str = $this->_subLogic($str, $name, $value);
        }

        return $str;
    }

    private function _subLogic($str, $name, $value): string
    {
        if ($value === false) {
            // true automatically maps to 1, false doesn't
            // so we'll handle that here
            return str_replace('$' . $name, 0, $str);
        }

        if (($value === null) || ($value === 'null')) {
            // Turn null values (or strings that look like null) in to null SQL statements
            return str_replace('$' . $name, 'null', $str);
        }

        if (is_array($value)) {
            // Attempt to turn array keys into flattened column
            // names. e.g., due.date --> due_date
            foreach ($value as $subname => $subvalue) {
                $derivedName = $name.'_'.$subname;
                $str = $this->_subLogic($str, $derivedName, $subvalue);
            }
            return $str;
        }

        if (is_numeric($value)) {
            return str_replace('$' . $name, $value, $str);
        } else {
            return str_replace('$' . $name, SQLite3::escapeString($value), $str);
        }
    }

}

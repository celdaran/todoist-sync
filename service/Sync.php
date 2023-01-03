<?php namespace App\Service;

use Exception;
use Dotenv\Dotenv;

/**
 * Primary class for todoist synchronization app
 * It talks to Todoist via the Sync API
 * And stores what it learns in a local sqlite database
 */
class Sync
{
    /** @var string */
    const VERSION = '9.0.0';

    /** @var Todoist */
    private Todoist $todoist;

    /** @var Data */
    private Data $data;

    /** @var array */
    private array $stats;

    /** @var array */
    private array $debug;

    public function init()
    {
        echo "---------------------------------------------------------\n";
        echo "Script version " . self::VERSION . " started at " . date('c') . "\n";
        echo "---------------------------------------------------------\n";

        // Read .env file
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        // Instantiate main Todoist and Database classes
        $this->todoist = new Todoist();
        $this->data = new Data();

        // Initialize stats
        $this->stats = [];
        $this->debug = [
            'projects' => 0,
            'archived-tasks' => 0,
        ];
    }

    /**
     * Primary sync method
     */
    public function exec()
    {
        try {
            # Pass 1: Active
            $todoist = $this->todoist->getAll();
            $this->updateLabels($todoist['labels']);
            $this->updateProjects($todoist['projects']);
            $this->updateUser($todoist['user']);
            $this->updateSections($todoist['sections']);
            $this->updateTasks($todoist['items']);    // tasks are still called items in api v8 and v9
            $this->updateComments($todoist['notes']); // comments are still called notes in api v8 and v9

            # Pass 2: Archive
            $projects = $this->data->getProjects();
            foreach ($projects as $p) {
                $this->debug['projects']++;
                $tasks = $this->todoist->getArchive($p['id']);
                $this->updateArchivedProject($tasks);
            }
        }
        catch (Exception $e) {
            echo "Exception detected: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Finalize execution by reporting stats
     */
    public function term()
    {
        $batch = date('c');
        foreach ($this->stats as $entity => $value) {
            $sum = [
                'batch' => $batch,
                'entity' => $entity,
                'success' => 0,
                'inserted' => 0,
                'updated' => 0,
                'deleted' => 0,
            ];
            /** @var Result $result */
            foreach ($value as $result) {
//                echo $entity .
//                    "\t" . ($result->getSucceeded() ? 1 : 0) .
//                    "\t" . ($result->getInserted() ? 1 : 0) .
//                    "\t" . ($result->getUpdated() ? 1 : 0) .
//                    "\t" . ($result->getDeleted() ? 1 : 0) .
//                    "\t" . $result->getMessage() .
//                    "\n";
                if ($result->getSucceeded()) {
                    $sum['success']++;
                }
                if ($result->getInserted()) {
                    $sum['inserted']++;
                }
                if ($result->getUpdated()) {
                    $sum['updated']++;
                }
                if ($result->getDeleted()) {
                    $sum['deleted']++;
                }
            }
            $this->data->insertImportHistory($sum);
        }

        $stats = $this->todoist->getApiStats();

        echo "Total API calls: " . $stats['post_count'] . "\n";
        echo "Total projects: " . $this->debug['projects'] . "\n";
        echo "Total tasks: " . count($this->stats['tasks']) . "\n";
        echo "Total archived tasks: " . $this->debug['archived-tasks'] . "\n";
        echo "Script finished at " . date('c') . "\n";
    }

    //------------------------------------------------------------------
    // Primary sync methods
    //------------------------------------------------------------------

    /**
     * @param array $labels
     */
    private function updateLabels(array $labels)
    {
        foreach ($labels as $label) {
            $result = $this->data->updateLabel($label);
            $this->stats['labels'][] = $result;
        }
    }

    /**
     * @param array $projects
     */
    private function updateProjects(array $projects)
    {
        foreach ($projects as $project) {
            $result = $this->data->updateProject($project);
            $this->stats['projects'][] = $result;
        }
    }

    /**
     * @param array $user
     */
    private function updateUser(array $user)
    {
        $result = $this->data->updateUser($user);
        $this->stats['user'][] = $result;

        $result = $this->data->insertUserHistory($user);
        $this->stats['user-history'][] = $result;
    }

    /**
     * @param array $sections
     */
    private function updateSections(array $sections)
    {
        foreach ($sections as $section) {
            $result = $this->data->updateSection($section);
            $this->stats['sections'][] = $result;
        }
    }

    /**
     * @param array $tasks
     */
    private function updateTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            // Handle task table
            $result = $this->data->updateTask($task);
            $this->stats['tasks'][] = $result;

            // Break down due-date substructure into upserts
            if (is_array($task['due'])) {
                $taskDue = [
                    'id' => $task['id'],
                    'due_date' => $task['due']['date'],
                    'due_is_recurring' => $task['due']['is_recurring'],
                    'due_string' => $task['due']['string'],
                ];

                $result = $this->data->updateTaskDue($taskDue);
                $this->stats['tasks-due'][] = $result;
            }

            // Lastly, upsert labels
            if (is_array($task['labels']) && count($task['labels']) > 0) {

                // first, fetch what's already stored locally
                $localTaskLabels = $this->data->getTaskLabels($task['id']);
                $a = [];
                foreach ($localTaskLabels as $localTaskLabel) {
                    $a[] = $localTaskLabel['label_id'];
                }

                $upstreamLabelsNotFoundLocally = array_diff($task['labels'], $a);
                $localLabelsNotFoundUpstream = array_diff($a, $task['labels']);
                /*
                    php > $a = [1, 2, 3, 5, 7, 11, 13, 17, 19];
                    php > $b = [1, 3, 5, 7, 9, 11, 13, 15, 17];

                    php > print_r(array_diff($a, $b));
                    Array
                    (
                        [1] => 2
                        [8] => 19
                    )

                    php > print_r(array_diff($b, $a));
                    Array
                    (
                        [4] => 9
                        [7] => 15
                    )
                 */

                //
                foreach ($localLabelsNotFoundUpstream as $label_id) {
                    $taskLabel = [
                        'task_id' => $task['id'],
                        'label_id' => $label_id,
                    ];

                    $result = $this->data->deleteTaskLabel($taskLabel);
                    $this->stats['tasks-labels'][] = $result;
                }

                foreach ($upstreamLabelsNotFoundLocally as $label_id) {
                    $taskLabel = [
                        'task_id' => $task['id'],
                        'label_id' => $label_id,
                    ];

                    $result = $this->data->insertTaskLabel($taskLabel);
                    $this->stats['tasks-labels'][] = $result;
                }
            }
        }
    }

    /**
     * @param array $comments
     */
    private function updateComments(array $comments)
    {
        foreach ($comments as $comment) {
            $result = $this->data->updateComment($comment);
            $this->stats['comments'][] = $result;
        }
    }

    /**
     * Accept an array of tasks from sync/v9/archive/items?project_id=9999
     * and update statuses in local database
     *
     * @param array $tasks
     */
    private function updateArchivedProject(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->debug['archived-tasks']++;
            $this->data->updateTask($task);
        }
    }

}

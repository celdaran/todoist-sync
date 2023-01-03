<?php namespace App\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Todoist API Wrapper Class
 */
class Todoist
{
    /** @var Client */
    private Client $client;

    /** @var array */
    private array $stats;

    /**
     * Instantiates guzzle client
     */
    public function __construct()
    {
        $endpoint = $_ENV['TODOIST_API_ENDPOINT'];
        $this->client = new Client([
            'base_uri' => $endpoint,
        ]);
        $this->stats = [
            'post_count' => 0,
        ];
    }

    /**
     * Call the Sync API to get all resources
     * @return array
     */
    public function getAll(): array
    {
        // get real data
        return $this->postSync('sync', [
            'sync_token' => '*',
            'resource_types' => '["all"]',
        ]);
    }

    /**
     * Call the Sync API to get all archived resources
     * @param string $projectId
     * @return array
     */
    public function getArchive(string $projectId): array
    {
        $archive['has_more'] = true;
        $archives = [];

        while ($archive['has_more']) {
            $uri = 'archive/items?project_id=' . $projectId;
            if (array_key_exists('next_cursor', $archive)) {
                $uri .= '&cursor=' . $archive['next_cursor'];
            }
            $archive = $this->postSync($uri, [
                'sync_token' => '*',
                'resource_types' => '["all"]',
            ]);
            foreach ($archive['items'] as $delme) {
                if ($delme['parent_id']) {
                    $foo = 'bar';
                }
            }
            $archives = array_merge($archives, $archive['items']);
        }

        return $archives;
    }

    /**
     * Call the Sync API to get an individual task
     * @param int $taskId
     * @return array
     */
    public function getTask(int $taskId): array
    {
        $task = $this->postSync('items/get', ['item_id' => $taskId]);
        if (($task === null) || (is_array($task) && count($task) === 0)) {
            $task = [
                'item' => [
                    'id' => $taskId,
                    'is_deleted' => true,
                ]];
        }
        return $task;
    }

    /**
     * Call the Sync API to get an individual project
     * @param int $projectId
     * @return array
     */
    public function getProject(int $projectId): array
    {
        return $this->postSync('projects/get', ['project_id' => $projectId, 'all_data' => true]);
    }

    /**
     * Call the Sync API to get an individual section
     * @param int $sectionId
     * @return array
     */
    public function getSection(int $sectionId): array
    {
        return $this->postSync('sections/get', ['section_id' => $sectionId]);
    }

    /**
     * Return API stats
     * @return array|int[]
     */
    public function getApiStats(): array
    {
        return $this->stats;
    }

    /**
     * @param string $uri
     * @param array $body
     * @return array
     * @throws Exception
     */
    private function postSync(string $uri, array $body = []): array
    {
        try {
            $uri = $_ENV['TODOIST_API_VERSION'] . $uri;
            $element = $this->_postSync($uri, $body);
        } catch (GuzzleException $e) {
            if ($e->getCode() !== 404) {
                // If it's anything other than a 404, [re]throw the error
                throw new Exception($e->getMessage());
            }
            $element = [];
        } catch (Exception $e) {
            echo "Other Exception: " . $e->getMessage();
            $element = [];
        }

        return $element;
    }

    /**
     * @throws GuzzleException
     */
    private function _postSync(string $uri, array $body = [])
    {
        $token = $_ENV['TODOIST_API_TOKEN'];

        $request = $this->client->post($uri, [
            'headers' => [
                'Authorization' => "Bearer $token"
            ],
            'form_params' => $body,
        ]);

        echo "POST $uri " . json_encode($body) . "\n";

        $contents = $request->getBody()->getContents();

        $this->stats['post_count']++;

        return json_decode($contents, true);
    }
}

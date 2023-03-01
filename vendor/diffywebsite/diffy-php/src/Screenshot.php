<?php

namespace Diffy;

class Screenshot
{
    static $TYPES = ['production', 'staging', 'development', 'custom', 'upload'];

    // Screenshots were not started.
    const NOT_STARTED = 0;
    // Actively in progress.
    const PROGRESS = 1;
    // Completed but event "completed" is not yet fired. We send notifications,
    // webhook on this event.
    const COMPLETED = 2;
    // "Completed" event is fired. Starting to create a zipfile.
    const COMPLETED_HOOK_EXECUTED = 3;
    // Zipfile is completed.
    const ZIPFILE = 4;

    /**
     * Screenshot's data.
     *
     * @var array
     */
    public $data;

    public $screenshotId;

    protected function __construct(int $screenshotId)
    {
        $this->screenshotId = $screenshotId;
    }

    /**
     * Create set of Screenshots.
     *
     * @param int $projectId
     * @param string $environment
     * @param array $options
     *
     * @return mixed
     *
     * @throws InvalidArgumentsException
     */
    public static function create(int $projectId, string $environment, $options = [])
    {
        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }

        if (!in_array($environment, self::$TYPES)) {
            throw new InvalidArgumentsException('"' . $environment . '" is not a valid environment. Can be one of: production, staging, development, custom');
        }

        if ($environment == 'custom' && !isset($options['baseUrl'])) {
            throw new InvalidArgumentsException('If custom environment selected you need to specify "customUrl" parameter');
        }

        return Diffy::request('POST', 'projects/' . $projectId . '/screenshots', [
            'environment' => $environment,
        ] + $options);
    }

    /**
     * Set whole set of screenshots as a Baseline.
     *
     * @param int $projectId
     * @param int $screenshotId
     *
     * @return mixed
     */
    public static function setBaselineSet(int $projectId, int $screenshotId)
    {
        return Diffy::request('PUT', 'projects/' . $projectId . '/set-base-line-set/' . $screenshotId);
    }

    /**
     * Get list of all Screenshots.
     */
    public static function all(int $projectId)
    {
        return Diffy::request('GET', 'projects/' . $projectId . '/screenshots');
    }

    /**
     * Load full info on Screenshot.
     *
     * @param int $screenshotId
     *
     * @return Screenshot
     */
    public static function retrieve(int $screenshotId)
    {
        $instance = new Screenshot($screenshotId);
        $instance->refresh();

        return $instance;
    }

    /**
     * Refresh data about current Screenshot.
     */
    public function refresh()
    {
        $this->data = Diffy::request('GET', 'snapshots/' . $this->screenshotId);
    }

    /**
     * Set name of Screenshot.
     */
    public function setName($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentsException('Screenshot name can not be empty');
        }

        $this->data = Diffy::request('POST', 'snapshots/' . $this->screenshotId, ['name' => $name]);
    }

    /**
     * Check if Screenshot is completed.
     *
     * @return boolean
     */
    public function isCompleted()
    {
        return in_array($this->data['state'], [self::COMPLETED, self::COMPLETED_HOOK_EXECUTED, self::ZIPFILE]);
    }

    public function getEstimate()
    {
        return $this->data['status']['estimate'];
    }

    /**
     * Create set of Screenshots from images.
     *
     * @param int $projectId
     *
     * @throws InvalidArgumentsException
     */
    public static function createUpload(int $projectId, array $upload)
    {
        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }

        if (!isset($upload['files']) || !is_array($upload['files'])) {
            throw new InvalidArgumentsException('"files" property is missing or is not an array');
        }

        if (!isset($upload['snapshotName']) || empty($upload['snapshotName'])) {
            throw new InvalidArgumentsException('"snapshotName" property is missing');
        }

        if (!isset($upload['breakpoints']) || !is_array($upload['breakpoints'])) {
            throw new InvalidArgumentsException('"breakpoints" property is missing or is not an array');
        }

        if (!isset($upload['urls']) || !is_array($upload['urls'])) {
            throw new InvalidArgumentsException('"urls" property is missing or is not an array');
        }

        if (count($upload['files']) != count($upload['urls']) || count($upload['urls']) != count($upload['urls'])) {
            throw new InvalidArgumentsException('Number of "urls", "breakpoints" and "files" should be the same');
        }

        foreach ($upload['files'] as $filepath) {
            if (!file_exists($filepath)) {
                throw new InvalidArgumentsException(sprintf('File %s can not be found. Check file exists and readable.', $filepath));
            }
        }

        $data = [
            ['name' => 'snapshotName', 'contents' => $upload['snapshotName']],
        ];

        foreach ($upload['breakpoints'] as $key => $breakpoint) {
            $data[] = ['name' => 'breakpoints[' . $key . ']', 'contents' => $breakpoint];
        }

        foreach ($upload['urls'] as $key => $url) {
            $data[] = ['name' => 'urls[' . $key . ']', 'contents' => $url];
        }

        foreach ($upload['files'] as $key => $filepath) {
            $data[] = [
                'Content-type' => 'multipart/form-data',
                'name' => 'files[' . $key . ']',
                'filename' => basename($filepath),
                'contents' => file_get_contents($filepath),
            ];
        }

        return Diffy::multipartRequest('POST', 'projects/' . $projectId . '/create-custom-snapshot', $data);
    }

    /**
     * Create Screenshot from browserstack.
     *
     * @param int $projectId
     * @param array $screenshots
     *
     * @return mixed
     *
     * @throws InvalidArgumentsException
     */
    public static function createBrowserStackScreenshot(int $projectId, array $screenshots)
    {
        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }

        if (empty($screenshots)) {
            throw new InvalidArgumentsException('Screenshots list can not be empty');
        }

        return Diffy::request('POST', 'projects/' . $projectId . '/create-browser-stack-screenshot', [
            'screenshots' => $screenshots,
        ]);
    }

    /**
     * Create Screenshot with custom files.
     *
     * @param int $projectId
     * @param array $data Format: $data[] = ['file'=> '', 'url'=> '', 'breakpoint'=> '']. File should be in PNG format.
     * @param string $screenshotName
     *
     * @return mixed
     *
     * @throws InvalidArgumentsException
     */
    public static function createCustomScreenshot(int $projectId, array $data, string $screenshotName)
    {
        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }

        if (empty($data)) {
            throw new InvalidArgumentsException('Data list can not be empty');
        }

        $params = [
            'multipart' => [
                [
                    'name' => 'snapshotName',
                    'contents' => $screenshotName,
                ],
            ],
        ];

        foreach ($data as $key => $item) {
            if (
                isset($item['file']) && isset($item['url']) && isset($item['breakpoint']) &&
                !empty($item['file']) && !empty($item['url']) && !empty($item['breakpoint'])
            ) {
                $params['multipart'][] = ['name' => "files[$key]", 'contents' => $item['file']];
                $params['multipart'][] = ['name' => "urls[$key]", 'contents' => $item['url']];
                $params['multipart'][] = ['name' => "breakpoints[$key]", 'contents' => $item['breakpoint']];
            } else {
                throw new InvalidArgumentsException('Data list contain not valid data. Each item of list should be array with items: [\'file\'=> \'\', \'url\'=> \'\', \'breakpoint\'=> \'\']');
            }
        }

        return Diffy::request('POST', 'projects/' . $projectId . '/create-custom-snapshot', [], $params);
    }
}

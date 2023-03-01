<?php

namespace Diffy;

class Project
{
    public static $ENVIRONMENTS = ['prod', 'stage', 'dev', 'baseline', 'custom', 'upload'];

    /**
     * Get list of all Projects.
     */
    public static function all()
    {
        return Diffy::request('GET', 'projects');
    }

    /**
     * @param int $projectId Project ID.
     *
     * @return mixed
     *
     * @throws InvalidArgumentsException
     */
    public static function compare(int $projectId, $params = [])
    {
        if (!isset($params['env1'])) {
            throw new InvalidArgumentsException('Compare call requires "env1" as the first environment to compare.');
        }

        if (!isset($params['env2'])) {
            throw new InvalidArgumentsException('Compare call requires "env2" as the second environment to compare.');
        }

        if (!in_array($params['env1'], self::$ENVIRONMENTS)) {
            throw new InvalidArgumentsException('"env1" is not a valid environment. Can be one of: prod, stage, dev, baseline, custom');
        }

        if (!in_array($params['env2'], self::$ENVIRONMENTS)) {
            throw new InvalidArgumentsException('"env2" is not a valid environment. Can be one of: prod, stage, dev, baseline, custom');
        }

        if ($params['env1'] == 'custom' && !isset($params['env1Url'])) {
            throw new InvalidArgumentsException('"env1" is Custom but you did not provide URl. Please provide it in env1Url variable');
        }

        if ($params['env2'] == 'custom' && !isset($params['env2Url'])) {
            throw new InvalidArgumentsException('"env2" is Custom but you did not provide URl. Please provide it in env2Url variable');
        }

        $arguments = [
            'env1' => $params['env1'],
            'env2' => $params['env2'],
        ];

        if ($params['env1'] == 'custom') {
            $arguments['env1Url'] = $params['env1Url'];

            if (isset($params['env1User']) && isset($params['env1Pass'])) {
                $arguments['env1CredsMode'] = true;
                $arguments['env1CredsUser'] = $params['env1User'];
                $arguments['env1CredsPass'] = $params['env1Pass'];
            }
        }

        if ($params['env2'] == 'custom') {
            $arguments['env2Url'] = $params['env2Url'];

            if (isset($params['env2User']) && isset($params['env2Pass'])) {
                $arguments['env2CredsMode'] = true;
                $arguments['env2CredsUser'] = $params['env2User'];
                $arguments['env2CredsPass'] = $params['env2Pass'];
            }
        }

        if (isset($params['commitSha'])) {
            $arguments['commitSha'] = $params['commitSha'];
        }

        return Diffy::request('POST', 'projects/' . $projectId . '/compare', $arguments);
    }

    /**
     * Get project settings.
     *
     * @param int $projectId
     *
     * @return mixed
     */
    public static function get(int $projectId)
    {
        return Diffy::request('GET', 'projects/' . $projectId);
    }

    /**
     * Scan a URL for internal pages.
     *
     * @param string $url
     *
     * @throws InvalidArgumentsException
     */
    public static function scan(string $url)
    {
        if (empty($url)) {
            throw new InvalidArgumentsException('Scanning URL can not be empty');
        }

        return Diffy::request('POST', 'scan', ['url' => $url]);
    }

    /**
     * Create a project with production URL $url.
     *
     * @param string $base_url
     * @param array $urls
     *
     * @throws InvalidArgumentsException
     */
    public static function create(string $base_url, array $urls)
    {
        if (empty($base_url)) {
            throw new InvalidArgumentsException('Project Production URL can not be empty');
        }

        if (empty($urls)) {
            throw new InvalidArgumentsException('Project URLs list can not be empty');
        }

        $data = [
            'baseUrl' => $base_url,
            'name' => $base_url,
            'urls' => $urls,
            'staging' => '',
            'scanUrl' => '',
        ];

        return Diffy::request('POST', 'projects', $data);
    }

    /**
     * Create project.
     *
     * @return mixed
     */
    public static function createFromData(array $data)
    {
        return Diffy::request('POST', 'projects', $data);
    }

    /**
     * Update project settings.
     *
     * @param int $projectId
     *
     * @return mixed
     */
    public static function update(int $projectId, array $data)
    {
        return Diffy::request('POST', 'projects/' . $projectId, $data);
    }
}

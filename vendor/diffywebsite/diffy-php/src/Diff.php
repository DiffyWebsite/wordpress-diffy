<?php

namespace Diffy;

/**
 * Class to interact with Diffs.
 */
class Diff
{

    // Statuses of the Diff:
    const NOTSTARTED = 0;
    // If there are not completed snapshots (they are in progress).
    // The difference between these two is non-trivial. SNAPSHOT_IN_PROGRESS
    // is when Diff has *active jobs*. I.e. our queue has jobs that we process.
    // SNAPSHOT_IN_PROGRESS_DIFF_IN_PROGRESS on the other hand means that screenshots
    // are in progress but we do not have any active jobs in the queue. This was
    // done in order not to mix with the situation when incompleted Diff has no
    // jobs. This means that it was "stopped".
    const SNAPSHOT_IN_PROGRESS_DIFF_IN_PROGRESS = 8;
    const SNAPSHOT_IN_PROGRESS = 5;
    // If all screenshots are completed and diff is in progress
    const PROGRESS = 1;
    // Diff is completed but event was not triggered (webhooks, notifications)
    const COMPLETED = 2;
    // Completed event is completed. Creating a zipfile.
    const COMPLETED_HOOK_EXECUTED = 3;
    // Zipfile is created.
    const ZIPFILE = 4;
    // Diff witout zip.
    const WITHOUT_ZIP = 7;

    /**
     * Diff's data.
     *
     * @var array
     */
    public $data;

    public $diffId;

    /**
     * Diff constructor.
     */
    protected function __construct(int $diffId)
    {
        $this->diffId = $diffId;
    }

    /**
     * Create a Diff.
     *
     * @param int $projectId
     * @param int $screenshotId1
     * @param int $screenshotId2
     * @return mixed
     * @throws \Diffy\InvalidArgumentsException
     */
    public static function create(int $projectId, int $screenshotId1, int $screenshotId2)
    {

        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }
        if (empty($screenshotId1)) {
            throw new InvalidArgumentsException('Screenshot 1 ID can not be empty');
        }
        if (empty($screenshotId2)) {
            throw new InvalidArgumentsException('Screenshot 2 ID can not be empty');
        }

        return Diffy::request(
            'POST',
            'projects/'.$projectId.'/diffs',
            [
                'snapshot1' => $screenshotId1,
                'snapshot2' => $screenshotId2,
            ]
        );
    }

    /**
     * Load full info on Diff.
     *
     * @param int $diffId
     * @return \Diffy\Diff
     */
    public static function retrieve(int $diffId)
    {
        $instance = new Diff($diffId);
        $instance->refresh();

        return $instance;
    }

    /**
     * Refresh data about current Diff.
     */
    public function refresh()
    {
        $this->data = Diffy::request('GET', 'diffs/'.$this->diffId);
    }

    /**
     * Check if Diff is completed.
     *
     * @return boolean
     */
    public function isCompleted()
    {
        return in_array($this->data['state'], [self::COMPLETED, self::ZIPFILE, self::COMPLETED_HOOK_EXECUTED, self::WITHOUT_ZIP]);
    }

    /**
     * How long it will take to complete the diff.
     *
     * @return string
     */
    public function getEstimate()
    {
      return 'under 1 minute';
    }

    /**
     * How long it will take to complete the diff.
     *
     * @return string
     */
    public function getReadableResult()
    {
      if ($this->data['result'] == 0) {
        return 'No changes found';
      }
      return sprintf('%d%% pages changed. <a target="_blank" href="%s">See the report</a>', (int)$this->data['result'], $this->data['diffSharedUrl']);
    }

    /**
     * Check if Diff is failed.
     *
     * @return boolean
     */
    public function isFailed()
    {
        return !in_array(
            $this->data['state'],
            [
                self::NOTSTARTED,
                self::SNAPSHOT_IN_PROGRESS_DIFF_IN_PROGRESS,
                self::SNAPSHOT_IN_PROGRESS,
                self::PROGRESS,
                self::COMPLETED,
                self::ZIPFILE,
                self::COMPLETED_HOOK_EXECUTED,
                self::WITHOUT_ZIP,
            ]
        );
    }

    /**
     * Get the percentage of changes in pages.
     *
     * For example if there were 20 pages to compare and we found changes in
     * 2 pages result will be 10 (%).
     *
     * @return integer
     */
    public function getChangesPercentage()
    {
        return $this->data['result'];
    }

    /**
     * Get diffs list for project.
     *
     * @param int $projectId
     * @param int $page
     * @return mixed
     * @throws InvalidArgumentsException
     */
    public static function list(int $projectId, int $page = 0)
    {

        if (empty($projectId)) {
            throw new InvalidArgumentsException('Project ID can not be empty');
        }

        return Diffy::request(
            'GET',
            'projects/'.$projectId.'/diffs?page='.$page
        );
    }

    /**
     * Get diff state name.
     *
     * @param $state
     * @return string
     */
    public static function getStateName($state)
    {
        $name = '';
        switch ($state) {
            case self::NOTSTARTED:
                $name = 'Not started';
                break;
            case self::PROGRESS:
            case self::SNAPSHOT_IN_PROGRESS:
            case self::SNAPSHOT_IN_PROGRESS_DIFF_IN_PROGRESS:
                $name = 'In progress';
                break;
            case self::COMPLETED:
            case self::COMPLETED_HOOK_EXECUTED:
            case self::ZIPFILE:
            case self::WITHOUT_ZIP:
                $name = 'Completed';
                break;
        }

        return $name;
    }

}

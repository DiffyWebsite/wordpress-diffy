<?php

require_once('vendor/autoload.php');

$project_id = 999;
$screenshot_id = 32092;
$key = 'c31fec8e123e479e75d46744c13a7d91';

\Diffy\Diffy::setApiKey($key);

// List all projects.
//print_r(\Diffy\Project::all());

// Create screenshots.
//print_r(\Diffy\Screenshot::create($project_id, 'production'));

// Retrieve a Screenshot.
//$screenshot = \Diffy\Screenshot::retrieve($screenshot_id);
//var_export($screenshot->isCompleted());
// Full data about Screenshot.
//print_r($screenshot->data);

// Set screenshots set as baseline.
//print_r(\Diffy\Screenshot::setBaselineSet($project_id, $screenshot_id));

// Compare environments.
//print_r(\Diffy\Project::compare($project_id, ['env1' => 'prod', 'env2' => 'stage']));

// Create a Diff.
//$screenshot_id1 = \Diffy\Screenshot::create($project_id, 'production');
//$screenshot_id2 = \Diffy\Screenshot::create($project_id, 'staging');
//print_r(\Diffy\Diff::create($project_id, $screenshot_id1, $screenshot_id2));

// Create custom screenshot with file uploads.
//$screenshotName = 'custom test';
//$data = [];
//$data[] = ['file'=> fopen(__DIR__. '/720.png', 'r'), 'url'=> '/', 'breakpoint'=> 720];
//$data[] = ['file'=> fopen(__DIR__.'/1280.png', 'r'), 'url'=> '/', 'breakpoint'=> 1280];
//
//print_r(\Diffy\Screenshot::createCustomScreenshot($project_id, $data, $screenshotName));

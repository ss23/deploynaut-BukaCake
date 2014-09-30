<?php
use \Symfony\Component\Process\Process;

class BukaCakeDeploymentBackend implements DeploymentBackend {
	
	/**
	 * This is a bit of a hack. To actually get the deployment, we should check one of the live servers configuration
	 * TODO: Make this not a hack
	 */
	public function currentBuild($environment) {
		$file = DEPLOYNAUT_LOG_PATH . '/' . $environment . ".deploy-history.txt";

		if(file_exists($file)) {
			$lines = file($file);
			$lastLine = array_pop($lines);
			return $this->convertLine($lastLine);
		}
	}

	/**
	 * Use Snowcake to do the deployment
	 * TODO: OH GOD PLEASE WHY NO
	 */
	public function deploy(DNEnvironment $environment, $sha, DeploynautLogFile $log, DNProject $project, $leaveMaintenancePage = false) {
		$log->write('Deploying "'.$sha.'" to "'.$projectName.':'.$environmentName.'"');

		if (!defined('SNOWCAKE_PATH')) {
			$log->write('SNOWCAKE_PATH is not defined');
			throw new RuntimeException("SNOWCAKE_PATH is not defined");
		}
		
		// Construct our snowcake command
		// ./bin/snowcake-linux deploy $ENVIRONMENT $RECOGNIZABLESTRING $SHA
		$name = $environment->Name . '-' . substr($sha, 0, 8) . '-' . mt_rand();
		$command = SNOWCAKE_PATH . ' deploy ' . $environment->Name . ' ' . $name . ' ' . $sha;
		
		$log->write("Running command: $command");

		$process = new Process($command);
		$process->setTimeout(3600);

		$process->run(function ($type, $buffer) use($log) {
			$log->write($buffer);
		});

		// OH GOD, AN ERROR?
		if(!$process->isSuccessful()) {
			throw new RuntimeException($command->getErrorOutput());
		}

		$log->write('Deploy done "'.$sha.'" to "'.$projectName.':'.$environmentName.'"');
	}

	public function enableMaintenance(DNEnvironment $environment, DeploynautLogFile $log, DNProject $project) {
		// NOOP
	}

	public function disableMaintenance(DNEnvironment $environment, DeploynautLogFile $log, DNProject $project) {
		// NOOP
	}

	public function dataTransfer(DNDataTransfer $dataTransfer, DeploynautLogFile $log) {
		throw new Exception("No dataTransfer implemented");
	}

	/**
	 * TODO: implment
	 */
	public function ping(DNEnvironment $environment, DeploynautLogFile $log, DNProject $project) {
		throw new Exception("Not implemented yet");
	}

}

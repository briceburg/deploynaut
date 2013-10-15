<?php

class DNBuild extends ViewableData {

	/**
	 *
	 * @var string
	 */
	protected $filename;

	/**
	 *
	 * @var string
	 */
	protected $buildname;

	/**
	 *
	 * @var string
	 */
	protected $simplename;

	/**
	 * Project this DNBuild belongs to. Effectively, a has_one-like relatonship.
	 */
	protected $project;

	/**
	 *
	 * @var DNData
	 */
	protected $data;

	/**
	 *
	 * @param string $filename
	 * @param string $project
	 * @param DNData $data
	 */
	public function __construct($filename, $project, DNData $data) {
		$this->data = $data;
		$this->project = $project;

		$this->filename = $filename;
		$this->buildname = preg_replace('/\.tar\.gz$/', '', basename($this->filename));
		$this->simplename = preg_replace('/^[^-]+-/', '', $this->buildname);

		parent::__construct();
	}

	/**
	 *
	 * @return string
	 */
	public function Link() {
		return Controller::join_links('naut', 'project', $this->project->Name, "build", $this->name);
	}

	/**
	 *
	 * @return string
	 */
	public function FullName() {
		return $this->buildname;
	}

	/**
	 *
	 * @return string
	 */
	public function Name() {
		return $this->simplename;
	}

	/**
	 *
	 * @return string
	 */
	public function Filename() {
		return $this->filename;
	}

	public function getProject() {
		return $this->project;
	}

	/**
	 *
	 * @return SS_Datetime
	 */
	public function Created() {
		$d = new SS_Datetime();
		$d->setValue(date('Y-m-d H:i:s', filemtime($this->filename)));
		return $d;
	}

	/**
	 *
	 * @return ArrayList
	 */
	public function CurrentlyDeployedTo() {
		$envNames = array();
		foreach($this->project->currentBuilds() as $envName => $currentBuild) {
			if($currentBuild == $this->buildname) $envNames[] = $envName;
		}

		if($envNames) return $this->project->Environments()->filter('Name', $envNames);
		else return new ArrayList;
	}

	/**
	 *
	 * @param type $environmentName
	 */
	public function EverDeployedTo($environmentName) {
		$environment = $this->project->DNEnvironmentList()->filter('Name', $environmentName)->First();
	}
}
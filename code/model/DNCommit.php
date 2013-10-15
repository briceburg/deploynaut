<?php
class DNCommit extends ViewableData {

	/**
	 *
	 * @var Gitonomy\Git\Commit
	 */
	protected $commit = null;

	protected $name = null;

	protected $references = null;

	protected $ownerBranchName = null;

	/**
	 *
	 * @param Gitonomy\Git\Commit $commit
	 * @param DNProject $project
	 * @param DNData $data
	 */
	public function __construct(Gitonomy\Git\Commit $commit, DNProject $project, DNData $data, $ownerBranchName = null) {
		$this->commit = $commit;
		$this->buildname = $commit->getHash();
		$this->project = $project;
		$this->data = $data;
		$this->ownerBranchName = $ownerBranchName;
	}

	/**
	 * Return the hash of the commit, used to name this commit.
	 * @return string
	 */
	public function Name() {
		if($this->name == null) {
			$this->name = $this->commit->getFixedShortHash(8);
		}

		return $this->name;
	}

	/**
	 * Return the first line of the commit message.
	 * @return string
	 */
	public function SubjectMessage() {
		if($this->subjectMessage == null) {
			$this->subjectMessage = $this->commit->getSubjectMessage();
		}

		return $this->subjectMessage;
	}

	public function BodyMessage() {
		if($this->bodyMessage == null) {
			$this->bodyMessage = $this->commit->getBodyMessage();
		}

		return $this->bodyMessage;
	}

	/**
	 *
	 * @return ArrayList
	 */
	public function References() {
		if($this->references !== null ) {
			return $this->references;
		}
		$this->references = new ArrayList();

		// Add tags
		foreach($this->commit->resolveReferences() as $reference) {
			if($reference instanceof \Gitonomy\Git\Reference\Tag) {
				$this->references->push(new ArrayData(array(
					'Name' => $reference->getName(),
					'Type' => 'Tag',
				)));
			}
		}

		// Add other branches that this SHA belongs to
		foreach(GitonomyCache::getIncludingBranches($this->commit) as $branch) {
			if(!$this->ownerBranchName || $branch->getName() != $this->ownerBranchName) {
				$this->references->push(new ArrayData(array(
					'Type' =>'OtherBranch',
					'Name' => $branch->getName(),
				)));
			}
		}

		return $this->references;
	}

	/**
	 *
	 * @return string
	 */
	public function FullName() {
		return $this->commit->getHash();
	}

	/**
	 *
	 * @return string
	 */
	public function Filename() {
		return $this->commit->getHash();
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
	 * @param string $environmentName
	 * @return boolean True if this release has ever been deployed to the given environment
	 */
	public function EverDeployedTo($environmentName) {
		$sha = $this->commit->getHash();

		$history = $this->data->Backend()->deployHistory($this->project->Name.':'.$environmentName);
		foreach($history as $item) {
			if($item['buildname'] == $sha) return true;
		}

		return false;
	}

	/**
	 * Release steps of the project and their status for this release
	 */
	public function ReleaseSteps() {
		$output = new ArrayList;
		$sha = $this->commit->getHash();

		foreach($this->project->ReleaseSteps() as $step) {
			$output->push($step->customise(array(
				'Status' => $step->getReleaseStatus($this, $sha),
				'Link' => $step->getReleaseLink($this, $sha),
			)));
		}

		return $output;
	}
	/**
	 *
	 * @return SS_Datetime
	 */
	public function Created() {
		$created = $this->commit->getCommitterDate();

		// gitonomy sets the time to UTC, so now we set the timezone to
		// whatever PHP is set to (date.timezone). This will change in the future if each
		// deploynaut user has their own timezone
		$created->setTimezone(new DateTimeZone(date_default_timezone_get()));

		$d = new SS_Datetime();
		$d->setValue($created->format('Y-m-d H:i:s'));

		return $d;
	}

}
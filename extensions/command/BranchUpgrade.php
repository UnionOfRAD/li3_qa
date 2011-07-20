<?php
/**
 * Lithium QA: a collection of commands to ensure code quality for development in the
 *             Lithium core and with Lithium applications.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium_qa\extensions\command;

/**
 * When pushing a new version, cleans up all local version-dependent feature branches which are
 * based on the existing version, and re-clones them based on the new version. For example:
 *
 * {{{li3 branch_upgrade /path/to/project 1.5 1.6}}}
 *
 * Given the local branch `data`, cloned from `origin/1.5-data`, the `data` branch will be dropped
 * and re-checked-out from `origin/1.6-data`.
 *
 * For more information on feature/version branching schemes, see
 * (http://rad-dev.org/wiki/guides/branch-strategy)
 */
class BranchUpgrade extends \lithium\console\Command {

	public function run() {
		$path = $this->request->action;

		if (count($this->request->params['passed']) < 3) {
			$this->error('Not enough arguments given.');
			return false;
		}
		if (!$path = realpath($path) || !is_dir($path)) {
			$this->error('Not a valid path to a project directory.');
			return false;
		}
		list($old, $new) = $this->request->params['passed'];
		$locals = $remotes = $trackings = array();
		$current = null;

		chdir($path);
		`git pull origin`;
		`git remote prune origin`;

		foreach (array_map('trim', explode("\n", trim(`git branch -a`))) as $branch) {
			if (strpos($branch, 'remotes/') === 0) {
				$remotes[] = substr($branch, 8);
				continue;
			}
			$locals[] = $branch;
		}

		foreach ($locals as $i => $branch) {
			if (strpos($branch, '*') === 0) {
				$locals[$i] = $branch = substr($branch, 2);
				$current = $branch;
			}
			$cmd = "git config --get branch.{$branch}";

			$remote = trim(str_replace('refs/heads/', '', `{$cmd}.merge`));
			$trackings[$branch] = $remote;
		}

		if ($current != 'master') {
			`git checkout master`;
		}

		$merged = array_map('trim', explode(
			"\n", str_replace('*', '', trim(`git branch --merged`))
		));

		foreach ($trackings + array('stable' => $old) as $local => $remote) {
			if (strpos($remote, "{$old}-") !== 0) {
				continue;
			}
			if (!in_array($local, $merged) && $local != 'stable') {
				$this->out("Branch {$local} has not been merged into HEAD - aborting.");
				$this->_stop();
			}
			$this->out("Dropping branch {$local} (tracks origin/{$remote})");

			if ($result = `git branch -d {$local}` && !preg_match('/^Deleted branch/', $result)) {
				$this->out("Problem encountered when dropping local branch {$local}: {$result}");
				$this->_stop();
			}
		}

		foreach ($remotes as $branch) {
			if (!strpos($branch = str_replace('origin/', '', $branch), $new) === 0) {
				continue;
			}
			if ($branch == $new) {
				$this->out("Checking out branch stable, tracking origin/{$new}");
				`git checkout -b stable --track origin/{$new}`;
				continue;
			}
			if (!strpos($branch, '-')) {
				continue;
			}
			list($version, $branch) = explode('-', $branch, 2);

			if ($version != $new) {
				continue;
			}

			$this->out("Checking out branch {$branch}, tracking origin/{$new}-{$branch}");
			`git checkout -b {$branch} --track origin/{$new}-{$branch}`;
		}
		`git checkout stable`;
	}
}

?>
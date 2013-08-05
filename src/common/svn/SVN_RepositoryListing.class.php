<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'SVN_PermissionsManager.class.php';
require_once 'SVN_Svnlook.class.php';
require_once 'SVN_RevisionPathInfo.class.php';

class SVN_RepositoryListing {
    /**
     * @var SVN_PermissionsManager
     */
    private $svn_permissions_manager;

    /**
     * @var SVN_Svnlook
     */
    private $svnlook;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(SVN_PermissionsManager $svn_permissions_manager, SVN_Svnlook $svnlook, UserManager $user_manager) {
        $this->svn_permissions_manager = $svn_permissions_manager;
        $this->svnlook                 = $svnlook;
        $this->user_manager            = $user_manager;
    }

    public function getSvnPaths(PFUser $user, Project $project, $svn_path) {
        $paths            = array();
        $content          = $this->svnlook->getDirectoryListing($project, $svn_path);
        foreach ($content as $line) {
            if ($this->svn_permissions_manager->userCanRead($user, $project, $line)) {
                $paths[]= $this->extractDirectoryContent($line, $svn_path);
            }
        }
        return array_filter($paths);
    }

    /**
     * Returns array of svn paths with log details
     *
     * @param PFUser $user
     * @param Project $project
     * @param string $svn_path
     *
     * @throws SVN_SvnlookException when parameters are invalid
     *
     * @return SVN_RevisionPathInfo[]
     */
    public function getSvnPathsWithLogDetails(PFUser $user, Project $project, $svn_path) {
        $data = array();
        $paths = $this->getSvnPaths($user, $project, $svn_path);

        if (empty($paths)) {
            return $data;
        }

        foreach ($paths as $svn_path) {
            $data[] = $this->getSvnSinglePathWithLogDetails($project, $svn_path);
        }

        return $data;
    }

    private function getSvnSinglePathWithLogDetails(Project $project, $svn_path) {
        $history = $this->splitHistory($this->svnlook->getPathLastHistory($project, $svn_path));
        $last_revison = $history[0];

        return $this->getRevisionInfo($last_revison, $project);
    }

    private function splitHistory(array $output) {
        $history = array();
        for ($i = 2; $i < count($output); $i++) {
            $matches = array();
            if (preg_match('/\s*(\d+)\s*(.*)/', $output[$i], $matches)) {
                $history[] = array(
                    'revision' => $matches[1],
                    'path'     => $matches[2],
                );
            }
        }
        return $history;
    }

    private function getRevisionInfo($revision, Project $project) {
        $info = $this->svnlook->getInfo($project, $revision['revision']);
        $date_parts = explode(' (', $info[1]);

        return new SVN_RevisionPathInfo(
            $revision['revision'],
            $revision['path'],
            $this->getUserId($info[0]),
            strtotime($date_parts[0]),
            $info[3]
        );
    }

    private function extractDirectoryContent($line, $svn_path) {
        $match_path_regex = "%^$svn_path/%";
        if (preg_match($match_path_regex, $line)) {
            return trim(preg_replace($match_path_regex, '', $line), '/');
        }
        return '';
    }

    private function getUserId($user_name) {
        $user = $this->user_manager->getUserByUserName($user_name);
        if (! $user) {
            return null;
        }

        return $user->getId();
    }
}
?>

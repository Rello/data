<?php
/**
 * Analytics
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <analytics@scherello.de>
 * @copyright 2019-2022 Marcel Scherello
 */

namespace OCA\Analytics\Service;

use OCA\Analytics\Activity\ActivityManager;
use OCA\Analytics\Db\ShareMapper;
use OCA\Analytics\Db\ReportMapper;
use OCP\DB\Exception;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class ShareService
{
    const SHARE_TYPE_USER = 0;
    const SHARE_TYPE_GROUP = 1;
    const SHARE_TYPE_USERGROUP = 2; // obsolete
    const SHARE_TYPE_LINK = 3;
    const SHARE_TYPE_ROOM = 10;

    /** @var IUserSession */
    private $userSession;
    /** @var LoggerInterface */
    private $logger;
    /** @var ShareMapper */
    private $ShareMapper;
    private $ReportMapper;
    private $secureRandom;
    private $ActivityManager;
    /** @var IGroupManager */
    private $groupManager;
    /** @var IUserManager */
    private $userManager;
    private $VariableService;

    public function __construct(
        IUserSession $userSession,
        LoggerInterface $logger,
        ShareMapper $ShareMapper,
        ReportMapper $ReportMapper,
        ActivityManager $ActivityManager,
        IGroupManager $groupManager,
        ISecureRandom $secureRandom,
        IUserManager $userManager,
        VariableService $VariableService
    )
    {
        $this->userSession = $userSession;
        $this->logger = $logger;
        $this->ShareMapper = $ShareMapper;
        $this->ReportMapper = $ReportMapper;
        $this->secureRandom = $secureRandom;
        $this->groupManager = $groupManager;
        $this->ActivityManager = $ActivityManager;
        $this->userManager = $userManager;
        $this->VariableService = $VariableService;
    }

    /**
     * create a new share
     *
     * @NoAdminRequired
     * @param $reportId
     * @param $type
     * @param $user
     * @return bool
     * @throws \OCP\DB\Exception
     */
    public function create($reportId, $type, $user)
    {
        $token = null;
        if ((int)$type === self::SHARE_TYPE_LINK) {
            $token = $this->generateToken();
        }
        $this->ShareMapper->createShare($reportId, $type, $user, $token);
        $this->ActivityManager->triggerEvent($reportId, ActivityManager::OBJECT_REPORT, ActivityManager::SUBJECT_REPORT_SHARE);
        return true;
    }

    /**
     * get all shares for a report
     *
     * @NoAdminRequired
     * @param $reportId
     * @return array
     */
    public function read($reportId)
    {

        $shares = $this->ShareMapper->getShares($reportId);
        foreach ($shares as &$share) {
            if ((int)$share['type'] === self::SHARE_TYPE_USER) {
                $share['displayName'] = $this->userManager->get($share['uid_owner'])->getDisplayName();
            }
            $share['pass'] = $share['pass'] !== null;
        }
        return $shares;
    }

    /**
     * get all report by token
     *
     * @NoAdminRequired
     * @param $token
     * @return array
     */
    public function getReportByToken($token)
    {
        $reportId = $this->ShareMapper->getReportByToken($token);
        return $this->VariableService->replaceTextVariables($reportId);
    }

    /**
     * verify password hahes
     *
     * @NoAdminRequired
     * @param $password
     * @param $sharePassword
     * @return bool
     */
    public function verifyPassword($password, $sharePassword)
    {
        return password_verify($password, $sharePassword);
    }

    /**
     * get all reports shared with user
     *
     * @NoAdminRequired
     * @throws Exception
     */
    public function getSharedReports()
    {
        $sharedReports = $this->ShareMapper->getAllSharedReports();
        $groupsOfUser = $this->groupManager->getUserGroups($this->userSession->getUser());
        $reports = array();

        foreach ($sharedReports as $sharedReport) {
            // shared with a group?
            if ($sharedReport['shareType'] === self::SHARE_TYPE_GROUP) {
                // is the current user part of this group?
                if (array_key_exists($sharedReport['shareUid_owner'], $groupsOfUser)) {
                    // was the report not yet added to the result?
                    if (!in_array($sharedReport["id"], array_column($reports, "id"))) {
                        unset($sharedReport['shareType']);
                        unset($sharedReport['shareUid_owner']);
                        $sharedReport['isShare'] = self::SHARE_TYPE_GROUP;
                        $reports[] = $sharedReport;
                    }
                }
            // shared with a user directly?
            } elseif ($sharedReport['shareType'] === self::SHARE_TYPE_USER) {
                // current user matching?
                if ($this->userSession->getUser()->getUID() === $sharedReport['shareUid_owner']) {
                    // was the report not yet added to the result?
                    if (!in_array($sharedReport["id"], array_column($reports, "id"))) {
                        unset($sharedReport['shareType']);
                        unset($sharedReport['shareUid_owner']);
                        $sharedReport['isShare'] = self::SHARE_TYPE_USER;
                        $reports[] = $sharedReport;
                    }
                }
            }
        }

        foreach ($reports as $report) {
            // if it is a shared group, get all reports below
            if ($report['type'] === ReportService::REPORT_TYPE_GROUP) {
                $subreport = $this->ReportMapper->getReportsByGroup($report['id']);
                $subreport = array_map(function($report) {
                    $report['isShare'] = self::SHARE_TYPE_GROUP;
                    return $report;
                }, $subreport);

                $reports = array_merge($reports, $subreport);
            }
        }
        return $reports;
    }

    /**
     * get metadata of a report, shared with current user
     * used to check if user is allowed to execute current report
     *
     * @NoAdminRequired
     * @param $reportId
     * @return array
     * @throws Exception
     */
    public function getSharedReport($reportId)
    {
        $sharedReport = $this->getSharedReports();
        if (in_array($reportId, array_column($sharedReport, "id"))) {
            $key = array_search($reportId, array_column($sharedReport, 'id'));
            return $sharedReport[$key];
        } else {
            return [];
        }
    }

    /**
     * Delete an own share (sharee or receiver)
     *
     * @NoAdminRequired
     * @param $shareId
     * @return bool
     * @throws Exception
     */
    public function delete($shareId)
    {
        $this->ShareMapper->deleteShare($shareId);
        return true;
    }

    /**
     * delete all shares for a report
     *
     * @NoAdminRequired
     * @param $reportId
     * @return bool
     */
    public function deleteShareByReport($reportId)
    {
        return $this->ShareMapper->deleteShareByReport($reportId);
    }

    /**
     * update/set share password
     *
     * @NoAdminRequired
     * @param $shareId
     * @param string|null $password
     * @param string|null $canEdit
     * @param string|null $domain
     * @return bool
     */
    public function update($shareId, $password = null, $canEdit = null, $domain = null)
    {
        if ($password !== null) {
            $password = password_hash($password, PASSWORD_DEFAULT);
            return $this->ShareMapper->updateSharePassword($shareId, $password);
        }
        if ($domain !== null) {
            return $this->ShareMapper->updateShareDomain($shareId, $domain);
        }
        if ($canEdit !== null) {
            $canEdit === true ? $canEdit = \OCP\Constants::PERMISSION_UPDATE : $canEdit = \OCP\Constants::PERMISSION_READ;
            return $this->ShareMapper->updateSharePermissions($shareId, $canEdit);
        }
    }

    /**
     * generate to token used to authenticate federated shares
     *
     * @return string
     */
    private function generateToken()
    {
        $token = $this->secureRandom->generate(
            15,
            ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS);
        return $token;
    }
}
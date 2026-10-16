<?php
// check_voting_timeout.php
// 檢查 group 係咪需要自動計票（48 小時已過）
// 需要：$conn, $groupID

if (!function_exists('finalizeGroupVoting')) {

    /**
     * 檢查並結算投票
     * @param mysqli $conn
     * @param int    $groupID
     * @return bool  true = 已處理 / 未需要處理
     */
    function finalizeGroupVoting($conn, $groupID) {

        // 1. 取 group 資料
        $stmt = mysqli_prepare($conn, "
            SELECT id, status, creator_id, formed_at, project_id
            FROM `groups`
            WHERE id = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $grp = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        if (!$grp) return false;

        // 只有 Voting 狀態才處理
        if ($grp['status'] !== 'Voting') return true;

        // 2. 取票數 + 成員數
        $stmt = mysqli_prepare($conn, "
            SELECT
                (SELECT COUNT(*) FROM `group_members` WHERE group_id = ?) AS total_members,
                (SELECT COUNT(*) FROM `leader_votes`   WHERE group_id = ?) AS total_votes
        ");
        mysqli_stmt_bind_param($stmt, "ii", $groupID, $groupID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $counts = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        $totalMembers = (int)$counts['total_members'];
        $totalVotes   = (int)$counts['total_votes'];

        if ($totalMembers === 0) return false;

        // 3. 判斷係咪要結算
        $allVoted = ($totalVotes >= $totalMembers);
        $timedOut = false;

        if (!empty($grp['formed_at'])) {
            $startTs   = strtotime($grp['formed_at']);
            $elapsed   = time() - $startTs;
            $timedOut  = ($elapsed >= 48 * 3600); // 48 小時
        }

        // 未夠鐘 + 未投完 → 唔結算
        if (!$allVoted && !$timedOut) {
            return true;
        }

        // =========================================================
        // 4. 開始計票
        // =========================================================
        $leaderID = 0;

        if ($totalVotes === 0) {
            // 冇人投票 → 發起人自動當選
            $leaderID = (int)$grp['creator_id'];

        } else {
            // 有人投票 → 搵最高票
            $stmt = mysqli_prepare($conn, "
                SELECT candidate_id, COUNT(*) AS vote_count
                FROM `leader_votes`
                WHERE group_id = ?
                GROUP BY candidate_id
                ORDER BY vote_count DESC
            ");
            mysqli_stmt_bind_param($stmt, "i", $groupID);
            mysqli_stmt_execute($stmt);
            $rs = mysqli_stmt_get_result($stmt);

            $candidates = array();
            $maxVotes   = 0;

            while ($row = mysqli_fetch_assoc($rs)) {
                $vc = (int)$row['vote_count'];
                if ($vc > $maxVotes) {
                    $maxVotes   = $vc;
                    $candidates = array((int)$row['candidate_id']);
                } elseif ($vc === $maxVotes) {
                    $candidates[] = (int)$row['candidate_id'];
                }
            }
            mysqli_stmt_close($stmt);

            // 同票 → 隨機揀一個
            if (count($candidates) > 0) {
                $leaderID = $candidates[array_rand($candidates)];
            } else {
                // 理論上唔會發生
                $leaderID = (int)$grp['creator_id'];
            }
        }

        // =========================================================
        // 5. 更新 group_members：先全部 member，再設 leader
        // =========================================================
        $stmt = mysqli_prepare($conn, "
            UPDATE `group_members`
            SET role = 'member'
            WHERE group_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "
            UPDATE `group_members`
            SET role = 'leader'
            WHERE group_id = ? AND student_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $groupID, $leaderID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // =========================================================
        // 6. 更新 group → Executing
        // =========================================================
        $stmt = mysqli_prepare($conn, "
            UPDATE `groups`
            SET leader_id = ?,
                status = 'Executing'
            WHERE id = ? AND status = 'Voting'
        ");
        mysqli_stmt_bind_param($stmt, "ii", $leaderID, $groupID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 同步 project
        $stmt = mysqli_prepare($conn, "
            UPDATE `projects`
            SET status = 'Executing'
            WHERE id = ? AND status = 'Voting'
        ");
        mysqli_stmt_bind_param($stmt, "i", $grp['project_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // =========================================================
        // 7. 攞 leader 資料
        // =========================================================
        $stmt = mysqli_prepare($conn, "
            SELECT u.id AS user_id, u.username
            FROM `students` s
            JOIN `users` u ON u.id = s.user_id
            WHERE s.id = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "i", $leaderID);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        $leaderUser = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($stmt);

        // =========================================================
        // 8. 寫 activities
        // ⚠️ mysqli_stmt_bind_param 需要變數，唔可以傳 (int) 表達式
        // =========================================================
        if ($leaderUser) {
            $activityProjectID = (int)$grp['project_id'];
            $activityGroupID   = (int)$groupID;
            $activityActorID   = (int)$leaderUser['user_id'];
            $detailFull        = 'was elected as team leader';

            $stmt = mysqli_prepare($conn, "
                INSERT INTO `activities` (project_id, group_id, actor_id, action, detail)
                VALUES (?, ?, ?, 'elected', ?)
            ");
            mysqli_stmt_bind_param(
                $stmt, "iiis",
                $activityProjectID, $activityGroupID, $activityActorID, $detailFull
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // =========================================================
        // 9. 通知所有組員
        // =========================================================
        $stmt = mysqli_prepare($conn, "
            SELECT s.user_id
            FROM `group_members` gm
            JOIN `students` s ON s.id = gm.student_id
            WHERE gm.group_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $rsMembers = mysqli_stmt_get_result($stmt);

        $memberUserIDs = array();
        while ($m = mysqli_fetch_assoc($rsMembers)) {
            $memberUserIDs[] = (int)$m['user_id'];
        }
        mysqli_stmt_close($stmt);

        if (count($memberUserIDs) > 0 && $leaderUser) {
            $notifyTitle = 'Group Leader Elected';

            if ($totalVotes === 0) {
                $notifyMessage = $leaderUser['username'] . ' (group creator) has been auto-elected as the group leader because no votes were cast within 48 hours.';
            } elseif ($timedOut && !$allVoted) {
                $notifyMessage = $leaderUser['username'] . ' has been elected as the group leader after the 48-hour voting window ended.';
            } else {
                $notifyMessage = $leaderUser['username'] . ' has been elected as the group leader.';
            }

            $notifyLink = 'student_create_group.php?group_id=' . $groupID;

            $stmt = mysqli_prepare($conn, "
                INSERT INTO `notifications` (user_id, type, title, message, link, is_read)
                VALUES (?, 'team', ?, ?, ?, 0)
            ");
            foreach ($memberUserIDs as $uid) {
                mysqli_stmt_bind_param($stmt, "isss", $uid, $notifyTitle, $notifyMessage, $notifyLink);
                mysqli_stmt_execute($stmt);
            }
            mysqli_stmt_close($stmt);
        }

        return true;
    }
}
<?php

/**
 * Handles all data manipulation of the admin part
 * 处理所有的数据操作管理的部分
 */
class AdminModel
{
    /**
     * Sets the deletion and suspension values
     * 设置删除和悬挂的值
     *
     * @param $suspensionInDays
     * @param $softDelete
     * @param $userId
     */
    public static function setAccountSuspensionAndDeletionStatus($suspensionInDays, $softDelete, $userId)
    {

        // Prevent to suspend or delete own account.
        // 防止暂停或删除自己的帐户。
        // If admin suspend or delete own account will not be able to do any action.
        // 如果管理员暂停或删除自己的帐户将无法做任何行动。
        if ($userId == Session::get('user_id')) {
            Session::add('feedback_negative', Text::get('FEEDBACK_ACCOUNT_CANT_DELETE_SUSPEND_OWN'));
            return false;
        }

        if ($suspensionInDays > 0) {
            $suspensionTime = time() + ($suspensionInDays * 60 * 60 * 24);
        } else {
            $suspensionTime = null;
        }

        // FYI "on" is what a checkbox delivers by default when submitted. Didn't know that for a long time :)
        // 通知你“on”是一个复选框默认提供时提交。不知道很长一段时间:)
        if ($softDelete == "on") {
            $delete = 1;
        } else {
            $delete = 0;
        }

        // write the above info to the database
        // 上面的信息写入数据库
        self::writeDeleteAndSuspensionInfoToDatabase($userId, $suspensionTime, $delete);

        // if suspension or deletion should happen, then also kick user out of the application instantly by resetting
        // the user's session :)
        // 如果发生暂停或删除,然后还把用户从应用程序立即重置用户的会话:)
        if ($suspensionTime != null OR $delete = 1) {
            self::resetUserSession($userId);
        }
    }

    /**
     * Simply write the deletion and suspension info for the user into the database, also puts feedback into session
     * 简单地删除和悬架为用户信息写入数据库,也使反馈到会话
     *
     * @param $userId
     * @param $suspensionTime
     * @param $delete
     * @return bool
     */
    private static function writeDeleteAndSuspensionInfoToDatabase($userId, $suspensionTime, $delete)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_suspension_timestamp = :user_suspension_timestamp, user_deleted = :user_deleted  WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(
            ':user_suspension_timestamp' => $suspensionTime,
            ':user_deleted' => $delete,
            ':user_id' => $userId
        ));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_SUSPENSION_DELETION_STATUS'));
            return true;
        }
    }

    /**
     * Kicks the selected user out of the system instantly by resetting the user's session.
     * This means, the user will be "logged out".
     * 将所选用户的系统立即通过重置用户的会话。
     * 这意味着,用户将“注销”。
     *
     * @param $userId
     * @return bool
     */
    private static function resetUserSession($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET session_id = :session_id  WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(
            ':session_id' => null,
            ':user_id' => $userId
        ));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_USER_SUCCESSFULLY_KICKED'));
            return true;
        }
    }
}

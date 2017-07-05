<?php

class AvatarModel
{
    /**
     * Gets a gravatar image link from given email address
     * 获得链接功能图像从给定的电子邮件地址
     *
     * Gravatar is the #1 (free) provider for email address based global avatar hosting.
     * The URL (or image) returns always a .jpg file ! For deeper info on the different parameter possibilities:
     * 功能是# 1(免费的)提供者基于电子邮件地址全球《阿凡达》主办。
     * 总是一个URL(或图像)的回报.jpg文件!更深层次的信息的不同参数的可能性:
     * @see http://gravatar.com/site/implement/images/
     * @source http://gravatar.com/site/implement/images/php/
     *
     * This method will return something like http://www.gravatar.com/avatar/79e2e5b48aec07710c08d50?s=80&d=mm&r=g
     * Note: the url does NOT have something like ".jpg" ! It works without.
     * 这个方法会返回类似 http://www.gravatar.com/avatar/79e2e5b48aec07710c08d50?s=80&d=mm&r=g
     * 注意:url没有类似".jpg"!它的工作原理。
     *
     * Set the configs inside the application/config/ files.
     * 设置application/config/文件中的配置。
     *
     * @param string $email The email address 电子邮件地址
     * @return string
     */
    public static function getGravatarLinkByEmail($email)
    {
        return 'http://www.gravatar.com/avatar/' .
            md5(strtolower(trim($email))) .
            '?s=' . Config::get('AVATAR_SIZE') . '&d=' . Config::get('GRAVATAR_DEFAULT_IMAGESET') . '&r=' . Config::get('GRAVATAR_RATING');
    }

    /**
     * Gets the user's avatar file path
     * 得到用户的《阿凡达》文件路径
     * @param int $user_has_avatar Marker from database 标记从数据库
     * @param int $user_id User's id 用户的id
     * @return string Avatar file path 《阿凡达》文件路径
     */
    public static function getPublicAvatarFilePathOfUser($user_has_avatar, $user_id)
    {
        if ($user_has_avatar) {
            return Config::get('URL') . Config::get('PATH_AVATARS_PUBLIC') . $user_id . '.jpg';
        }

        return Config::get('URL') . Config::get('PATH_AVATARS_PUBLIC') . Config::get('AVATAR_DEFAULT_IMAGE');
    }

    /**
     * Gets the user's avatar file path
     * 得到用户的《阿凡达》文件路径
     * @param $user_id integer The user's id 用户的id
     * @return string avatar picture path 《阿凡达》图片路径
     */
    public static function getPublicUserAvatarFilePathByUserId($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("SELECT user_has_avatar FROM users WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(':user_id' => $user_id));

        if ($query->fetch()->user_has_avatar) {
            return Config::get('URL') . Config::get('PATH_AVATARS_PUBLIC') . $user_id . '.jpg';
        }

        return Config::get('URL') . Config::get('PATH_AVATARS_PUBLIC') . Config::get('AVATAR_DEFAULT_IMAGE');
    }

    /**
     * Create an avatar picture (and checks all necessary things too)
     * 创建一个阿凡达图片(并检查所有必要的事情)
     * TODO decouple 解耦
     * TODO total rebuild 全部重建
     */
    public static function createAvatar()
    {
        // check avatar folder writing rights, check if upload fits all rules
        // 检查《阿凡达》文件夹写权利,检查是否上传符合所有规则
        if (self::isAvatarFolderWritable() AND self::validateImageFile()) {

            // create a jpg file in the avatar folder, write marker to database
            // 在《阿凡达》的文件夹中,创建一个jpg文件标记写入数据库
            $target_file_path = Config::get('PATH_AVATARS') . Session::get('user_id');
            self::resizeAvatarImage($_FILES['avatar_file']['tmp_name'], $target_file_path, Config::get('AVATAR_SIZE'), Config::get('AVATAR_SIZE'));
            self::writeAvatarToDatabase(Session::get('user_id'));
            Session::set('user_avatar_file', self::getPublicUserAvatarFilePathByUserId(Session::get('user_id')));
            Session::add('feedback_positive', Text::get('FEEDBACK_AVATAR_UPLOAD_SUCCESSFUL'));
        }
    }

    /**
     * Checks if the avatar folder exists and is writable
     * 检查如果《阿凡达》文件夹存在并且是可写的
     *
     * @return bool success status 成功地位
     */
    public static function isAvatarFolderWritable()
    {
        if (is_dir(Config::get('PATH_AVATARS')) AND is_writable(Config::get('PATH_AVATARS'))) {
            return true;
        }

        Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_FOLDER_DOES_NOT_EXIST_OR_NOT_WRITABLE'));
        return false;
    }

    /**
     * Validates the image
     * Only accepts gif, jpg, png types
     * 验证图像
     * 只接受gif、jpg,png类型
     * @see http://php.net/manual/en/function.image-type-to-mime-type.php
     *
     * @return bool
     */
    public static function validateImageFile()
    {
        if (!isset($_FILES['avatar_file'])) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_IMAGE_UPLOAD_FAILED'));
            return false;
        }

        // if input file too big (>5MB)
        // 如果输入文件太大(> 5 mb)
        if ($_FILES['avatar_file']['size'] > 5000000) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_UPLOAD_TOO_BIG'));
            return false;
        }

        // get the image width, height and mime type
        // 得到图像的宽度,高度和mime类型
        $image_proportions = getimagesize($_FILES['avatar_file']['tmp_name']);

        // if input file too small, [0] is the width, [1] is the height
        // 如果输入文件太小,[0]是宽度,高度[1]
        if ($image_proportions[0] < Config::get('AVATAR_SIZE') OR $image_proportions[1] < Config::get('AVATAR_SIZE')) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_UPLOAD_TOO_SMALL'));
            return false;
        }

        // if file type is not jpg, gif or png
        // 如果文件类型不是jpg,gif或png
        if (!in_array($image_proportions['mime'], array('image/jpeg', 'image/gif', 'image/png'))) {
            Session::add('feedback_negative', Text::get('FEEDBACK_AVATAR_UPLOAD_WRONG_TYPE'));
            return false;
        }

        return true;
    }

    /**
     * Writes marker to database, saying user has an avatar now
     *
     * @param $user_id
     */
    public static function writeAvatarToDatabase($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_has_avatar = TRUE WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(':user_id' => $user_id));
    }

    /**
     * Resize avatar image (while keeping aspect ratio and cropping it off in a clean way).
     * Only works with gif, jpg and png file types. If you want to change this also have a look into
     * method validateImageFile() inside this model.
     * 调整《阿凡达》形象(同时保持长宽比和种植在一个干净的方式)。
     * 仅适用于gif、jpg和png文件类型。如果你想改变这也有一个调查方法validateImageFile()在这个模型。
     *
     * TROUBLESHOOTING: You don't see the new image ? Press F5 or CTRL-F5 to refresh browser cache.
     * 故障诊断:你没有看到新的图片吗?按F5或CTRL-F5刷新浏览器缓存。
     *
     * @param string $source_image The location to the original raw image 最初的原始图像的位置
     * @param string $destination The location to save the new image 的位置保存新形象
     * @param int $final_width The desired width of the new image 所需的宽度的新形象
     * @param int $final_height The desired height of the new image 所需的新形象的高度
     *
     * @return bool success state 成功的国家
     */
    public static function resizeAvatarImage($source_image, $destination, $final_width = 44, $final_height = 44)
    {
        $imageData = getimagesize($source_image);
        $width = $imageData[0];
        $height = $imageData[1];
        $mimeType = $imageData['mime'];

        if (!$width || !$height) {
            return false;
        }

        switch ($mimeType) {
            case 'image/jpeg':
                $myImage = imagecreatefromjpeg($source_image);
                break;
            case 'image/png':
                $myImage = imagecreatefrompng($source_image);
                break;
            case 'image/gif':
                $myImage = imagecreatefromgif($source_image);
                break;
            default:
                return false;
        }

        // calculating the part of the image to use for thumbnail
        // 计算使用的图像缩略图的一部分
        if ($width > $height) {
            $verticalCoordinateOfSource = 0;
            $horizontalCoordinateOfSource = ($width - $height) / 2;
            $smallestSide = $height;
        } else {
            $horizontalCoordinateOfSource = 0;
            $verticalCoordinateOfSource = ($height - $width) / 2;
            $smallestSide = $width;
        }

        // copying the part into thumbnail, maybe edit this for square avatars
        // 部分复制到缩略图,也许编辑这个平方化身
        $thumb = imagecreatetruecolor($final_width, $final_height);
        imagecopyresampled($thumb, $myImage, 0, 0, $horizontalCoordinateOfSource, $verticalCoordinateOfSource, $final_width, $final_height, $smallestSide, $smallestSide);

        // add '.jpg' to file path, save it as a .jpg file with our $destination_filename parameter
        // 添加'.jpg'文件路径,将其保存为一个.jpg文件与我们的$destination_filename参数
        imagejpeg($thumb, $destination . '.jpg', Config::get('AVATAR_JPEG_QUALITY'));
        imagedestroy($thumb);

        if (file_exists($destination)) {
            return true;
        }
        return false;
    }

    /**
     * Delete a user's avatar
     * 删除用户的《阿凡达》
     *
     * @param int $userId
     * @return bool success 成功
     */
    public static function deleteAvatar($userId)
    {
        if (!ctype_digit($userId)) {
            Session::add("feedback_negative", Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }

        // try to delete image, but still go on regardless of file deletion result
        // 试着删除图片,但仍继续不管文件删除的结果
        self::deleteAvatarImageFile($userId);

        $database = DatabaseFactory::getFactory()->getConnection();

        $sth = $database->prepare("UPDATE users SET user_has_avatar = 0 WHERE user_id = :user_id LIMIT 1");
        $sth->bindValue(":user_id", (int)$userId, PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 1) {
            Session::set('user_avatar_file', self::getPublicUserAvatarFilePathByUserId($userId));
            Session::add("feedback_positive", Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_SUCCESSFUL"));
            return true;
        } else {
            Session::add("feedback_negative", Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }
    }

    /**
     * Removes the avatar image file from the filesystem
     * 从文件系统中删除《阿凡达》的图像文件
     *
     * @param integer $userId
     * @return bool
     */
    public static function deleteAvatarImageFile($userId)
    {
        // Check if file exists
        if (!file_exists(Config::get('PATH_AVATARS') . $userId . ".jpg")) {
            Session::add("feedback_negative", Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_NO_FILE"));
            return false;
        }

        // Delete avatar file
        if (!unlink(Config::get('PATH_AVATARS') . $userId . ".jpg")) {
            Session::add("feedback_negative", Text::get("FEEDBACK_AVATAR_IMAGE_DELETE_FAILED"));
            return false;
        }

        return true;
    }
}

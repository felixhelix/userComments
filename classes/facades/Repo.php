<?php

namespace APP\plugins\generic\userComments\classes\facades;

use APP\plugins\generic\userComments\classes\userComment\Repository as UserCommentRepository;

class Repo extends \APP\facades\Repo
{
    public static function userComment(): UserCommentRepository
    {
        return app(UserCommentRepository::class);
    }
}

<?php

namespace Webkul\Pos\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Pos\Contracts\UserDrawer;

class UserDrawerRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return UserDrawer::class;
    }
}

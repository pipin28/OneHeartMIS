<?php

namespace App\Services;

use App\Models\Member;

class MemberService
{
    public function getAll()
    {
        return Member::latest()->get();
    }

    public function create(array $data)
    {
        return Member::create($data);
    }
}
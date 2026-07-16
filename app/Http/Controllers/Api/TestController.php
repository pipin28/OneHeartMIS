<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'API Working',
            'version' => 'v1'
        ], 200);
    }
}
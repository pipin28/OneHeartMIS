<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    public function index(): JsonResponse
    {
        $members = $this->memberService->getAll();

        return response()->json([
            'status' => true,
            'data' => MemberResource::collection($members),
        ]);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = $this->memberService->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Member created successfully.',
            'data' => new MemberResource($member),
        ], 201);
    }
}

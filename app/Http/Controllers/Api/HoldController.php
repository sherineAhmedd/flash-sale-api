<?php

namespace App\Http\Controllers\Api;
use App\Models\Hold;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Services\HoldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoldController extends Controller
{
    protected $holdService;

    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }
public function store(Request $request)
{
    try {
        $result = $this->holdService->createHold($request->all());

        if ($result['error']) {
            return response()->json([
                'message' => $result['message']
            ], $result['status']);
        }

        return response()->json([
            'hold_id'    => $result['data']->id,
            'expires_at' => $result['data']->expires_at,
        ]);

    } catch (\Exception $e) {
        // Temporary debug: return full error in Postman
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile()
        ], 500);
    }
}

}

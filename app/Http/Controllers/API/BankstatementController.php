<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BankStatement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankstatementController extends Controller
{
    use ApiResponse;
    public function addBankStatement(Request $request){
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'iban' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }
        try {
            $user = auth()->guard('api')->user();
            $bankStatement =BankStatement::where('user_id', $user->id)->first();
            if(!$bankStatement){
                $bankStatement = new BankStatement();
            }

            $bankStatement->user_id = $user->id;
            $bankStatement->full_name = $request->input('full_name');
            $bankStatement->iban = $request->input('iban');
            $bankStatement->save();

            return $this->success(['message' => 'Bank statement added successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    public function getBankStatement(){
        try {
            $user = auth()->guard('api')->user();
            $bankStatement = BankStatement::where('user_id', $user->id)->first();

            if (!$bankStatement) {
                return $this->success([],'Bank statement not found');
            }
            $data = [
                'full_name' => $bankStatement->full_name,
                'iban' => $bankStatement->iban,
                'user_id' => $bankStatement->user_id,
            ];
            return $this->success($data, 'Bank statement retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }
}


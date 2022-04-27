<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Candidate;
use App\Models\Company;

class CandidateController extends Controller
{
    const COST_OF_CONTACT = 5;

    public function index(){
        $candidates = Candidate::all();
        $coins = Company::find(1)->wallet->coins;
        $costOfContact = CandidateController::COST_OF_CONTACT;
        return view('candidates.index', compact('candidates', 'coins', 'costOfContact'));
    }

    public function contact(Request $request){
        $company = Company::find(1);
        $costOfContact = CandidateController::COST_OF_CONTACT;

        // Don't allow contact if you don't have enough coins
        if ($company->wallet->coins < $costOfContact) {
            return response(json_encode([
                'status' => 'error',
                'message' => 'insufficient coins',
            ]), 424)
            ->header('Content-Type', 'application/json');
        }

        // Only deduct cost if company has not contacted this candidate before
        Log::info($request->getContent());
        $company->wallet->decrement('coins', $costOfContact);

        // @todo
        // Mark candidate as contacted
        $company->candidates()->attach(1);

        // @todo
        // Send mail to contact

        return response(json_encode([
            'status' => 'success',
            'coins' => $company->wallet->coins,
        ]), 200)
        ->header('Content-Type', 'application/json');

    }

    public function hire(){
        // @todo
        // Your code goes here...
    }
}

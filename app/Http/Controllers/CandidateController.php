<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Candidate;
use App\Models\Company;

class CandidateController extends Controller
{
    const COST_OF_CONTACT = 5;

    private function errorResponse($code, $message) {
        return response(json_encode([
            'status' => 'error',
            'message' => $message,
        ]), $code)
        ->header('Content-Type', 'application/json');
    }

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
            return $this->errorResponse(424, 'insufficient coins');
        }

        // User input can be dangerous, check if this is a correctly formatted numerical ID
        $userInput = json_decode($request->getContent());
        if (!is_object($userInput) || !property_exists($userInput, 'candidate') || !is_integer($userInput->candidate)) {
            return $this->errorResponse(400, 'invalid candidate id');
        }
        $candidateId = $userInput->candidate;

        // Only allow existing IDs for existing candidates
        $candidate = Candidate::find($candidateId);
        if ($candidate === null) {
            return $this->errorResponse(404, 'candidate does not exist');
        }

        // If the company hasn't contact this candidate before,
        // deduct balance from wallet and mark as contacted
        if (!$company->candidates->contains($candidate)) {
            $company->wallet->decrement('coins', $costOfContact);
            $company->candidates()->attach($candidate);
        }

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

<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use App\Mail\CandidateContacted;
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
        $company = Company::find(1);
        $candidates = Candidate::all();
        foreach ($candidates as $candidate) {
            $candidate->contacted = $candidate->contactedBy->contains($company);
            unset($candidate['email']);
            unset($candidate['contactedBy']);
        }
        $coins = Company::find(1)->wallet->coins;
        $costOfContact = CandidateController::COST_OF_CONTACT;
        return view('candidates.index', compact('candidates', 'coins', 'costOfContact'));
    }

    public function contact(Request $request){
        $company = Company::find(1);
        $costOfContact = CandidateController::COST_OF_CONTACT;

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

        // Send mail to contact
        // Just passing the candidate works since it has a `name` and `email` property
        try {
            env('SKIP_MAILS') || Mail::to($candidate)->send(new CandidateContacted($company, $candidate));
        }
        catch(\Exception $e){
            return $this->errorResponse(500, 'email failed to send');
        }

        // Don't allow contact if you don't have enough coins
        if (!$company->candidates->contains($candidate)) {
            if ($company->wallet->coins < $costOfContact) {
                return $this->errorResponse(424, 'insufficient coins');
            } 
            // If the company hasn't contact this candidate before,
            // deduct balance from wallet and mark as contacted
            $company->wallet->decrement('coins', $costOfContact);
        }

        // Save every time a company contacts a candidate, for logging purposes
        // eg spam detection, abuse of the system by companies
        $company->candidates()->attach($candidate);


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

<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use App\Mail\CandidateContacted;
use App\Mail\CandidateHired;
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

    private function parseNumericId($userInput, $property) {
        // User input can be dangerous, check if this is a correctly formatted numerical ID
        if (!is_object($userInput) || !property_exists($userInput, $property) || !is_integer($userInput->{$property})) {
            return $this->errorResponse(400, 'invalid candidate id');
        }
        return $userInput->{$property};
    }

    private function findCandidate($id) {
        // Only allow existing IDs for existing candidates
        $candidate = Candidate::find($id);
        if ($candidate === null) {
            return $this->errorResponse(404, 'candidate does not exist');
        }
        return $candidate;
    }

    private function payForContact($company, $candidate) {
         // Don't allow contact if you don't have enough coins
        if (!$company->candidates->contains($candidate)) {
            if ($company->wallet->coins < CandidateController::COST_OF_CONTACT) {
                return $this->errorResponse(424, 'insufficient coins');
            } 
            // If the company hasn't contact this candidate before,
            // deduct balance from wallet and mark as contacted
            $company->wallet->decrement('coins', CandidateController::COST_OF_CONTACT);
        }
    }

    private function sendMail($recipient, $mailable) {
        try {
            // Just passing the Candidate works since it has a `name` and `email` property
            // (or simply don't send the email if SKIP_MAILS was configured as `true`)
            env('SKIP_MAILS') === true || Mail::to($recipient)->send($mailable);
        }
        catch(\Exception $e){
            return $this->errorResponse(500, 'email failed to send');
        }
    }

    private function sendContactMail($company, $candidate) {
        $this->sendMail($candidate, new CandidateContacted($company, $candidate));
    }

    private function sendHireMail($company, $candidate) {
        $this->sendMail($candidate, new CandidateHired($company, $candidate));
    }

    public function index(){
        $company = Company::find(1);
        $candidates = Candidate::doesntHave('hiredBy')->get();

        foreach ($candidates as $candidate) {
            $candidate->contacted = $candidate->contactedBy->contains($company);
            unset($candidate['email']);
            unset($candidate['contactedBy']);
            unset($candidate['hiredBy']);
        }

        $coins = Company::find(1)->wallet->coins;
        $costOfContact = CandidateController::COST_OF_CONTACT;

        return view('candidates.index', compact('candidates', 'coins', 'costOfContact'));
    }

    public function contact(Request $request){
        $company = Company::find(1);

        $candidateId = $this->parseNumericId(json_decode($request->getContent()), 'candidate');
        $candidate = $this->findCandidate($candidateId);
        $this->sendContactMail($company, $candidate);
        $this->payForContact($company, $candidate);

        // Save every time a company contacts a candidate, for logging purposes
        // eg spam detection, abuse of the system by companies
        $company->candidates()->attach($candidate);

        return response(json_encode([
            'status' => 'success',
            'coins' => $company->wallet->coins,
        ]), 200)
        ->header('Content-Type', 'application/json');

    }

    public function hire(Request $request){
        $company = Company::find(1);
    
        $candidateId = $this->parseNumericId(json_decode($request->getContent()), 'candidate');
        $candidate = $this->findCandidate($candidateId);

        if (!empty($candidate->hiredBy)) {
            if ($candidate->hiredBy->id !== $company->id)
                return $this->errorResponse(424, 'candidate is already hired by another company');
            else
                return $this->errorResponse(424, 'you already hired this candidate');
        }

        if (!$company->candidates->contains($candidate)) {
            return $this->errorResponse(424, 'candidate must be contacted first');
        }

        $this->sendHireMail($company, $candidate);
        $company->wallet->increment('coins', CandidateController::COST_OF_CONTACT);

        $candidate->hiredBy()->associate($company->id);
        $candidate->save();

        return response(json_encode([
            'status' => 'success',
            'coins' => $company->wallet->coins,
        ]), 200)
        ->header('Content-Type', 'application/json');
    }
}

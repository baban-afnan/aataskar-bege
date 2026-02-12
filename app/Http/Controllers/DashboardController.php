<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\VirtualAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $status = $user->kyc_status;
        $kycPending = ($status == 'Pending' || $status == 'Submitted') && !$user->virtualAccount()->exists();

        return view('user.dashboard', [
            'kycPending' => $kycPending,
            'status' => $status
        ]);
    }

    public function submitKyc(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'bvn' => 'required|string|size:11',
            'dob' => 'required|date',
        ]);

        $user = auth()->user();

        try {
            // Update user information
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone,
                'email' => $request->email,
                'bvn' => $request->bvn,
                'dob' => $request->dob,
                'kyc_status' => 'Submitted'
            ]);

            // Create Virtual Account
            $virtualAccountRepo = new VirtualAccountRepository();
            $result = $virtualAccountRepo->createVirtualAccount($user->id);

            if ($result['success']) {
                $user->update(['kyc_status' => 'Verified']);
                return redirect()->route('user.dashboard')->with('success', 'KYC submitted and Virtual Account created successfully!');
            } else {
                return redirect()->back()->with('error', 'KYC submitted but failed to create virtual account: ' . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('KYC Submission Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred during KYC submission. Please try again.');
        }
    }
}

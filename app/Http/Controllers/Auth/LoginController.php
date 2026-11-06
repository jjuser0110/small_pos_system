<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ShiftClosing;
use App\Models\StaffAttendance;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        logout as protected traitLogout;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'username';
    }

    public function authenticated(Request $request, $user)
    {
        if ($user->is_active != 1) {
            return $this->refuse($request, 'Your account has been locked. Please contact your Boss!');
        }

        // staff: only on their company's domain, only inside their shift window
        if ($problem = $user->staffAccessProblem($request->getHost())) {
            return $this->refuse($request, $problem);
        }

        if ($user->isStaff()) {
            StaffAttendance::clockIn($user);
            ShiftClosing::openOrStart($user);
            return redirect()->route('counter');
        }

        return redirect()->route('home')->withSuccess('Successfully Login');
    }

    /** Normal logout: staff attendance records when they left. */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->isStaff()) {
            StaffAttendance::clockOut($user, 'logout');
        }

        return $this->traitLogout($request);
    }

    private function refuse(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors($message);
    }
}

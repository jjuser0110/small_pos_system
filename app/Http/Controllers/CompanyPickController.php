<?php

namespace App\Http\Controllers;

use App\Support\CompanyPick;
use Illuminate\Http\Request;

/** Saves the company chosen in the top bar picker (empty = all companies). */
class CompanyPickController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['company_id' => 'nullable|integer']);

        if ($request->filled('company_id')) {
            $company = CompanyPick::options()->firstWhere('id', (int) $request->company_id);
            abort_unless($company, 403, 'Access denied');
            session([CompanyPick::SESSION_KEY => $company->id]);
        } else {
            session()->forget(CompanyPick::SESSION_KEY);
        }
        CompanyPick::flush();

        return redirect()->back();
    }
}

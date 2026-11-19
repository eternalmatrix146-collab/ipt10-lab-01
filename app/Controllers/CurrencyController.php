<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CurrencyService;
use Phantom\Http\Controller;
use Phantom\Http\Request;
use Phantom\Http\Response;

class CurrencyController extends Controller
{
    public function update(Request $request, CurrencyService $currency): Response
    {
        $code = strtoupper((string) $request->input('currency', 'PHP'));
        if (in_array($code, $currency->supported(), true)) {
            session()->put('currency', $code);
        }
        if ($request->wantsJson()) {
            return $this->json(['currency' => current_currency(), 'rate' => $currency->rate(current_currency())]);
        }
        return $this->back();
    }
}

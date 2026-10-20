<?php

namespace IlBronza\Contacts\Http\Controllers\Contacts;

use IlBronza\Contacts\Helpers\ContactEmailConfirmationHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ContactEmailVerificationController extends Controller
{
    public function __invoke(Request $request, ContactEmailConfirmationHelper $helper): Response
    {
        $helper->confirm($request);

        return response()->view('contacts::contacts.email-verified')
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }
}

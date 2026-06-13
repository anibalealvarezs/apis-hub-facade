<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function privacy($locale = null)
    {
        if ($locale === 'es') {
            app()->setLocale('es');
            return view('legal.privacy-es');
        }
        app()->setLocale('en');
        return view('legal.privacy');
    }

    public function tos($locale = null)
    {
        if ($locale === 'es') {
            app()->setLocale('es');
            return view('legal.tos-es');
        }
        app()->setLocale('en');
        return view('legal.tos');
    }

    public function dataDeletion($locale = null)
    {
        if ($locale === 'es') {
            app()->setLocale('es');
            return view('legal.data-deletion-es');
        }
        app()->setLocale('en');
        return view('legal.data-deletion');
    }
}

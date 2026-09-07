<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class RevisionConflictException extends HttpResponseException
{
    public function __construct(string $message = 'Revision conflict.')
    {
        parent::__construct(response()->json(['message'=>$message], 409));
    }
}

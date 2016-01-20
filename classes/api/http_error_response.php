<?php

class OcOpenDataErrorResponse implements ezcMvcResultStatusObject
{
    public $code;
    public $message;
    public $errorCode;
    public $errorDetails;

    public function __construct(
        $code = null,
        $message = null,
        $errorCode = null
    )
    {
        $this->code = $code;
        $this->message = $message;
        $this->errorCode = $errorCode;
    }

    public function process( ezcMvcResponseWriter $writer )
    {
        if ( $writer instanceof ezcMvcHttpResponseWriter )
        {
            $writer->headers["HTTP/1.1 " . $this->code] = $this->message;
        }

        if ( $this->message !== null )
        {
            $writer->headers['Content-Type'] = 'application/json; charset=UTF-8';
            $writer->response->body = json_encode(
                array(
                    'error_code' => $this->errorCode,
                    'error_message' => $this->message
                )
            );
        }
    }
}
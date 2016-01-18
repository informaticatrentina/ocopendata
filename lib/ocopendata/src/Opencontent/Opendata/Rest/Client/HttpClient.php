<?php

namespace OpenContent\Opendata\Rest\Client;


class HttpClient
{
    protected $server;

    protected $login;

    protected $password;

    protected $proxy;

    protected $proxyPort;

    protected $proxyLogin;

    protected $proxyPassword;

    protected $proxyAuthType;

    protected $apiEndPontBase;

    public static $connectionTimeout = 60;

    public static $processTimeout = 60;

    public function __construct( $server, $login, $password, $apiEndPontBase = '/api/opendata/v2' )
    {
        $this->server = rtrim( $server, '/' );
        $this->login = $login;
        $this->password = $password;
        $this->apiEndPontBase = rtrim( $apiEndPontBase, '/' );
    }

    public function setProxy( $proxy, $proxyPort, $proxyLogin = null, $proxyPassword = null, $proxyAuthType = 1 )
    {
        $this->proxy = $proxy;
        $this->proxyPort = $proxyPort;
        $this->proxyLogin = $proxyLogin;
        $this->proxyPassword = $proxyPassword;
        $this->proxyAuthType = $proxyAuthType;
    }

    public function create( $data )
    {
        return $this->request( 'POST', '/create', json_encode( $data ) );
    }

    public function update( $data )
    {
        return $this->request( 'POST', '/update' , json_encode( $data ) );
    }

    public function delete( $data )
    {
        return $this->request( 'POST', '/delete' , json_encode( $data ) );
    }

    public function request( $method, $path, $data = null )
    {
        $credentials = "{$this->login}:{$this->password}";
        $url = $this->server . $this->apiEndPontBase .  $path;

        $headers = array( "Authorization: Basic " . base64_encode( $credentials ) );

        $ch = curl_init();
        if ( $method == "POST" )
        {
            curl_setopt( $ch, CURLOPT_POST, 1 );
        }
        if ( $data !== null )
        {
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen( $data );
        }
        curl_setopt( $ch, CURLOPT_HEADER, 1 );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, self::$connectionTimeout );
        curl_setopt( $ch, CURLOPT_TIMEOUT, self::$processTimeout );

        if( $this->proxy !== null )
        {
            curl_setopt( $ch, CURLOPT_PROXY, $this->proxy . ':' . $this->proxyPort );
            if( $this->proxyLogin !== null )
            {
                curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $this->proxyLogin . ':' . $this->proxyPassword );
                curl_setopt( $ch, CURLOPT_PROXYAUTH, $this->proxyAuthType );
            }
        }

        $data = curl_exec( $ch );

        if ( $data === false )
        {
            $errorCode = curl_errno( $ch ) * -1;
            $errorMessage = curl_error( $ch );
            curl_close( $ch );
            throw new \Exception( $errorString, $errorNumber );
        }

        $info = curl_getinfo( $ch );
        curl_close( $ch );
        $headers = substr( $data, 0, $info['header_size'] );
        $body = substr( $data, -$info['download_content_length'] );

        return $this->parseResponse( $info, $headers, $body );
    }

    protected function parseResponse( $info, $headers, $body )
    {
        $data = json_decode( $body );

        if ( isset( $data->errorCode ) )
        {
            $errorCode = $data->errorCode;
            $errorMessage = '';
            if ( isset( $data->errorMessage ) )
            {
                $errorMessage = $data->errorMessage;
            }
            throw new \Exception( $errorMessage, $errorCode );
        }

        if ( !in_array( $info['http_code'], array( 100, 200, 201, 202 ) ) )
        {
            throw new \Exception( "Unknown error" );
        }

        return $data;
    }
}
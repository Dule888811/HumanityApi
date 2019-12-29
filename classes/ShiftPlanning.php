<?php
 class ShiftPlanning {

    private $_key;
	private $_callback;
	private $_init;
	private $_debug;
	private $request = array( );
	private $requests = array( );
	private $response = array( );
    private $raw_response = array( );
    
    const session_identifier = 'SP';
	const api_endpoint = 'https://www.humanity.com/api/';
    const output_type = 'json';

    
    public function __construct( $config = array() )
    {
        try
        {
            $this->_debug = false;
			$this->startSession( );
			$this->setAppKey( $config['key'] );
			if( !function_exists( 'curl_init' ) )
			{
				throw new Exception( $this->internal_errors( 6 ) );
			}
			if( !function_exists( 'json_decode' ) )
			{
				throw new Exception( $this->internal_errors( 7 ) );
            }
        }
        catch ( Exception $e )
        {
            echo $e->getMessage( ); exit;
        }
    }
        public function setDebug( $switch = false )
        {
            if( file_exists('log.txt') )
            {
                unlink( 'log.txt' );
            }
            $this->_debug = true;
		}
		public function doLogin( $user = array( ) )
	{
		return $this->setRequest(
			array(
				'module' => 'staff.login',
				'method' => 'GET',
				'username' => $user['username'],
				'password' => $user['password']
			)
		);
	}
        protected function startSession( )
        {
            session_name( self::session_identifier );
            session_start( );
        }
        private function setSession( )
        {
            $_SESSION['token'] = $this->response['token'][0];
            $_SESSION['data'] = $this->response['data'][0];
        }
        private function destroySession( )
        {
            $logout = $this->setRequest( array (
                'module' => 'staff.logout',
                'method' => 'GET'
            ) );
            if( $logout['status']['code'] == 1 )
            {
                unset( $_SESSION['token'] );
                unset( $_SESSION['data'] );
                
                
                setcookie("mobile_token", $this->response['token'][0], time()-(30*86400), "/", ".humanity.com");
            }
            return $logout;
        }
        public function getSession( )
        {
            if( isset( $_SESSION['token'] ) )
            {
                return $_SESSION['data'];
            }
            else
            {
                return false;
            }
        }
        private function setCallback( $callback )
	{
		$this->_callback = $callback;
		return $this->_callback;
	}
	public function getRawResponse( )
	{
		return $this->raw_response;
	}
	public function getAppKey( )
	{
		return $this->_key;
	}
	public function setAppKey( $key )
	{
		$this->_key = $key;
		return $this->_key;
    }
    public function getAppToken( )
	{
		try
		{
			if( $this->getSession( ) )
			{
				return $_SESSION['token'];
			}
			else
			{
				throw new Exception( $this->internal_errors( 4 ) );
			}
		}
		catch( Exception $e )
		{
			echo $e->getMessage();
		}
    }
    public function setRequest( $requests = array( ) )
	{
		unset( $this->requests );
		$this->request['output'] = self::output_type;
		$this->_init = 0;
		foreach( $requests as $r => $v )
		{
			if( is_array( $v ) )
			{
				$this->requests[] = $v;
			}
			else
			{
				if( $requests['module'] == 'staff.login' )
				{
					$this->_init = 1;
				}
				$this->requests[] = $requests; break;
			}
		}
		return $this->api( );
	}
	public function getRequest( )
	{
		return array_merge( $this->request, array( 'request' => $this->requests ) );
	}
	private function setResponse( $response )
	{// set the response data
		// remove previous response data
		unset( $this->response );
		// set new response data
		if( isset($response['data']) )
		{//
			$this->response['response'][0] = array(
				'code' => $response['status'],
				'text' => $this->getResponseText( $response['status'] ),
				'error' => (isset($response['error']))?$response['error']:''
			);
			$this->response['data'][0] = $response['data'];
			$this->response['token'][0] = $response['token'];
		}
		else
		{//
			foreach( $response as $num => $data )
			{// loop through each response
				if( isset($data['status']) ){
					$this->response['response'][$num] = array(
						'code' => $data['status'],
						'text' => $this->getResponseText( $data['status'] ),
						'error' => (isset($data['error']))?$data['error']:''
					);
					$tmp = array( );
					$id = 0;
					if( is_array( $data['data'] ) )
					{// is there an array returned
						foreach( $data['data'] as $n => $v )
						{//
							if( is_array( $v ) )
							{//
								foreach( $v as $key => $val )
								{//
									$tmp[$n][$key] = $val;
								}
							}
							else
							{//
								$tmp[$n] = $v;
							}
						}
						$id++;
						$this->response['data'][$num] = $tmp;
					}
					else
					{// the data response is text
						$this->response['data'][$num] = $data['data'];
					}
				}
			}
		}
	}
	public function getResponse( $call_num = 0 )
	{// return the API response data to the calling method
		return array(
			'status' => $this->response['response'][$call_num],
			'data' => $this->response['data'][$call_num],
			'error' => (isset($response['error']))?$response['error'][$call_num]:''
		);
    }
    private function getResponseText( $code )
	{// return a reason text for a response code
		switch( $code )
		{// select a response code to display
			case '-3' : $reason = 'Flagged API Key - Pemanently Banned'; break;
			case '-2' : $reason = 'Flagged API Key - Too Many invalid access attempts - contact us'; break;
			case '-1' : $reason = 'Flagged API Key - Temporarily Disabled - contact us'; break;
			case '1' : $reason = 'Success -'; break;
			case '2' : $reason = 'Invalid API key - App must be granted a valid key by ShiftPlanning'; break;
			case '3' : $reason = 'Invalid token key - Please re-authenticate'; break;
			case '4' : $reason = 'Invalid Method - No Method with that name exists in our API'; break;
			case '5' : $reason = 'Invalid Module - No Module with that name exists in our API'; break;
			case '6' : $reason = 'Invalid Action - No Action with that name exists in our API'; break;
			case '7' : $reason = 'Authentication Failed - You do not have permissions to access the service'; break;
			case '8' : $reason = 'Missing parameters - Your request is missing a required parameter'; break;
			case '9' : $reason = 'Invalid parameters - Your request has an invalid parameter type'; break;
			case '10' : $reason = 'Extra parameters - Your request has an extra/unallowed parameter type'; break;
			case '12' : $reason = 'Create Failed - Your CREATE request failed'; break;
			case '13' : $reason = 'Update Failed - Your UPDATE request failed'; break;
			case '14' : $reason = 'Delete Failed - Your DELETE request failed'; break;
			case '20' : $reason = 'Incorrect Permissions - You don\'t have the proper permissions to access this'; break;
			case '90' : $reason = 'Suspended API key - Access for your account has been suspended, please contact ShiftPlanning'; break;
			case '91' : $reason = 'Throttle exceeded - You have exceeded the max allowed requests. Try again later.'; break;
			case '98' : $reason = 'Bad API Paramaters - Invalid POST request. See Manual.'; break;
			case '99' : $reason = 'Service Offline - This service is temporarily offline. Try again later.'; break;
			default : $reason = 'Error code not found'; break;
		}
		// return the reason text
		return $reason;
	}
	private function internal_errors( $errno )
	{// errors internal to the ShiftPlanning SDK
		switch( $errno )
		{// internal error messages
			case 1 :
				$message = 'The requested API method was not found in this SDK.';
				break;
			case 2 :
				$message = 'The ShiftPlanning API is not responding.';
				break;
			case 3 :
				$message = 'You must use the login method before accessing other modules of this API.';
				break;
			case 4 :
				$message = 'A session has not yet been established.';
				break;
			case 5 :
				$message = 'You must specify your Developer Key when using this SDK.';
				break;
			case 6 :
				$message = 'The ShiftPlanning SDK needs the CURL PHP extension.';
				break;
			case 7 :
				$message = 'The ShiftPlanning SDK needs the JSON PHP extension.';
				break;
			case 8 :
				$message = 'File doesn\'t exist.';
				break;
			case 9 :
				$message = 'Could not find the correct mime for the file supplied.';
				break;
			default :
				$message = 'Could not find the requested error message.';
				break;
		}
		return $message; exit;
    }
    private function api( )
	{// create the api call
		if( $this->_callback == null )
		{// method to call after successful api request
			$this->setCallback( 'getResponse' );
		}
		if( $this->getSession( ) )
		{// session already established, use token
			// remove the developer key from the request, since it's not necessary
			unset( $this->request['key'] );
			// set the token for this request, since the user is already authenticated
			$this->request['token'] = $_SESSION['token'];
		}
		else
		{// session has not been established, use developer key to access API
			try
			{//
				if( isset( $this->_key ) )
				{// developer key is set
					$this->request['key'] = $this->_key;
				}
				else
				{// developer key is not set
					throw new Exception( $this->internal_errors( 5 ) );
				}
			}
			catch( Exception $e )
			{//
				echo $e->getMessage( );
			}
		}
		// make the api request
		return $this->perform_request( );
    }
    private function perform_request( )
	{// perform the api request
		try
		{//
			$ch = curl_init( self::api_endpoint );
			$filedata = '';
			if( is_array( $this->requests ) )
			{//
				foreach( $this->requests as $key => $request )
				{//
					if( isset($request['filedata']) && $request['filedata'] )
					{//
						$filedata = $request['filedata'];
						unset( $this->requests[$key]['filedata'] );
					}
				}
			}
			$post = $filedata ? array( 'data'=> json_encode( $this->getRequest( ) ),
				'filedata' => $filedata ) : array( 'data' => json_encode( $this->getRequest( ) ) );
			curl_setopt( $ch, CURLOPT_URL, self::api_endpoint );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
			// return the response from the api
			$response = curl_exec( $ch );
			$http_response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			curl_close( $ch );
			if( $http_response_code == 200 )
			{// response from API was a successful
				$temp = json_decode( $response, true );
				// decode the response and store each call response in its own array
				$this->setResponse( $temp );
				if( $this->_init == 1 )
				{// initialize a session
					$this->setSession( );
				}
				// raw response call
				$this->raw_response = $temp;
				if( $this->_debug == true )
				{// debug mode is on
					$request = json_encode( $this->getRequest( ) );
					$tmp_vals = array( );
					if( is_array( $this->response['data'][0] ) )
					{//
						foreach( $this->response['data'] as $n => $v )
						{//
							foreach( $v as $key => $val )
							{//
								$tmp_vals[$n][$key] = $val;
							}
						}
					}
					else
					{//
						foreach( $this->response['data'] as $n => $v )
						{//
							$tmp_vals[$n] = $v;
						}
					}
					
				}
				// perform the callback method
				return $this->{ $this->_callback }( );
			}
			else
			{// response from API was unsuccessful
				throw new Exception( $this->internal_errors( 2 ) );
            }
        }
            catch( Exception $e )
            {//
                echo $e->getMessage();
            }
    }
   
    public function getShifts( )
	{
		return $this->setRequest(
			array(
				'module' => 'schedule.shifts',
				'method' => 'GET'
			)
		);
    }
   
    public function getAPIConfig( )
	{
		return $this->setRequest(
			array(
				'module' => 'api.config',
				'method' => 'GET'
			)
		);
    }
    public function getAPIMethods( )
	{
		return $this->setRequest(
			array(
				'module' => 'api.methods',
				'method' => 'GET'
			)
		);
	}


    
}

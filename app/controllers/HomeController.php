<?php
use LaravelDuo\LaravelDuo;

class HomeController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/
        private $_laravelDuo;

    function __construct(LaravelDuo $laravelDuo)
    {
        $this->_laravelDuo = $laravelDuo;
    }

    /**
     * Stage One - The Login form
     * @return  Login View
     */
    public function getIndex()
    {
        return View::make('pages.login');
    }

    /**
     * Stage Two - The Duo Auth form
     * @return Duo Login View or Redirect on error 
     */
    public function postSignin()
    {
        $user = array(
            'username' => Input::get('username'),
            'password' => Input::get('password')
        );

        /**
         * Validate the user details, but don't log the user in
         */
        if(Auth::validate($user))
        {
            $U    = Input::get('username');

            $duoinfo = array(
                'HOST' => $this->_laravelDuo->get_host(),
                'POST' => URL::to('/') . '/duologin',
                'USER' => $U,
                'SIG'  => $this->_laravelDuo->signRequest($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $U)
            );

            return View::make('pages.duologin')->with(compact('duoinfo'));
        }
        else
        {
            return Redirect::to('/')->with('message', 'Your username and/or password was incorrect')->withInput();
        }

    }

    /**
     * Stage Three - After Duo Auth Form
     * @return Redirect to home
     */
    public function postDuologin()
    {
        /**
         * Sent back from Duo
         */
        $response = $_POST['sig_response'];

        $U = $this->_laravelDuo->verifyResponse($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $response);

        /**
         * Duo response returns USER field from Stage Two
         */
        if($U){

            /**
             * Get the id of the authenticated user from their email address
             */
            $id = User::getIdFromUsername($U);

            /**
             * Log the user in by their ID
             */
            Auth::loginUsingId($id);

            /**
             * Check Auth worked, redirect to homepage if so
             */
            if(Auth::check())
            {
                return Redirect::to('/');
            }
        }

        /**
         * Otherwise, Auth failed, redirect to homepage with message
         */
        return Redirect::to('/')->with('message', 'Unable to authenticate you.');

    }

    /**
     * Log user out
     * @return Redirect to Home
     */
    public function getLogout()
    {
        Auth::logout();
        return Redirect::to('/');
    }

	/**
	 * Get the list of all security groups
     * @return getGroups view
	 */
	public function getGroups()
	{
        $leases= Lease::get();
		$ec2 = App::make('aws')->get('ec2');
		$security_groups=$ec2->describeSecurityGroups();
		$security_groups=$security_groups['SecurityGroups'];

        return View::make('getGroups')->with('security_groups', $security_groups)->with('leases', $leases);
	}

    /*
     * Displays a security groups details with active leases & security rules.
     * @return getManage View
     */
	public function getManage($group_id)
	{

        //get security group details
		$ec2 = App::make('aws')->get('ec2');
		$security_group=$ec2->describeSecurityGroups(array(
			'GroupIds' => array($group_id),
        ));
		$security_group=$security_group['SecurityGroups'][0];

        //get Active Leases
        $leases= Lease::getByGroupId($group_id);

        return View::make('getManage')->with('security_group', $security_group)->with('leases', $leases);
	}

    /*
     * Handles Lease creation & termination post requests to Group Manage page
     * @return Redirect to getManage View with error/success
     */
    public function postManage($group_id)
    {
        $input=Input::all();
        $messages=array();
        /*
         For Lease Creation
        */
        if(isset($input['rule_type'])){

            if("ssh"==$input["rule_type"])
            {
                $protocol="tcp";
                $port_from="22";
                $port_to="22";
            }
            elseif("https"==$input["rule_type"])
            {
                $protocol="tcp";
                $port_from="443";
                $port_to="443";
            }
            elseif("custom"==$input["rule_type"])
            {
                $protocol=$input['protocol'];
                $port_from=$input['port_from'];
                $port_to=$input['port_to'];

                //Validations
                if($protocol != "tcp" && $protocol!="udp") array_push($messages, "Invalid Protocol");
                if(!is_numeric($port_from) || $port_from>65535 || $port_from<=0) array_push($messages, "Invalid From port");
                if(!is_numeric($port_to) || $port_to>65535 || $port_to<=0) array_push($messages, "Invalid To port");
                if($port_from>$port_to) array_push($messages, "From port Must be less than equal to To Port");
            }
            else
            {
                App::abort(403, 'Unauthorized action.');
            }

            //Expiry Time validation
            $expiry=$input['expiry'];
            if(!is_numeric($expiry) || $expiry <= 0 || $expiry >86400) array_push($messages, "Invalid Expiry Time");
            
            //Validation fails
            if(!empty($messages)) 
            {
                return Redirect::to("/manage/$group_id")
                                ->with('message', implode("<br/>", $messages));

            }

            //Creating the lease
            $lease=array(
                'user_id'=>Auth::User()->id,
                'group_id'=>$group_id,
                'lease_ip'=>$_SERVER['REMOTE_ADDR']."/32",
                'protocol'=>$protocol, 
                'port_from'=>$port_from,
                'port_to'=>$port_to,
                'expiry'=>$expiry
            );
            $result=$this->createLease($lease);

            if(!$result)
            {   
                //Lease Creation Failed. AWS Reported an error. Generally in case if a lease with same ip, protocl, port already exists on AWS.
                return Redirect::to("/manage/$group_id")
                                ->with('message', "Lease Creation Failed! Does a similar lease already exist? Terminate that first.");
            }
            $lease=Lease::create($lease);
            $this->NotificationMail($lease, TRUE);
            return Redirect::to("/manage/$group_id")
                            ->with('message', "Lease created successfully!");
        }
        /* 
         * Lease termination Call
         */
        elseif(isset($input['lease_id'])){
            // Check for existence of lease
            try
            {
                $lease=Lease::findorFail($input['lease_id']);
            }
            catch(Exception $e)
            {
                $message="Lease not found";
                return Redirect::to("/manage/$group_id")->with('message', $message);
            }

            // Terminate the lease on AWS
            $result=$this->terminateLease($lease->toArray());

            //Delete from DB
            $lease->delete();
            $this->NotificationMail($lease, FALSE);

            if(!$result)
            {   
                //Should not occur even if lease doesn't exist with AWS. Check AWS API Conf.
                return Redirect::to("/manage/$group_id")
                                ->with('message', "Lease Termination returned error. Assumed the lease was already deleted");
            }
            
            return Redirect::to("/manage/$group_id")
                                ->with('message', "Lease terminated successfully");
        }
        else{
            App::abort(403, 'Unauthorized action.');
        }
    }

    /*
     * Handles sending of notification mail
     * Requires two arguements $lease, $ mode. 
     * $lease = Lease Object Containing the lease created or deleted
     * $mode = TRUE for lease created, FALSE for lease deleted
     */

    private function NotificationMail($lease, $mode)
    {
        $data=array('lease'=>$lease->toArray(), 'mode'=>$mode);

        if($mode)
        {
            //In case of Lease Creation
            Mail::queue('emails.notification', $data, function($message)
            {
                $message->to(Config::get('custom_config.notification_emailid'), 'Security Notification' )->subject('Secure Access Lease Created');
            });
        }
        else
        {
            //In Case of Lease Termination
            Mail::queue('emails.notification', $data, function($message)
            {
                $message->to(Config::get('custom_config.notification_emailid'), 'Security Notification' )->subject('Secure Access Lease Terminated');
            });
        }
    }

    /*
     * Handles cleaning of expired lease, called via artisan command custom:leasemanager run via cron
     * return void
     */

    public function cleanLeases()
    {
        $messages=array();
        $leases=Lease::get();
        foreach($leases as $lease)
        {
            $time_left=strtotime($lease->created_at)+$lease->expiry-time(); 
            if($time_left<=0){
                $result=$this->terminateLease($lease->toArray());
                $lease->delete();
                $this->NotificationMail($lease, FALSE);
                if(!$result)
                {
                    array_push($messages,"Lease Termination of Lease ID $lease->id reported error on AWS API Call. Assumed already deleted.");
                }
            }
        }
        if(!empty($messages)) return implode("\n", $messages);
        return;
    }

    /*
     * Handles lease creation by communitacting with AWS API
     * Requires an associative array of lease row.
     * return true if successful, false when AWS API returns error
     */
    private function createLease($lease)
    {
        $ec2 = App::make('aws')->get('ec2');
        try
        {
            $result = $ec2->authorizeSecurityGroupIngress(array(
            'DryRun' => false,
            'GroupId' =>  $lease['group_id'],
            'IpProtocol' => $lease['protocol'],
            'FromPort' => $lease['port_from'],
            'ToPort' => $lease['port_to'],
            'CidrIp' => $lease['lease_ip'],
            ));
        }
        catch(Exception $e)
        {
            return FALSE;
        }
        return TRUE;
    }
    /*
     * Handles lease termination by communitacting with AWS API
     * Requires an associative array of lease row.
     * return true if successful, false when AWS API returns error
     */
    private function terminateLease($lease)
    {
        $ec2 = App::make('aws')->get('ec2');
        try
        {
            $result = $ec2->revokeSecurityGroupIngress(array(
            'DryRun' => false,
            'GroupId' => $lease['group_id'],
            'IpProtocol' => $lease['protocol'],
            'FromPort' => $lease['port_from'],
            'ToPort' => $lease['port_to'],
            'CidrIp' => $lease['lease_ip'],
            ));
        }
        catch(Exception $e)
        {
            return FALSE;
        }
        return TRUE;  
    }

    /*
     * Returns the form for changing Password
     */ 
    public function getPassword()
    {
        return View::make('getPassword');
    }
    
    /*
     * Handles the form submission for changing Password
     */ 
    public function postPassword()
    {
        $input=Input::all();
        $user = array(
            'username' => Auth::user()->username,
            'password' => $input['old_password']
        );

        /**
         * Validate the user details to check old password
         */
        if(! Auth::validate($user))
        {
            return Redirect::to('/password')
                            ->with('message', "Incorrect Password");
        }

        //Validation Rules
        $password_rules = array(
        'password'              => 'required|between:7,50|confirmed|case_diff|numbers|letters',
        'password_confirmation' => 'required|between:7,50');
        
        $validator = Validator::make($input,$password_rules);

        if ($validator->fails())
        {
             return Redirect::to('/password')
                            ->with('message', implode("<br/>", $validator->messages()->get('password')));
        }

        //Everything Good. Change the password
        $password = array(
            'password' => Hash::make($input['password'])
        );
        
        $result = Auth::user()->update($password);

        return Redirect::to('/password')
                            ->with('message', "Password Changed Successfully");


    }
}

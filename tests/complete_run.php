<?php

require_once("../examples/SplClassLoader.php");

$classLoader = new SplClassLoader('pmill\Plesk', '../src');
$classLoader->register();

/*
 * Utility functions
 */

function random_string($length=8) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	return substr(str_shuffle($chars),0,$length);
}

/*
 * This file attempts the following operations on a real Plesk server (intended to be run by an admin account)
 *
 * 1.  Get server information (GetServerInfo)
 * 2.  Find shared ip address (ListIPAddresses)
 * 3.  Find unlimited service plan (ListServicePlans)
 * 4.  Creates a new client (CreateClient)
 * 5.  Get new client from server (GetClient)
 * 6.  Update client information (UpdateClient)
 * 7.  Create subscription (CreateSubscription)
 * 8.  List subscriptions (ListSubscriptions)
 * 9.  Create new site (CreateSite)
 * 10. Find created site (ListSite)
 * 11. Update site information (UpdateSite)
 * 12. Create email address (CreateEmailAddress)
 * 13. List email addresses (ListEmailAddresses)
 * 14. Update email address (UpdateEmailAddress)
 * 15. Delete email address (DeleteEmailAddress)
 * 16. Create site alias (CreateSiteAlias)
 * 17. List site aliases (ListSiteAliases)
 * 18. Delete site alias (DeleteEmailAddress)
 * 19. Create subdomain (CreateSubdomain)
 * 20. List subdomains (ListSubdomains)
 * 21. Update subdomain(UpdateSubdomain)
 * 22. Rename subdomain(RenameSubdomain)
 * 23. Delete subdomain(DeleteSubdomain)
 * 24. Delete previously created site (DeleteSite)
 * 25. Delete previously created subscription (DeleteSubscription)
 * 26. Deletes previously created client (DeleteClient)
 *
 */

$config = array(
    'host'=>'example.com',
    'username'=>'username',
    'password'=>'password',
);

$data = array();

/*
 * 1. Get server information (GetServerInfo)
 */

$request = new \pmill\Plesk\GetServerInfo($config);
$info = $request->process();

echo "Running test suite on ".$info['server_name']."<BR>";


/*
 * 2. Find shared ip address (ListIPAddresses)
 */

$request = new \pmill\Plesk\ListIPAddresses($config);
$ips = $request->process();

foreach ($ips AS $ip) {
	if ($ip['is_default']) {
		$data['shared_ip_address'] = $ip['ip_address'];
	}
}

if (!isset($data['shared_ip_address'])) {
	throw new Exception("Couldn't find any shared IP addresses");
}

echo "Shared IP Address found: ".$data['shared_ip_address']."<BR>";

/*
 * 3.  Find unlimited service plan (ListServicePlans)
 */

$request = new \pmill\Plesk\ListServicePlans($config);
$plans = $request->process();

foreach ($plans AS $plan) {
	if (strtolower($plan['name']) == 'unlimited') {
		$data['unlimited_plan_id'] = $plan['id'];
		echo "Unlimited Service Plan found: ".$data['unlimited_plan_id']."<BR>";
		break;
	}
}

if (!isset($data['unlimited_plan_id'])) {
	throw new Exception("Couldn't find unlimited service plan");
}

echo "Unlimited service plan found: ".$data['unlimited_plan_id']."<BR>";

/*
 * 4. Creates a new client (CreateClient)
 */

$data['client_username'] = strtolower(random_string());

$request = new \pmill\Plesk\CreateClient($config, array(
	'contact_name'=>random_string(),
	'username'=>$data['client_username'],
	'password'=>random_string(16)."1!",
));

$request->process();
$data['client_id'] = $request->id;
echo "Client created: ".$data['client_username']."<BR>";


try {
	/*
	 * 5. Get new client from server (GetClient)
	 */

	$request = new \pmill\Plesk\GetClient($config, array(
		'username'=>$data['client_username'],
	));
	$info = $request->process();
    echo "Client found: ".$data['client_username']."<BR>";

	/*
	 * 6. Update client information (UpdateClient)
	 */

	$request = new \pmill\Plesk\UpdateClient($config, array(
		'username'=>$data['client_username'],
		'phone'=>random_string(),
		'email'=>random_string().'@example.com',
	));
	$info = $request->process();
    echo "Client updated: ".$data['client_username']."<BR>";


	/*
	 * 7.  Create subscription (CreateSubscription)
	 */

	$params = array(
		'domain_name'=>random_string().'.com',
		'username'=>$data['client_username'],
		'password'=>random_string(16).'1!',
		'ip_address'=>$data['shared_ip_address'],
		'owner_id'=>$data['client_id'],
		'service_plan_id'=>$data['unlimited_plan_id'],
	);

	$request = new \pmill\Plesk\CreateSubscription($config, $params);
	$request->process();
	$data['subscription_id'] = $request->id;
	echo "Subscription created: ".$data['subscription_id']."<BR>";


	/*
	 * 8.  List subscriptions (ListSubscriptions)
	 */

	$request = new \pmill\Plesk\ListSubscriptions($config);
	$subscriptions = $request->process();

	$subscription_found = FALSE;
	foreach ($subscriptions AS $subscription) {
		if ($subscription['id'] == $data['subscription_id']) {
			$subscription_found = TRUE;
		}
	}

	if (!$subscription_found) {
		throw new Exception("Couldn't find created subscription");
	}
    
    echo "Subscription found: ".$data['subscription_id']."<BR>";

	/*
	 * 9.  Create new site (CreateSite)
	 */
    
    $data['domain'] = random_string().'.com';
	$request = new \pmill\Plesk\CreateSite($config, array(
		'domain'=>$data['domain'],
		'subscription_id'=>$data['subscription_id'],
	));
	$info = $request->process();
	$data['site_id'] = $request->id;
    echo "Site created: ".$data['domain']."<BR>";


	/*
	 * 10. Find created site (ListSite)
	 */

	$request = new \pmill\Plesk\ListSites($config, array(
		'subscription_id'=>$data['subscription_id'],
	));
	$sites = $request->process();

	$site_found = FALSE;
	foreach ($sites AS $site) {
		if ($site['id'] == $data['site_id']) {
			$site_found = TRUE;
		}
	}

	if (!$site_found) {
		throw new Exception("Couldn't find created site");
	}
    
    echo "Site found: ".$data['domain']."<BR>";


	/*
	 * 11. Update site information (UpdateSite)
	 */
    
    $data['domain'] = random_string().'.com';
	$request = new \pmill\Plesk\UpdateSite($config, array(
		'id'=>$data['site_id'],
		'domain'=>$data['domain'],
	));
	$info = $request->process();
    echo "Site updated: ".$data['domain']."<BR>";

    /*
     * 12. Create email address (CreateEmailAddress)
     */
    
    $data['email_address'] = random_string(4).'@'.$data['domain'];
    $request = new \pmill\Plesk\CreateEmailAddress($config, array(
        'email'=>$data['email_address'],
        'password'=>random_string()."1!",
    ));
    $info = $request->process();
    $data['email_address_id'] = $request->id;
    echo "Email address created: ".$data['email_address']."<BR>";
    
    /*
     * 13. List email addresses (ListEmailAddresses)
     */
    $request = new \pmill\Plesk\ListEmailAddresses($config, array(
        'site_id'=>$data['site_id'],
    ));
    $email_addresses = $request->process();

    $email_address_found = FALSE;
    foreach ($email_addresses AS $email_address) {
        if ($email_address['id'] == $data['email_address_id']) {
            $email_address_found = TRUE;
        }
    }
    
    if (!$email_address_found) {
		throw new Exception("Couldn't find created email address (".$data['email_address_id'].")");
	}
    
    echo "Email address found: ".$data['email_address']."<BR>";

    
    /*
     * 14. Update email address (UpdateEmailAddress)
     */
    
    $request = new \pmill\Plesk\UpdateEmailPassword($config, array(
        'email'=>$data['email_address'],
        'password'=>random_string(),
    ));
    $info = $request->process();
    echo "Email address password changed: ".$data['email_address']."<BR>";

    
    /*
     * 15. Delete email address (DeleteEmailAddress)
     */

    $request = new \pmill\Plesk\DeleteEmailAddress($config, array(
        'email'=>$data['email_address'],
    ));
    $info = $request->process();
    echo "Email address deleted: ".$data['email_address']."<BR>";
    
    
    /*
     * 16. Create site alias (CreateSiteAlias)
     */
    
    $data['site_alias'] = random_string().'.'.$data['domain'];
    $params = array(
        'site_id'=>$data['site_id'],
        'alias'=>$data['site_alias'],
    );

    $request = new \pmill\Plesk\CreateSiteAlias($config, $params);
    $info = $request->process();
    $data['site_alias_id'] = $request->id;
    echo "Site alias created: ".$data['site_alias']."<BR>";
    
    
    /*
     * 17. List site aliases (ListSiteAliases)
     */
    
    $request = new \pmill\Plesk\ListSiteAliases($config, array(
        'site_id'=>$data['site_id'],
    ));
    $aliases = $request->process();
    
    $alias_found = FALSE;
    foreach ($aliases AS $alias_id=>$alias_name) {
        if ($alias_id == $data['site_alias_id']) {
            $alias_found = TRUE;
        }
    }
    
    if (!$alias_found) {
		throw new Exception("Couldn't find created site alias");
	}
    
    echo "Site alias found: ".$data['site_alias']."<BR>";
    
    /*
     * 18. Delete site alias (DeleteEmailAddress)
     */

    $request = new \pmill\Plesk\DeleteSiteAlias($config, array(
        'id'=>$data['site_alias_id'],
    ));
    $info = $request->process();
    echo "Site alias deleted: ".$data['site_alias']."<BR>";

    
    /*
     * 19. Create subdomain (CreateSubdomain)
     */
    
    $data['subdomain'] = random_string();
    $request = new \pmill\Plesk\CreateSubdomain($config, array(
        'domain'=>$data['domain'],
        'subdomain'=>$data['subdomain'],
        'www_root'=>'/subdomains/'.strtolower($data['subdomain']),
        'fpt_username'=>random_string(),
        'fpt_password'=>random_string(),
    ));
    $info = $request->process();
    $data['subdomain_id'] = $request->id;
    echo "Subdomain created: ".$data['subdomain']."<BR>";
    
    
    /*
     * 20. List subdomains (ListSubdomains)
     */
    
    $request = new \pmill\Plesk\ListSubdomains($config, array(
        'site_id'=>$data['site_id'],
    ));
    $subdomains = $request->process();
    
    $subdomain_found = FALSE;
    foreach ($subdomains AS $subdomain) {
        if ($subdomain['id'] == $data['subdomain_id']) {
            $subdomain_found = TRUE;
        }
    }
    
    if (!$subdomain_found) {
		throw new Exception("Couldn't find created subdomain");
	}
    
    echo "Subdomain found: ".$data['subdomain']."<BR>";
    
    
    /*
     * 21. Update subdomain(UpdateSubdomain)
     */

    $request = new \pmill\Plesk\UpdateSubdomain($config, array(
        'id'=>$data['subdomain_id'],
        'fpt_username'=>random_string(),
        'fpt_password'=>random_string(),
    ));
    $info = $request->process();
    echo "Subdomain updated: ".$data['subdomain']."<BR>";

    
    /*
     * 22. Rename subdomain(RenameSubdomain)
     */
    
    $data['subdomain'] = random_string();
    $request = new \pmill\Plesk\RenameSubdomain($config, array(
        'id'=>$data['subdomain_id'],
        'name'=>$data['subdomain'],
    ));
    $info = $request->process();
    echo "Subdomain renamed: ".$data['subdomain']."<BR>";

    
    /*
     * 23. Delete subdomain(DeleteSubdomain)
     */
    
    $request = new \pmill\Plesk\DeleteSubdomain($config, array(
        'id'=>$data['subdomain_id'],
    ));
    $info = $request->process();
    echo "Subdomain deleted: ".$data['subdomain']."<BR>";
    
    
	/*
	 * 24. Delete site (DeleteSite)
	 */

    $request = new \pmill\Plesk\DeleteSite($config, array(
	   'id'=>$data['site_id'],
    ));
    $info = $request->process();
    echo "Site deleted: ".$data['domain']."<BR>";

}
catch(Exception $e) {
	throw $e;
}
finally {
	/*
	 * 25. Deletes previously created client (DeleteClient)
	 */

	$request = new \pmill\Plesk\DeleteClient($config, array(
		'id'=>$data['client_id'],
	));
	$request->process();
	echo "Client deleted: ".$data['client_id']."<BR>";
}

<?php
/*
* Plugin Name: ReCaptcha/NetSuite Form Integration
* Description: Plugin uses an shortcode embedded with the publishable form URL from hardcoded NetSuite forms and validates a ReCaptcha 2.0 before submitting the data to NetSuite..
* Version: 1.0
* Author: BCS ProSoft
* Author URI: https://www.bcsprosoft.com/netsuite
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action ('wp_head',function(){
	echo '<style>#recaptcha-err {font-size:0.7em;color:#ff0000}</style>';
	echo '<script type="text/javascript">captchaerr="noerr";</script>';}
	);
add_action('wp_footer',function(){
							echo '
						<script type="text/javascript"> 
						if (captchaerr!="noerr"){
							var RCError = document.getElementById("recaptcha-err");
							RCError.innerHTML="<span>"+captchaerr+"</span>";
							document.getElementById("recaptcha-err").appendChild(RCError);
						}
						</script>';
});



// Example 1 : WP Shortcode to display form on any page or post.
function RCValidate($atts){
	
	 $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$validURL = str_replace("&", "&amp", $url);
	$defaultRedirect ='';// (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") ."://".$_SERVER['HTTP_HOST']."/thank-you";
	
	extract( shortcode_atts( array(
			'sitekey' => '','secretkey'=>'', 'postto'=>''//, redirectto=>$defaultRedirect 
	), $atts ) );
	
	if (empty($sitekey)||empty($secretkey)) die('keys not valid');
	$err_msg='ok';

	if ($_COOKIE['campaignevent']) {
						$postto.="&campaignevent=".$_COOKIE['campaignevent'];
					}
    /*if (is_page('upwork'))	{					
	echo ('posto'); var_dump ($postto,$redirectto);
	}*/
	try
	
	{
		
	if($_POST)
		{
		
		
		//var_dump($_POST);	
			foreach($_POST as $key => $value)

				$form[$key] = $value; // Create the form prefill values

				

			$error = 0;
			
			if ($_POST['g-recaptcha-response']){
				//echo 'RESPONSE';
			try{
				include_once( plugin_dir_path( __FILE__ ).'recaptcha20/autoload.php'); // reCaptcha 2.0
				$recaptcha = new \ReCaptcha\ReCaptcha($secretkey);

				$resp = $recaptcha->verify($_POST["g-recaptcha-response"], $_SERVER["REMOTE_ADDR"]);

				//$resp = recaptcha_check_answer($privatekey,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]); // Check captcha

				//echo '$resp->isSuccess():'.$resp->isSuccess();

				if(!$resp->isSuccess()){
					$error=1;
					

				}
			}
			catch(Exception $e)
				{
					$err_msg =  $e->getMessage();
				exit;
				} 
			}
			else $error=1;

			if( $error === 0)
			{
				try 

				{	
				if ($postto) {
					
					$postto=str_replace('$$url$$',$validUrl,$postto);

					
					//unset($_POST["g-recaptcha-response"]);
					if (function_exists('curl_version')){
						$ch = curl_init($postto);
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
						curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
						$result = curl_exec($ch);
						$redirectURL = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL );
						curl_close($ch);

					if (($result) && ($redirectURL)) {
						
											wp_redirect($redirectURL,303);
					}
					  
						
					}
					
	
				}

				}

				catch(Exception $e)
				{
					$err_msg = $e->getMessage();
				exit;
				}
			}
			else {
			
				echo '<script type="text/javascript">captchaerr="Error Validating ReCaptcha. Please try again.";</script>';
			
			}

		}


		

	}

	catch(Exception $e)

	{

		$err_msg= $e->getMessage();

	}

}
add_shortcode('RCValidate', 'RCValidate',10,2);
?>

<?php
/**
 * Created by PhpStorm.
 * User: ditrics
 * Date: 20/03/15
 * Time: 16:53
 */

namespace XBsystem\WirecardBundle\Controller;

use Derigo\SiteBundle\Utils\Derigo;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends Controller {


	const TESTSECRET = "B8AKTPWBRMNBV455FG6M2DANE99WU2";

	/**
	 * Generates a form, that WireCard checkout expects
	 *
	 * @param $value number The amount of money to transfer
	 * @param string $currency string The short type of currency default is EUR
	 * @param $desc string The "orderDescription" value
	 * @param $stmt string The "customerStatement" value
	 * @param $ref string The "orderReference" value
	 * @param array $customParams array If you need custom parameteres in the response of wirecard,
	 * you can add it here in the folowwing format: "YOURKEY" => "YOURVALUE" in the response itt will be
	 * "cust_YOURKEY" => "YOURVALUE"
	 * @param array $urls array If you want to overwrite the default handleresponse - You should do that -
	 * place the URL-s in this array. <br />Options:<br />
	 * 	- successUrl<br />
	 * 	- cancelUrl<br />
	 * 	- failureUrl<br />
	 * 	- pendingUrl<br />
	 * 	- serviceUrl<br />
	 * 	- confirmUrl<br />
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function genformAction($value, $currency = "EUR", $desc, $stmt, $ref, array $customParams = array(), array $urls = array(), array $submitAttr = array()){

		// Get the secret from the config.yml
		$secret = $this->container->getParameter("wirecard_passkey");

		// Fill up needed values
		$hiddenFields = array(
			"customerId" => $this->container->getParameter("wirecard_user"),
			"shopId" => "",
			"amount" => $value,
			"currency" => $currency,
			"orderDescription" => $desc,
			"customerStatement" => $stmt,
			"orderReference" => $ref,
			"duplicateRequestCheck" => "no",
			"language" => "en",
			"displayText" => "Thank you very much for your order.",
			"imageUrl" => "http://derigo.me/bundles/sitestaticsite/images/derigo_logo.png",
			"paymenttype" => "CCARD",
			"successUrl" => isset($urls["successUrl"]) ? $urls["successUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true),
			"cancelUrl" => isset($urls["cancelUrl"]) ? $urls["cancelUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true),
			"failureUrl" => isset($urls["failureUrl"]) ? $urls["failureUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true),
			"pendingUrl" => isset($urls["pendingUrl"]) ? $urls["pendingUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true),
			"serviceUrl" => isset($urls["serviceUrl"]) ? $urls["serviceUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true),
			"confirmUrl" => isset($urls["confirmUrl"]) ? $urls["confirmUrl"] : $this->generateUrl("x_bsystem_wirecard_handleresponse", array(), true)
		);

		// If there is any custom parameter, fill up the array
		if(sizeof($customParams) > 0){
			foreach($customParams as $key => $value){
				$hiddenFields["cust_".$key] = $value;
			}
		}

		// If test is TRUE in config.yml, set the test merchant
		if($this->container->getParameter("wirecard_test")){
			$hiddenFields["customerId"] = "D200001";
			$secret = "B8AKTPWBRMNBV455FG6M2DANE99WU2";
		}

		// Generate fingerprints...
		$hiddenFields["requestFingerprintOrder"] = $this->getRequestFingerprintOrder($hiddenFields);
		$hiddenFields["requestFingerprint"] = $this->getRequestFingerprint($hiddenFields, $secret);

		// Set the formUrl.
		$formUrl = $this->container->getParameter("wirecard_checkouturl");


		return $this->render("XBsystemWirecardBundle:Checkout:form.html.twig", array(
			"formUrl" => $formUrl,
			"hidden" => $hiddenFields,
			"submitattr" => $submitAttr
		));
	}

	public function genformajaxAction(Request $request){

		if(!$request->request->has("value") || !$request->request->has("currency") || !$request->request->has("desc") || !$request->request->has("stmt") || !$request->request->has("ref")){
			return new JsonResponse(array(success => false));
		}

		$urls = array();

		if($request->request->has("successUrl"))
			$urls["successUrl"] = $request->request->get("successUrl");
		if($request->request->has("cancelUrl"))
			$urls["cancelUrl"] = $request->request->get("cancelUrl");
		if($request->request->has("failureUrl"))
			$urls["failureUrl"] = $request->request->get("failureUrl");
		if($request->request->has("pendingUrl"))
			$urls["pendingUrl"] = $request->request->get("pendingUrl");
		if($request->request->has("serviceUrl"))
			$urls["serviceUrl"] = $request->request->get("serviceUrl");
		if($request->request->has("confirmUrl"))
			$urls["confirmUrl"] = $request->request->get("confirmUrl");

		$customP = array();

		if($request->request->has("custom_param")){
			$cp = $request->request->get("custom_param");
			if(is_array($cp) && sizeof($cp) > 0){
				foreach ($cp as $key => $val) {
					$customP[$key] = $val;
				}
			}
		}

		$submitAttr = array();

		if($request->request->has("submitattr")){
			$sa = $request->request->get("submitattr");
			if(is_array($sa) && sizeof($sa) > 0){
				foreach ($sa as $key => $val) {
					$submitAttr[$key] = $val;
				}
			}
		}

		return $this->genformAction($request->request->get("value"), $request->request->get("currency"),
			$request->request->get("desc"),$request->request->get("stmt"),$request->request->get("ref"), $customP, $urls, $submitAttr);

	}

	public function handleresponseAction(Request $request, $respMode = 0){

		return $this->render("XBsystemWirecardBundle:Default:test.html.twig", array(
			"response" => $request->request->all()
		));

	}

	// Returns the value for the request parameter "requestFingerprintOrder".
	private function getRequestFingerprintOrder($theParams) {
		$ret = "";
		foreach ($theParams as $key=>$value) {
			$ret .= "$key,";
		}
		$ret .= "requestFingerprintOrder,secret";
		return $ret;
	}

	// Returns the value for the request parameter "requestFingerprint".
	private function getRequestFingerprint($theParams, $theSecret) {
		$ret = "";
		foreach ($theParams as $key=>$value) {
			$ret .= "$value";
		}
		$ret .= "$theSecret";
		return md5($ret);
	}


	// Checks if response parameters are valid by computing and comparing the fingerprints.
	public static function areReturnParametersValid($theParams, $theSecret = null) {

		if(is_null($theSecret))
			$theSecret = self::TESTSECRET;

		// gets the fingerprint-specific response parameters sent by Wirecard
		$responseFingerprintOrder = isset($theParams["responseFingerprintOrder"]) ? $theParams["responseFingerprintOrder"] : "";
		$responseFingerprint = isset($theParams["responseFingerprint"]) ? $theParams["responseFingerprint"] : "";

		// values of the response parameters for computing the fingerprint
		$fingerprintSeed = "";

		// array containing the names of the response parameters used by Wirecard to compute the response fingerprint
		$order = explode(",", $responseFingerprintOrder);

		// checks if there are required response parameters in responseFingerprintOrder
		if (in_array ("paymentState", $order) && in_array ("secret", $order) ) {
			// collects all values of response parameters used for computing the fingerprint
			for ($i = 0; $i < count($order); $i++) {
				$name = $order[$i];
				$value = isset($theParams[$name]) ? $theParams[$name] : "";
				$fingerprintSeed .= $value; // adds value of response parameter to fingerprint
				if (strcmp($name, "secret") == 0) {
					$fingerprintSeed .= $theSecret; // adds your secret to fingerprint
				}
			}
			$fingerprint = md5($fingerprintSeed); // computes the fingerprint
			// checks if computed fingerprint and responseFingerprint have the same value
			if (strcmp($fingerprint, $responseFingerprint) == 0) {
				return true; // fingerprint check passed successfully
			}
		}
		return false;
	}

	// Checks the result of the payment state and returns an appropiate text message.
	private function handleCheckoutResult($theParams, $theSecret) {
		$paymentState = isset($theParams["paymentState"]) ? $theParams["paymentState"] : "";
		switch ($paymentState) {
			case "FAILURE":
				$error_message = isset($theParams["message"]) ? $theParams["message"] : "";
				$message = "An error occured during the checkout process: " . $error_message;
				// NOTE: please log this error message in a persistent manner for later use
				break;
			case "CANCEL":
				$message = "The checkout process has been cancelled by the user.";
				break;
			case "PENDING":
				if (self::areReturnParametersValid($theParams, $theSecret)) {
					$message = "The checkout process is pending and not yet finished.";
					// NOTE: please store all related information regarding the transaction
					//       in a persistant manner for later use
				} else {
					$message = "The verification of the returned data was not successful. ".
						"Maybe an invalid request to this page or a wrong secret?";
				}
				break;
			case "SUCCESS":
				if (self::areReturnParametersValid($theParams, $theSecret)) {
					$message = "The checkout process has been successfully finished.";
					// NOTE: please store all related information regarding the transaction
					//       in a persistant manner for later use
				} else {
					$message = "The verification of the returned data was not successful. ".
						"Maybe an invalid request to this page or a wrong secret?";
				}
				break;
			default:
				$message = "Error: The payment state $paymentState is not a valid state.";
				break;
		}
		return $message;
	}


}
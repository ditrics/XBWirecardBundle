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
use Symfony\Component\HttpFoundation\Request;

class CheckoutController extends Controller {

	public function indexAction(Request $request){

		return $this->render("XBsystemWirecardBundle:Default:test.html.twig", array(

		));

	}

	public function genformAction($value, $currency = "EUR", $desc, $stmt, $ref){

		$secret = $this->container->getParameter("wirecard_passkey");

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
			"successUrl" => $this->getBaseUrl() . "return.php#success",
			"cancelUrl" => $this->getBaseUrl() . "return.php#success",
			"failureUrl" => $this->getBaseUrl() . "return.php#success",
			"pendingUrl" => $this->getBaseUrl() . "return.php#success",
			"serviceUrl" => $this->getBaseUrl() . "return.php#success",
			"confirmUrl" => $this->getBaseUrl() . "return.php#success"
		);


		if($this->container->getParameter("wirecard_test")){
			$hiddenFields["customerId"] = "D200001";
			$secret = "B8AKTPWBRMNBV455FG6M2DANE99WU2";
		}

		$hiddenFields["requestFingerprintOrder"] = $this->getRequestFingerprintOrder($hiddenFields);
		$hiddenFields["requestFingerprint"] = $this->getRequestFingerprint($hiddenFields, $secret);

		$formUrl = $this->container->getParameter("wirecard_checkouturl");


		return $this->render("XBsystemWirecardBundle:Checkout:form.html.twig", array(
			"formUrl" => $formUrl,
			"hidden" => $hiddenFields
		));
	}

	public function genformajaxAction(Request $request){

		if(!$request->request->has("value") || !$request->request->has("currency") || !$request->request->has("desc") || !$request->request->has("stmt") || !$request->request->has("ref")){
			return Derigo::returnAjaxResponse(false, "missing_parameters");
		}

		return $this->genformAction($request->request->get("value"), $request->request->get("currency"), $request->request->get("desc"),$request->request->get("stmt"),$request->request->get("ref"));

	}

	// Returns the protocol, servername, port and path for the current page.
	private function getBaseUrl() {
		$baseUrl = $_SERVER['SERVER_NAME'] . ":". $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		$baseUrl = substr($baseUrl, 0, strrpos($baseUrl, "/")) . "/";
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
			$baseUrl = "https://" . $baseUrl;
		} else {
			$baseUrl = "http://" . $baseUrl;
		}
		return $baseUrl;
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
				if (areReturnParametersValid($theParams, $theSecret)) {
					$message = "The checkout process is pending and not yet finished.";
					// NOTE: please store all related information regarding the transaction
					//       in a persistant manner for later use
				} else {
					$message = "The verification of the returned data was not successful. ".
						"Maybe an invalid request to this page or a wrong secret?";
				}
				break;
			case "SUCCESS":
				if (areReturnParametersValid($theParams, $theSecret)) {
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
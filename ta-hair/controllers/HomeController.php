<?php 
namespace App\Controllers;

use App\Helpers\Utils;
use App\Models\SiteSetting;
use App\Models\Order;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Symfony\Component\Mime\Email;

class HomeController extends CoreController {

    public function __construct($container) {
        parent::__construct($container);
    }

    public function index($request, $response, $args) {
    	$user = \App\Models\User::get()->count();
        $service = \App\Models\Service::get()->count();
        $appointments = \App\Models\appointment::get()->count();
    	$this->view($response, 'index.twig', compact('user','service','appointments'));
    }
    
    public static function payment($order, $card, $address) {
        $amount = $order->total;

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName("24QU3qh7ZX");
        $merchantAuthentication->setTransactionKey("6JY3d26thh7T5hSF");
        
        // Set the transaction's refId
        $refId = 'ref' . time();

        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($card['number']);
        $creditCard->setExpirationDate($card['expiry']);
        $creditCard->setCardCode($card['code']);

        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        // Create order information
        $order1 = new AnetAPI\OrderType();
        $order1->setInvoiceNumber($order->id);
        $order1->setDescription("BrrOrders");

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName($order->user->name);
        $customerAddress->setLastName("");
        $customerAddress->setAddress($address['address']);
        $customerAddress->setCity($address['city']);
        $customerAddress->setState($address['state']);
        $customerAddress->setZip($address['postcode']);
        $customerAddress->setCountry("AU");

        // Set the customer's identifying information
        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setType("individual");
        $customerData->setId($order->user->id);
        $customerData->setEmail($order->user->email);

        // Add values for transaction settings
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName("duplicateWindow");
        $duplicateWindowSetting->setSettingValue("60");

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setOrder($order1);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setCustomer($customerData);
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        

        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();
            
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    return true;
                } else {
                    return false;
                }
                // Or, print errors if the API request wasn't successful
            } else {
                return false;
            }
        } else {
           return false;
        }
    }

    public static function sendSMS($content) {
        $ch = curl_init('https://api.smsbroadcast.com.au/api-adv.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec ($ch);
        curl_close ($ch);
        return $output;    
    }

    public static function send($phone, $message){
        $username = 'kulchanbinod';
        $password = 'Roeingbp@453';
        $destination = $phone; //Multiple numbers can be entered, separated by a comma
        $source    = 'Caesar';
        $text = $message;
        $ref = 'abc123';
            
        $content =  'username='.rawurlencode($username).
                    '&password='.rawurlencode($password).
                    '&to='.rawurlencode($destination).
                    '&from='.rawurlencode($source).
                    '&message='.rawurlencode($text).
                    '&ref='.rawurlencode($ref);
      
        $smsbroadcast_response = static::sendSMS($content);
        $response_lines = explode("\n", $smsbroadcast_response);
        
         foreach( $response_lines as $data_line){
            $message_data = "";
            $message_data = explode(':',$data_line);
            if($message_data[0] == "OK"){
                echo "The message to ".$message_data[1]." was successful, with reference ".$message_data[2]."\n";
            }elseif( $message_data[0] == "BAD" ){
                echo "The message to ".$message_data[1]." was NOT successful. Reason: ".$message_data[2]."\n";
            }elseif( $message_data[0] == "ERROR" ){
                echo "There was an error with this request. Reason: ".$message_data[1]."\n";
            }
        }
    }
}
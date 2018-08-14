<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class MMStripConnect {
  
  function __construct()
  {
    require_once('./vendor/autoload.php');
    \Stripe\Stripe::setApiKey('sk_test_3JcuUVp7qSjptRcxuDXSpcBT');
  }

   public function getSupporedCountries() 
  {

    $response = new Stdclass();

    try {

      $countries = \Stripe\CountrySpec::all();
      $result = array();
      foreach ($countries->data as $key => $value) {
        $result[$value->id]= $value->id;
      }

      $response->data = (object) $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;
    }
    
  }

  public function createAccount($email) 
  {

    $response = new Stdclass();

    try {
      $result = \Stripe\Account::create(array(
        "type" => "custom",
        "country" => "US",
        "email" => $email
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;
    }

  }

  public function getAccount($client_key) 
  {

    $response = new Stdclass();

    try {
      $result = \Stripe\Account::retrieve($client_key);

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;
    }
  }

  public function createBankAccount($account_holder_name, $routing_number, $account_number ) 
  {

    $response = new Stdclass();

    try {      
      $result = \Stripe\Token::create(array(
        "bank_account" => array(
          "country" => "US",
          "currency" => "usd",
          "account_holder_name" => $account_holder_name,
          "account_holder_type" => "individual",
          "routing_number" => $routing_number,
          "account_number" => $account_number
        )
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }

  public function updateAccount($client_key, $bank_token=null)
  {

    $response = new Stdclass();

    try {
      $account = \Stripe\Account::retrieve($client_key);

      $account->tos_acceptance->date = time();
      $account->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];        

      if($bank_token) {
        $account->external_accounts->create(array("external_account" => $bank_token));
      }

      $result = $account->save();
      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;


    }

  }

  public function updateBankAccount($client_key, $bank_id) 
  {

    $response = new Stdclass();

    try {      
      $account = \Stripe\Account::retrieve($client_key);
      $bank_account = $account->external_accounts->retrieve($bank_id);
      $result = $bank_account->save();

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }

  public function createCardToken($number, $exp_month, $exp_year, $cvc ) 
  {

    $response = new Stdclass();

    try {      
      $result = \Stripe\Token::create(array(
        "card" => array(
          "number" => $number,
          "exp_month" => $exp_month,
          "exp_year" => $exp_year,
          "cvc" => $cvc
        )
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }


  }

  public function directCharge($client_id, $total_amount, $application_fee, $cardToken ) 
  {

    $response = new Stdclass();

    try {

      $result = \Stripe\Charge::create(array(
        "amount" => $total_amount*100,
        "currency" => "usd",
        "source" => $cardToken,
        "application_fee" => $application_fee*100,
      ), array("stripe_account" => $client_id));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }



  public function transfer($client_key, $total_amount ) 
  {

    $response = new Stdclass();

    try {

      $result = \Stripe\Transfer::create(array(
        "amount" => $total_amount,
        "currency" => "usd",
        "destination" => $client_key,
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;
    }

  }


  public function getBankAccounts($client_key) 
  {

    $response = new Stdclass();

    try {      
      $result = \Stripe\Account::retrieve($client_key)->external_accounts->all(array(
        'limit'=>1, 'object' => 'bank_account'));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }

  public function getAccountBalance($client_key) 
  {

    $response = new Stdclass();
    $result = new Stdclass();

    try {      
     $balance = \Stripe\Balance::retrieve([
          'stripe_account' => $client_key
      ]);
     
      $result->currency = $balance->available[0]->currency;
      $result->payble_amount = ($balance->available[0]->amount)/100;
      $result->pending_amount = ($balance->pending[0]->amount)/100;

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }

  public function refundCharge($charge_id, $client_id ) 
  {

    $response = new Stdclass();

    try {     

      $result = \Stripe\Refund::create(array(
        "charge" => $charge_id,
        "refund_application_fee" => true
      ), array(
        "stripe_account" => $client_id
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      echo '<pre>'; print_r($e);die;
      return $response;

    }

  }

  public function transferToConnected($amount, $client_id ) 
  {

    $response = new Stdclass();

    try {      
      
      $result = \Stripe\Transfer::create(array(
        "amount" => $amount*100,
        "currency" => "usd",
        "destination" => $client_id
      ));

      $response->data = $result;
      $response->status = true;
      return $response;

    } catch (Exception $e) {
      $response->status = false;
      return $response;

    }

  }

}


$obj = new MMStripConnect();
// // $result = $obj->createCardToken('4242424242424242','7', '2019', '101');

echo '<pre>';
// $result1 = $obj->createBankAccount('monu','110000000','000123456789');
    
$result2 = $obj->transferToConnected(5,'acct_1Cps43FbxhNwvreK');

print_r($result2);
?>
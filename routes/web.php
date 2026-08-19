<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Http\Controllers\IVRController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\SquareOAuthController;
use App\Http\Controllers\SquareWebhookController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

/*Route::get('/', function () {
return view('welcome');initiateCall
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');*/

Route::get('/', function () {
    return redirect(\route('admin.dashboard'));
});

Route::get('about-us', 'HomeController@about_us')->name('about-us');
Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});


//SSLCOMMERZ Start
Route::post('sslcommerz/pay', 'SslCommerzPaymentController@index')->name('pay-ssl');
Route::post('sslcommerz/success','SslCommerzPaymentController@success')->name('ssl-success');
Route::post('sslcommerz/failure','SslCommerzPaymentController@fail')->name('ssl-failure');
Route::post('sslcommerz/cancel','SslCommerzPaymentController@cancel')->name('ssl-cancel');
Route::post('sslcommerz/ipn','SslCommerzPaymentController@ipn')->name('ssl-ipn');
//SSLCOMMERZ END

/*paypal*/
/*Route::get('/paypal', function (){return view('paypal-test');})->name('paypal');*/
Route::post('pay-paypal', 'PaypalPaymentController@payWithpaypal')->name('pay-paypal');
Route::get('paypal-status', 'PaypalPaymentController@getPaymentStatus')->name('paypal-status');
/*paypal*/

/*Route::get('stripe', function (){
return view('stripe-test');
});*/

Route::match(['get', 'post'], 'pay-square', 'SquarePaymentController@payment_process_3d')->name('pay-square');
Route::get('pay-square/success', 'SquarePaymentController@success')->name('pay-square.success');
Route::get('pay-square/fail', 'SquarePaymentController@fail')->name('pay-square.fail');
Route::get('square/oauth/connect/{branch}', [SquareOAuthController::class, 'connect'])->middleware('admin')->name('square.oauth.connect');
Route::get('square/oauth/callback', [SquareOAuthController::class, 'callback'])->name('square.oauth.callback');
Route::post('square/webhook', [SquareWebhookController::class, 'handle'])->name('square.webhook');

// Route::get('square/checkout', 'SquarePaymentController@showCheckout')->name('square-checkout');
// Route::post('square/process-payment', 'SquarePaymentController@processPayment')->name('square-payment-process');

Route::get('pay-stripe', 'StripePaymentController@payment_process_3d')->name('pay-stripe');
Route::get('pay-stripe/success', 'StripePaymentController@success')->name('pay-stripe.success');
Route::get('pay-stripe/fail', 'StripePaymentController@success')->name('pay-stripe.fail');

// Get Route For Show Payment Form
Route::get('paywithrazorpay', 'RazorPayController@payWithRazorpay')->name('paywithrazorpay');
Route::post('payment-razor', 'RazorPayController@payment')->name('payment-razor');

/*Route::fallback(function () {
return redirect('/admin/auth/login');
});*/

//internal point pay
Route::post('internal-point-pay', 'InternalPointPayController@payment')->name('internal-point-pay');

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');

//senang pay
Route::match(['get', 'post'], '/return-senang-pay', 'SenangPayController@return_senang_pay')->name('return-senang-pay');


//paystack
Route::post('/paystack-pay', 'PaystackController@redirectToGateway')->name('paystack-pay');
Route::get('/paystack-callback', 'PaystackController@handleGatewayCallback')->name('paystack-callback');
Route::get('/paystack',function (){
    return view('paystack');
});

/*Route::fallback(function () {
return redirect('/admin/auth/login');
});*/
Route::match(['get', 'post'], '/return-senang-pay', 'SenangPayController@return_senang_pay')->name('return-senang-pay');

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');

//bkash
Route::group(['prefix'=>'bkash'], function () {
    // Payment Routes for bKash
    Route::post('get-token', 'BkashPaymentController@getToken')->name('bkash-get-token');
    Route::post('create-payment', 'BkashPaymentController@createPayment')->name('bkash-create-payment');
    Route::post('execute-payment', 'BkashPaymentController@executePayment')->name('bkash-execute-payment');
    Route::get('query-payment', 'BkashPaymentController@queryPayment')->name('bkash-query-payment');
    Route::post('success', 'BkashPaymentController@bkashSuccess')->name('bkash-success');

    // Refund Routes for bKash
    Route::get('refund', 'BkashRefundController@index')->name('bkash-refund');
    Route::post('refund', 'BkashRefundController@refund')->name('bkash-refund');
});

// paymob
Route::post('/paymob-credit', 'PaymobController@credit')->name('paymob-credit');
Route::get('/paymob-callback', 'PaymobController@callback')->name('paymob-callback');

// The callback url after a payment
Route::get('mercadopago/home', 'MercadoPagoController@index')->name('mercadopago.index');
Route::post('mercadopago/make-payment', 'MercadoPagoController@make_payment')->name('mercadopago.make_payment');
Route::get('mercadopago/get-user', 'MercadoPagoController@get_test_user')->name('mercadopago.get-user');

// The route that the button calls to initialize payment
Route::post('/flutterwave-pay','FlutterwaveController@initialize')->name('flutterwave_pay');
// The callback url after a payment
Route::get('/rave/callback', 'FlutterwaveController@callback')->name('flutterwave_callback');

Route::get('add-currency', function () {
    $currencies = file_get_contents("installation/currency.json");
    $decoded = json_decode($currencies, true);
    $keep = [];
    foreach ($decoded as $item) {
        array_push($keep, [
            'country'         => $item['name'],
            'currency_code'   => $item['code'],
            'currency_symbol' => $item['symbol_native'],
            'exchange_rate'   => 1,
        ]);
    }
    DB::table('currencies')->insert($keep);
    return response()->json(['ok']);
});

Route::match(['get','post'], '/test',[\App\Http\Controllers\SenangPayController::class,'pay'])->name('test');
Route::get('/test',function(){
   $date = '2024-07-11 03:02';
        $pickUpTimeEst=date('Y-m-d\TH:i:s\Z',strtotime('+5 hours',strtotime($date)));
        dd($pickUpTimeEst);

});


// IVR and Call Routes
Route::post('/handleIncomingCall', [TwilioController::class, 'handleJenksIncomingCall']);
Route::post('/twilio/jenks-gather', [TwilioController::class, 'handleJenksDtmfInput'])->name('twilio.jenks.gather');
Route::get('/twilio/order-call-twiml', [IVRController::class, 'orderCallTwiml'])->name('twilio.order-call-twiml');
Route::post('/twilio/call-status', [TwilioController::class, 'handleCallStatus'])->name('twilio.call.status');

// audio routes
Route::get('/playOrder', 'IVRController@playOrder');

Route::get('/playJenksIntro', [IVRController::class, 'playJenksIntro']);
Route::get('/playJenksAudio2', 'IVRController@playJenksAudio2');
Route::get('/playJenksAudio3', 'IVRController@playJenksAudio3');
Route::get('/playJenksCallWait', 'IVRController@playJenksCallWait');

Route::get('/initiate-call', [TwilioController::class, 'initiateCall']);
Route::post('/outbond-call-response', [TwilioController::class, 'outboundWebhook']);
Route::get('/call/answer/{branch}', [TwilioController::class, 'handleAnswer'])->name('call.answer');
Route::get('/call/hangup', [TwilioController::class, 'handleHangup'])->name('call.hangup');
Route::post('/handle-incomming-call', [TwilioController::class, 'handelIncommingCall']);
Route::post('/handle-incomming-call-status', [TwilioController::class, 'handleCallStatus']);
Route::post('/handle-gather-action', [TwilioController::class, 'handleDtmfInput'])->name('gather.action');




use App\Model\Order;
use Illuminate\Support\Facades\Mail;
Route::get('/teste-email',function(){
    $points='23';
    $reward='12';
    
    $user=DB::table('users')->orderBy('id','desc')->first();
    $order=Order::where('id','101465')->first();
    $username=$user->f_name.' '.$user->l_name;
    $coupnText='';
    // return view('email-templates.wallet-point-notification',compact('points','reward','username'));
    $orderMailSent=Order::orderBy('id','DESC')->first();
    
                           
                        $mail=Mail::to('bizbackend@gmail.com')->send(new \App\Mail\OrderPlaced($order,$coupnText));
                        
                        dd($mail);
});


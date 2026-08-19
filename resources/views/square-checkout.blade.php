@php($currency=\App\Model\BusinessSetting::where(['key'=>'currency'])->first()->value)
@php($squareSettings=app(\App\Services\SquareService::class)->settings())

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>
        @yield('title')
    </title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="_token" content="{{ csrf_token() }}">
    <!-- Viewport-->
    {{--<meta name="_token" content="{{csrf_token()}}">--}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon and Touch Icons-->
    <link rel="shortcut icon" href="favicon.ico">
    <!-- Font -->
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">


    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap.css">
    <script type="text/javascript" src="{{($squareSettings['environment'] ?? 'sandbox') === 'production' ? 'https://web.squarecdn.com/v1/square.js' : 'https://sandbox.web.squarecdn.com/v1/square.js'}}"></script>

</head>
<!-- Body-->
<body class="toolbar-enabled">
{{--loader--}}
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" style="display: none;">
                <div style="position: fixed;z-index: 9999; left: 40%;top: 37% ;width: 100%">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>
{{--loader--}}
<!-- Page Content-->
<div class="container pb-5 mb-2 mb-md-4">
    <div class="row">
        
            <div class="col-md-12 mb-5 pt-5">
            <center class="">
                <h1>{{ translate('Square Payment') }}</h1>
            </center>
        </div>
        <section class="col-lg-12">
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6 mb-5 pt-5">
                        <input type="hidden" id="csrf" value="{{ csrf_token() }}" />
                        <input type="hidden" id="customer" value="{{ $details['customer_id'] }}" />
                        <input type="hidden" id="amount" value="{{ $details['order_amount'] }}" />
                        <input type="hidden" id="idempotencyKey" value="{{ $details['idempotencyKey'] }}" />
                     <form id="payment-form">
                       <div id="card-container"></div>
                       <button id="card-button" type="button">Pay {{ $details['order_amount'] }}</button>
                     </form>
                     <div id="payment-status-container"></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
const appId = @json($squareSettings['application_id']);
const locationId = @json($squareSettings['location_id']);
// const csrf = document.querySelector('meta[name="csrf-token"]').content;
    // Initialize the payment form
   async function initializeCard(payments) {
       const card = await payments.card();
       await card.attach('#card-container'); 
       return card; 
     }
     // Payments API
     // Call this function to send a payment token, buyer name, and other details
     // to the project server code so that a payment can be created with 
     // Payments API
     const token = 'adsad';
     const idempotencyKey = document.getElementById('idempotencyKey').value;
      const csrf = document.getElementById('csrf').value;
      const customer = document.getElementById('customer').value;
      const amount = document.getElementById('amount').value;
     async function createPayment(token) {
       const body = JSON.stringify({
         locationId,
         sourceId: token,
         idempotencyKey,
         customer: customer,
         amount: amount,
         
       });
       const paymentResponse = await fetch('/square/process-payment', {
         method: 'POST',
         headers: {
           'Content-Type': 'application/json',
           'X-CSRF-Token': csrf
         },
         body,
       });
       if (paymentResponse.ok) {
         return paymentResponse.json();
       }
       const errorBody = await paymentResponse.text();
       throw new Error(errorBody);
     }
    
     // This function tokenizes a payment method. 
     // The ‘error’ thrown from this async function denotes a failed tokenization,
     // which is due to buyer error (such as an expired card). It's up to the
     // developer to handle the error and provide the buyer the chance to fix
     // their mistakes.
     async function tokenize(paymentMethod) {
       const tokenResult = await paymentMethod.tokenize();
       if (tokenResult.status === 'OK') {
         return tokenResult.token;
       } else {
         let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
         if (tokenResult.errors) {
           errorMessage += ` and errors: ${JSON.stringify(
             tokenResult.errors
           )}`;
         }
         throw new Error(errorMessage);
       }
     }
    
     // Helper method for displaying the Payment Status on the screen.
     // status is either SUCCESS or FAILURE;
     function displayPaymentResults(status) {
       const statusContainer = document.getElementById(
         'payment-status-container'
       );
       if (status === 'SUCCESS') {
         statusContainer.classList.remove('is-failure');
         statusContainer.classList.add('is-success');
       } else {
         statusContainer.classList.remove('is-success');
         statusContainer.classList.add('is-failure');
       }
    
       statusContainer.style.visibility = 'visible';
     }
    
    document.addEventListener('DOMContentLoaded', async function () {
       if (!window.Square) {
        throw new Error('Square.js failed to load properly');
      }
    
      const payments = window.Square.payments(appId, locationId);
      let card;
      try {
        card = await initializeCard(payments);
      } catch (e) {
        console.error('Initializing Card failed', e);
        return;
      }
    
      async function handlePaymentMethodSubmission(event, paymentMethod) {
        event.preventDefault();
    
        try {
          // disable the submit button as we await tokenization and make a
          // payment request.
          cardButton.disabled = true;
          const token = await tokenize(paymentMethod);
          const paymentResults = await createPayment(token);
          displayPaymentResults('SUCCESS');
    
          console.debug('Payment Success', paymentResults);
        } catch (e) {
          cardButton.disabled = false;
          displayPaymentResults('FAILURE');
          console.error(e.message);
        }
      }
    
      const cardButton = document.getElementById(
        'card-button'
      );
      cardButton.addEventListener('click', async function (event) {
        await handlePaymentMethodSubmission(event, card);
      });
    
      // Step 5.2: create card payment
    });
</script>

</body>
</html>

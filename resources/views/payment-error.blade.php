<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Error</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #222222;
        }
        .payment-error {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
            box-sizing: border-box;
        }
        .payment-error__content {
            max-width: 420px;
        }
        .payment-error__title {
            margin: 0 0 12px;
            font-size: 24px;
            font-weight: 700;
        }
        .payment-error__message {
            margin: 0;
            font-size: 16px;
            line-height: 1.5;
            color: #555555;
        }
    </style>
</head>
<body>
<main class="payment-error">
    <section class="payment-error__content">
        <h1 class="payment-error__title">Payment could not start</h1>
        <p class="payment-error__message">{{ $message }}</p>
    </section>
</main>
</body>
</html>

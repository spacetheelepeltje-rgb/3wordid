
composer require stripe/stripe-php



<?php
require_once('vendor/autoload.php');

// Set your secret key. Remember to switch to your live secret key in production!
\Stripe\Stripe::setApiKey('your_stripe_secret_key');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create a Customer:
        $customer = \Stripe\Customer::create([
            'email' => $_POST['email'],
            'source' => $_POST['stripeToken'] // obtained with Stripe.js
        ]);

        // Create a Subscription with the annual plan for 12 euros
        $subscription = \Stripe\Subscription::create([
            'customer' => $customer->id,
            'items' => [
                ['price' => 'your_price_id_for_12_euro_annual'], // This ID should be from your Stripe dashboard where you've set up this price
            ],
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Subscription created successfully!',
            'subscriptionId' => $subscription->id
        ]);
    } catch (\Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Subscription</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <form id="payment-form">
        <div>
            <label for="email">Email Address</label>
            <input id="email" type="email" required>
        </div>
        <div id="card-element">
            <!-- A Stripe Element will be inserted here. -->
        </div>

        <button id="submit">Subscribe</button>
    </form>

    <script>
        var stripe = Stripe('your_stripe_publishable_key');
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        var style = {
            base: {
                color: '#32325d',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        // Create an instance of the card Element.
        var card = elements.create('card', {style: style});

        // Add an instance of the card Element into the `card-element` <div>
        card.mount('#card-element');

        card.on('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Inform the customer that there was an error
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server
                    stripeTokenHandler(result.token);
                }
            });
        });

        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            var form = document.getElementById('payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            // Submit the form
            form.submit();
        }
    </script>
</body>
</html>


Notes:
Replace your_stripe_secret_key and your_stripe_publishable_key with the actual keys from your Stripe account.
Ensure you have already created a product and a price in your Stripe dashboard for the annual subscription of 12 euros, and use the price ID in the PHP code where it says your_price_id_for_12_euro_annual.
This example assumes you're collecting the email to create a customer in Stripe. Adjust according to your data collection needs.
Always implement error handling and validation on both client and server sides for a production environment.

This setup provides a basic flow for setting up an annual subscription with Stripe, but you'll need to expand on this for handling webhooks, managing subscription status, and other Stripe features like invoices or customer portal integration.



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Subscription Button</title>
</head>
<body>
    <div id="paypal-button-container-P-1FG26942UU083052AM56O4OA"></div>

    <script src="https://www.paypal.com/sdk/js?client-id=ASAw7jutQoPtnXRDFxy4Mt3JLAOU2kLZ47yFpAnbYfQB7LrCGh2fDuDcfdB-1W4M3x2gfcef_x_0x3wb&vault=true&intent=subscription" data-sdk-integration-source="button-factory"></script>
    <script>
        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'gold',
                layout: 'vertical',
                label: 'subscribe'
            },
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    plan_id: 'P-1FG26942UU083052AM56O4OA'
                });
            },
            onApprove: function(data, actions) {
                // This function is called when the user approves the subscription
                // You should process the subscription on your server side
                return fetch('/process-subscription', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        subscriptionID: data.subscriptionID
                    })
                }).then(response => {
                    if (response.ok) {
                        alert('Subscription successful! Your subscription ID is: ' + data.subscriptionID);
                    } else {
                        alert('An error occurred while processing your subscription. Please try again later.');
                        return actions.restart();
                    }
                }).catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your subscription. Please try again later.');
                    return actions.restart();
                });
            },
            onError: function(err) {
                console.error('An error occurred with the PayPal button:', err);
                alert('An error occurred. Please try again.');
            }
        }).render('#paypal-button-container-P-1FG26942UU083052AM56O4OA');
    </script>
</body>
</html>

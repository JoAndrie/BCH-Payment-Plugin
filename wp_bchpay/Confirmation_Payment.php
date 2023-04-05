<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/Confirm_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Confirmation Payment</title>
</head>
<body>
    <div class="section wf-section">
        <div class="container">
            <img src="assets/images/Bitcoin_Cash.png" loading="lazy" width="70" alt="" class="bch-logo">
            <div class="total-amount">Payment Succesful</div>
            <div class="description">Your money has been successfully sent to Tetsuro Kirisaki. To view your transaction please click the link for more details.</div>
            <div class="spacer"></div>
            <div class="payment-amount">Total Payment</div>
            <div class="total-amount">0.000000 BCH</div>
            <div class="payment-amount">1 BCH = <em>$3.00 USD</em></div>
            <div class="horizontal-line"></div>
            <div class="spacer"></div>
            <button onclick="window.location.href='index.php'" class="transaction-button w-clearfix">
                <div class="c-transaction">Confirm Transaction</div><img src="assets/images/verify-icon.png" loading="lazy" width="19" alt="" class="verify-icon">
            </button>
            <div class="transac-details"><a href="#">Transaction Details</a></div>
        </div>
        <img src="assets/images/1.png" loading="lazy" sizes="100vw"  alt="" class="bg-img">
    </div>
    <script src="https://d3e54v103j8qbb.cloudfront.net/js/jquery-3.5.1.min.dc5e7f18c8.js?site=63edbb3a4ccacv5268fc605b" type="text/javascript" integrity="sha256-9/aliU8dGd2tb60SsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="js/webflow.js" type="text/javascript"></script>
</body>
</html>
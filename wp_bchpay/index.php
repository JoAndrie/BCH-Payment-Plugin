<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Plugin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">
    <?php wp_head();?>
</head>
<body class="body">
    <div class="section wf-section">
       
        <div class="header">
            <div class="text-block">BCHPAY</div>
        </div> 
        <div>
            <img src="<?php echo get_template_directory_uri() . '/assets/images/1.png' ?>" class="bg-image">
        </div>
        <div class="head">
            <div class="p-one"><em class="i-text1">Payment Method</em></div>
            <div class="p-two"><em class="i-text2">To complete transaction, click the Bitcoin Cash method below.</em></div>
        </div>
    
        <div class="container">
            <a href="<?php echo get_template_directory_uri(); ?>/Payment.php" class="btn" >
                <div class="b-cash">Bitcoin Cash</div>
                <div class="n-cost">Network Cost: ₱ 0.0 PHP</div>
                <div class="btc">BCH</div><img src="<?php echo get_template_directory_uri() . '/assets/images/Bitcoin_Cash.png' ?>" loading="lazy" alt="" class="btc-img" >
            </a>
        </div>
    
    </div> 
    <script src="https://d3e54v103j8qbb.cloudfront.net/js/jquery-3.5.1.min.dc5e7f18c8.js?site=63ec6ec535f3f7b3b2df3935c" type="text/javascript" integrity="sha256-9/aliU8dGd2tb60SsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="js/webflow.js" type="text/javascript"></script>
    <script src="https://cdnis.cloudfare.com/ajax/libs/placeholders/3.0.2/placeholders.min.js"></script>
    

</body>
</html>
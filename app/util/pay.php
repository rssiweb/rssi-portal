<html>
    <head>
       <title>Pay now</title>
       <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0"/>
    </head>

<body>
<script type="application/javascript" crossorigin="anonymous" src="https://securegw.paytm.in/merchantpgpui/checkoutjs/merchants/OsXyfL78631649755177.js" onload="onScriptLoad();"></script>
<script>
    function onScriptLoad(){
        var config = {
         "root": "",
         "flow": "DEFAULT",
         "data": {
          "orderId": "123" /* update order id */,
          "token": "456" /* update token value */,
          "tokenType": "TXN_TOKEN",
          "amount": "5" /* update amount */
         },
         "handler": {
            "notifyMerchant": function(eventName,data){
              console.log("notifyMerchant handler function called");
              console.log("eventName => ",eventName);
              console.log("data => ",data);
            } 
          }
        };

        if(window.Paytm && window.Paytm.CheckoutJS){
            window.Paytm.CheckoutJS.onLoad(function excecuteAfterCompleteLoad() {
                // initialze configuration using init method 
                window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
                   // after successfully update configuration invoke checkoutjs
                   window.Paytm.CheckoutJS.invoke();
                }).catch(function onError(error){
                    console.log("error => ",error);
                });
            });
        } 
    }
</script>
</body>
</html>
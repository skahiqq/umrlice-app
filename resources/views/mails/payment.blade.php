
<div>
    <span>Status: <b>{{ $data['transactionStatus'] }}</b></span>
    <br />
    <span>Order ID: <b>{{ $data['purchaseId'] }}</b></span>
    <br />
    <span>Amount: <b>{{ $data['amount'] . ' ' . $data['currency'] }}</b></span>
    <br />
    <span>Card Type: <b>{{ $data['returnData']['binBrand'] . ' ****' . $data['returnData']['lastFourDigits'] }}</b></span>
    <br />
    <span>Bank Code: <b>{{ $data['extradata']['authCode'] ?? "" }}</b></span>
    <br />
    <span>Timestamp: <b>{{ $data['timestamp'] }} UTC</b></span>
</div>

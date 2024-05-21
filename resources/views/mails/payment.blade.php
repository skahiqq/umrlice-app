
<div>
    <span>Status: <b>{{ $data['transactionStatus'] }}</b></span>
    <br />
    <span>Order ID: <b>{{ $data['purchaseId'] }}</b></span>
    <br />
    <span>Amount: <b>{{ $data['amount'] . ' ' . $data['currency'] }}</b></span>
    <br />
    <span>Card Type: <b>{{ $data['returnData']['binBrand'] . ' ****' . $data['returnData']['lastFourDigits'] }}</b></span>
    <br />
    <span>Bank Code: <b>XXXXXX</b></span>
    <br />
    <span>Timestamp: <b>{{ $data['timestamp'] }}</b></span>
</div>

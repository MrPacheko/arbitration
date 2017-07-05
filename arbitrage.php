<?php
include('arbitrage/class_exch.php');
include('arbitrage/class_arbit.php');

//Tests exchange_class
$exch_1 = new Exchange_usdtbtc();
$exch_2 = new Exchange_usdtbtc();
$exch_3 = new Exchange_usdtbtc();
$exch_1->hydrate_exchange(
    'bitfinex',
    'https://api.bitfinex.com/v1/book/btcusd',
    0.001,
    2,
    0.25,
    0.25
);
$exch_2->hydrate_exchange(
    'poloniex',
    'https://poloniex.com/public?command=returnOrderBook&currencyPair=USDT_BTC',
    0.001,
    2,
    0.25,
    0.25
);
$exch_3->hydrate_exchange(
    'bittrex',
    'https://bittrex.com/api/v1.1/public/getorderbook?market=USDT-BTC&type=both&depth=50',
    0.001,
    2,
    0.25,
    0.25
);

//Test Arbitration class
$arbit = new Arbitration_usdtbtc(0,20);

//Add exchanges to Arbitration
$arbit->add_exchange($exch_1);
$arbit->add_exchange($exch_2);
$arbit->add_exchange($exch_3);

//Test max_profit computing
/*while(true){
    $arbit->max_profitable_operation();
}*/
$arbit->max_profitable_operation();
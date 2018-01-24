<?php
include 'config/bootstrap.php';


// $rock = new \ccxt\liqui(['verbose'=>true]);
// $tickers = $rock->fetch_tickers();
// obj_dump($rock->load_markets());
 // obj_dump($rock->fetch_markets());
// obj_dump($rock->fetch_balance());
// var_dump($rock->parse_ticker('PPCBTC'));
// obj_dump($rock->fetch_order_book('REQ/BTC'));
// $time = microtime(true);
$time = time();
//filter extreme price differences
const PRICE_JUMP = 10;
//compare with how far into history
const HISTORY_MINUTES = 10;


// $indexes = $redis->keys('tickers.*');
foreach ($redis->keys('tickers*') as $exchange) {

    $tickers = unserialize($redis->get($exchange));
    $exchange = explode(".", $exchange)[1];

    $api = create_exchange($exchange);

  foreach ($tickers as $pair => $ticker) {
    $params = [];
    if ($exchange == 'bittrex') $params['type'] = 'buy';
    if ($exchange == 'binance') $params['limit'] = 500;
    //get order book
    try {
      $buys = $api->fetch_order_book($pair, $params)['bids'];
    } catch (Exception $e) {
      echo $exchange . ' error: ' . $e->getMessage();
      continue;
    }
    list($volume_coin, $volume_btc) = aggregate($buys);

    //save current btc volume
    $key = 'orderbook.' . $exchange . '.' . $pair . '.bid.recent.volume';
    $recent_oldvolume = $redis->get($key);
    $redis->setex($key, 61, $volume_btc);

    $key .= '.old';
    $redis->setex($key, 61, $recent_oldvolume);

    //save this volume into history too
    $mod = date('i')[1] % HISTORY_MINUTES - 1;
    $mod = ($mod<0) ? HISTORY_MINUTES - 1 : $mod;
    $key2 = '.' . HISTORY_MINUTES . '.' . $mod;
    $redis->setex($key.$key2, HISTORY_MINUTES * 60 + 3 * 60, $volume_btc);

    }
}
obj_dump(intval(time() - $time));
die();

function aggregate(array $orderbook) {
  $sum_coin = $sum_btc = 0;
  try {
    $highest = $orderbook[0][0];
    foreach ($orderbook as $key => $value) {
      if (($value[0]*PRICE_JUMP) < $highest) continue; //dont work with extreme orders
      $sum_coin += $value[1];
      $sum_btc += ($value[0] * $value[1]);
    }
  } catch (Exception $e) {

  }
  return [round($sum_coin, 2), round($sum_btc, 2)];
}

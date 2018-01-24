<?php
include 'config/bootstrap.php';

//define minimum volume treshold
$volume_min = 50;
$volume_max = 200;
$max_coins = 30;
//key expiration
$ttl = 60 * 60 * 24;
$exchanges =[
  'liqui',
  'bittrex',
  'binance',
  'hitbtc2',
  'therock',
];


//reload tickers
foreach ($exchanges as $exchange) {
  $counter = 0;
  $ex = create_exchange($exchange);
  try {
    $tickers = $ex->fetch_tickers();
  } catch (\Exception $e) {
    echo $exchange . ' error: ' . $e->getMessage();
    continue;
  }
  $tickers = shuffle_assoc($tickers);
  foreach ($tickers as $key => $value) {
    //only btc pairs
    if (strpos(strtolower($key), 'btc') === false) {
      unset($tickers[$key]);
      continue;
    }
    if (count($tickers) < $max_coins) continue;
    if (intval($value['quoteVolume']) < $volume_min) {
      unset($tickers[$key]);
      continue;
    }
    if (intval($value['quoteVolume']) > $volume_max) {
      unset($tickers[$key]);
      continue;
    }
    if ($counter++ > $max_coins) {
      unset($tickers[$key]);
      continue;
    }
  }
  if (!isset($tickers)) $tickers = [];
  $key = 'tickers.' . $exchange;
  $redis->setex($key, $ttl, serialize($tickers));
  // $redis->expire($key, $ttl);
  obj_dump($key);
}
$redis->set('debug.tickers.start', $time);
$redis->set('debug.tickers.end', microtime(true) - $time);
die('done ' . floatval(microtime(true) - $time));


function shuffle_assoc($list) {
  if (!is_array($list)) return $list;

  $keys = array_keys($list);
  shuffle($keys);
  $random = array();
  foreach ($keys as $key) {
    $random[$key] = $list[$key];
  }
  return $random;
}

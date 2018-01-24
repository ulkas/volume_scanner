<?php
include 'config/bootstrap.php';

//filter extreme price differences
const PRICE_JUMP = 10;
//compare how far into history
const HISTORY_MINUTES = 10;

$pairs = $redis->keys('orderbook.' . $exchange . '*' . '.bid.recent.volume*');
$values = $redis->mget($pairs);

$data = $tmp = [];
foreach ($pairs as $key => $value) {
  $divide = explode(".", $value);
  $pair = $divide[2];
  //$coin = explode("/", $pair)[0];
  $coin = $pair;
  if (count($divide) == 6) $data[$coin]['last_volume'] = $values[$key];
  if (count($divide) == 7) $data[$coin]['recent_volume'] = $values[$key];
  if (count($divide) > 7) $data[$coin]['history'][$value] = $values[$key];
}

//obtain history key within the algebraic group, oldest first.
$mod = (date('i')[1] % HISTORY_MINUTES) - 1;
$keys = [];
for ($i=0; $i < HISTORY_MINUTES; $i++) {
  $j = $i + $mod + 1;
  if ($j >= HISTORY_MINUTES) {
    $j -= HISTORY_MINUTES;
  }
  $keys[$i] = $j;
}
array_reverse($keys);


$i = 0;
$result = [];
foreach ($data as $pair => $value) {
  $trend = [];
  $ping = 0;
  if (!isset($value['recent_volume']) || !isset($value['last_volume']) || !isset($value['history'])) continue;

  $mainkey = 'orderbook.' . $exchange . '.' . $pair . '.bid.recent.volume.old.' . HISTORY_MINUTES . '.';
  $sufix = date('i')[1] % HISTORY_MINUTES;

  $recent_volume = $value['recent_volume'];
  $last_volume = $value['last_volume'];
  $oldvolume = (isset($value['history'][$mainkey.$sufix])) ? $value['history'][$mainkey.$sufix] : 0;

  foreach ($keys as $key => $mod) {
    if (!isset($value['history'][$mainkey.$mod])) {
      $trend[] = 0;
      continue;
    }
    $key2 = $key + 1;
    if ($key2 >= count($keys)) {
      $step = $last_volume;
    } else {
      $step = (isset($value['history'][$mainkey.$keys[$key2]])) ? $value['history'][$mainkey.$keys[$key2]] : $value['history'][$mainkey.$mod];
    }
    $tmp = ($value['history'][$mainkey.$mod] == 0) ? 0 : round((($step - $value['history'][$mainkey.$mod]) / $value['history'][$mainkey.$mod]) * 100, 2);
    //$tmp = ($value['history'][$mainkey.$mod] == 0) ? 0 : round($step - $value['history'][$mainkey.$mod] , 2);
    $ping = ($tmp > 0) ? $ping + 1 : $ping;
    $ping = ($tmp < 0) ? 0 : $ping;
    $trend[] = $tmp;
  }



  $result[$i]['coin'] = explode("\\", $pair)[0];
  $result[$i]['pings'] = $ping;
  $result[$i]['Volume BTC'] = round($last_volume - $oldvolume, 2);
  $result[$i]['Volume BTC %'] = ($oldvolume == 0) ? 'N/A' : round((($last_volume - $oldvolume) / $oldvolume) * 100, 2);
  $result[$i]['Total Volume BTC'] = $last_volume;
  $result[$i]['Recent Volume BTC'] = round($last_volume - $recent_volume, 2);
  $result[$i]['Recent Volume BTC %'] = ($recent_volume == 0) ? 'N/A' : round((($last_volume - $recent_volume) / $recent_volume ) *100, 2);
  $result[$i]['trend'] = $trend;
  $result[$i++]['Datetime'] = date('H:i:s m/d/y');
}
$treshold = 5; //remove nonmoving coins
foreach ($result as $key => $value) {
  if ($value['Volume BTC %'] > $treshold) continue;
  if ($value['Volume BTC %'] < (-1 * $treshold)) continue;
  if ($value['Recent Volume BTC %'] > $treshold) continue;
  if ($value['Recent Volume BTC %'] < (-1 * $treshold)) continue;
  unset($result[$key]);
}
function sortByOrder($a, $b) {
    //return $a['Recent Volume BTC %'] < $b['Recent Volume BTC %'];
    //return $a['pings'] < $b['pings'];
    return array_sum($a['trend']) < array_sum($b['trend']);
}

usort($result, 'sortByOrder');

include 'view/scanner.php';
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
